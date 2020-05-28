<?php

namespace Pest\Drift\Testing\Rectors\PHPUnit;

use Iterator;
use Pest\Drift\Testing\BaseRectorTestCase;

class PHPUnitRectorTestCase extends BaseRectorTestCase
{
    public function testCanConvertMethod(): void
    {
        $this->doTestFile("{$this->fixturePath()}/phpunit_test_to_pest.php.inc");
    }

    /**
     * @dataProvider provideDataForGroup
     */
    public function testCanConvertGroup(string $path): void
    {
        $this->doTestFile($path);
    }

    public function provideDataForGroup(): array
    {
        return [
            'class group' => ["{$this->fixturePath()}/phpunit_class_group_to_pest_file_group.php.inc"],
            'method group' => ["{$this->fixturePath()}/phpunit_method_group_to_pest_test_group.php.inc"],
        ];
    }

    protected function provideConfig(): string
    {
        return __DIR__ . '/../../config/phpunit_rectors.yml';
    }

    protected function fixturePath(): string
    {
        return __DIR__ . '/../../fixtures/PHPUnit';
    }
}
