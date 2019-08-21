<?php

use Yaf\Controller_Abstract;

class AbstractController extends Controller_Abstract
{
    protected $user_info;

    /**
     * yaf构造函数
     * 相当于__construct
     */
    public function init()
    {
        $this->checkLogin();
        $this->setLoginUser();
    }

    protected function checkLogin()
    {
        return true;
    }

    protected function setLoginUser()
    {
        $this->user_info = ['user_id' => 123,'real_name' => 'yafa'];
    }

}
