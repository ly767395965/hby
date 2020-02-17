<?php
namespace Home\Controller;

use Common\Common\BaseHomeController;


class CarTypeController extends BaseHomeController
{
    public function index()
    {
        $table = M('car_carmodel');
        $cartype = I('get.cartype');

        if ($cartype == 1){
            $sql = "SELECT a.id,a.frontimg, a.carmodelname,a.displacement,a.carmodeltype,a.configstyle,a.agestyle,a.bearboxtype,a.sitecount,a.shortdayprice,a.weekdayprice,a.monthdayPrice,b.brand FROM car_carmodel a LEFT JOIN car_barand b ON a.barandid=b.id WHERE (a.isrecommend=1) AND (a.state = 0) AND (a.isdel=0) AND (a.carmodeltype=1) ORDER BY a.sort ASC LIMIT 0,12";
        }
        if ($cartype == 2){
            $sql = "SELECT a.id,a.frontimg, a.carmodelname,a.displacement,a.carmodeltype,a.configstyle,a.agestyle,a.bearboxtype,a.sitecount,a.shortdayprice,a.weekdayprice,a.monthdayPrice,b.brand FROM car_carmodel a LEFT JOIN car_barand b ON a.barandid=b.id WHERE (a.isrecommend=1) AND (a.state = 0) AND (a.isdel=0) AND (a.carmodeltype=2) ORDER BY a.sort ASC LIMIT 0,12";
        }
        if ($cartype == 3){
            $sql = "SELECT a.id,a.frontimg, a.carmodelname,a.displacement,a.carmodeltype,a.configstyle,a.agestyle,a.bearboxtype,a.sitecount,a.shortdayprice,a.weekdayprice,a.monthdayPrice,b.brand FROM car_carmodel a LEFT JOIN car_barand b ON a.barandid=b.id WHERE (a.isrecommend=1) AND (a.state = 0) AND (a.isdel=0) AND (a.carmodeltype=3) ORDER BY a.sort ASC LIMIT 0,12";
        }
        if ($cartype == 4){
            $sql = "SELECT a.id,a.frontimg, a.carmodelname,a.displacement,a.carmodeltype,a.configstyle,a.agestyle,a.bearboxtype,a.sitecount,a.shortdayprice,a.weekdayprice,a.monthdayPrice,b.brand FROM car_carmodel a LEFT JOIN car_barand b ON a.barandid=b.id WHERE (a.isrecommend=1) AND (a.state = 0) AND (a.isdel=0) AND (a.carmodeltype=4) ORDER BY a.sort ASC LIMIT 0,12";
        }

        if ($cartype == 5){
            $sql = "SELECT a.id,a.frontimg, a.carmodelname,a.displacement,a.carmodeltype,a.configstyle,a.agestyle,a.bearboxtype,a.sitecount,a.shortdayprice,a.weekdayprice,a.monthdayPrice,b.brand FROM car_carmodel a LEFT JOIN car_barand b ON a.barandid=b.id WHERE (a.isrecommend=1) AND (a.state = 0) AND (a.isdel=0) AND (a.carmodeltype=5) ORDER BY a.sort ASC LIMIT 0,12";
        }

        if ($cartype == ''){
            $sql = "SELECT a.id,a.frontimg, a.carmodelname,a.displacement,a.carmodeltype,a.configstyle,a.agestyle,a.bearboxtype,a.sitecount,a.shortdayprice,a.weekdayprice,a.monthdayPrice,b.brand FROM car_carmodel a LEFT JOIN car_barand b ON a.barandid=b.id WHERE (a.isrecommend=1) AND (a.state = 0) AND (a.isdel=0) ORDER BY a.sort ASC LIMIT 0,12";
        }


        $list = $table->query($sql);
        foreach ($list as $key =>$data){
            //判断图片文件是否存在
            if (file_exists("./Public".$data['frontimg'])){
//            检测图片文件是否可读
                if (is_readable("./Public".$data['frontimg'])){
                    $frontimg = $data['frontimg'];
                } else {
                    $frontimg = '';
                }

            } else {
                $frontimg = '';
            }
            $list[$key]['frontimg'] = $frontimg;
        }

        $this->assign('list',$list);
        $this->display();
    }


}