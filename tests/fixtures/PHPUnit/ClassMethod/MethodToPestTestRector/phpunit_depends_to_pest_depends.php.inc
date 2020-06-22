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
it('testDependedOnByNextTest', function () {
    echo "Depended on";
});
it('testDependsOnPreviousTest', function () {
    echo "Depends on";
})->depends('testDependedOnByNextTest');
?>
