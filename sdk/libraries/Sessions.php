<?php
/**
 *
 * @file Sessions.php
 * @author bianhy
 * @date 2019-04-28 14:40
 *
 */

namespace SDK\Libraries;

use Yaf\Registry;
use Yaf\Session;

class Sessions
{
    protected static $instance;

    public static function getInstance()
    {
        if (!self::$instance instanceof self) {
            self::$instance = new static;
        }
        return self::$instance;
    }

    public function __construct()
    {
        $this->init();
    }

    public function init()
    {
        $config = Registry::get('session_ini');
        if (!$config){
            throw new \Exception('session配置信息获取失败！');
        }
        $handler = $config['session_save_handle'];

        switch ($handler) {

            case 'redis':
            case 'memcache':
                $host = $config['session_host'];
                $port = $config['session_port'];
                $auth = $config['session_auth'];
                $save_path = 'tcp://' . $host . ':' . $port;
                if ($auth) {
                    $save_path .= '?auth=' . $auth;
                }
                break;
            case 'files':
                $save_path = $config['session_save_path'];
                break;
            default:
                throw new \Exception('错误的session存储方式');
        }
        if (!$save_path){
            throw new \Exception('获取session存储路径失败！');
        }

        $expire = $config['session_expire'] ?: 1800;
        ini_set('session.save_handler', $handler);
        ini_set('session.save_path', $save_path);
        ini_set('session.gc_maxlifetime', $expire);
        ini_set('session.cookie_lifetime', $expire);
    }

    public function set($name, $value)
    {
        return Session::getInstance()->set($name, $value);

    }

    public function get($name)
    {
        return Session::getInstance()->get($name);
    }

    public function del($name)
    {
        return Session::getInstance()->del($name);
    }
}