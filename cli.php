<?php

define('IS_CLI', (PHP_SAPI == 'cli') ? true : false);

if (!IS_CLI) {
    echo 'not cli mode';exit;
}

define('APP_PATH', dirname(__FILE__));
$autoLoader = require_once APP_PATH . '/vendor/autoload.php';
$autoLoader->addPsr4("SDK\\", APP_PATH.'/sdk');

require_once APP_PATH.'/init.php';

(new Yaf\Application(APP_PATH . "/conf/application.ini"))->bootstrap()->getDispatcher()->dispatch(new Yaf\Request\Simple());
