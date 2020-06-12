<?php
use PHPUnit\Framework\TestCase;

class DataProviderHelperMethodTest extends TestCase
{
    /**
     * @dataProvider getEmails
     */
    public function testEmail($email)
    {
        $this->assertWrapper($email);
    }

    public function assertWrapper($email)
    {
        $this->assertNotEmpty($email);
    }

    public function getEmails()
    {
        return [
            ['enunomaduro@gmail.com'],
            ['other@example.com']
        ];
    }
}
?>
-----
<?php
use PHPUnit\Framework\TestCase;
dataset('getEmails', function () {
    return [
        ['enunomaduro@gmail.com'],
        ['other@example.com']
    ];
});
function assertWrapper($email)
{
    test()->assertNotEmpty($email);
}
it('testEmail', function ($email) {
    assertWrapper($email);
})->with('getEmails');
?>
