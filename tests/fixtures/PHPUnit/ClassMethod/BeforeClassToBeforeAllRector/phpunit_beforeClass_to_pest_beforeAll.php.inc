<?php
use PHPUnit\Framework\TestCase;

class BeforeClassTest extends TestCase
{
    /**
     * @beforeClass
     */
    public function before()
    {
        echo 'beforeAll';
    }

    public function testBeforeClass()
    {
        $this->assertTrue(true);
    }
}
?>
-----
<?php
use PHPUnit\Framework\TestCase;
beforeAll(function () {
    echo 'beforeAll';
});
test('testBeforeClass', function () {
    $this->assertTrue(true);
});
?>
