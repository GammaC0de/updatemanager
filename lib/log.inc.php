<?php
require('vendor/autoload.php');

define('LOGDIR', 'logs');


class Logger
{
    private $l;
    private $cli;

    function __construct()
    {
        $this->l = getenv('DYNO') == false ? new Katzgrau\KLogger\Logger(LOGDIR) : null;
        $this->cli = php_sapi_name() == 'cli';
    }

    public function debug($msg) {
        fwrite(STDOUT, $msg . PHP_EOL);
        if (!$this->cli)
            if (is_null($this->l)) {
                fwrite(STDERR, $msg . PHP_EOL);
            }
            else {
                $this->l->debug($msg);
            }
    }

    public function info($msg) {
        fwrite(STDOUT, $msg . PHP_EOL);
        if (!$this->cli)
            if (is_null($this->l)) {
                fwrite(STDERR, $msg . PHP_EOL);
            }
            else {
                $this->l->info($msg);
            }
    }

    public function warning($msg) {
        fwrite(STDOUT, $msg . PHP_EOL);
        if (!$this->cli)
            if (is_null($this->l)) {
                fwrite(STDERR, $msg . PHP_EOL);
            }
            else {
                $this->l->warning($msg);
            }
    }

    public function error($msg) {
        fwrite(STDOUT, $msg . PHP_EOL);
        if (!$this->cli)
            if (is_null($this->l)) {
                fwrite(STDERR, $msg . PHP_EOL);
            }
            else {
                $this->l->error($msg);
            }
    }
}
?>