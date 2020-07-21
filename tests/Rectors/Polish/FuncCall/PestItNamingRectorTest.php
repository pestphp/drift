<?php

namespace Pest\Drift\Testing\Rectors\Polish\FuncCall;

use Iterator;
use Pest\Drift\Testing\Rectors\PHPUnit\BasePHPUnitRectorTest;

class PestItNamingRectorTest extends BasePHPUnitRectorTest
{
    /**
     * @dataProvider provideData()
     */
    public function test(string $file): void
    {
        $this->doTestFile($file);
    }

    public function provideData(): Iterator
    {
        return $this->yieldFilesFromDirectory(
            __DIR__ . '/../../../fixtures/Polish/FuncCall/PestItNamingRector'
        );
    }
}
