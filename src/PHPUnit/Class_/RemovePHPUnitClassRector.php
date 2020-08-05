<?php

declare(strict_types=1);

namespace Pest\Drift\PHPUnit\Class_;

use Pest\Drift\PHPUnit\AbstractPHPUnitToPestRector;
use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PHPUnit\Framework\TestCase;

final class RemovePHPUnitClassRector extends AbstractPHPUnitToPestRector
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
        if (! $this->isObjectType($node, TestCase::class)) {
            return null;
        }

        if (! $this->canRemovePhpUnitClass($node)) {
            return null;
        }

        $this->removeNode($node);
        return $node;
    }
}
