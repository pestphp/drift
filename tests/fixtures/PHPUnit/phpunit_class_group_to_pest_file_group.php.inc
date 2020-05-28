<?php
use PHPUnit\Framework\TestCase;

/**
 * @group integration
 */
class Example2Test extends TestCase
{
    public function testSimple()
    {
        $this->assertTrue(true);
    }
}
?>
-----
<?php
use PHPUnit\Framework\TestCase;
uses()->group('integration');
it('testSimple', function () {
    $this->assertTrue(true);
});
?>
