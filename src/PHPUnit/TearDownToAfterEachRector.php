<?php

namespace Pest\Drift\PHPUnit;

use PhpParser\Node;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PHPUnit\Framework\TestCase;

class TearDownToAfterEachRector extends AbstractPHPUnitToPestRector
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
            if (!$this->isTearDownMethod($method)) {
                continue;
            }

            $pestAfterEachNode = $this->createPestAfterEach($method);
            $this->removeNode($method);
            $this->pestCollector->addAfterEach($node, $pestAfterEachNode);
        }

        return $node;
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
                new Closure(['stmts' => $method->stmts]),
            ]
        );
    }
}
