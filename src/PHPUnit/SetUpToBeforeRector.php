<?php

namespace Pest\Drift\PHPUnit;

use PhpParser\Node;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PHPUnit\Framework\TestCase;

class SetUpToBeforeRector extends AbstractPHPUnitToPestRector
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
            if (!$this->isSetUpMethod($method)) {
                continue;
            }

            $pestBeforeEachNode = $this->createPestBeforeEach($method);
            $this->removeNode($method);
            $this->pestCollector->addBeforeEach($node, $pestBeforeEachNode);
        }

        return $node;
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
                new Closure(['stmts' => $method->stmts]),
            ]
        );
    }
}
