<?php

namespace Pest\Drift\PHPUnit;

use PhpParser\Node;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Stmt\Class_;
use PHPUnit\Framework\TestCase;

class CustomTestCaseToUsesRector extends AbstractPHPUnitToPestRector
{
    public function getNodeTypes(): array
    {
        return [Class_::class];
    }

    /**
     * @param Class_ $node
     */
    public function refactor(Node $node): ?Node
    {
        if (!$this->isObjectType($node, TestCase::class)) {
            return null;
        }

        if ($this->canRemovePhpUnitClass($node)
            && $this->isName($node->extends, TestCase::class)
        ) {
            return null;
        }

        $newNode = $this->createFuncCall('uses', [
            new ClassConstFetch(
                $this->canRemovePhpUnitClass($node) ? $node->extends : $node->name,
                'class'
            )
        ]);

        $this->pestCollector->addUses($node, $newNode);
        return $node;
    }
}
