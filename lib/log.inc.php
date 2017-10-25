<?php
require('vendor/autoload.php');

define('LOGDIR', 'logs');


class Logger
{
    private $l;
    private $cli;

    function __construct()
    {
        print ("php_sapi_name() =" . php_sapi_name());
        $this->cli = php_sapi_name() == 'cli';
        $this->l = getenv('DYNO') == false  && !$this->cli ? new Katzgrau\KLogger\Logger(LOGDIR) : null;
    }

    public function debug($msg) {
        print($msg . PHP_EOL);
        if (!$this->cli)
            if (is_null($this->l)) {
                error_log($msg);
            }
            else {
                $this->l->debug($msg);
            }
        flush();
    }

    public function info($msg) {
        print($msg . PHP_EOL);
        if (!$this->cli)
            if (is_null($this->l)) {
                error_log($msg);
            }
            else {
                $this->l->info($msg);
            }
        flush();
    }

    public function warning($msg) {
        print($msg . PHP_EOL);
        if (!$this->cli)
            if (is_null($this->l)) {
                error_log($msg);
            }
            else {
                $this->l->warning($msg);
            }
        flush();
    }

    public function error($msg) {
        print($msg . PHP_EOL);
        if (!$this->cli)
            if (is_null($this->l)) {
                error_log($msg);
            }
            else {
                $this->l->error($msg);
            }
        flush();
    }
}
?>