<?php
use Pest\Drift\Testing\fixtures\TraitA;
use PHPUnit\Framework\TestCase;

class UsesTest extends TestCase
{
    use TraitA;

    public function testUses()
    {
        $this->assertTrue(true);
    }
}
?>
-----
<?php
use Pest\Drift\Testing\fixtures\TraitA;
use PHPUnit\Framework\TestCase;
uses(TraitA::class);
test('testUses', function () {
    $this->assertTrue(true);
});
?>
