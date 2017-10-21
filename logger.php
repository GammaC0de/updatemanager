<?php
file_put_contents('php://stderr', "Logger started.\n");
file_put_contents('php://stderr', '$_GET = ' . print_r($_GET, TRUE). "\n");
file_put_contents('php://stderr', '$_POST = ' . print_r($_POST, TRUE). "\n");
file_put_contents('php://stderr','Body = ' . file_get_contents('php://stdin') . "\n");
?>