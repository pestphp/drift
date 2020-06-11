<?php

namespace Pest\Drift\PHPUnit;

use Exception;
use Nette\Utils\Strings;
use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Stmt\Class_;
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

        /** @var Node\Stmt\ClassMethod[] $methods */
        $methods = $this->betterNodeFinder->findInstanceOf($node, Node\Stmt\ClassMethod::class);

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

                $groups = $this->getPhpDocGroupNames($method);
                if (!empty($groups)) {
                    $pestTestNode = $this->createMethodCall($pestTestNode, 'group', $groups);
                }

                $dataProvider = $this->getDataProviderName($method);
                if ($dataProvider !== null) {
                    $pestTestNode = $this->createMethodCall($pestTestNode, 'with', [$dataProvider]);
                }

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

    public function isTestMethod(Node\Stmt\ClassMethod $node): bool
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
     * @return Node\Expr\FuncCall
     */
    private function createPestTest($method): Node\Expr\FuncCall
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

    private function isDataProviderMethod(Node\Stmt\ClassMethod $method, array $methods)
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

    private function createPestDataProvider(Node\Stmt\ClassMethod $method): Node\Expr\FuncCall
    {
        return $this->builderFactory->funcCall(
            'dataset',
            [
                $this->getName($method),
                new Node\Expr\Closure(['stmts' => $method->stmts]),
            ]
        );
    }

    private function getDataProviderName(Node\Stmt\ClassMethod $method): ?string
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
}
