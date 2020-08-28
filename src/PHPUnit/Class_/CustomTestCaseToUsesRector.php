<?php

declare(strict_types=1);

namespace Pest\Drift\PHPUnit\Class_;

use Pest\Drift\PHPUnit\AbstractPHPUnitToPestRector;
use Pest\Exceptions\ShouldNotHappen;
use PhpParser\Node;
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

        /** @var Name $class */
        $class = $this->canRemovePhpUnitClass($node) ? $node->extends : $node->namespacedName;
        $className = $this->getName($class);

        if ($className === null) {
            throw ShouldNotHappen::fromMessage('Failed getting name of the class');
        }

        $newNode = $this->createFuncCall('uses', [$this->createClassConstantReference($className)]);

        $this->pestCollector->addUses($node, $newNode);
        return $node;
    }
}
