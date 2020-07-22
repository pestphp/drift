<?php
use PHPUnit\Framework\TestCase;

class Example3Test extends TestCase
{
    /**
     * @group integration
     */
    public function testSimple()
    {
        $this->assertTrue(true);
    }
}
?>
-----
<?php
use PHPUnit\Framework\TestCase;

test('testSimple', function () {
    $this->assertTrue(true);
})->group('integration');
?>
