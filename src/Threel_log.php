<?php

namespace Malanciault\Threelci;

use Monolog\Logger;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\SyslogUdpHandler;

class Threel_log extends \CI_Log {

  protected $my_custom_var;

  public function __construct() {
     parent :: __construct();
     //your stuff here
     $this->my_custom_var = "Custom Loggers Are Great!";
   }

  //your new class function
  public function my_special_logger() {
      //your code
  }

  //overwrite a base class function
  public function write_log($level, $msg) {
    parent::write_log($level, $msg);

    $data = array(
        'level' => $level,
        'msg' => $msg,
        'url' => (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]",
        'ip' => $_SERVER['REMOTE_ADDR'],
        'user_id' => isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'anonymous',
        'email' => isset($_SESSION['email']) ? $_SESSION['email'] : '',
        'post' => is_array($_POST) && count($_POST) > 0 ? var_export($_POST, true) : '',
      );

    if (ENVIRONMENT != 'development') {


      if ($level == 'warning') {
        // papertrail
        // Set the format
        $output = "%channel%.%level_name%: %message%";
        $formatter = new LineFormatter($output);

        // Setup the logger
        $logger = new Logger('planetair-logger-'.ENVIRONMENT);
        $syslogHandler = new SyslogUdpHandler("logs7.papertrailapp.com", 52026);
        $syslogHandler->setFormatter($formatter);
        $logger->pushHandler($syslogHandler);

        // Use the new logger
        $logger->addWarning($msg . ' - user_id=' . $data['user_id'] . ' email=' . $data['email'] . ' url=' .  $data['url'] . ' post=' . $data['post']);
      }

      if ($level == 'error') {

        // papertrail
        // Set the format
        $output = "%channel%.%level_name%: %message%";

        $formatter = new LineFormatter($output);

        // Setup the logger
        $logger = new Logger('planetair-logger-'.ENVIRONMENT);
        $syslogHandler = new SyslogUdpHandler("logs7.papertrailapp.com", 52026);
        $syslogHandler->setFormatter($formatter);
        $logger->pushHandler($syslogHandler);

        // Use the new logger

        $logger->error($msg . ' - user_id=' . $data['user_id'] . ' email=' . $data['email'] . ' url=' .  $data['url'] . ' post=' . $data['post']);
      }
    }
  }
}