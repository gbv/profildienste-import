<?php

namespace Util;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\ErrorLogHandler;


class Log {

    private static $instance;

    private $log;

    private static $verbose;

    private function __construct(){
        $this->log = new Logger('Importer');
        $this->log->pushHandler(new StreamHandler(Config::getInstance()->getValue('logging', 'dir', true).'Import_'.date('d-m-y_H-i-s').'.log', Logger::INFO));
        if(Log::$verbose){
            $this->log->pushHandler(new ErrorLogHandler());
        }
    }

    public static function getInstance(){
        if(is_null(Log::$instance)){
            Log::$instance = new Log();
        }

        return Log::$instance;
    }

    public static function setVerbose($v){
        Log::$verbose = $v;
    }

    public function getLog(){
        return $this->log;
    }

}