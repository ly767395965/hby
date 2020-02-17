<?php
namespace Wap\Controller;


use Common\Common\BaseHomeController;




class NewsController extends BaseHomeController
{

    public function index()
    {
        $this->display('News');
    }


}