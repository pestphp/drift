<?php

declare(strict_types=1);

namespace Pest\Drift\PHPUnit;

use Exception;
use Pest\Drift\PestCollector;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTagNode;
use PHPUnit\Framework\TestCase;
use Rector\BetterPhpDocParser\PhpDocInfo\PhpDocInfo;
use Rector\Core\Rector\AbstractRector;
use Rector\Core\RectorDefinition\RectorDefinition;
use Rector\NodeTypeResolver\Node\AttributeKey;

abstract class AbstractPHPUnitToPestRector extends AbstractRector
{
    public PestCollector $pestCollector;

    public function __construct(PestCollector $pestCollector)
    {
        $this->pestCollector = $pestCollector;
    }

    public function getDefinition(): RectorDefinition
    {
        return new RectorDefinition('Migrate PHPUnit behavior to Pest');
    }

    public function isInPhpUnitBehavior(Node $node): bool
    {
        $classNode = $node->getAttribute(AttributeKey::CLASS_NODE);
        if ($classNode === null) {
            return false;
        }

        return $this->isObjectType($classNode, TestCase::class);
    }

    public function createUsesCall(): FuncCall
    {
        return $this->createFuncCall('uses');
    }

    protected function getPestClosure(Expr $pestTest): Closure
    {
        return $this->getInnerVariable($pestTest)->args[1]->value;
    }

    protected function getDataProviderName(ClassMethod $method): ?string
    {
        /** @var PhpDocInfo|null $phpDocInfo */
        $phpDocInfo = $method->getAttribute(AttributeKey::PHP_DOC_INFO);
        if ($phpDocInfo === null) {
            return null;
        }

        $dataProviders = array_map(
            static fn (PhpDocTagNode $tag): string => (string) $tag->value,
            $phpDocInfo->getTagsByName('dataProvider')
        );

        if ($dataProviders === []) {
            return null;
        }

        if (count($dataProviders) !== 1) {
            throw new Exception('Multiple data providers found on one method.');
        }

        return $dataProviders[0];
    }

    protected function canRemovePhpUnitClass(Class_ $node): bool
    {
        foreach ($node->getMethods() as $method) {
            if (! $this->isNodeRemoved($method)) {
                return false;
            }
        }
        return true;
    }

    private function getInnerVariable(Expr $expr): Expr
    {
        if (isset($expr->var)) {
            return $this->getInnerVariable($expr->var);
        }

        return $expr;
    }
}
