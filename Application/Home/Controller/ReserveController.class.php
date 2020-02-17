<?php
namespace Home\Controller;

use Common\Common\BaseHomeController;

/**
 * Class NewsController
 * @package Home\Controller
 * 预定租车
 */
class ReserveController extends BaseHomeController
{
    public function index()
    {
        $m = M();
        if ($_GET) {
            $id = I('get.id');
            $sql = "SELECT a.id,a.frontimg,a.agestyle,a.configstyle,a.bearboxtype,a.shortdayprice,a.weekdayprice,a.monthdayPrice,b.brand,a.carmodelname,a.carmodeltype,a.leftanterior,a.rightfront,a.rightimg,a
.behindimg FROM car_carmodel a LEFT JOIN car_barand b ON a.barandid=b.id WHERE (a.isdel=0) AND (a.id=%d)";
            $arr = [$id];
        }
        $list = $m->query($sql,$arr);

        //判断图片文件是否存在
        if (file_exists("./Public".$list[0]['frontimg'])){
//            检测图片文件是否可读
            if (is_readable("./Public".$list[0]['frontimg'])){
                $frontimg = $list[0]['frontimg'];
            } else {
                $frontimg = '';
            }

        } else {
            $frontimg = '';
        }
        if (file_exists("./Public".$list[0]['leftanterior'])){
//            检测图片文件是否可读
            if (is_readable("./Public".$list[0]['leftanterior'])){
                $leftanterior = $list[0]['leftanterior'];
            } else {
                $leftanterior = '';
            }
        } else {
            $leftanterior = '';
        }

        if (file_exists("./Public".$list[0]['rightfront'])){
            // 检测图片文件是否可读
            if (is_readable("./Public".$list[0]['rightfront'])){
                $rightfront = $list[0]['rightfront'];
            } else {
                $rightfront = '';
            }
        } else {
            $rightfront = '';
        }

        if (file_exists("./Public".$list[0]['rightimg'])){
            // 检测图片文件是否可读
            if (is_readable("./Public".$list[0]['rightimg'])){
                $rightimg = $list[0]['rightimg'];
            } else {
                $rightimg = '';
            }
        } else {
            $rightimg = '';
        }

        if (file_exists("./Public".$list[0]['behindimg'])){

            // 检测图片文件是否可读
            if (is_readable("./Public".$list[0]['behindimg'])){
                $behindimg = $list[0]['behindimg'];
            } else {
                $behindimg = '';
            }
        } else {
            $behindimg = '';
        }
        $list[0]['frontimg'] = $frontimg;
        $list[0]['leftanterior'] = $leftanterior;
        $list[0]['rightfront'] = $rightfront;
        $list[0]['rightimg'] = $rightimg;
        $list[0]['behindimg'] = $behindimg;
        $this->assign('list',$list);
        //站点信息
        $sitetitle = new IndexController();
        $sitetitle = $sitetitle->webInfo();
        $this->assign('sys_title',$sitetitle[0]['title']);
        $this->display();


    }



}