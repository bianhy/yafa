<?php

use SDK\Libraries\DB;
use SDK\Libraries\Data;

class CliController extends AbstractController {


    /**
     * CLI 下执行脚本
     * php cli.php -c=cli -a=db
     *
     */
	public function dbAction()
    {
	    $s    = DB::builder('message')->max('id');
        var_dump($s);exit;
	}

    public function redisAction()
    {
        $set = Data::redis('default')->set('t1',123);
        $get = Data::redis('default')->get('t1');
        var_dump($set,$get);exit;
	}
}
