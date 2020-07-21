<?php

namespace Pest\Drift\Testing\Rectors\Polish\FuncCall;

use Iterator;
use Pest\Drift\Pest\FuncCall\PestItNamingRector;
use Pest\Drift\Testing\Rectors\Polish\BasePolishRectorTest;
use Symplify\SmartFileSystem\SmartFileInfo;

class PestItNamingRectorTest extends BasePolishRectorTest
{
    protected function getRectorClass(): string
    {
        return PestItNamingRector::class;
    }

    /**
     * @dataProvider provideData()
     */
    public function test(SmartFileInfo $fileInfo): void
    {
        $this->doTestFileInfo($fileInfo);
    }

    public function provideData(): Iterator
    {
        return $this->yieldFilesFromDirectory(
            __DIR__ . '/../../../fixtures/Polish/FuncCall/PestItNamingRector'
        );
    }
}
