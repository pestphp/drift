<?php
test('it does something', function () {
    $this->assertTrue(true);
});
?>
-----
<?php
it('does something', function () {
    $this->assertTrue(true);
});
?>
