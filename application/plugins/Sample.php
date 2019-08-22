<?php
/**
 * @name SamplePlugin
 * @desc Yaf定义了如下的6个Hook,插件之间的执行顺序是先进先Call
 * @see http://www.php.net/manual/en/class.yaf-plugin-abstract.php
 * @author Administrator
 */
use Yaf\Plugin_Abstract;
use Yaf\Request_Abstract;
use Yaf\Response_Abstract;

class SamplePlugin extends Plugin_Abstract {

	public function routerStartup(Request_Abstract $request, Response_Abstract $response) {
	}

	public function routerShutdown(Request_Abstract $request, Response_Abstract $response)
    {
        if (IS_CLI){
            $opt        = getopt('c:a::');
            $controller = isset($opt['c']) ? $opt['c'] : 'index';
            $action     = isset($opt['a']) ? $opt['a'] : 'index';
            $request->controller = $controller;
            $request->action     = $action;
        }
	}

	public function dispatchLoopStartup(Request_Abstract $request, Response_Abstract $response) {
	}

	public function preDispatch(Request_Abstract $request, Response_Abstract $response) {
	}

	public function postDispatch(Request_Abstract $request, Response_Abstract $response) {
	}

	public function dispatchLoopShutdown(Request_Abstract $request, Response_Abstract $response) {
	}
}
