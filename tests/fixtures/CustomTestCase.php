<?php

declare(strict_types=1);

namespace Pest\Drift\Testing\fixtures;

use PHPUnit\Framework\TestCase;

class CustomTestCase extends TestCase
{
    public function getPestCreator(): string
    {
        return 'Nuno';
    }
}
