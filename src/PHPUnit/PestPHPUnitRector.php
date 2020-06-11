<?php

namespace Pest\Drift\PHPUnit;

use Exception;
use Nette\Utils\Strings;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\TraitUse;
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

        if (($traits = $node->getTraitUses()) !== []) {
            $pestUsesNode = $this->createPestUses($traits);

            // Add the pest afterEach to the top of the file
            array_unshift($nodesToAdd, $pestUsesNode);
        }


        foreach ($methods as $method) {
            if ($this->isTestMethod($method)) {
                $pestTestNode = $this->createPestTest($method);

                $pestTestNode = $this->migratePhpDocGroup($method, $pestTestNode);

                $pestTestNode = $this->migrateDataProvider($method, $pestTestNode);

                $pestTestNode = $this->migrateExpectException($method, $pestTestNode);

                $pestTestNode = $this->migrateSkipCall($method, $pestTestNode);

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
                $pestBeforeEachNode = $this->createPestBeforeEach($method);

                // Delete the phpunit setup method from the phpunit class
                $this->removeNode($method);

                // Add the pest beforeEach to the top of the file
                array_unshift($nodesToAdd, $pestBeforeEachNode);
            } elseif ($this->isTearDownMethod($method)) {
                $pestAfterEachNode = $this->createPestAfterEach($method);

                // Delete the phpunit tearDown method from the phpunit class
                $this->removeNode($method);

                // Add the pest afterEach to the top of the file
                array_unshift($nodesToAdd, $pestAfterEachNode);
            } elseif ($this->isAfterClassMethod($method)) {
                $pestAfterAllNode = $this->createPestAfterAll($method);

                // Delete the phpunit afterClass method from the phpunit class
                $this->removeNode($method);

                // Add the pest afterAll to the top of the file
                array_unshift($nodesToAdd, $pestAfterAllNode);
            } elseif ($this->isBeforeClassMethod($method)) {
                $pestBeforeAllNode = $this->createPestBeforeAll($method);

                // Delete the phpunit beforeClass method from the phpunit class
                $this->removeNode($method);

                // Add the pest beforeAll to the top of the file
                array_unshift($nodesToAdd, $pestBeforeAllNode);
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

    private function isTearDownMethod(ClassMethod $method): bool
    {
        return $this->isName($method, 'tearDown');
    }

    private function createPestAfterEach(ClassMethod $method): FuncCall
    {
        return $this->builderFactory->funcCall(
            'afterEach',
            [
                new Node\Expr\Closure(['stmts' => $method->stmts]),
            ]
        );
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
            $this->removeStmt($pestTestNode->args[1]->value, $expectExceptionCallKey);
            // And add pest skip chain.
            $pestTestNode = $this->createMethodCall(
                $pestTestNode,
                'skip',
                $expectExceptionCall->expr->args
            );
        }

        return $pestTestNode;
    }

    /**
     * @param TraitUse[] $traitUses
     */
    private function createPestUses(array $traitUses)
    {
        $traits = [];
        foreach ($traitUses as $traitUse) {
            $traits = array_merge($traits, $traitUse->traits);
        }

        $traits = array_map(function ($trait) {
            return $this->createArg(
                new Expr\ClassConstFetch($trait, 'class')
            );
        }, $traits);

        return $this->builderFactory->funcCall(
            'uses',
            $traits,
        );
    }

    private function isAfterClassMethod(ClassMethod $method): bool
    {
        /** @var PhpDocInfo $phpDoc */
        $phpDoc = $method->getAttribute(AttributeKey::PHP_DOC_INFO);

        return $phpDoc && $phpDoc->hasByName('afterClass');
    }

    private function createPestAfterAll(ClassMethod $method): FuncCall
    {
        return $this->builderFactory->funcCall(
            'afterAll',
            [
                new Node\Expr\Closure(['stmts' => $method->stmts]),
            ]
        );
    }

    private function isBeforeClassMethod(ClassMethod $method): bool
    {
        /** @var PhpDocInfo $phpDoc */
        $phpDoc = $method->getAttribute(AttributeKey::PHP_DOC_INFO);

        return $phpDoc && $phpDoc->hasByName('beforeClass');
    }

    private function createPestBeforeAll(ClassMethod $method): FuncCall
    {
        return $this->builderFactory->funcCall(
            'beforeAll',
            [
                new Node\Expr\Closure(['stmts' => $method->stmts]),
            ]
        );
    }
}
