<?php
print("Logger started.\n");
ob_start();
var_dump($_GET);
var_dump($_POST);
$result = ob_get_clean();
print($result . "\n");
?>