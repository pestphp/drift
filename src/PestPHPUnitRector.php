<?php

namespace Pest\Drift;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PHPUnit\Framework\TestCase;

class PestPHPUnitRector extends AbstractPHPUnitToPestRector
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

        $methods = $this->betterNodeFinder->findInstanceOf($node, Node\Stmt\ClassMethod::class);

        foreach ($methods as $method) {
            $funcCall = $this->builderFactory->funcCall(
                'it',
                [
                    $this->getName($method),
                    new Node\Expr\Closure(['stmts' => $method->stmts]),
                ]
            );

            $this->addNodeAfterNode($funcCall, $node);
        }

        $this->removeNode($node);
        return $node;
    }
}
