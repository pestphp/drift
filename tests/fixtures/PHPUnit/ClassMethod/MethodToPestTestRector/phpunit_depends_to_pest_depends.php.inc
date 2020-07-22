<?php
use PHPUnit\Framework\TestCase;

class DependsTest extends TestCase
{
    public function testDependedOnByNextTest()
    {
        echo "Depended on";
    }

    /** @depends testDependedOnByNextTest */
    public function testDependsOnPreviousTest()
    {
        echo "Depends on";
    }
}
?>
-----
<?php
use PHPUnit\Framework\TestCase;
test('testDependedOnByNextTest', function () {
    echo "Depended on";
});
test('testDependsOnPreviousTest', function () {
    echo "Depends on";
})->depends('testDependedOnByNextTest');
?>
