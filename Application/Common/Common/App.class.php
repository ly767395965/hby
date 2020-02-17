<?php
namespace Common\Common;

use Think\Page;

class App
{

    private function __construct()
    {
//        parent::__construct();
    }


    private static $_instance;

    public function __clone()
    {
        trigger_error('Clone is not allow!', E_USER_ERROR);
    }

    public static function getInstance()
    {
        if (!(self::$_instance instanceof self)) {
            self::$_instance = new self;
        }
        return self::$_instance;
    }

    /**
     * pageDisplay 分页显示方法，
     * @param string $sql SQL查询语句
     * @param string $countSql 获取分页数据总条数 select count(id) from 表名 where 条件
     * @param string $pageNum 每页显示几条数据
     * @param array $ary 参数数组，用于条件查询参数传递，防止SQL注入的预处理
     * @param string $keyName 读取总记录条数的参数 要与countSql中的count(id)语句一样
     * @param string $listName 模板name名，传递数据到模板关联
     * @param string $showPage 模板分页绑定的输出参数，显示分页导航
     * @param bool $isPage 分页开关，True显示分页，False不显示分页
     * @return void
     */
    function pageDisplay($sql, $countSql, $pageNum, $ary, $keyName, $isPage)
    {

        $m = M();
        $countList = $m->query($countSql, $ary);
        $count = $countList[0][$keyName];
        if ($isPage == true) {
            $page = new Page($count, $pageNum);
            $sql = $sql . " LIMIT %d,%d";
            array_push($ary,$page->firstRow,$page->listRows);
            $list = $m->query($sql, $ary);
        } else {
            $list = $m->query($sql, $ary);
        }

        return $list;


    }



}
