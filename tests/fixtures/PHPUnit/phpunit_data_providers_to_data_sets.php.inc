<?php
use PHPUnit\Framework\TestCase;

class DataProviderTest extends TestCase
{
    /**
     * @dataProvider getNames
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
dataset('getEmails', [
    'enunomaduro@gmail.com',
    'other@example.com'
]);
it('testEmail', function ($email) {
    $this->assertNotEmpty($email);
})->with('getEmails');
?>
