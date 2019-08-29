<?php

namespace SDK\Libraries;

use SDK\Libraries\Logger\AbstractLogger;
use Monolog\Logger as MonologLogger;
use Yaf\Registry;

class Logger extends AbstractLogger
{
    public static $container = null;

    private $name = null;

    private static $instances;

    private  $dir = '/data/logs';

    /**
     * @param $name
     * @param $file
     * @return MonologLogger
     */
    public static function get($name,$file = '')
    {
        $key = $name;
        if (!isset(self::$instances[$key]) || !(self::$instances[$key] instanceof self)) {
            self::$instances[$key] = new self($key,$file);
        }
        return self::$instances[$key];
    }

    public function __construct($name,$file = '')
    {
        $this->setDir();

        $this->name = $name;
        $logger     = new MonologLogger($name);
        $stream     = $this->dir.'/'.$name.'/'.$file.'/'.date('Ymd').'.log';
        $logger->pushHandler($this->getDebugHandler(MonologLogger::DEBUG));
        $logger->pushHandler($this->getStreamHandler($stream,MonologLogger::DEBUG));


        $this->logger = $logger;
        return $this;
    }

    public function setDir()
    {
        $logIni = Registry::get('log_ini');
        if ($logIni['path']){
            $this->dir = $logIni['path'];
        }
    }
}
