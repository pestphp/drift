<?php
use PHPUnit\Framework\TestCase;

class SetUpTest extends TestCase
{
    protected $stack;

    protected function setUp(): void
    {
        $this->stack = [];
    }

    public function testEmpty()
    {
        $this->assertTrue(empty($this->stack));
    }
}
?>
-----
<?php
use PHPUnit\Framework\TestCase;
beforeEach(function () {
    $this->stack = [];
});
test('testEmpty', function () {
    $this->assertTrue(empty($this->stack));
});
?>
