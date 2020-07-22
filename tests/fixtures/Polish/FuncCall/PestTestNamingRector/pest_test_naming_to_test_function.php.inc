<?php
test('test does something', function () {
    $this->assertTrue(true);
});
?>
-----
<?php
test('does something', function () {
    $this->assertTrue(true);
});
?>
