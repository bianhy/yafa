<?php
/**
 *
 * @file Data.php
 * @author bianhy
 * @date 2019-04-28 14:40
 *
 */

namespace SDK\libraries;

use SDK\Libraries\Cache\Memcache;
use SDK\Libraries\Cache\Redis;

class Data
{
    /**
     * @param $channel
     * @param null $hash
     * @return \Redis
     * @throws \Exception
     */
    public static function redis($channel, $hash=null)
    {
        $config = ConfigLoader::redis($channel, $hash);
        return Redis::getInstance($config['host'], $config['port'], $config['timeout'], $config['auth']);
    }

    /**
     * @param $channel
     * @param null $hash
     * @return \Memcache
     * @throws \Exception
     */
    public static function memcache($channel, $hash=null)
    {
        $config = ConfigLoader::memcache($channel, $hash);
        return Memcache::getInstance($config['host'], $config['port']);
    }
}
