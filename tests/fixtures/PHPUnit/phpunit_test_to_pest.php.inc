<?php
use PHPUnit\Framework\TestCase;

class ExampleTest extends TestCase
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

it('testSimple', function () {
    $this->assertTrue(true);
});
?>
