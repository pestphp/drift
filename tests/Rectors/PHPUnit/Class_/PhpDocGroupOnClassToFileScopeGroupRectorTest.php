<?php

namespace Pest\Drift\Testing\Rectors\PHPUnit\Class_;

use Iterator;
use Pest\Drift\Testing\Rectors\PHPUnit\BasePHPUnitRectorTest;
use Symplify\SmartFileSystem\SmartFileInfo;

class PhpDocGroupOnClassToFileScopeGroupRectorTest extends BasePHPUnitRectorTest
{
    /**
     * @dataProvider provideData()
     */
    public function test(SmartFileInfo $fileInfoInfo): void
    {
        $this->doTestFileInfo($fileInfo);
    }

    public function provideData(): Iterator
    {
        return $this->yieldFilesFromDirectory(
            __DIR__ . '/../../../fixtures/PHPUnit/Class_/PhpDocGroupOnClassToFileScopeGroupRector'
        );
    }
}
