<?php
namespace Admin\Controller;
use Common\Common\BaseController;
use Think\Cache\Driver\Memcachesae;
use Think\Controller;

/**
 * Class CarController
 * @package Admin\Controller
 *新闻分类控制器
 */
class NewclassController extends BaseController {
    protected static $table = 'new_class';
    public function index() {
        $sql = "SELECT id,classname FROM  new_class WHERE isdel=%d ORDER BY ID DESC";
        //获取数据总记录数量
        $countSql = "SELECT COUNT(ID) FROM new_class WHERE isdel=%d";
        //参数数组，按顺序传递你要传递的参数值
        $ary = array(0);
        //显示分页，最后一个true为分页开关，false则不显示分页，只显示数据
        $this->pageDisplay($sql,$countSql,16,$ary,'count(id)','list','page',flash);

    	 $this->display();

    }
    
    //新闻分类添加方法 addNewclass()
    public function addNewclass() {
        //判断输出模板
        if (empty($_POST)){
            $this->display();
        }
        if ($_POST){
            $newclass = M(self::$table);
            $data =array(
                array('classname','require','<script>alert("分类名称不能为空");history.back(-1);</script>',1),
            );
            if(!$newclass->validate($data)->create()){
                // 如果创建失败 表示验证没有通过 输出错误提示信息
                exit($newclass->getError());
            }else{
                //记录操作日志
                if ($newclass->add()) {
                    //获取添加成功返回的数据id
                    $returnid = M(self::$table)->order('id desc')->find();
                    //接收当前管理员登陆名
                    $auserInfo = UserInfo();
                    $log = self::writeLog(self::$table, $returnid['id'], 'add', Date('Y-m-d H:i:sA'), $auserInfo['name']);
                    if ($log){
                        $this->success("添加成功!",U('Newclass/Index',1));
                    }
                }else{
                    $this->error("添加失败",'',1);
                }
            }
        }
    }


    //新闻分类添加方法 editNewclass()
    public function editNewclass() {
        $newclass = M(self::$table);
        //判断操作数据的id的提交方式并接收
        if (empty($_POST)){
            $id = $_GET['id'];
            cookie('id',$id);
            $cid = cookie('id');
            $data = $newclass->where(array('id='.$cid))->select();
            $this->assign('list',$data);
            $this->Display();
        }

        if ($_POST){
            $cid = cookie('id');
            $data =array(
                array('classname','require','<script>alert("分类名称不能为空!");history.back(-1);</script>',1),
            );
            if(!$newclass->validate($data)->create()){
                // 如果创建失败 表示验证没有通过 输出错误提示信息
                exit($newclass->getError());
            }else{
                //记录操作日志
                if ($newclass->where(array('id='.$cid))->save()) {
                    $auserInfo = UserInfo();//接收当前管理员登陆名
                    $log = self::writeLog(self::$table, $cid, 'edit', Date('Y-m-d H:i:sA'), $auserInfo['name']);
                    if ($log){
                        cookie('id',null);
                        $this->success("修改成功!",U('Newclass/Index',1));
                    }
                }else{
                    $this->error("修改失败",'',1);
                }
            }
        }

    }

    
    //新闻分类删除方法 del()
    public function del() {
        //实例化文章表
        $aticle = M('new_aticle');
        //加载广告分类表
        $newclass = M(self::$table);
        $id = $_GET['id'];
        $data['isdel'] = 1;
        //关联删除查询
        $sql = "SELECT COUNT(ID) FROM new_aticle WHERE (isdel=0) AND (catid=%d)";
        $arr = [$id];
        $aticleList = $aticle->query($sql,$arr);
        //如果该分类下是否有关联数据
        if ($aticleList[0]['count(id)']>0){
            echo '<script>alert("该新闻分类下有数据，暂时不能删除该分类!");history.back(-1);</script>';
            exit();
        } else {
            $LinkModlesace=$newclass->where(array('id'=>$id))->save($data);
            //记录操作日志
            if ($LinkModlesace) {
                $auserInfo = UserInfo();//接收当前管理员登陆名
                $log = self::writeLog(self::$table, $id, 'del', Date('Y-m-d H:i:sA'), $auserInfo['name']);
                if ($log) {
                    $this->success('删除成功!', U('Newclass/Index',1));
                }
            }else{
                $this->error('删除失败','',1);
            }
        }

    }

    //批量删除
    public function delAll()
    {
        //实例化文章表
        $aticle = M('new_aticle');
        //$isError 空数组用于存储不能删除的数据id。
        $isError = [];
        //$isDelStr 空字符串变量用于并接 $isError 数组里面的id。
        $isDelStr = '';
        if (!empty($_POST)) {
            $ids = I('post.ids');
            $m = M(self::$table);
            $data['isdel'] = 1;
            //关联删除查询
            $arr = explode(",",$ids);
            foreach ($arr as $key){
                $sql = "SELECT COUNT(ID) FROM new_aticle WHERE (isdel=0) AND (catid=%d)";
                $arry = $key;
                $aticleList = $aticle->query($sql,$arry);
                //如果该分类下是否有关联数据
                if ($aticleList[0]['count(id)']>0){
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

}
