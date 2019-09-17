<?php

use SDK\Libraries\ApiDoc;

class DocController extends AbstractController
{
    protected $CHECK_SIGN = true;

    public function indexAction()
    {
        $controller = $this->getRequest()->getQuery('c','index');
        $action     = $this->getRequest()->getQuery('a','index');
        $api_desc_tpl = ApiDoc::parse($controller, $action);
        $this->getView()->display('doc/index.php',$api_desc_tpl);
        return false;
    }
}
