<?php
use PHPUnit\Framework\TestCase;

class ThrowsExceptionTest extends TestCase
{
    public function testThrowsException()
    {
        $this->expectException(Exception::class);
        throw new Exception('Something happened.');
    }
}
?>
use PHPUnit\Framework\TestCase;

it('testThrowsException', function () {
    throw new Exception('Something happened.');
})->throws(Exception::class);
