<?php
require('vendor/autoload.php');

define('LOGDIR', 'logs');


class Logger
{
    private $l;
    private $cli;
    private $dyno;

    function __construct()
    {
        $this->cli = php_sapi_name() == 'cli';
        $this->dyno = getenv('DYNO') != false;
        $this->l = !$this->dyno  && !$this->cli ? new Katzgrau\KLogger\Logger(LOGDIR) : null;
    }

    function send_remote_syslog($message, $component = "updater", $program="updatemanager") {
        $sock = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
        foreach(explode(PHP_EOL, $message) as $line) {
            $syslog_message = "<22>" . date('M d H:i:s ') . $program . ' ' . $component . ': ' . $line;
            socket_sendto($sock, $syslog_message, strlen($syslog_message), 0, getenv('RSYSLOG_SERVER'), getenv('RSYSLOG_PORT'));
        }
        socket_close($sock);
    }

    public function debug($msg) {
        print($msg . PHP_EOL);
        if ($this->dyno)
            $this->send_remote_syslog($msg);
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
        if ($this->dyno)
            $this->send_remote_syslog($msg);
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
        if ($this->dyno)
            $this->send_remote_syslog($msg);
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
        if ($this->dyno)
            $this->send_remote_syslog($msg);
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