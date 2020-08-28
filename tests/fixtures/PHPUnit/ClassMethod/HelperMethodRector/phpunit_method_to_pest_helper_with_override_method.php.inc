<?php
namespace MethodOverride;
use Pest\Drift\Testing\fixtures\CustomTestCase;

class MethodOverrideTest extends CustomTestCase
{
    public function getPestCreator(): string
    {
        return "Oliver";
    }

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
namespace MethodOverride;
use Pest\Drift\Testing\fixtures\CustomTestCase;

class MethodOverrideTest extends CustomTestCase
{
    public function getPestCreator(): string
    {
        return "Oliver";
    }
}
uses(\MethodOverride\MethodOverrideTest::class);
function alwaysTrueHelper()
{
    test()->assertTrue(true);
}
test('testMethod', function () {
    alwaysTrueHelper();
});
?>
