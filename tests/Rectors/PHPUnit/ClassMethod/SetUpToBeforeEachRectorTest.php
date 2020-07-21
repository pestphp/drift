<?php

namespace Pest\Drift\Testing\Rectors\PHPUnit\ClassMethod;

use Iterator;
use Pest\Drift\PHPUnit\ClassMethod\SetUpToBeforeEachRector;
use Pest\Drift\Testing\Rectors\PHPUnit\BasePHPUnitRectorTest;
use Symplify\SmartFileSystem\SmartFileInfo;

class SetUpToBeforeEachRectorTest extends BasePHPUnitRectorTest
{
    protected function getRectorClass(): string
    {
        return SetUpToBeforeEachRector::class;
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
            __DIR__ . '/../../../fixtures/PHPUnit/ClassMethod/SetUpToBeforeEachRector'
        );
    }
}
