<?php
/**
 * 初始化 加载文件
 */

use Yaf\Registry;
use Yaf\Config\Ini;

$environ = ini_get('yaf.environ') ?? 'dev';

// mysql 配置注册
$mysqlIni = new Ini(APP_PATH . '/conf/mysql.ini', $environ);
Registry::set('mysql_ini', $mysqlIni);

// 注册mysql_conn配置
Registry::set('mysql_conn', []);

// redis 配置注册
$redisIni = new Ini(APP_PATH . '/conf/redis.ini', $environ);
Registry::set('redis_ini', $redisIni);

// memcache 配置注册
$memcacheIni = new Ini(APP_PATH . '/conf/memcache.ini', $environ);
Registry::set('memcache_ini', $memcacheIni);

//session 配置注册
$sessionIni = new Ini(APP_PATH . '/conf/session.ini', $environ);
Registry::set('session_ini', $sessionIni);

//log 配置注册
$logIni = new Ini(APP_PATH . '/conf/log.ini', $environ);
Registry::set('log_ini', $logIni);
