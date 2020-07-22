<?php
use PHPUnit\Framework\TestCase;

class MethodTest extends TestCase
{
    private function alwaysTrueHelper()
    {
        $this->assertTrue(true);
    }

    public function testMethod()
    {
        $this->alwaysTrueHelper();
    }
}
?>
-----
<?php
use PHPUnit\Framework\TestCase;
function alwaysTrueHelper()
{
    test()->assertTrue(true);
}
test('testMethod', function () {
    alwaysTrueHelper();
});
?>
