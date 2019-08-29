<?php

namespace SDK\Libraries\Logger;

use Monolog\Formatter\LineFormatter;
use Monolog\Handler\BrowserConsoleHandler;
use Monolog\Handler\NullHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

Abstract class AbstractLogger
{
    protected $logger;
    /**
     * @param $method
     * @param $arguments
     */
    public function __call($method, $arguments)
    {
        try {
            call_user_func_array(array($this->logger, $method), $arguments);
        } catch (\Exception $e) {
        }
    }

    protected function getDebugHandler($level)
    {
        if ($level != Logger::DEBUG) {
            return new NullHandler(Logger::DEBUG);
        }

        if (IS_CLI) {
            $opt        = getopt('c:a:d',['debug']);
            if (isset($opt['d']) || isset($opt['debug'])) {
                return (new StreamHandler('php://output', Logger::DEBUG));
            }
        }

        return (!defined('IS_DEBUG') || IS_DEBUG != true) ? new NullHandler(Logger::DEBUG) : new BrowserConsoleHandler(Logger::DEBUG);
    }

    protected function getStreamHandler($stream,$level = Logger::DEBUG)
    {
        $handler = new StreamHandler($stream,$level);
        $dateFormat = "Y-m-d H:i:s";
        $output = "[%datetime%] %level_name%  %message% %context%\n";
        $handler->setFormatter(new LineFormatter($output, $dateFormat));
        return $handler;
    }
}
