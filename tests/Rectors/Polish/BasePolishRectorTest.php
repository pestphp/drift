<?php

declare(strict_types=1);

namespace Pest\Drift\Testing\Rectors\Polish;

use Pest\Drift\Testing\BaseRectorTestCase;
use Symplify\SmartFileSystem\SmartFileInfo;

abstract class BasePolishRectorTest extends BaseRectorTestCase
{
    protected function provideConfigFileInfo(): ?SmartFileInfo
    {
        return new SmartFileInfo(__DIR__ . '/../../config/polish_rectors.php');
    }
}
