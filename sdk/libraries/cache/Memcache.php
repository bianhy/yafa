<?php

namespace SDK\Libraries\Cache;

if (!class_exists('MemcacheException')) {
    class MemcacheException extends \Exception{}
}

class Memcache
{
    /**
     * @var \Memcache
     */
    public $memcache;

    public $host;

    public $port;

    public $connect = false;

    private static $instances;

    public function __construct($host, $port)
    {
        $this->memcache = new \Memcache();
        $this->host     = $host;
        $this->port     = $port;
        if (!$this->memcache->connect($this->host, $this->port)) {
            throw new MemcacheException('addserver error:'.$this->host.':'.$this->port);
        }
    }

    public static function getInstance($host, $port)
    {
        $key   = $host.':'.$port;
        if (!isset(self::$instances[$key]) || !(self::$instances[$key] instanceof self)) {
            self::$instances[$key] = new self($host, $port);
        }
        return self::$instances[$key];
    }

    public function __call($method, $arguments)
    {
        $ret = call_user_func_array(array($this->memcache, $method), $arguments);

        if ($ret === false && strtolower($method) != 'get') {
            throw new MemcacheException('memcache error => method:'.$method.', arguments:'.json_encode($arguments), 404);
        }
        return $ret;
    }
}
