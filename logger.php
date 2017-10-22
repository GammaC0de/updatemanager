<?php
file_put_contents('php://stderr', "Logger started.\n");
if(php_sapi_name() != 'cli' && isset($_SERVER['HTTP_USER_AGENT']) && substr($_SERVER['HTTP_USER_AGENT'], 0, 16) == 'GitHub-Hookshot/') {
    $json = json_decode($_POST['payload'], true);
    $ref = $json['ref'];
    file_put_contents('php://stderr', "ref='$ref'\n");
    if ($json['ref'] == ('refs/heads/' . PYLOAD_BRANCH)) {
        file_put_contents('php://stderr', '$_GET = ' . print_r($_GET, TRUE). "\n");
        file_put_contents('php://stderr', '$_POST = ' . print_r($_POST, TRUE). "\n");
        file_put_contents('php://stderr', '$_SERVER = ' . print_r($_SERVER, TRUE). "\n");
    }
    else {
        file_put_contents('php://stderr', "Wrong brnach.\n");
        exit(0);
    }
}
?>