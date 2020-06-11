<?php
use Pest\Drift\Testing\fixtures\TraitA;
use PHPUnit\Framework\TestCase;

class UsesMultipleTest extends TestCase
{
    use TraitA, \Pest\Drift\Testing\fixtures\TraitB;

    public function testUsesMultiple()
    {
        $this->assertTrue(true);
    }
}
?>
-----
<?php
use Pest\Drift\Testing\fixtures\TraitA;
use PHPUnit\Framework\TestCase;
uses(TraitA::class, \Pest\Drift\Testing\fixtures\TraitB::class);
it('testUsesMultiple', function () {
    $this->assertTrue(true);
});
?>
