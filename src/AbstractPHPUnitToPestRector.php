<?php

namespace Pest\Drift;

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
}
