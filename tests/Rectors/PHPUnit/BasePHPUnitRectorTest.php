<?php

namespace Pest\Drift\Testing\Rectors\PHPUnit;

use Pest\Drift\Testing\BaseRectorTestCase;
use Symplify\SmartFileSystem\SmartFileInfo;

abstract class BasePHPUnitRectorTest extends BaseRectorTestCase
{
    protected function provideConfigFileInfo(): ?SmartFileInfo
    {
        return new SmartFileInfo(__DIR__ . '/../../config/phpunit_rectors.yml');
    }
}
