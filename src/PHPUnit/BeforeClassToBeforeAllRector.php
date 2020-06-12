<?php

namespace Pest\Drift\PHPUnit;

use PhpParser\Node;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PHPUnit\Framework\TestCase;
use Rector\BetterPhpDocParser\PhpDocInfo\PhpDocInfo;
use Rector\NodeTypeResolver\Node\AttributeKey;

class BeforeClassToBeforeAllRector extends AbstractPHPUnitToPestRector
{
    public function getNodeTypes(): array
    {
        return [Class_::class];
    }

    public function refactor(Node $node): ?Node
    {
        if (!$this->isObjectType($node, TestCase::class)) {
            return null;
        }

        /** @var ClassMethod[] $methods */
        $methods = $this->betterNodeFinder->findInstanceOf(
            $node,
            ClassMethod::class
        );

        foreach ($methods as $method) {
            if (!$this->isBeforeClassMethod($method)) {
                continue;
            }

            $newNode = $this->createPestBeforeAll($method);
            $this->removeNode($method);
            $this->pestCollector->addBeforeAll($node, $newNode);
        }

        return $node;
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
                new Closure(['stmts' => $method->stmts]),
            ]
        );
    }
}
