<?php

declare(strict_types=1);

namespace Pest\Drift\PHPUnit\ClassMethod;

use Exception;
use Nette\Utils\Strings;
use Pest\Drift\PestCollector;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Expression;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTagNode;
use Rector\BetterPhpDocParser\PhpDocInfo\PhpDocInfo;
use Rector\Core\Exception\ShouldNotHappenException;
use Rector\NodeTypeResolver\Node\AttributeKey;

class MethodToPestTestRector extends AbstractClassMethodRector
{
    public ?string $type = PestCollector::TEST_METHODS;

    public function classMethodRefactor(Class_ $classNode, ClassMethod $classMethodNode): ?Node
    {
        if (! $this->isTestMethod($classMethodNode)) {
            return null;
        }

        $pestTestNode = $this->createPestTest($classMethodNode);

        $pestTestNode = $this->migratePhpDocGroup($classMethodNode, $pestTestNode);

        $pestTestNode = $this->migrateDataProvider($classMethodNode, $pestTestNode);

        $pestTestNode = $this->migrateExpectException($classMethodNode, $pestTestNode);

        $pestTestNode = $this->migrateSkipCall($classMethodNode, $pestTestNode);

        $pestTestNode = $this->migratePhpDocDepends($classMethodNode, $pestTestNode);

        return $pestTestNode;
    }

    public function isTestMethod(ClassMethod $classMethod): bool
    {
        /** @var PhpDocInfo|null $phpDoc */
        $phpDoc = $classMethod->getAttribute(AttributeKey::PHP_DOC_INFO);
        if ($phpDoc && $phpDoc->hasByName('test')) {
            return true;
        }

        $classMethodName = $this->getName($classMethod);
        if ($classMethodName === null) {
            return false;
        }

        return Strings::startsWith($classMethodName, 'test');
    }

    /**
     * @return string[]
     */
    public function getPhpDocGroupNames(Node $node): array
    {
        /** @var PhpDocInfo|null $phpDocInfo */
        $phpDocInfo = $node->getAttribute(AttributeKey::PHP_DOC_INFO);
        if ($phpDocInfo === null) {
            return [];
        }

        return array_map(static function (PhpDocTagNode $tag): string {
            return (string) $tag->value;
        }, $phpDocInfo->getTagsByName('group'));
    }

    /**
     * @return string[]
     */
    public function getPhpDocDependsNames(Node $node): array
    {
        /** @var PhpDocInfo|null $phpDocInfo */
        $phpDocInfo = $node->getAttribute(AttributeKey::PHP_DOC_INFO);
        if ($phpDocInfo === null) {
            return [];
        }

        return array_map(static function (PhpDocTagNode $tag): string {
            return (string) $tag->value;
        }, $phpDocInfo->getTagsByName('depends'));
    }

    private function createPestTest(ClassMethod $classMethod): FuncCall
    {
        $functionName = $this->getName($classMethod);
        if ($functionName === null) {
            throw new ShouldNotHappenException();
        }

        $arguments = [
            $functionName,
            new Closure([
                'stmts' => $classMethod->stmts,
                'params' => $classMethod->params,
            ]),
        ];

        return $this->builderFactory->funcCall('test', $arguments);
    }

    /**
     * @return array<int|null, MethodCall|null>
     */
    private function getExpectExceptionCall(ClassMethod $method): array
    {
        /** @var Expression $stmt */
        foreach ((array) $method->getStmts() as $key => $stmt) {
            if (isset($stmt->expr) && $this->isMethodCall($stmt->expr, 'this', 'expectException')) {
                return [$key, $stmt->expr];
            }
        }
        return [null, null];
    }

    /**
     * @param FuncCall|MethodCall $pestTestNode
     * @return FuncCall|MethodCall
     */
    private function migrateExpectException(ClassMethod $method, Expr $pestTestNode): Expr
    {
        [$expectExceptionCallKey, $expectExceptionCall] = $this->getExpectExceptionCall($method);
        if ($expectExceptionCall !== null) {
            /** @var MethodCall $expectExceptionCall */
            // Remove expect exception call from pest test class
            /** @var int $expectExceptionCallKey */
            $this->removeStmt($pestTestNode->args[1]->value, $expectExceptionCallKey);
            // And add pest throws chain.
            $pestTestNode = $this->createMethodCall($pestTestNode, 'throws', $expectExceptionCall->args);
        }

        return $pestTestNode;
    }

    /**
     * @param FuncCall|MethodCall $pestTestNode
     * @return FuncCall|MethodCall
     */
    private function migrateDataProvider(ClassMethod $method, Expr $pestTestNode)
    {
        $dataProvider = $this->getDataProviderName($method);
        if ($dataProvider !== null) {
            return $this->createMethodCall($pestTestNode, 'with', [$dataProvider]);
        }

        return $pestTestNode;
    }

    /**
     * @param FuncCall|MethodCall $pestTestNode
     * @return FuncCall|MethodCall
     */
    private function migratePhpDocGroup(ClassMethod $method, Expr $pestTestNode): \PhpParser\Node
    {
        $groups = $this->getPhpDocGroupNames($method);
        if ($groups !== []) {
            return $this->createMethodCall($pestTestNode, 'group', $groups);
        }
        return $pestTestNode;
    }

    /**
     * @param FuncCall|MethodCall $pestTestNode
     * @return FuncCall|MethodCall
     */
    private function migratePhpDocDepends(ClassMethod $method, Expr $pestTestNode): Expr
    {
        $depends = $this->getPhpDocDependsNames($method);
        if ($depends !== []) {
            return $this->createMethodCall($pestTestNode, 'depends', $depends);
        }

        return $pestTestNode;
    }

    /**
     * @return array<int|null, MethodCall|null>
     */
    private function getMarkTestSkippedCall(ClassMethod $classMethod): array
    {
        /** @var Expression $stmt */
        foreach ((array) $classMethod->getStmts() as $key => $stmt) {
            if (isset($stmt->expr) && $this->isMethodCall($stmt->expr, 'this', 'markTestSkipped')) {
                /** @var int $key */
                /** @var MethodCall $methodCall */
                $methodCall = $stmt->expr;
                return [$key, $methodCall];
            }
        }

        return [null, null];
    }

    /**
     * @param FuncCall|MethodCall $pestTestNode
     * @return FuncCall|MethodCall
     */
    private function migrateSkipCall(ClassMethod $method, Expr $pestTestNode): Expr
    {
        [$expectExceptionCallKey, $expectExceptionCall] = $this->getMarkTestSkippedCall($method);
        if ($expectExceptionCall !== null) {
            // Remove markTestSkipped call from pest test class
            /** @var int $expectExceptionCallKey */
            $this->removeStmt($this->getPestClosure($pestTestNode), $expectExceptionCallKey);
            // And add pest skip chain.
            /** @var MethodCall $expectExceptionCall */
            return $this->createMethodCall($pestTestNode, 'skip', $expectExceptionCall->args);
        }

        return $pestTestNode;
    }
}
