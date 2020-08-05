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
    return "true";
}
test('testMethod', function () {
    alwaysTrueHelper() . ' works';
});
?>
