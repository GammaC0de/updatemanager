<?php
require('vendor/autoload.php');

define('LOGDIR', 'logs');


class Logger
{
    private $l;
    private $cli;

    function __construct()
    {
        $this->cli = php_sapi_name() == 'cli';
        $this->l = getenv('DYNO') == false  && !$this->cli ? new Katzgrau\KLogger\Logger(LOGDIR) : null;
    }

    public function debug($msg) {
        print($msg . PHP_EOL);
        if (!$this->cli)
            if (is_null($this->l)) {
                file_put_contents('php://stderr', $msg . PHP_EOL);
            }
            else {
                $this->l->debug($msg);
            }
    }

    public function info($msg) {
        print($msg . PHP_EOL);
        if (!$this->cli)
            if (is_null($this->l)) {
                file_put_contents('php://stderr', $msg . PHP_EOL);
            }
            else {
                $this->l->info($msg);
            }
    }

    public function warning($msg) {
        print($msg . PHP_EOL);
        if (!$this->cli)
            if (is_null($this->l)) {
                file_put_contents('php://stderr', $msg . PHP_EOL);
            }
            else {
                $this->l->warning($msg);
            }
    }

    public function error($msg) {
        print($msg . PHP_EOL);
        if (!$this->cli)
            if (is_null($this->l)) {
                file_put_contents('php://stderr', $msg . PHP_EOL);
            }
            else {
                $this->l->error($msg);
            }
    }
}
?>