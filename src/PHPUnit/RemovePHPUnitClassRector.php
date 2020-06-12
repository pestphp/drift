<?php

namespace Pest\Drift\PHPUnit;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PHPUnit\Framework\TestCase;

class RemovePHPUnitClassRector extends AbstractPHPUnitToPestRector
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

        if (!$this->canRemovePhpUnitClass($node)) {
            return null;
        }

        $this->removeNode($node);
        return $node;
    }
}
