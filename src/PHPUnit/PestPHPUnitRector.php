<?php

namespace Pest\Drift\PHPUnit;

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

    public function refactor(Node $node): ?Node
    {
        if (!$this->isObjectType($node, TestCase::class)) {
            return null;
        }

        /** @var Node\Stmt\ClassMethod $methods */
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

        foreach ($methods as $method) {
            if ($this->isTestMethod($method)) {
                $pestTestNode = $this->createPestTest($method);

                $groups = $this->getPhpDocGroupNames($method);
                if (!empty($groups)) {
                    $pestTestNode = $this->createMethodCall($pestTestNode, 'group', $groups);
                }


                // Delete the phpunit method from the phpunit class
                $this->removeNode($method);
                // Add the pest test to the file
                $this->addNodeAfterNode($pestTestNode, $node);
            } else {
                $canDeleteClass = false;
            }
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
                new Node\Expr\Closure(['stmts' => $method->stmts]),
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
}
