<?php

namespace Pest\Drift\PHPUnit\ClassMethod;

use Exception;
use Nette\Utils\Strings;
use Pest\Drift\PestCollector;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTagNode;
use PHPUnit\Framework\TestCase;
use Rector\BetterPhpDocParser\PhpDocInfo\PhpDocInfo;
use Rector\NodeTypeResolver\Node\AttributeKey;

class MethodToPestTestRector extends AbstractClassMethodRector
{
    public ?string $type = PestCollector::TEST_METHODS;

    public function classMethodRefactor(Class_ $classNode, ClassMethod $classMethodNode): ?Node
    {
        if (!$this->isTestMethod($classMethodNode)) {
            return null;
        }

        $pestTestNode = $this->createPestTest($classMethodNode);

        $pestTestNode = $this->migratePhpDocGroup($classMethodNode, $pestTestNode);

        $pestTestNode = $this->migrateDataProvider($classMethodNode, $pestTestNode);

        $pestTestNode = $this->migrateExpectException($classMethodNode, $pestTestNode);

        $pestTestNode = $this->migrateSkipCall($classMethodNode, $pestTestNode);

        return $pestTestNode;
    }

    public function isTestMethod(ClassMethod $node): bool
    {
        /** @var PhpDocInfo $phpDoc */
        $phpDoc = $node->getAttribute(AttributeKey::PHP_DOC_INFO);

        if ($phpDoc && $phpDoc->hasByName('test')) {
            return true;
        }

        return Strings::startsWith($this->getName($node), 'test');
    }

    /**
     * @return string[]|null
     */
    public function getPhpDocGroupNames(Node $node): ?array
    {
        /** @var PhpDocInfo|null $phpDocInfo */
        $phpDocInfo = $node->getAttribute(AttributeKey::PHP_DOC_INFO);
        if ($phpDocInfo === null) {
            return null;
        }

        return array_map(static function (PhpDocTagNode $tag): string {
            return (string) $tag->value;
        }, $phpDocInfo->getTagsByName('group'));
    }

    /**
     * @param $method
     * @return FuncCall
     */
    private function createPestTest($method): FuncCall
    {
        return $this->builderFactory->funcCall(
            'it',
            [
                $this->getName($method),
                new Closure([
                    'stmts' => $method->stmts,
                    'params' => $method->params,
                ]),
            ]
        );
    }

    /**
     * @param ClassMethod $method
     * @return array|null[]
     */
    private function getExpectExceptionCall(ClassMethod $method)
    {
        /** @var Node\Stmt\Expression $stmt */
        foreach ($method->getStmts() as $key => $stmt) {
            if (isset($stmt->expr) && $this->isMethodCall($stmt->expr, 'this', 'expectException')) {
                return [$key, $stmt];
            }
        }
        return [null, null];
    }

    private function migrateExpectException(ClassMethod $method, Expr $pestTestNode): Expr
    {
        [$expectExceptionCallKey, $expectExceptionCall] = $this->getExpectExceptionCall($method);
        if ($expectExceptionCall !== null) {
            // Remove expect exception call from pest test class
            $this->removeStmt($pestTestNode->args[1]->value, $expectExceptionCallKey);
            // And add pest throws chain.
            $pestTestNode = $this->createMethodCall(
                $pestTestNode,
                'throws',
                $expectExceptionCall->expr->args
            );
        }

        return $pestTestNode;
    }

    private function migrateDataProvider(ClassMethod $method, Expr $pestTestNode): Expr
    {
        $dataProvider = $this->getDataProviderName($method);
        if ($dataProvider !== null) {
            $pestTestNode = $this->createMethodCall($pestTestNode, 'with', [$dataProvider]);
        }
        return $pestTestNode;
    }

    private function migratePhpDocGroup(ClassMethod $method, Expr $pestTestNode): Expr
    {
        $groups = $this->getPhpDocGroupNames($method);
        if (!empty($groups)) {
            $pestTestNode = $this->createMethodCall($pestTestNode, 'group', $groups);
        }
        return $pestTestNode;
    }

    /**
     * @return array|null[]
     */
    private function getMarkTestSkippedCall(ClassMethod $method)
    {
        /** @var Node\Stmt\Expression $stmt */
        foreach ($method->getStmts() as $key => $stmt) {
            if (isset($stmt->expr) && $this->isMethodCall($stmt->expr, 'this', 'markTestSkipped')) {
                return [$key, $stmt];
            }
        }
        return [null, null];
    }

    private function migrateSkipCall(ClassMethod $method, Expr $pestTestNode): Expr
    {
        [$expectExceptionCallKey, $expectExceptionCall] = $this->getMarkTestSkippedCall($method);
        if ($expectExceptionCall !== null) {
            // Remove markTestSkipped call from pest test class
            $this->removeStmt($this->getPestClosure($pestTestNode), $expectExceptionCallKey);
            // And add pest skip chain.
            $pestTestNode = $this->createMethodCall(
                $pestTestNode,
                'skip',
                $expectExceptionCall->expr->args
            );
        }

        return $pestTestNode;
    }
}
