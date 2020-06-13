<?php

namespace Pest\Drift\Testing\Rectors\PHPUnit;

use Pest\Drift\Testing\BaseRectorTestCase;

abstract class BasePHPUnitRectorTest extends BaseRectorTestCase
{
    protected function provideConfig(): string
    {
        return __DIR__ . '/../../config/phpunit_rectors.yml';
    }
}
