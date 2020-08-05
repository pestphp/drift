<?php

declare(strict_types=1);

namespace Pest\Drift\PHPUnit\Class_;

use Pest\Drift\PHPUnit\AbstractPHPUnitToPestRector;
use PhpParser\Node;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\TraitUse;
use PHPUnit\Framework\TestCase;

class TraitUsesToUsesRector extends AbstractPHPUnitToPestRector
{
    public function getNodeTypes(): array
    {
        return [Class_::class];
    }

    public function refactor(Node $node): ?Node
    {
        if (! $this->isObjectType($node, TestCase::class)) {
            return null;
        }

        if (($traits = $node->getTraitUses()) !== []) {
            $pestUsesNode = $this->createPestUses($traits);

            $this->pestCollector->addUses($node, $pestUsesNode);
        }

        return $node;
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
            return $this->createArg(new ClassConstFetch($trait, 'class'));
        }, $traits);

        return $this->builderFactory->funcCall('uses', $traits, );
    }
}
