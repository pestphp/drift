<?php
use Pest\Drift\Testing\fixtures\TraitA;
use Pest\Drift\Testing\fixtures\TraitB;
use PHPUnit\Framework\TestCase;

class UsesMultipleLinesTest extends TestCase
{
    use TraitA;
    use TraitB;

    public function testUsesMultipleLines()
    {
        $this->assertTrue(true);
    }
}
?>
-----
<?php
use Pest\Drift\Testing\fixtures\TraitA;
use Pest\Drift\Testing\fixtures\TraitB;
use PHPUnit\Framework\TestCase;
uses(TraitA::class, TraitB::class);
test('testUsesMultipleLines', function () {
    $this->assertTrue(true);
});
?>
