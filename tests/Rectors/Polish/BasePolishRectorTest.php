<?php

namespace Pest\Drift\Testing\Rectors\Polish;

use Pest\Drift\Testing\BaseRectorTestCase;

abstract class BasePolishRectorTest extends BaseRectorTestCase
{
    protected function provideConfig(): string
    {
        return __DIR__ . '/../../config/polish_rectors.yml';
    }
}
