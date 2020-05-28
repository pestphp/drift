<?php
use PHPUnit\Framework\TestCase;

class TearDownTest extends TestCase
{
    protected function tearDown(): void
    {
        echo "works";
    }

    public function testTearDown()
    {
        $this->assertTrue(true);
    }
}
?>
    -----
<?php
use PHPUnit\Framework\TestCase;

afterEach(function () {
    echo "works";
});
it('testTearDown', function () {
    $this->assertTrue(true);
});
