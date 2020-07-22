<?php
use PHPUnit\Framework\TestCase;

class DataProviderTest extends TestCase
{
    /**
     * @dataProvider getEmails
     */
    public function testEmail($email)
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
test('testEmail', function ($email) {
    $this->assertNotEmpty($email);
})->with('getEmails');
?>
