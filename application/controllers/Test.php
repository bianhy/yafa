<?php

use SDK\Libraries\DB;
use SDK\Libraries\Data;

class TestController extends AbstractController {

	public function dbAction() {

	    $s    = DB::builder('message')->max('id');
        var_dump($s);
	    $user = DB::builder('users.user')->where('uid','=',123)->first();
	    var_dump($user);exit;
	}

    public function redisAction()
    {
        $set = Data::redis('default')->set('t1',123);
        $get = Data::redis('default')->get('t1');
        var_dump($set,$get);exit;
	}
}
