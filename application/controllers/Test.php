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
    /**
     * 测试文档
     * @desc  测试文档
     * @apiparam {"name":"phone", "type":"string", "desc":"phone", "require":true}
     * @apiparam {"name":"password", "type":"string", "desc":"password", "require":true}
     * @apireturn {"name":"phone", "type":"int", "desc":"phone", "require":true}
     * @apireturn {"name":"password", "type":"string", "desc":"password", "require":true}
     * @example   http://local.yafa.com/test/doc
     * @example_ret {"code":"200","data":{"ret":"1"},"time":"1524745122"}
     */
    public function docAction()
    {
        $phone    = $this->getRequest()->getQuery("phone");
        $password = $this->getRequest()->getQuery("password");
        echo json_encode(['phone'=>$phone,'password'=>$password]);
        return false;
	}
}
