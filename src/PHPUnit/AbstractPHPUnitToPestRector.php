<?php

namespace Pest\Drift\PHPUnit;

use PhpParser\Node\Expr\FuncCall;
use Rector\Core\Rector\AbstractRector;
use Rector\Core\RectorDefinition\RectorDefinition;

abstract class AbstractPHPUnitToPestRector extends AbstractRector
{
    public function getDefinition(): RectorDefinition
    {
        return new RectorDefinition(
            'Migrate PHPUnit behavior to Pest'
        );
    }

    public function createUsesCall(): FuncCall
    {
        return $this->createFuncCall('uses');
    }
}
