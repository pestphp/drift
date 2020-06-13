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
uses(CustomTestCase::class);
it('testCustomTestCase', function () {
    $this->assertTrue(true);
});
?>
