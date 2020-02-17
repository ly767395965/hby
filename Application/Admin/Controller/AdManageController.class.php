<?php
namespace Admin\Controller;

use Common\Common\BaseController;
use Think\Controller;

use Think\Session\Driver\Memcache;

/**
 * Class CarController
 * @package Admin\Controller
 *广告信息管理控制器
 */
class AdManageController extends BaseController
{
    protected static $table = 'ad_banner';

    //广告liet Index()
    public function index()
    {
        $key = I('get.key');//接受模糊查询的条件
        $ary = [$key];//将条件传给数组
        $selectid = I('get.select');
        $sql = "SELECT a.id as id,a.imgtitle,a.imgurl,b.classname,a.ad_url FROM ad_banner a LEFT JOIN ad_class b ON a.classid=b.id WHERE (a.isdel=0) ";
        $countSql = "SELECT COUNT(ID) FROM ad_banner WHERE isdel=0 ";
        switch ($selectid){
            case 1:
                $sql = $sql."AND (imgtitle LIKE '%%%s%%')";
                $countSql = $countSql."AND (imgtitle LIKE '%%%s%%')";
                break;
            case  2:
                $sql = $sql." AND (b.classname LIKE '%%%s%%') ";
                $countSql = "SELECT COUNT(a.id) FROM ad_banner a JOIN ad_class b ON a.classid=b.id where a.isdel=0 AND (b.classname LIKE '%%%s%%')";
                break;
            case 3:
//                $sql = "SELECT a.id as id,a.imgtitle,a.imgurl,b.classname,a.ad_url   FROM  ad_banner a LEFT JOIN ad_class b ON a.classid=b.id WHERE a.isdel=0 AND a.ad_url LIKE  '%%%s%%' ORDER BY a.id DESC";
                $sql = $sql."AND (ad_url LIKE '%%%s%%')";
                $countSql = $countSql."AND (ad_url LIKE '%%%s%%')";
                break;
            default:
                $sql = $sql."ORDER BY a.id DESC";
                $countSql = $countSql;

        }
        //判断 $selectid 不为空时 追加id排序
        if ($selectid != null) {
            $sql = $sql . "ORDER BY id DESC";
            $ary = [$key];
        } else {
            $ary = [];
        }
        if ($selectid==2) {
            $this->pageDisplay($sql, $countSql, 16, $ary, 'count(a.id)', 'list', 'page', true);
        } else {
            $this->pageDisplay($sql, $countSql, 16, $ary, 'count(id)', 'list', 'page', true);
        }
        $this->display();

    }

    //广告添加方法 addAdManage()
    public function addAdManage()
    {
        //接收当前管理员登陆名
        $auserInfo = UserInfo();
        //判断输出模板
        if (empty($_POST)) {
            //查询广告分类
            $addata = M('ad_class');
            $adclass = $addata->where(array('isdel=0'))->select();
            $this->assign('adclass', $adclass);
            $this->display();
        }
        //判断并接受页面传递的值
        if (!empty($_POST)) {
            $adinfo = M(self::$table);
            // 实例化上传类
            $upload = new \Think\Upload();
            // 设置附件上传大小（300KB）
            $upload->maxSize = 307200;
            // 设置附件上传类型
            $upload->exts = array('jpg', 'png', 'jpeg');
            // 设置附件上传目录
            $upload->savePath = '/Uploads/Adimg/';
            //设置子目录
            $upload->autoSub = true;
            //同名则替换
            $upload->uploadReplace = true;
            // 上传文件
            $info = $upload->upload();
            // 上传错误提示错误信息
            if (!$info) {
                $this->error($upload->getError());
            }
            $rules = array(
                array('imgtitle', 'require', '<script>alert("图片属性不能为空！");history.back(-1);</script>', 1), //默认情况下用正则进行验证
                array('adclass', 'require', '<script>alert("广告分类不能为空！");history.back(-1);</script>', 1), //默认情况下用正则进行验证
//                array('ad_url', 'require', '<script>alert("广告地址不能为空！");history.back(-1);</script>', 1), //默认情况下用正则进行验证

            );

            if (!$adinfo->validate($rules)->create()) {
                // 如果创建失败 表示验证没有通过 输出错误提示信息
                exit($adinfo->getError());
            } else {
                //判断PC广告地址
                if (I('post.ad_url') == '') {
                    $urlstr = '#';
                } else {
                    $str = I('post.ad_url');
                    $strs=explode("/",$str); //字符分割
                    $weburl = "http://".$strs[2];

                    $urlstr = '';//定义一个空字符串来存储域名之后的字符串
                    if ($weburl == 'http://www.hbzc777.com' || $weburl == 'http://www.xhbqc.cn' || $weburl=='http://localhost' || $weburl == 'http://127.0.0.1'){
                        for($i = 0; $i<count($strs);$i++){
                            if ($i > 2){
                                //判断截取域名之后的地址
                                $urlstr .= "/".$strs[$i];
                            }

                        }
                    } else {
                        $urlstr = $str;
                    }

                }
                //获取保存路径
                $savepath = $info[0]['savepath'];
                $data['imgtitle'] = I('post.imgtitle');
                $data['imgurl'] =  $savepath . $info[0]['savename'];
                $data['ad_url'] = $urlstr;
                $data['classid'] = I('post.adclass');
                // 记录操作日志
                if ($adinfo->add($data)) {
                    //获取添加成功返回的数据id
                    $returnid = M(self::$table)->order('id desc')->find();
                    $log = self::writeLog(self::$table, $returnid['id'], 'add', Date('Y-m-d H:i:sA'), $auserInfo['name']);
                    if ($log) {
                        $this->success("添加成功!", U('AdManage/Index',1));
                    }
                } else {
                    $this->error("添加失败",'',1);
                }

            }
        }
    }

    //广告信息修改方法 editAdManage()
    public function editAdManage()
    {
        //广告表
        $adinfo = M(self::$table);
        //广告分类表
        $addata = M('ad_class');
        //全局变量
        $isUp = false;
        //文件路径
        $path = "";
        //判断操作数据的id的提交方式并接收
        if (empty($_POST)) {
            $id = $_GET['id'];
            cookie('id', $id);
            $cid = cookie('id');
            $sqlad = "SELECT a.id as id,a.imgtitle,a.imgurl,a.ad_url,b.classname,b.id as bid FROM ad_banner a LEFT JOIN ad_class b ON a.classid=b.id WHERE a.id=%d";
            $arr = [$cid];
            $list = $adinfo->query($sqlad, $arr);
            $this->assign('list', $list);
            cookie('imgname', $list[0]['imgurl']);
            //查询广告分类
            $adclass = $addata->where(array('isdel=0'))->select();
            $this->assign('adclass', $adclass);
            $this->display();
        }
        //保存修改的信息
        if ($_POST) {
            $cid = cookie('id');
            if ($_POST) {
                $rules = array(
                    array('imgtitle', 'require', '<script>alert("图片属性不能为空！");history.back(-1);</script>', 1), //默认情况下用正则进行验证
                    array('adclass', 'require', '<script>alert("广告分类不能为空！");history.back(-1);</script>', 1), //默认情况下用正则进行验证
//                    array('ad_url', 'require', '<script>alert("广告地址不能为空！");history.back(-1);</script>', 1), //默认情况下用正则进行验证
                );
                // 实例化上传类
                $upload = new \Think\Upload();
                // 设置附件上传大小
                $upload->maxSize = 307200;//单位字节
                // 设置附件上传类型
                $upload->exts = array('jpg', 'png', 'jpeg');
                // 设置附件上传目录
                $upload->savePath = '/Uploads/Adimg/';
                //设置子目录
                $upload->autoSub = true;
                //如果存在同名文件是否进行覆盖
                $upload->uploadReplace = true;
                // 上传文件
                $info = $upload->upload();
                // 上传错误提示错误信息
                if ($info) {
                    //通过img下标，得到上传图片字段，通过该字段读取原数据字段的文件路径并删除
                    $selimgurl = "SELECT imgurl FROM ad_banner WHERE id=%d";
                    $arr = [$cid];
                    $path = $adinfo->query($selimgurl, $arr);
                    $isUp = true;
                } else {
                    $isUp = false;

                }
                if (!$adinfo->validate($rules)->create()) {
                    // 如果创建失败 表示验证没有通过 输出错误提示信息
                    exit($adinfo->getError());
                }
                //判断PC广告地址
                if (I('post.ad_url') == '') {
                    $ad_url = '#';
                } else {
                    $ad_url = I('post.ad_url');
                }
                //获取保存路径
                $savepath = $info[0]['savepath'];
                $data = array();
                $data['imgtitle'] = I('post.imgtitle');
                $data['ad_url'] = $ad_url;
                $data['classid'] = I('post.adclass');
                if ($isUp) {
                    //删除原图片
                    unlink('Public/'.$path[0]['imgurl']);
                    $data['imgurl'] = $savepath . $info[0]['savename'];
                } else {
                    $data['imgurl'] = cookie('imgname');
                }
                //记录操作日志
                if ($adinfo->where(array('id=' . $cid))->save($data)) {
                    //接收当前管理员登陆名
                    $auserInfo = UserInfo();
                    $log = self::writeLog(self::$table, $cid, 'edit', Date('Y-m-d H:i:sA'), $auserInfo['name']);
                    if ($log) {
                        cookie('id', null);
                        $this->success("修改成功!", U('AdManage/Index',1));
                    }

                } else {
                    $this->error("修改失败",'',1);
                }

            }
        }

    }

    //广告信息删除方法 del()
    public function del()
    {
        $adinfo = M(self::$table);
        //获取作数据的id
        $id = I('get.id');
        $data['isdel'] = 1;
        $data['imgurl'] = "";
        $adimg = "SELECT imgurl FROM ". self::$table ." WHERE (id=%d)";
        $arr = $id;
        $img = $adinfo->query($adimg,$arr);
        //删除原图片
        unlink('Public/'.$img[0]['imgurl']);
        if ($adinfo->where(array('id=' . $id))->save($data)) {
            // 记录操作日志
            $auserInfo = UserInfo(); //接收当前管理员登陆名
            $log = self::writeLog(self::$table, $id, 'del', Date('Y-m-d H:i:sA'), $auserInfo['name']);
            if ($log) {
                $this->success("删除成功!", U('AdManage/Index',1));
            }
        } else {
            $this->error('删除失败','',1);
        }
    }

    //批量删除
    public function delAll()
    {
        if (!empty($_POST)) {
            $ids = I('post.ids');
            $m = M(self::$table);
            $where['id'] = array('IN', $ids);
            $data['isdel'] = 1;
            $data['imgurl'] = "";
            $arr1 = explode(",",$ids);
            foreach ($arr1 as $key){
                $adimg = "SELECT imgurl FROM ". self::$table ." WHERE (id=%d)";
                $arr = $key;
                $img = $m->query($adimg,$arr);
                //循环删除图片
                unlink('Public/'.$img[0]['imgurl']);
            }
            //执行数据逻辑批量删除
            $res = $m->where($where)->save($data);
            if ($res) {
                $ids = explode(',', $ids);
                foreach ($ids as $id) {
                    self::writeLog(self::$table, $id, 'delAll', date('Y-m-d H:i:sA'), self::cookieName());
                }
                $this->ajaxReturn(array('state' => 1));
            }


        }
    }


}