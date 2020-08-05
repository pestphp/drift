<?php

declare(strict_types=1);

namespace Pest\Drift\PHPUnit\Class_;

use Pest\Drift\PHPUnit\AbstractPHPUnitToPestRector;
use PhpParser\Node;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Class_;
use PHPUnit\Framework\TestCase;

final class CustomTestCaseToUsesRector extends AbstractPHPUnitToPestRector
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

        if ($node->extends === null) {
            return null;
        }

        if ($this->canRemovePhpUnitClass($node)
            && $this->isName($node->extends, TestCase::class)
        ) {
            return null;
        }

        /** @var Name $className */
        $className = $this->canRemovePhpUnitClass($node) ? $node->extends : $node->name;
        $newNode = $this->createFuncCall('uses', [new ClassConstFetch($className, 'class')]);

        $this->pestCollector->addUses($node, $newNode);
        return $node;
    }
}
