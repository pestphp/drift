<?php

namespace Pest\Drift\PHPUnit;

use Exception;
use Nette\Utils\Strings;
use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTagNode;
use PHPUnit\Framework\TestCase;
use Rector\BetterPhpDocParser\PhpDocInfo\PhpDocInfo;
use Rector\NodeTypeResolver\Node\AttributeKey;

class PestPHPUnitRector extends AbstractPHPUnitToPestRector
{
    private const ANNOTATION_TO_METHOD = [
        'group' => 'group',
    ];

    public function getNodeTypes(): array
    {
        return [Class_::class];
    }

    /**
     * @throws Exception
     */
    public function refactor(Node $node): ?Node
    {
        if (!$this->isObjectType($node, TestCase::class)) {
            return null;
        }

        /** @var ClassMethod[] $methods */
        $methods = $this->betterNodeFinder->findInstanceOf($node, ClassMethod::class);

        $canDeleteClass = true;

        // Add groups from class to whole file
        $classGroupNames = $this->getPhpDocGroupNames($node);
        if (!empty($classGroupNames)) {
            $this->addNodeAfterNode(
                $this->createFileScopeGroupCall($classGroupNames),
                $node
            );
        }

        $nodesToAdd = [];

        foreach ($methods as $method) {
            if ($this->isTestMethod($method)) {
                $pestTestNode = $this->createPestTest($method);

                $pestTestNode = $this->migratePhpDocGroup($method, $pestTestNode);

                $pestTestNode = $this->migrateDataProvider($method, $pestTestNode);

                $pestTestNode = $this->migrateExpectException($method, $pestTestNode);

                // Delete the phpunit method from the phpunit class
                $this->removeNode($method);

                // Add the pest test to the file
                $nodesToAdd[] = $pestTestNode;
            } elseif ($this->isDataProviderMethod($method, $methods)) {
                $pestDataProviderNode = $this->createPestDataProvider($method);

                // Delete the phpunit data provider method from the phpunit class
                $this->removeNode($method);

                // Add the pest data provider to the top of the file
                array_unshift($nodesToAdd, $pestDataProviderNode);
            } elseif ($this->isSetUpMethod($method)) {
                $pestSetUpNode = $this->createPestBeforeEach($method);

                // Delete the phpunit setup method from the phpunit class
                $this->removeNode($method);

                // Add the pest beforeEach to the top of the file
                array_unshift($nodesToAdd, $pestSetUpNode);
            } else {
                $canDeleteClass = false;
            }
        }

        foreach ($nodesToAdd as $nodeToAdd) {
            $this->addNodeAfterNode($nodeToAdd, $node);
        }

        if ($canDeleteClass) {
            $this->removeNode($node);
        }

        return $node;
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
                new Node\Expr\Closure([
                    'stmts' => $method->stmts,
                    'params' => $method->params,
                ]),
            ]
        );
    }


    /**
     * @param string[] $groups
     */
    private function createFileScopeGroupCall(array $groups): MethodCall
    {
        return $this->createMethodCall(
            $this->createUsesCall(),
            'group',
            $groups
        );
    }

    private function isDataProviderMethod(ClassMethod $method, array $methods)
    {
        foreach ($methods as $lookUpMethod) {
            $dataProviderName = $this->getDataProviderName($lookUpMethod);

            if ($dataProviderName === null) {
                continue;
            }

            if ($this->isName($method, $dataProviderName)) {
                return true;
            }
        }

        return false;
    }

    private function createPestDataProvider(ClassMethod $method): FuncCall
    {
        return $this->builderFactory->funcCall(
            'dataset',
            [
                $this->getName($method),
                new Node\Expr\Closure(['stmts' => $method->stmts]),
            ]
        );
    }

    private function getDataProviderName(ClassMethod $method): ?string
    {
        /** @var PhpDocInfo|null $phpDocInfo */
        $phpDocInfo = $method->getAttribute(AttributeKey::PHP_DOC_INFO);
        if ($phpDocInfo === null) {
            return null;
        }

        $dataProviders = array_map(static function (PhpDocTagNode $tag): string {
            return (string) $tag->value;
        }, $phpDocInfo->getTagsByName('dataProvider'));

        if ($dataProviders === []) {
            return null;
        }

        if (count($dataProviders) !== 1) {
            throw new Exception("Multiple data providers found on one method.");
        }

        return $dataProviders[0];
    }

    /**
     * @param ClassMethod $method
     * @return array|null[]
     */
    private function getExpectExceptionCall(ClassMethod $method)
    {
        /** @var Node\Stmt\Expression $stmt */
        foreach ($method->getStmts() as $key => $stmt) {
            if ($this->isMethodCall($stmt->expr, 'this', 'expectException')) {
                return [$key, $stmt];
            }
        }
        return [null, null];
    }

    private function migrateExpectException(ClassMethod $method, FuncCall $pestTestNode): FuncCall
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

    private function migrateDataProvider(ClassMethod $method, FuncCall $pestTestNode): FuncCall
    {
        $dataProvider = $this->getDataProviderName($method);
        if ($dataProvider !== null) {
            $pestTestNode = $this->createMethodCall($pestTestNode, 'with', [$dataProvider]);
        }
        return $pestTestNode;
    }

    private function migratePhpDocGroup(ClassMethod $method, FuncCall $pestTestNode): FuncCall
    {
        $groups = $this->getPhpDocGroupNames($method);
        if (!empty($groups)) {
            $pestTestNode = $this->createMethodCall($pestTestNode, 'group', $groups);
        }
        return $pestTestNode;
    }

    private function isSetUpMethod(ClassMethod $method): bool
    {
        return $this->isName($method, 'setUp');
    }

    private function createPestBeforeEach(ClassMethod $method): FuncCall
    {
        return $this->builderFactory->funcCall(
            'beforeEach',
            [
                new Node\Expr\Closure(['stmts' => $method->stmts]),
            ]
        );
    }
}
