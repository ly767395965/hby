<?php
namespace Home\Controller;

class PHPExcelController extends ContanctController
{
    //在类初始化方法中，引入相关类库
    public function _initialize() {
        vendor('PHPExcel.PHPExcel');
    }
    public function index()
    {

        $dir = dirname(__FILE__);//找到当前脚本所在路径

//        require $dir."/PHPExcel/PHPExcel.php";//引入文件

        //实例化PHPExcel类，等同于在桌面上创建一个excel表格
//        $obj = new \PHPExcel();
        $obj = new \PHPExcel();

        //开始数据分类填充数据
        for ($i = 0;$i<2;$i++){
            if ($i>0){

            }
            if ($i == 0){
                $tabName = '普通用户';
            } else if ($i == 1) {
                $tabName = '大客户';
            }
            $obj->createSheet();//创建新的内置表
            $obj->setActiveSheetIndex($i);//把新创建的sheet设定为当前活动sheet
            $objSheet = $obj->getActiveSheet();//获取当前活动sheet的操作对象
            $objSheet->setTitle($tabName.'用户信息表');//给当前活动sheet设置名称
            $list = $this->getUserData($i);//获取填充数据

            $objSheet->setCellValue('A1','编号')->setCellValue('B1','姓名')->setCellValue('C1','电话')->setCellValue('D1','用户类型')->setCellValue('E1','身份证号')->setCellValue('F1','性别')->setCellValue('G1','注册时间');//给当前活动sheet填充数据

            $j = 2;
            foreach ($list as $k=>$v){
                $objSheet->setCellValue('A'.$j,$v['id'])->setCellValue('B'.$j,$v['username'])->setCellValue('C'.$j,$v['phone'])->setCellValue('D'.$j,$v['usertype'])->setCellValue('E'.$j,$v['identitys'])->setCellValue('F'.$j,$v['sex'])->setCellValue('G'.$j,$v['addtime']);
                $j++;
            }
        }
//end数据分类填充数据




        //开始不分类数据填充
//        $obj->createSheet();//创建新的内置表
//        $obj->setActiveSheetIndex();//把新创建的sheet设定为当前活动sheet
//        $objSheet = $obj->getActiveSheet();//获取当前活动sheet的操作对象
//        $objSheet->setTitle('用户信息表');//给当前活动sheet设置名称
        $list = $this->getUserDataAll();//获取填充数据
//
//        $objSheet->setCellValue('A1','编号')->setCellValue('B1','姓名')->setCellValue('C1','电话')->setCellValue('D1','用户类型')->setCellValue('E1','身份证号')->setCellValue('F1','性别')->setCellValue('G1','注册时间');//给当前活动sheet填充数据
//
//        $j = 2;
//        foreach ($list as $k=>$v){
//            $objSheet->setCellValue('A'.$j,$v['id'])->setCellValue('B'.$j,$v['username'])->setCellValue('C'.$j,$v['phone'])->setCellValue('D'.$j,$v['usertype'])->setCellValue('E'.$j,$v['identitys'])->setCellValue('F'.$j,$v['sex'])->setCellValue('G'.$j,$v['addtime']);
//            $j++;
//        }
        //end不分类数据填充





        //重新填充数据





        $objWriter = \PHPExcel_IOFactory::createWriter($obj,'Excel2007');//按照指定格式生成excel文件


        $objWriter->save('./用户表.xlsx');//保存文件并给文件命名
//        $this->browser_export('Excel5','user.xls');//输出到浏览器 xlsx
//        $objWriter->save('php://output');

        echo '执行成功';

    }

    //分类数据查询
    public function getUserData($type){
        $m = M();
        $sql = "SELECT id,username,phone,usertype,identitys,sex,addtime FROM work_member WHERE usertype = %d";
        $list = $m->query($sql,[$type]);
        for ($i = 0;$i<count($list);$i++){
            if ($list[$i]['usertype'] == 0){
                $list[$i]['usertype'] = '普通用户';
            } else{
                $list[$i]['usertype'] = '大客户';
            }

            if ($list[$i]['sex'] == 1){
                $list[$i]['sex'] = '男';
            } else {
                $list[$i]['sex'] = '女';
            }

        }
        return $list;
    }


    //不分类数据查询
    public function getUserDataAll(){
        $m = M();
        $sql = "SELECT id,username,phone,usertype,identitys,sex,addtime FROM work_member";
        $list = $m->query($sql);
        for ($i = 0;$i<count($list);$i++){
            if ($list[$i]['usertype'] == 0){
                $list[$i]['usertype'] = '普通用户';
            } else{
                $list[$i]['usertype'] = '大客户';
            }

            if ($list[$i]['sex'] == 1){
                $list[$i]['sex'] = '男';
            } else {
                $list[$i]['sex'] = '女';
            }

        }
        return $list;
    }





    public function browser_export($type,$filename){

        if ($type == 'Excel5'){
            header('Content-Type: application/vnd.ms-excel');//告诉浏览器将要输出excel03文件
        } else {
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');//告诉浏览器将要输出excel07文件
        }
        header('Content-Disposition: attachment;filename="'.$filename.'"');//告诉浏览器将要输出文件的名称
        header('Cache-Control: max-age=0');//禁止缓存
    }


    /**
     * 根据下标获取单元所在列位置
     */
    public function getCells($index){
        $arr = range('A','Z');
        return $arr[$index];

    }


}
