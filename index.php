<?php

define('APP_PATH', dirname(__FILE__));
$autoLoader = require_once APP_PATH . '/vendor/autoload.php';
$autoLoader->addPsr4("SDK\\", APP_PATH.'/sdk');

require_once APP_PATH.'/init.php';

$application = new Yaf\Application( APP_PATH . "/conf/application.ini");

$application->bootstrap()->run();
