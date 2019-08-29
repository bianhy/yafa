<?php

namespace SDK\Libraries\Cache;

use SDK\Libraries\Logger;

class Redis {

    /**
     * @var \Redis
     */
    public $redis;

    public $host;

    public $port;

    public $auth;

    public $timeout;

    public $connect = false;

    /**
     * @var \Monolog\Logger
     */
    public $logger;

    private static $instances;

    public function __construct($host, $port, $timeout = 1,$auth=null) {
        $this->redis   = new \Redis();
        $this->host    = $host;
        $this->port    = $port;
        $this->timeout = $timeout;
        $this->auth    = $auth;
        $this->logger  = Logger::get('redis');
    }

    public static function getInstance($host,$port,$timeout,$auth=null)
    {
        $key   = $host.':'.$port;
        if (!isset(self::$instances[$key]) || !(self::$instances[$key] instanceof self)) {
            self::$instances[$key] = new self($host,$port,$timeout,$auth);
        }
        return self::$instances[$key];
    }

    public function connect()
    {
        $start_time = microtime(true);
        try {
            $this->redis->connect($this->host, $this->port, $this->timeout);
            if ($this->auth) {
                $this->redis->auth($this->auth);
            }
            $this->redis->setOption(\Redis::OPT_SERIALIZER, \Redis::SERIALIZER_NONE);
        } catch (\RedisException $e) {
            $this->logger->error($this->host.":".$this->port."connect error |".$e->getCode().' => '.$e->getMessage());
            throw new \RedisException($e->getMessage(), $e->getCode());
        }
        $this->logger->debug('connect:'.$this->host.':'.$this->port);
        $this->logger->debug('use time:'.(microtime(true)- $start_time));
        $this->connect = true;
        //return $this;
    }

    public function __call($method,$arguments) {

        if (!$this->connect) {
            $this->connect();
        }
        $this->logger->debug('command:'.$method.','.json_encode($arguments));
        $start_time = microtime(true);
        $ret = call_user_func_array(array($this->redis,$method),$arguments);
        $use_time   = microtime(true)- $start_time;
        $this->logger->debug('use time:'.$use_time);
        if ($this->redis->getLastError() != null) {
            $this->logger->error('redis error => '.$this->host.':'.$this->port.' method:'.$method.', arguments:'.json_encode($arguments));
            throw new \RedisException('redis error => method:'.$method.', arguments:'.json_encode($arguments), 404);
        }
        if ($use_time > 0.1) {
            $this->logger->alert('use time: '.$use_time.', method:'.$method.', arguments:'.json_encode($arguments));
        }
        return $ret;
    }
}