<?php

namespace Pest\Drift\PHPUnit;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PHPUnit\Framework\TestCase;
use Rector\NodeTypeResolver\Node\AttributeKey;
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
        if (!$this->isObjectType($node, TestCase::class)) {
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
        $methodName = $this->getName($method);

        foreach ($classParents as $classParent) {
            if (!$classParent->hasMethod($methodName)) {
                continue;
            }

            return true;
        }
        return false;
    }

    private function createPestHelperMethod(ClassMethod $method): Node\Stmt\Function_
    {
        $stmts = array_map(function (Node\Stmt $stmt) {
            if (!isset($stmt->expr) || !($stmt->expr instanceof MethodCall)) {
                return $stmt;
            }

            if (!$this->isName($stmt->expr->var, 'this')) {
                return $stmt;
            }

            $stmt->expr->var = $this->createFuncCall('test');
            return $stmt;
        }, $method->getStmts());

        return $this->builderFactory->function($this->getName($method))
            ->addStmts($stmts)
            ->getNode();
    }

    private function migratePhpUnitMethodHelpers(Node $pestNode, ClassMethod $methodNode): void
    {
        /** @var MethodCall[] $pestMethodCalls */
        $pestMethodCalls = $this->betterNodeFinder->findInstanceOf($pestNode, MethodCall::class);
        $methodName = $this->getName($methodNode);

        foreach ($pestMethodCalls as $pestMethodCall) {
            if (!$this->isName($pestMethodCall->name, $methodName)) {
                continue;
            }

            // TODO: see if we can find another solution then replaceNode.
            $this->replaceNode($pestMethodCall, $this->createFuncCall($methodName));
        }
    }

    /**
     * @param string|null $className
     * @return ReflectionClass[]|void[]
     */
    private function getParentClasses(?string $className)
    {
        return array_map(function (string $classParent) {
            if (!class_exists($classParent)) {
                return;
            }
            return new ReflectionClass($classParent);
        }, class_parents($className));
    }
}
