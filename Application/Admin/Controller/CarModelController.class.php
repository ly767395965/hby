<?php
namespace Admin\Controller;
use Common\Common\BaseController;
use Org\Util\Date;
use Org\Util\PinYin;
use Org\Util\PinyinAction;
use Think\Controller;
use Think\Upload;

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/9/13
 * Time: 9:52
 * 车型控制器
 */
class CarModelController extends BaseController
{
    protected static $table = 'car_carmodel';

    //车型查询方法 Index()
    public function index() {
        $key = I('get.key');//接受模糊查询的条件
        $ary = [];//将条件传给数组
        $selectid = I('get.select');
        //判断车型类型
        if ($selectid == 3){
            if ($key == '商务车'){
                $key = 1;
            } elseif ($key == '越野车'){
                $key = 2;
            } elseif ($key == '面包车'){
                $key = 3;
            } elseif ($key == '轿车'){
                $key = 4;
            } elseif ($key == '客车'){
                $key = 5;
            }
        }
        $sql = "SELECT a.id,a.carmodelname,a.carmodeltype,a.state,a.isrecommend,a.sort,a.agestyle,a.configstyle,a.bearboxtype,a.chairtype,a.fuelstand,a.skylight,a.shortdayprice,a.weekdayprice,a.monthdayPrice,b.brand FROM ";
        $sql = $sql . "car_carmodel a LEFT JOIN car_barand b ON a.barandid=b.id WHERE (a.isdel=0) AND ";
        $countSql = "SELECT COUNT(a.ID) FROM car_carmodel a LEFT JOIN car_barand b ON a.barandid=b.id WHERE (a.isdel=0) AND (b.isdel=0) AND ";
        switch ($selectid){
            case 1:
                $sql = $sql . "(b.brand LIKE '%%%s%%') ";
                $countSql = $countSql . "(b.brand LIKE '%%%s%%')";
                break;
            case 2:
                $sql = $sql . "(a.carmodelname LIKE '%%%s%%') ";
                $countSql = $countSql . "(a.carmodelname LIKE '%%%s%%')";
                break;
            case 3:
                $sql = $sql . "(a.carmodeltype LIKE '%%%s%%') ";
                $countSql = $countSql . "(a.carmodeltype LIKE '%%%s%%')";
                break;
            default:
                $sql = "SELECT a.id,a.carmodelname,a.sort,a.carmodeltype,a.state,a.isrecommend,a.agestyle,a.configstyle,a.bearboxtype,a.chairtype,a.fuelstand,a.skylight,a.shortdayprice,a.weekdayprice,a.monthdayPrice,b.brand FROM ";
                $sql = $sql . "car_carmodel a LEFT JOIN car_barand b ON a.barandid=b.id WHERE a.isdel=0 ORDER BY a.id DESC";
                $countSql = "SELECT COUNT(a.ID) FROM car_carmodel a LEFT JOIN car_barand b ON a.barandid=b.id WHERE a.isdel=0";
        }
        //判断 $selectid 不为空时 追加id排序
        if ($selectid != null) {
            $sql = $sql . "ORDER BY a.id DESC";
            $ary = [$key];
        } else {
            $ary = [];
        }
        $this->pageDisplay($sql, $countSql, 16, $ary, 'count(a.id)', 'list', 'page', true);
        $this->display();

    }




    //车型添加方法 addCarModel()
    public function addCarModel(){
        //判断输出模板
        if (empty($_POST)){
            //查询品牌
            $sqlbarand = M('car_barand');
            $brand = $sqlbarand->where(array('isdel=0'))->select();
            $this->assign('brand',$brand);
            $this->display();
        }
        $adddata = M(self::$table);
        $numManager = M();
        if (!empty($_POST)) {
        // 实例化上传类
        $upload = new Upload();
        // 限制图片大小为100k
        $upload->maxSize = 102400;
        // 设置附件上传类型
        $upload->exts = array('jpg', 'png', 'jpeg');
        // 设置附件上传目录
        $upload->savePath = '/Uploads/Carimg/';
        //设置子目录
        $upload->autoSub = true;
        //同名则替换
        $upload->uploadReplace = true;
        //上传文件的保存规则，支持数组和字符串方式定义
//        $upload->saveName=array('uniqid','');
        //获取上传的文件
//            $info = $upload->upload();
        $info = $upload->upload(array($_FILES['photo']));
        $rules = array(
            array('barandid', 'require', '<script>alert("车型名称不能为空！");history.back(-1);</script>', 0),
            array('carmodelname', 'require', '<script>alert("车型名称不能为空！");history.back(-1);</script>', 0),
            array('agestyle','number','<script>alert("年代款不能为空");history.back(-1);</script>',0),
            array('configstyle','require','<script>alert("配置款不能为空");history.back(-1);</script>',0),
            array('sitecount','require','<script>alert("座位数不能为空");history.back(-1);</script>',0),
            array('bearboxtype','require','<script>alert("变速箱不能为空");history.back(-1);</script>',0),
            array('displacement','require','<script>alert("排量不能为空");history.back(-1);</script>',0),
            array('fuelstand','require','<script>alert("燃油标号不能为空");history.back(-1);</script>',0),
            array('skylight','require','<script>alert("天窗不能为空");history.back(-1);</script>',0),
            array('tankcapacity','require','<script>alert("油箱容量不能为空");history.back(-1);</script>',0),
            array('chairtype','require','<script>alert("座椅类型不能为空");history.back(-1);</script>',0),
            array('isrecommend', 'require', '<script>alert("是否推荐不能为空！");history.back(-1);</script>',0),
            array('state', 'require', '<script>alert("车型状态不能为空！");history.back(-1);</script>',0),
            array('shortdayprice','require','<script>alert("短租日租价不能为空");history.back(-1);</script>',0),
            array('weekdayprice','require','<script>alert("周租日租价不能为空");history.back(-1);</script>',0),
            array('monthdayPrice','require','<script>alert("月租日租价不能为空");history.back(-1);</script>',0),
            array('carmodeltype','require','<script>alert("车辆类型不能为空");history.back(-1);</script>',0),
        );
        if (!$adddata->validate($rules)->create()) {
            // 如果创建失败 表示验证没有通过 输出错误提示信息
            exit($adddata->getError());
        } else {
            //去重
            $sqlbrand = "SELECT COUNT(id) FROM car_carmodel WHERE carmodelname = '%s' AND isdel = 0 ";
            $ary = [I('post.carmodelname')];
            $list = $numManager->query($sqlbrand,$ary);
            $num = $list[0]['count(id)'];
            if ($num>0) {
                echo "<script>alert('该车型已存在，请重新输入！');history.back(-1);</script>)";
            } else {
                // 用数组保存上传的照片根据需要自行组装
                $data = array();

                for ($i=0;$i<=4;$i++) {
                    if ($info[$i]) {
                        switch ($i) {
                            case 0 :
                                $data['frontimg'] = $info[$i]['savepath'].$info[$i]['savename'];
                                break;
                            case 1 :
                                $data['leftanterior'] = $info[$i]['savepath'].$info[$i]['savename'];
                                break;
                            case 2 :
                                $data['rightfront'] = $info[$i]['savepath'].$info[$i]['savename'];
                                break;
                            case 3 :
                                $data['rightimg'] = $info[$i]['savepath'].$info[$i]['savename'];
                                break;
                            case 4 :
                                $data['behindimg'] = $info[$i]['savepath'].$info[$i]['savename'];
                                break;
                        }
                    }

                }
				if(empty(I('post.sort'))){
					$sort = 12;
				}else {
					$sort = I('post.sort');
				}
                $data['sort'] = $sort;
                $data['barandid'] = I('post.barand');
                $data['carmodelname'] = I('post.carmodelname');
                $data['agestyle'] = I('post.agestyle');
                $data['configstyle'] = I('post.configstyle');
                $data['sitecount'] = I('post.sitecount');
                $data['bearboxtype'] = I('post.bearboxtype');
                $data['displacement'] = I('post.displacement');
                $data['fuelstand'] = I('post.fuelstand');
                $data['skylight'] = I('post.skylight');
                $data['tankcapacity'] = I('post.tankcapacity');
                $data['chairtype'] = I('post.chairtype');
                $data['isrecommend'] = I('post.isrecommend');;
                $data['state'] = I('post.state');
                $data['shortdayprice'] = I('post.shortdayprice');
                $data['weekdayprice'] = I('post.weekdayprice');
                $data['monthdayPrice'] = I('post.monthdayPrice');
                $data['carmodeltype'] = I('post.carmodeltype');

                //记录操作日志
                if ($adddata->add($data)) {
                    //获取添加成功返回的数据id
                    $returnid = M(self::$table)->order('id desc')->find();
                    //接收当前管理员登陆名
                    $auserInfo = UserInfo();
                    $log = self::writeLog(self::$table, $returnid['id'], 'add', Date('Y-m-d H:i:sA'), $auserInfo['name']);
                    if ($log) {
                        $this->success("添加成功!", U('CarModel/Index',1));
                    }
                } else {
                    $this->error("添加失败",'',1);
                }
            }
        }
        }

    }


    //车型信息修改方法 editCarModel()
    public function editCarModel() {
        $up = M (self::$table);
        $isRepeat = false;
        //判断操作数据的id的提交方式并接收
        if (empty($_POST)){
            $id = $_GET['id'];
            cookie('id',$id);
            $cid = cookie('id');
            $sql = "SELECT a.id,a.carmodelname,a.sort,a.carmodeltype,a.state,a.agestyle,a.configstyle,a.bearboxtype,a.sitecount,a.displacement,a.tankcapacity,a.frontimg,a.leftanterior,a.rightfront,a.rightimg,a.behindimg,a.chairtype,a.fuelstand,a.skylight,a.isrecommend,a.shortdayprice,a.weekdayprice,a.monthdayPrice,b.brand,b.id as bid FROM ";
            $sql = $sql . "car_carmodel a LEFT JOIN car_barand b ON a.barandid=b.id WHERE a.isdel=0 AND a.id=%d ORDER BY ID DESC";
            $arr = [$cid];
            $list = $up->query($sql,$arr);
            $this->assign("updata",$list);
            cookie('carmoel',$list[0]['carmodelname']);
            //查询品牌
            $barand = M('car_barand');
            $result=$barand->select();
            $this->assign('barand',$result);
            $this->display();
        }
        if ($_POST) {
            $cid = cookie('id');
            // 实例化上传类
            $upload = new \Think\Upload();
            //限制图片大小为100k
            $upload->maxSize = 102400;
            // 设置附件上传类型
            $upload->exts = array('jpg','png', 'jpeg');
            // 设置附件上传目录
            $upload->savePath = '/Uploads/Carimg/';
            //设置子目录
            $upload->autoSub = true;
//            $upload->saveName=array('uniqid','');
            //同名则替换
            $upload->uploadReplace = true;
            //文件上传
//            $info = $upload->upload();
            $info = $upload->upload(array($_FILES['photo']));
            $rules = array(
                array('barandid', 'require', '<script>alert("车型名称不能为空！");history.back(-1);</script>', 0),
                array('carmodelname', 'require', '<script>alert("车型名称不能为空!");history.back(-1);</script>', 0),
                array('agestyle','number','<script>alert("年代款不能为空");history.back(-1);</script>',0),
                array('configstyle','require','<script>alert("配置款不能为空");history.back(-1);</script>',0),
                array('sitecount','require','<script>alert("座位数不能为空");history.back(-1);</script>',0),
                array('bearboxtype','require','<script>alert("变速箱不能为空");history.back(-1);</script>',0),
                array('displacement','require','<script>alert("排量不能为空");history.back(-1);</script>',0),
                array('fuelstand','require','<script>alert("燃油标号不能为空");history.back(-1);</script>',0),
                array('skylight','require','<script>alert("天窗不能为空");history.back(-1);</script>',0),
                array('tankcapacity','require','<script>alert("油箱容量不能为空");history.back(-1);</script>',0),
                array('chairtype','require','<script>alert("座椅类型不能为空");history.back(-1);</script>',0),
                array('isrecommend', 'require', '<script>alert("是否推荐不能为空！");history.back(-1);</script>',0),
                array('state', 'require', '<script>alert("车型状态不能为空！");history.back(-1);</script>',0),
                array('shortdayprice','require','<script>alert("短租日租价不能为空");history.back(-1);</script>',0),
                array('weekdayprice','require','<script>alert("周租日租价不能为空");history.back(-1);</script>',0),
                array('monthdayPrice','require','<script>alert("月租日租价不能为空");history.back(-1);</script>',0),
                array('carmodeltype','require','<script>alert("车辆类型不能为空");history.back(-1);</script>',0),

            );
            if (!$up->validate($rules)->create()) {
                // 如果创建失败 表示验证没有通过 输出错误提示信息
                exit($up->getError());
            } else{
                //判断是否去重
                $name = cookie('carmoel');
                if ($name != I('post.carmodelname')){
                    //去重
                    $sqlbrand = "SELECT COUNT(id) FROM car_carmodel WHERE carmodelname = '%s' AND isdel = 0 ";
                    $ary = [I('post.carmodelname')];
                    $list = $up->query($sqlbrand,$ary);
                    $num = $list[0]['count(id)'];

                    if ($num>0) {
                        echo "<script>alert('该车型名称已存在，请重新输入！');window.location.href = document.referrer;</script>";
                        exit();
                    }
                    $isRepeat = false;
                }else{
                    $isRepeat = true;
                }
                $data =array();
                if ($isRepeat){

                    $data['carmodelname'] = I('post.carmodelname');
                }else{
                    $data['carmodelname'] = $name;
                }
                //查询数据库中原图片路径
			
                $data['sort'] = I('post.sort');
                $data['barandid'] = I('post.barand');
                $data['agestyle'] = I('post.agestyle');
                $data['carmodelname'] = I('post.carmodelname');
                $data['configstyle'] = I('post.configstyle');
                $data['sitecount'] = I('post.sitecount');
                $data['bearboxtype'] = I('post.bearboxtype');
                $data['displacement'] = I('post.displacement');
                $data['fuelstand'] = I('post.fuelstand');
                $data['skylight'] = I('post.skylight');
                $data['tankcapacity'] = I('post.tankcapacity');
                $data['chairtype'] = I('post.chairtype');
                $data['isrecommend'] = I('post.isrecommend');
                $data['state'] = I('post.state');
                $data['shortdayprice'] = I('post.shortdayprice');
                $data['weekdayprice'] = I('post.weekdayprice');
                $data['monthdayPrice'] = I('post.monthdayPrice');
                $data['carmodeltype'] = I('post.carmodeltype');

                for ($i=0;$i<=4;$i++) {
                    if (!empty($info[$i])) {
                        switch ($i) {
                            case 0 :
                                $data['frontimg'] = $info[$i]['savepath'].$info[$i]['savename'];
                                break;
                            case 1 :
                                $data['leftanterior'] = $info[$i]['savepath'].$info[$i]['savename'];
                                break;
                            case 2 :
                                $data['rightfront'] = $info[$i]['savepath'].$info[$i]['savename'];
                                break;
                            case 3 :
                                $data['rightimg'] = $info[$i]['savepath'].$info[$i]['savename'];
                                break;
                            case 4 :
                                $data['behindimg'] = $info[$i]['savepath'].$info[$i]['savename'];
                                break;
                        }
                    }
                }

                //通过img数组，得到上传图片字段，通过该字段读取原数据字段的文件路径并删除
                $result=$up->where(array('id='.$cid))->select();
                foreach ($data as $key => $value) {
                    //遍历删除原有的文件
                    unlink('Public/'.$result[0][$key]);
                }
                //记录操作日志
			
                if ($up->where(array('id='.$cid))->save($data)) {
                    //接收当前管理员登陆名
                    $auserInfo = UserInfo();
                    $log = self::writeLog(self::$table, $cid, 'edit', Date('Y-m-d H:i:sA'), $auserInfo['name']);
                    if ($log){
                        cookie('id',null);
                        $this->success("修改成功!", U('CarModel/Index',1));
                    }
                } else {
                    $this->error("修改失败",'',1);
                }

            }
        }
    }


    //逻辑删除数据del()
    public function del() {
        //实例化车辆信息表
        $carinfo = M('car_carinfo');
        //加载车型表
        $delete = M (self::$table);
        //获取需要操作的数据id
        $cid = $_GET['id'];
        $arr = [];
        //关联删除查询
        $sql = "SELECT COUNT(ID) FROM car_carinfo WHERE (isdel=0) AND (carmodel=%d)";
        $arr1 = [$cid];
        $carmodelList = $carinfo->query($sql,$arr1);
        //如果该分类下是否有关联数据
        if ($carmodelList[0]['count(id)']>0){
            echo '<script>alert("该车型分类下有数据，暂时不能删除该分类!");history.back(-1);</script>';
            exit();
        } else {
            $carimg = "SELECT frontimg,leftanterior,rightfront,rightimg,behindimg FROM ". self::$table ." WHERE (id=%d)";
            $cararr = $cid;
            $carimgarr = $delete->query($carimg,$cararr);

            //通过 $carimgarr数组，得到非空的上传图片字段删除图片并赋值$arr数组进行数据库清空
            $arr = $this->delPic($carimgarr[0],$arr);
            //逻辑删除数据
            $result = $delete->where(array('id='.$cid))->save($arr);
            //记录操作日志
            if ($result){
                //接收当前管理员登陆名
                $auserInfo = UserInfo();
                $log = self::writeLog(self::$table, $cid, 'del', Date('Y-m-d H:i:sA'), $auserInfo['name']);
                if ($log){
                    $this->success("删除成功!",U('CarModel/Index',1));
                }
            }else{
                $this->error("删除失败!",'',1);
            }
        }

    }

    /*删除图片;返回需要清空的数据数组
      @ary      删除图片路径数组
      @dataArr  是清空的数据库字段数组
    */
    public function delPic($ary,$dataArr)
    {
        foreach ($ary as $key => $val) {
            if (!empty($val)) {
                unlink('Public/'.$val);
                $dataArr[$key] = '';
            }
        }
        $dataArr['isdel'] = 1;
        return $dataArr;
    }

    //批量删除
    public function delAll()
    {
        //实例化车辆信息表
        $carinfo = M('car_carinfo');
        //$isError 空数组用于存储不能删除的数据id。
        $isError = [];
        //$isDelStr 空字符串变量用于并接 $isError 数组里面的id。
        $isDelStr = '';
        if (!empty($_POST)) {
            $ids = I('post.ids');
            $m = M(self::$table);
            $data = array();
//            $data['isdel'] = 1;
            //关联删除查询
            $arr = explode(",",$ids);
            foreach ($arr as $key){
                $sql = "SELECT COUNT(ID) FROM car_carinfo WHERE (isdel=0) AND (carmodel=%d)";
                $arry = $key;
                $carmodelList = $carinfo->query($sql,$arry);

                //如果该分类下是否有关联数据
                if ($carmodelList[0]['count(id)']>0){
                    //如果该分类下存在数据则不能删除并终止该操作,并且追加到 $isError 数组
                    array_push($isError,$key);
                } else{
                    $carimg = "SELECT frontimg,leftanterior,rightfront,rightimg,behindimg FROM ". self::$table ." WHERE (id=%d)";
                    $cararr = $key;
                    $carimgarr = $m->query($carimg,$cararr);
                    //通过 $carimgarr数组，得到非空的上传图片字段删除图片并赋值$arr数组进行数据库清空
                    $data = $this->delPic($carimgarr[0],$data);
                    //执行逻辑删除数据
                    $res = $m->where('id='.$key)->save($data);
                    //删除成功并记录操作日志
                    if ($res) {
                        self::writeLog(self::$table, $key, 'delAll', date('Y-m-d H:i:sA'), self::cookieName());
                    }
                }
            }
            //循环 $isError 数组里面的值 并赋值给 $isDelStr 字符串变量。
            foreach ($isError as $val) {
                $isDelStr .= $val . ',';
            }
            //如果字符串变量 $isError 为空，表示所选的数据都能删除。
            if (empty($isError)) {
                $this->ajaxReturn(array('state' => 1));
            } else {
                //如果字符串变量 $isError 有值，则表示所选的数据不能删除，并反馈给操作用户
                $this->ajaxReturn(array('state'=>0,'msg'=>'id为:('. $isDelStr .')的分类下存在数据不能删除该分类，请清空数据再尝试！'));
            }

        }
    }

}




