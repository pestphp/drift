<?php

namespace Pest\Drift\Testing\Rectors\PHPUnit\ClassMethod;

use Iterator;
use Pest\Drift\PHPUnit\ClassMethod\HelperMethodRector;
use Pest\Drift\Testing\Rectors\PHPUnit\BasePHPUnitRectorTest;
use Symplify\SmartFileSystem\SmartFileInfo;

class HelperMethodRectorTest extends BasePHPUnitRectorTest
{
    protected function getRectorClass(): string
    {
        return HelperMethodRector::class;
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
            __DIR__ . '/../../../fixtures/PHPUnit/ClassMethod/HelperMethodRector'
        );
    }
}
