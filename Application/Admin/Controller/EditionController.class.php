<?php
namespace Admin\Controller;

use Common\Common\BaseController;
use Think\Cache\Driver\Memcachesae;

use Think\Controller;

/**
 * Class CarController
 * @package Admin\Controller
 * 版本管理控制器
 */
class EditionController extends BaseController {
    protected static $table = 'versioninfo';
    public function index() {

        $this->display();

    }

    //版本号添加方法
    public function addEdition(){
        $this->display();
    }

}
