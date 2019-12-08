<?php

namespace Malanciault\Threelci\Core;

use Monolog\Logger;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\SyslogUdpHandler;

class Threel_log extends \CI_Log
{
    public $app_key;
    public $host;
    public $port;

    public function __construct()
    {
        $config =& get_config();
        $this->app_key = $config['app_key'];
        $this->host = $config['papertrail_host'];
        $this->port = $config['papertrail_port'];
        parent:: __construct();
    }

    //overwrite a base class function
    public function write_log($level, $msg)
    {
        $data = array(
            'level' => $level,
            'msg' => $msg,
            'url' => (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]",
            'ip' => $_SERVER['REMOTE_ADDR'],
            'user_id' => isset($_SESSION['user_id']) ? $_SESSION['user_id'] : ''
        );

        if ($level == 'warning') {
            // papertrail
            // Set the format
            $output = "%channel%.%level_name%: %message%";
            $formatter = new LineFormatter($output);

            // Setup the logger
            $logger = new Logger($this->app_key . '-logger-'.ENVIRONMENT);
            $syslogHandler = new SyslogUdpHandler($this->host, $this->port);
            $syslogHandler->setFormatter($formatter);
            $logger->pushHandler($syslogHandler);


            // Use the new logger
            $logger->warning($msg . ' - user_id=' . $data['user_id'] . ' . url=' .  $data['url']);
        }

        if ($level == 'error') {
            // papertrail
            // Set the format
            $output = "%channel%.%level_name%: %message%";
            $formatter = new LineFormatter($output);

            // Setup the logger
            $logger = new Logger($this->app_key . '-logger-'.ENVIRONMENT);
            $syslogHandler = new SyslogUdpHandler($this->host, $this->port);
            $syslogHandler->setFormatter($formatter);
            $logger->pushHandler($syslogHandler);

            // Use the new logger

            $logger->error($msg . ' - user_id=' . $data['user_id'] . ' . url=' .  $data['url']);

        }
    }
}