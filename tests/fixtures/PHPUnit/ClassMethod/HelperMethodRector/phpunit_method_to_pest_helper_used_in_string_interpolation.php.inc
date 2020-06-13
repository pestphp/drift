<?php
use PHPUnit\Framework\TestCase;

class MethodStringInterpolationTest extends TestCase
{
    private function alwaysTrueHelper()
    {
        return "true";
    }

    public function testMethod()
    {
        "{$this->alwaysTrueHelper()} works";
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
it('testMethod', function () {
    alwaysTrueHelper() . " works";
});
?>
