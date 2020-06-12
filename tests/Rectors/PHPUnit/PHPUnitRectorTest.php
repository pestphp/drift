<?php

namespace Pest\Drift\Testing\Rectors\PHPUnit;

use Pest\Drift\Testing\BaseRectorTestCase;

class PHPUnitRectorTest extends BaseRectorTestCase
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

    public function testCanConvertDataProviders(): void
    {
        $this->doTestFile("{$this->fixturePath()}/phpunit_data_providers_to_data_sets.php.inc");
    }

    public function testCanConvertExpectException(): void
    {
        $this->doTestFile("{$this->fixturePath()}/phpunit_expect_exception_to_pest_throws.php.inc");
    }

    public function testCanConvertSetUp(): void
    {
        $this->doTestFile("{$this->fixturePath()}/phpunit_setUp_to_pest_before_each.php.inc");
    }

    public function testCanConvertTearDown(): void
    {
        $this->doTestFile("{$this->fixturePath()}/phpunit_tearDown_to_pest_after_each.php.inc");
    }

    public function testCanConvertSkip(): void
    {
        $this->doTestFile("{$this->fixturePath()}/phpunit_skip_to_pest_skip.php.inc");
    }

    /**
     * @dataProvider provideDataForUses
     */
    public function testCanConvertUses(string $path): void
    {
        $this->doTestFile($path);
    }

    public function provideDataForUses(): array
    {
        return [
            'One use' => ["{$this->fixturePath()}/phpunit_trait_to_pest_uses.php.inc"],
            'Multiple uses, single line' => ["{$this->fixturePath()}/phpunit_trait_to_pest_uses_multiple.php.inc"],
            'Multiple uses, multiple lines' => ["{$this->fixturePath()}/phpunit_trait_to_pest_uses_multiple_lines.php.inc"],
        ];
    }

    public function testCanConvertBeforeClass(): void
    {
        $this->doTestFile("{$this->fixturePath()}/phpunit_beforeClass_to_pest_beforeAll.php.inc");
    }

    public function testCanConvertAfterClass(): void
    {
        $this->doTestFile("{$this->fixturePath()}/phpunit_afterClass_to_pest_afterAll.php.inc");
    }

    public function testCanConvertHelperMethod(): void
    {
        $this->doTestFile("{$this->fixturePath()}/phpunit_method_to_pest_helper.php.inc");
    }

    public function testCanIgnoreHelperMethodOverridden(): void
    {
        $this->doTestFile("{$this->fixturePath()}/phpunit_method_to_pest_helper_with_override_method.php.inc");
    }

    public function testCanConvertCustomTestCase(): void
    {
        $this->doTestFile("{$this->fixturePath()}/phpunit_custom_test_case_to_pest_uses.php.inc");
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
