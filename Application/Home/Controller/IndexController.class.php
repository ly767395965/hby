<?php
namespace Home\Controller;

use Common\Common\BaseHomeController;


class IndexController extends BaseHomeController
{
    protected static $table = 'car_carmodel';

    function logger($log_content)
    {
        $max_size = 100000;
        $log_filename = "raw.log";
        if(file_exists($log_filename) and (abs(filesize($log_filename)) > $max_size)){unlink($log_filename);}
        file_put_contents($log_filename, date('H:i:s')." ".$log_content."\r\n", FILE_APPEND);
    }

    public function index()
    {
        $model = M();
        //热门车型查询
//        $sql = "SELECT id,frontimg, carmodelname,displacement,carmodeltype,configstyle,agestyle,bearboxtype,sitecount,shortdayprice,weekdayprice,monthdayPrice FROM car_carmodel WHERE (isrecommend=1) AND (isdel=0) LIMIT 0,12";
//        $list = $model->query($sql);
//        $this->assign('list',$list);

        //站点名称
        $systemtitle = $this->webInfo();
        $this->assign('sys_title',$systemtitle);
        //首页广告轮播
        $sqlad = "SELECT imgtitle,imgurl,ad_url FROM ad_banner WHERE (isdel=0) AND (classid=1)";
        $adlist = $model->query($sqlad);
        $this->assign('adlist',$adlist);
        //旅游信息
        $tourismSel = "SELECT id,title,subtitle,content,cover,describes FROM new_aticle WHERE (isdel=0) AND (catid=3) ORDER BY id DESC LIMIT 0,4";
        $tourismlist = $model->query($tourismSel);
        foreach ($tourismlist as $key =>$data){
            //判断图片文件是否存在
            if (file_exists("./Public".$tourismlist[0]['cover'])){
//            检测图片文件是否可读
                if (is_readable("./Public".$tourismlist[0]['cover'])){
                    $cover = $tourismlist[0]['cover'];
                } else {
                    $cover = '';
                }

            } else {
                $cover = '';
            }
            $tourismlist[0]['cover'] = $cover;
        }

        $this->assign('tourism',$tourismlist);

        //优惠信息
        $sqlpublish_event = "SELECT id,theme,cover FROM publish_event WHERE (is_del=0) ORDER BY id DESC LIMIT 0,4";
        $listActivity = $model->query($sqlpublish_event);
//判断图片文件是否存在
        if (file_exists("./Public".$listActivity[0]['cover'])){
//            检测图片文件是否可读
            if (is_readable("./Public".$listActivity[0]['cover'])){
                $cover = $listActivity[0]['cover'];
            } else {
                $cover = '';
            }

        } else {
            $cover = '';
        }
        $listActivity[0]['cover'] = $cover;
        $this->assign('listActivity',$listActivity);

        //华邦快讯
        $sqlnews = "SELECT id,title,describes,addtime FROM new_aticle WHERE (isdel=0) AND (catid=1) ORDER BY id DESC LIMIT 0,8";
        $listnews = $model->query($sqlnews);
        $this->assign('listnews',$listnews);
        //租车须知
        $sqltitle = "SELECT id,title,describes FROM  new_aticle WHERE (isdel=0) AND (catid=10) ORDER BY id DESC LIMIT 0,3";
        $catlist = $model->query($sqltitle);
        $this->assign('catlist',$catlist);

        //底部广告
        $footad = "SELECT imgtitle,imgurl,ad_url FROM ad_banner WHERE (isdel=0) AND (classid=2)";
        $listfootad = $model->query($footad);
        $this->assign('footad',$listfootad);
        //底部友情链接
        $sql = "SELECT linkname,linkurl,isdel FROM link_links WHERE (isdel=0) ORDER BY ID DESC";
        $linkList = $model->query($sql);
        $this->assign('linkList',$linkList);

        $this->display();
    }
    //站点信息
    public function webInfo() {
        $web = M();
        $sitetitle = "SELECT title,keywords,describes,company,domian,author,phone,record,email,address,`public`,company FROM system_site";
        $systemtitle = $web->query($sitetitle);
        cookie('phone',$systemtitle[0]['phone']);
        cookie('record',$systemtitle[0]['record']);
        cookie('email',$systemtitle[0]['email']);
        cookie('address',$systemtitle[0]['address']);
        cookie('public',$systemtitle[0]['public']);
        cookie('title',$systemtitle[0]['title']);
        cookie('domian',$systemtitle[0]['domian']);
        cookie('company',$systemtitle[0]['company']);
        return $systemtitle;

    }



}