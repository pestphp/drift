<?php

declare(strict_types=1);

namespace Pest\Drift\PHPUnit\Class_;

use Pest\Drift\PHPUnit\AbstractPHPUnitToPestRector;
use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Stmt\Class_;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTagNode;
use PHPUnit\Framework\TestCase;
use Rector\BetterPhpDocParser\PhpDocInfo\PhpDocInfo;
use Rector\NodeTypeResolver\Node\AttributeKey;

class PhpDocGroupOnClassToFileScopeGroupRector extends AbstractPHPUnitToPestRector
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

        // Add groups from class to whole file
        $classGroupNames = $this->getPhpDocGroupNames($node);
        if (! empty($classGroupNames)) {
            $this->pestCollector->addFileScopeGroup($node, $this->createFileScopeGroupCall($classGroupNames));
        }

        return $node;
    }

    /**
     * @return string[]|null
     */
    public function getPhpDocGroupNames(Node $node): ?array
    {
        /** @var PhpDocInfo|null $phpDocInfo */
        $phpDocInfo = $node->getAttribute(AttributeKey::PHP_DOC_INFO);
        if ($phpDocInfo === null) {
            return null;
        }

        return array_map(static function (PhpDocTagNode $tag): string {
            return (string) $tag->value;
        }, $phpDocInfo->getTagsByName('group'));
    }

    private function createFileScopeGroupCall(array $groups): MethodCall
    {
        return $this->createMethodCall($this->createUsesCall(), 'group', $groups);
    }
}
