<?php

declare(strict_types=1);

namespace Pest\Drift\PHPUnit\ClassMethod;

use Pest\Drift\PHPUnit\AbstractPHPUnitToPestRector;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\BinaryOp\Concat;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Scalar\Encapsed;
use PhpParser\Node\Scalar\EncapsedStringPart;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Function_;
use PHPUnit\Framework\TestCase;
use Rector\Core\Exception\ShouldNotHappenException;
use ReflectionClass;

class HelperMethodRector extends AbstractPHPUnitToPestRector
{
    public function getNodeTypes(): array
    {
        return [Class_::class];
    }

    /**
     * @param Class_ $node
     */
    public function refactor(Node $node): ?Node
    {
        if (! $this->isObjectType($node, TestCase::class)) {
            return null;
        }

        $className = $this->getName($node->namespacedName);
        $classParents = $this->getParentClasses($className);

        // Create the helper methods
        foreach ($node->getMethods() as $method) {
            // Ignore methods which other rectors already took care of.
            if ($this->isNodeRemoved($method)) {
                continue;
            }

            // Cannot convert helper methods which are part of parent classes
            // as they might be used somewhere else.
            if ($this->isMethodInParentClasses($method, $classParents)) {
                continue;
            }

            $newNode = $this->createPestHelperMethod($method);

            $this->removeNode($method);
            $this->pestCollector->addHelperMethod($node, $newNode);

            // Migrate the methods to use helper method
            foreach ($this->pestCollector->getMethodsOrdered($node) as $testMethod) {
                $this->migratePhpUnitMethodHelpers($testMethod, $method);
            }
        }

        return $node;
    }

    /**
     * @param ReflectionClass[] $classParents
     */
    private function isMethodInParentClasses(ClassMethod $method, array $classParents): bool
    {
        /** @var string|null $methodName */
        $methodName = $this->getName($method);
        if ($methodName === null) {
            return false;
        }

        foreach ($classParents as $classParent) {
            if (! $classParent->hasMethod($methodName)) {
                continue;
            }

            return true;
        }
        return false;
    }

    private function createPestHelperMethod(ClassMethod $method): Function_
    {
        $stmts = array_map(function (Stmt $stmt) {
            if (! isset($stmt->expr) || ! ($stmt->expr instanceof MethodCall)) {
                return $stmt;
            }

            /** @var MethodCall $methodCall */
            $methodCall = $stmt->expr;

            if (! $this->isName($methodCall->var, 'this')) {
                return $stmt;
            }

            $methodCall->var = $this->createFuncCall('test');
            return $stmt;
        }, (array) $method->getStmts());

        $methodName = $this->getName($method);
        if ($methodName === null) {
            throw new ShouldNotHappenException();
        }

        return $this->builderFactory->function($methodName)
            ->addStmts($stmts)
            ->getNode();
    }

    private function migratePhpUnitMethodHelpers(Node $pestNode, ClassMethod $methodNode): void
    {
        $methodName = $this->getName($methodNode);
        if ($methodName === null) {
            throw new ShouldNotHappenException();
        }

        if ($pestNode instanceof Function_) {
            $stmts = $pestNode->getStmts();
        } elseif ($pestNode instanceof Node\Stmt\Expression) {
            $stmts = $pestNode->expr->args[1]->value->stmts;
        } else {
            throw new ShouldNotHappenException("Can't this node yet.");
        }

        $this->traverseNodesWithCallable($stmts, function (Node $node) use ($methodName): ?Expr {
            if ($node instanceof Encapsed) {
                return $this->createConcatFromEncapsed($node);
            }

            if (! $node instanceof MethodCall) {
                return null;
            }

            if (! $this->isName($node->name, $methodName)) {
                return null;
            }

            return $this->createFuncCall($methodName);
        });
    }

    /**
     * @return ReflectionClass[]
     */
    private function getParentClasses(?string $className): array
    {
        if ($className === null) {
            return [];
        }

        return array_map(function (string $classParent) {
            if (! class_exists($classParent)) {
                return;
            }
            return new ReflectionClass($classParent);
        }, class_parents($className));
    }

    private function createConcatFromEncapsed(Encapsed $encapsed): Concat
    {
        $encapsedParts = $encapsed->parts;

        /** @var Expr $concatedItem */
        $concatedItem = array_pop($encapsedParts);
        $concatedItem = $this->normalizeEncapsedPart($concatedItem);

        foreach ($encapsedParts as $encapsedPart) {
            $expr = $this->normalizeEncapsedPart($encapsedPart);
            $concatedItem = new Concat($expr, $concatedItem);
        }

        return $concatedItem;
    }

    private function normalizeEncapsedPart(Expr $encapsedPart): Expr
    {
        if ($encapsedPart instanceof EncapsedStringPart) {
            return new String_($encapsedPart->value);
        }

        return $encapsedPart;
    }
}
