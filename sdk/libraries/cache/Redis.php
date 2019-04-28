<?php

namespace SDK\Libraries\Cache;

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

    private static $instances;

    public function __construct($host, $port, $timeout = 1,$auth=null) {
        $this->redis   = new \Redis();
        $this->host    = $host;
        $this->port    = $port;
        $this->timeout = $timeout;
        $this->auth    = $auth;
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
        try {
            $this->redis->connect($this->host, $this->port, $this->timeout);
            if ($this->auth) {
                $this->redis->auth($this->auth);
            }
            $this->redis->setOption(\Redis::OPT_SERIALIZER, \Redis::SERIALIZER_NONE);
        } catch (\RedisException $e) {
            throw new \RedisException($e->getMessage(), $e->getCode());
        }

        $this->connect = true;
        //return $this;
    }

    public function __call($method,$arguments) {

        if (!$this->connect) {
            $this->connect();
        }
        $ret = call_user_func_array(array($this->redis,$method),$arguments);

        if ($this->redis->getLastError() != null) {
            throw new \RedisException('redis error => method:'.$method.', arguments:'.json_encode($arguments), 404);
        }

        return $ret;
    }
}