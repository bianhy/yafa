<?php
/**
 *
 * @file DataConfigLoader.php
 * @author bianhy
 * @date 2019-04-28 15:36
 *
 */

namespace SDK\Libraries\Database;

use Yaf\Registry;

class DataConfigLoader
{

    public static function db($table, $hash=null) {
        if (!$table) {
            throw new \Exception('table 参数错误');
        }
        $con    = self::parseTable($table);
        $database = $con['database'];
        $table  = $con['table'];
        $mysqlIni = Registry::get('mysql_ini');

        $config = $mysqlIni->get($database);
        if (!$config) {
            throw new \Exception('db: '.$database.'对应的配置信息不存在');
        }

        $database    = isset($config['database']) ? $config['database'] : null ;
        $table_alias = '';
        if ($hash) {
            $return = call_user_func_array(__NAMESPACE__.'\DbHash::hash',[$database, $table, $hash]);
            $database    = $return['database'];
            $table       = $return['table'];
            $table_alias = $return['table_alias'];
        }
        /* 最终返回的信息, 以下字段为必须返回的 */
        $ret = array(
            'host'        => $config['host'],
            'user'        => $config['user'],
            'pass'        => $config['pass'],
            'port'        => $config['port'],
            'database'    => $database,
            'table'       => $table,
            'table_alias' => $table_alias
        );
        return $ret;
    }

    public static function redis($channel, $hash=null) {

        if (!$channel) {
            throw new \Exception('channel 参数错误');
        }

        $redisIni = Registry::get('redis_ini');
        $config   = $redisIni->get($channel);
        if (!$config) {
            throw new \Exception('redis: '.$channel.'对应的配置信息不存在');
        }
        if ($hash){
            //这里不做hash了，考虑到集群，还有可以自己在channel做
        }
        /* 最终返回的信息, 以下字段为必须返回的 */
        $ret = array(
            'host'        => $config['host'],
            'port'        => $config['port'],
            'timeout'     => $config['timeout'],
            'auth'        => isset($config['auth']) ? $config['auth']: null ,
        );
        return $ret;
    }

    public static function memcache($channel, $hash=null) {
        if (!$channel) {
            throw new \Exception('channel 参数错误');
        }

        $redisIni = Registry::get('memcache_ini');
        $config   = $redisIni->get($channel);
        if (!$config) {
            throw new \Exception('memcache: '.$channel.'对应的配置信息不存在');
        }
        if ($hash){
            //这里不做hash了，考虑到集群，还有可以自己在channel做
        }
        /* 最终返回的信息, 以下字段为必须返回的 */
        $ret = array(
            'host'        => $config['host'],
            'port'        => $config['port'],
            'timeout'     => $config['timeout'],
            'auth'        => isset($config['auth']) ? $config['auth']: null ,
        );
        return $ret;
    }

    public static function parseTable($table)
    {
        $ret   = ['database'=>'default', 'table'=>$table];
        $table = str_replace('.','/',$table);
        $last  = strrpos( $table, '/');
        if ($last !== false) {
            $database   = substr($table,0,$last);
            $table = substr($table, $last+1);
            $ret = ['database'=>$database, 'table'=>$table];
        }
        return $ret;
    }
}