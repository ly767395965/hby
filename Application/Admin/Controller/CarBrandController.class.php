<?php
namespace Admin\Controller;

use Common\Common\BaseController;
use Org\Util\Date;
use Org\Util\PinYin;
use Org\Util\PinyinAction;
use Think\Controller;

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/9/13
 * Time: 9:52
 * 品牌控制器
 */
class CarBrandController extends BaseController
{
    protected static $table = 'car_barand';
    //车辆品牌列表index()
    public function index()
    {
        $key = I('get.key');//接受模糊查询的条件
        $ary = [];//将条件传给数组
        $selectid = I('get.select');
        $sql = "SELECT id,brand,initial,state FROM car_barand  WHERE (isdel=0)  ";
        $countSql = "SELECT COUNT(ID) FROM car_barand WHERE (isdel=0) ";
        switch ($selectid){
            case 1:
                $sql = $sql . "AND (brand LIKE '%%%s%%') ";
                $countSql = $countSql ."AND (brand LIKE '%%%s%%')";
                break;
            case 2:
                $sql = $sql ."AND (initial LIKE '%%%s%%') ";
                $countSql = $countSql ."AND (initial LIKE '%%%s%%') ";
                break;
            default:
                $sql = $sql."ORDER BY ID DESC";
                $countSql = $countSql;
        }
        //判断 $selectid 不为空时 追加id排序
        if ($selectid != null) {
            $sql = $sql . "ORDER BY id";
            $ary = [$key];
        } else {
            $ary = [];
        }
        $this->pageDisplay($sql, $countSql, 16, $ary, 'count(id)', 'list', 'page', true);
        $this->display();
    }

    //品牌添加方法 addCarBrand（）
    public function addCarBrand()
    {
        $brand = M(self::$table);
        //判断输出模板
        if (empty($_POST)) {
            $this->display();
        }
        //判断页面值的传递方式并接收
        if (!empty($_POST)) {
            $rules = array(
                array('brand', 'require', '<script>alert("品牌名称不能为空！");history.back(-1);</script>', 0),
            );
            if (!$brand->validate($rules)->create()) {
                // 如果创建失败 表示验证没有通过 输出错误提示信息
                exit($brand->getError());
            } else {
                //去重
                $sqlbrand = "SELECT COUNT(id) FROM car_barand WHERE brand = '%s' AND isdel = 0 ";
                $ary = [I('post.brand')];
                $list = $brand->query($sqlbrand, $ary);
                $num = $list[0]['count(id)'];

                if ($num > 0) {
                    echo "<script>alert('该品牌已存在，请重新输入！');history.back(-1);</script>";

                } else {
                    //实例化中文拼音方法
                    import("ORG.Util.Pinyin");
                    $pinyin = new PinyinAction();
                    //转成汉语拼音首字母
                    $result = $pinyin->getfirstchar(I('post.brand'));
                    $data = array();
                    $data['brand'] = I('post.brand');
                    $data['initial'] = $result;
                    //记录操作日志
                    if ($brand->add($data)) {
                        //获取添加成功返回的数据id
                        $returnid = M(self::$table)->order('id desc')->find();
                        //接收当前管理员登陆名
                        $auserInfo = UserInfo();
                        $log = self::writeLog(self::$table, $returnid['id'], 'add', Date('Y-m-d H:i:sA'), $auserInfo['name']);
                        if ($log) {
                            $this->success("添加成功!", U('CarBrand/Index',1));
                        }
                    } else {
                        $this->error("添加失败",'',1);
                    }
                }
            }
        }
    }

    //修改品牌信息方法 editCarBrand()
    public function editCarBrand()
    {
        $updata = M(self::$table);
        //判断操作数据的id的提交方式并接收
        if (empty($_POST)) {
            $id = I('get.id');
            cookie('id', $id);
            $cid = cookie('id');
            //根据页面获取的id查询数据
            $result = $updata->where(array('id=' . $cid))->select();
            $this->assign("updata", $result);
            cookie('barand', $result[0]['brand']);
            //保存修改信息
            $this->display();
        }
        //判断页面值的传递方式并接收
        if (!empty($_POST)) {
            $cid = cookie('id');
            $rules = array(
                array('brand', 'require', '<script>alert("品牌名称不能为空!");history.back(-1);</script>', 0),
            );
            if (!$updata->validate($rules)->create()) {
                // 如果创建失败 表示验证没有通过 输出错误提示信息
                exit($updata->getError());
            } else {
                //判断是否去重
                $name = cookie('barand');
                if ($name != I('post.brand')) {
                    //去重
                    $sqlbrand = "SELECT COUNT(id) FROM car_barand WHERE brand = '%s' AND isdel = 0 ";
                    $ary = [I('post.brand')];
                    $list = $updata->query($sqlbrand, $ary);
                    $num = $list[0]['count(id)'];

                    if ($num > 0) {
                        echo "<script>alert('该品牌存在，请重新输入！');window.location.href = document.referrer;</script>)";

                    } else {
                        //实例化中文拼音方法
                        import("ORG.Util.Pinyin");
                        $pinyin = new PinyinAction();
                        //转成汉语拼音首字母
                        $result = $pinyin->getfirstchar(I('post.brand'));
                        $data = array();
                        $data['brand'] = I('post.brand');
                        $data['initial'] = $result;
                        //记录操作日志
                        if ($updata->where(array('id=' . $cid))->save($data)) {
                            //接收当前管理员登陆名
                            $auserInfo = UserInfo();
                            $log = self::writeLog(self::$table, $cid, 'edit', Date('Y-m-d H:i:sA'), $auserInfo['name']);
                            if ($log) {
                                cookie('id', null);
                                $this->success("修改成功!", U('CarBrand/Index',1));
                            }
                        } else {
                            $this->error("修改失败",'',1);
                        }
                    }

                }


            }

        }
    }

    //逻辑删除数据 del()
    public function del()
    {
        //实例化车型表
        $carmodel = M('car_carmodel');
        //加载品牌表
        $delete = M(self::$table);
        //获取需要操作的数据id
        $cid = $_GET['id'];
        $data = 1;
        $arr = array();
        $arr['isdel'] = $data;
        //关联删除查询
        $sql = "SELECT COUNT(ID) FROM car_carmodel WHERE (isdel=0) AND (barandid=%d)";
        $arr1 = [$cid];
        $barandList = $carmodel->query($sql,$arr1);
        //如果该分类下是否有关联数据
        if ($barandList[0]['count(id)']>0){
            echo '<script>alert("该品牌分类下有数据，暂时不能删除该分类!");history.back(-1);</script>';
            exit();
        } else {
            $result = $delete->where(array('id='.$cid))->save($arr);
            //记录操作日志
            if ($result) {
                //接收当前管理员登陆名
                $auserInfo = UserInfo();
                $log = self::writeLog(self::$table, $cid, 'del', Date('Y-m-d H:i:sA'), $auserInfo['name']);
                if ($log) {
                    $this->success("删除成功!", U('CarBrand/Index',1));
                }
            } else {
                $this->error("删除失败!",'',1);
            }
        }
    }

    //批量删除
    public function delAll()
    {
        //实例化车型表
        $carmodel = M('car_carmodel');
        //$isError 空数组用于存储不能删除的数据id。
        $isError = [];
        //$isDelStr 空字符串变量用于并接 $isError 数组里面的id。
        $isDelStr = '';
        if (!empty($_POST)) {
            $ids = I('post.ids');
            //加载品牌表
            $m = M(self::$table);
            $data['isdel'] = 1;
            //关联删除查询
            $arr = explode(",",$ids);
            foreach ($arr as $key){
                $sql = "SELECT COUNT(ID) FROM car_carmodel WHERE (isdel=0) AND (barandid=%d)";
                $arry = $key;
                $barandList = $carmodel->query($sql,$arry);
                //如果该分类下是否有关联数据
                if ($barandList[0]['count(id)']>0){
                    //如果该分类下存在数据则不能删除并终止该操作,并且追加到 $isError 数组
                    array_push($isError,$key);
                } else {
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

    //控制品牌是否显示
    public function isState(){
        $table = M();
        $id = I('get.id');
        $operation = I('get.operation');
        $sql = "UPDATE car_barand SET state=%d WHERE id=%d";
        $arr = [$operation,$id];
        $list = $table->execute($sql,$arr);
        if ($operation == 1){
            $str = '不显示';
        } else {
            $str = '显示';
        }
	
        if ($list>0){
            //接收当前管理员登陆名
            $auserInfo = UserInfo();
            $log = self::writeLog(self::$table, $id, $str, Date('Y-m-d H:i:sA'), $auserInfo['name']);
            if ($log){
                echo '<script>alert("操作成功");window.location.href = document.referrer;</script>';
            }

        } else {
            echo '<script>alert("操作失败");window.location.href = document.referrer;</script>';
        }
    }
}