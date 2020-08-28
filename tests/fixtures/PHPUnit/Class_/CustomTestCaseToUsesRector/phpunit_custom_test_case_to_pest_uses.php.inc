<?php
use Pest\Drift\Testing\fixtures\CustomTestCase;

class CustomTestCaseTest extends CustomTestCase
{
    public function testCustomTestCase()
    {
        $this->assertTrue(true);
    }
}
?>
-----
<?php
use Pest\Drift\Testing\fixtures\CustomTestCase;
uses(\Pest\Drift\Testing\fixtures\CustomTestCase::class);
test('testCustomTestCase', function () {
    $this->assertTrue(true);
});
?>
