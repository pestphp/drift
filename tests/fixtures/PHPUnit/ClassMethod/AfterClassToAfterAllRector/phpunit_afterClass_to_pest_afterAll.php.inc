<?php
use PHPUnit\Framework\TestCase;

class AfterClassTest extends TestCase
{
    /**
     * @afterClass
     */
    public function after()
    {
        echo 'afterAll';
    }

    public function testAfterClass()
    {
        $this->assertTrue(true);
    }
}
?>
-----
<?php
use PHPUnit\Framework\TestCase;
afterAll(function () {
    echo 'afterAll';
});
test('testAfterClass', function () {
    $this->assertTrue(true);
});
?>
