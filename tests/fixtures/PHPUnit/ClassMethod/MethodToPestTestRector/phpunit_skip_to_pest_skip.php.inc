<?php
use PHPUnit\Framework\TestCase;

class SkipTest extends TestCase
{
    public function testSkip()
    {
        echo "works";
        $this->markTestSkipped('Not yet finished');
    }
}
?>
-----
<?php
use PHPUnit\Framework\TestCase;

test('testSkip', function () {
    echo "works";
})->skip('Not yet finished');
?>
