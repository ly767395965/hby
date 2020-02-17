<?php
namespace Admin\Controller;
use Think\Controller;
class CommonController extends Controller {
	
	//获取数据列表
	/*
	* model 模块名称,默认访问的模块名
	* map查询条件，条件数组
	* paging是否开启分页，默认不开启
	* relation是否开启关联查询，默认不开启
	* asc是否顺序排列，默认倒序
	* order排序字段，默认为主键
	* pageNumber每页显示条数，默认20条
	*/
	/*protected function _list($parms){
		$map = $parms['map']?$parms['map']:array();
		$paging = $parms['paging']?$parms['paging']:false;
		$relation = $parms['relation']?$parms['relation']:false;
		$asc = $parms['asc']?$parms['asc']:false;
		$order=$parms['order']?$parms['order']:'';
		$pageNumber=$parms['pageNumber']?$parms['pageNumber']:'10';
		
		$model = $parms['model']?$parms['model']:D(CONTROLLER_NAME);
		$data=$model->create($map);
		//排序字段 默认为主键名
		if (isset($_REQUEST ['order'])) {
			$order = $_REQUEST ['order'];
		}
		if($order=='') {
			$order = $model->getPk();

		}
		if (isset($_REQUEST ['sort'])) {
			$sort = $_REQUEST ['sort'];
		}
		if($sort=='') {
			$sort = $asc ? 'asc' : 'desc';

		}
		if($paging){
			if (isset($_REQUEST ['pageNumber'])) {
				$pageNumber = $_REQUEST ['pageNumber'];
			}
			if($pageNumber=='') {
				$pageNumber = $pageNumber ? $pageNumber : '20';
			}
			$PageIndex = $_REQUEST["page"];
			$page['pageNumber'] = $pageNumber;
			if(!eregi("^[0-9]+$",$PageIndex) || !$PageIndex){
				$PageIndex = 1;
			}
			$dataSum=$relation?$model->relation(true)->where($map)->count():$model->where($map)->count();
			$page["dataSum"]=$dataSum;
			$pageSum = $dataSum / $page['pageNumber'];
			if(($dataSum % $page['pageNumber']) != 0){$pageSum = ceil($pageSum);}
			$page["pageSum"]=$pageSum;
			if($PageIndex > $pageSum){
				$PageIndex = $pageSum;
			}
			$page["pageIndex"]=$PageIndex;
			
			$list = $relation?$model->relation(true)->where($map)->page($PageIndex,$page['pageNumber'])->order("`" . $order . "` " . $sort)->select():$model->where($map)->page($PageIndex,$page['pageNumber'])->order("`" . $order . "` " . $sort)->select();
			$GETDATA = $_GET;
			unset($GETDATA['page']);
			$silfURL = U(ACTION_NAME, $GETDATA);
			$this->assign('silfURL',$silfURL);
			$this->assign('page',$page);
		}else{
			$list = $relation?$model->relation(true)->where($map)->order("`" . $order . "` " . $sort)->select():$model->where($map)->order("`" . $order . "` " . $sort)->select();
		}
		$this->assign("paging", $paging);
		$this->assign("map", $map);
		$this->assign('sort', $sort);
		$this->assign('order', $order);
		$this->assign('list',$list);
		return $list;
	}*/
	//添加
	
	//修改
	/*public function edit(){
		$model = D(CONTROLLER_NAME);
		$pk = $model->getPk();
		$id = $_REQUEST ['id'];
		if(is_int($id) || $id < 1){
			$this->ajaxReturn(array(
				'state'=>2,
				'msg'=>'参数错误，非法调用！',
			));
		}
		if(!$model->where(array($pk=>$id))->count()){
			$this->ajaxReturn(array(
				'state'=>2,
				'msg'=>'数据不存在！',
			));
		}
		$vo = $model->where(array($model->getPk()=>$id))->find();
		if(method_exists($this,'edit_before')){
			$this->edit_before($vo);
		}
		if(method_exists($this,'edit_after')){
			$this->edit_after($vo);
		}else{
			$this->assign('vo', $vo);
		}
		$this->display();
	}*/
	//查看
	public function show(){
		$this->display();
	}
	//插入数据
	public function ajax_insert(){
	$model = D(CONTROLLER_NAME);
	if(method_exists($this,'ajax_insert_before')){
		$this->ajax_insert_before();
	}
	if (!$model->create($_POST,1)){
		$this->ajaxReturn(array('state'=>2,'msg'=>$model->getError()));
	}else{
		if($id = $model->add()){
			if(method_exists($this,'ajax_insert_after')){
				$this->ajax_insert_after($id);
			}
			$this->ajaxReturn(array('state'=>1,'msg'=>'添加成功','href'=>U(CONTROLLER_NAME.'/Index')));
		}else{
			$this->ajaxReturn(array('state'=>2,'msg'=>'添加失败'));
		}
	}
}
	//修改数据
	/*public function ajax_update(){
		$id = I('get.id');		
		$model = D(CONTROLLER_NAME);
		$pk = $model->getPk();
		if(is_int($id) || $id < 1){
			$this->ajaxReturn(array('state'=>2,'msg'=>'参数错误，非法调用！'));
		}
		if(!$model->where(array($pk=>$id))->count()){
			$this->ajaxReturn(array('state'=>3,'msg'=>'数据不存在！'));
		}
		if(method_exists($this,'ajax_update_before')){
			 $editsuccess = $this->ajax_update_before();
		}
		if (!$model->create($_POST,2)){
			$this->ajaxReturn(array('state'=>2,'msg'=>$model->getError()));
		}else{
			if(!$model->where(array($pk=>$id))->save() && !$editsuccess){
				$this->ajaxReturn(array('state'=>2,'msg'=>'数据更新失败'));
			}
			if(method_exists($this,'ajax_update_after')){
				$this->ajax_update_after();
			}
			$this->ajaxReturn(array('state'=>1,'msg'=>'更新成功！','href'=>U(CONTROLLER_NAME.'/Index')));
		}
	}*/
	//删除数据
	/*public function ajax_delete(){
		$id = I('get.id');	
		$model = D(CONTROLLER_NAME);
		$pk = $model->getPk();
		if(method_exists($this,'ajax_delete_before')){
			$this->ajax_delete_before();
		}
		if(is_int($id) || $id < 1){
			$this->ajaxReturn(array(
				'state'=>2,
				'msg'=>'参数错误，非法调用！',
			));
		}
		if(!$model->where(array($pk=>$id))->count()){
			$this->ajaxReturn(array(
				'state'=>2,
				'msg'=>'数据不存在！',
			));
		}
		if($model->where(array($pk=>$id))->delete()){
			if(method_exists($this,'ajax_delete_after')){
				$this->ajax_delete_after();
			}
			$this->ajaxReturn(array(
				'state'=>1,
				'msg'=>'删除成功！',
				'href'=>U(CONTROLLER_NAME.'/Index'),
			));
		}else{
			$this->ajaxReturn(array(
				'state'=>2,
				'msg'=>'删除失败！',
			));
		}
	}*/
	/*public function getChildList($id=0,$tableName='',$key='pid'){
		if(IS_AJAX){
			$id=I('get.id');
			$tableName=I('get.tableName');
			$key=I('get.key');
		}
		$model = ($tableName=='' || $tableName=='undefined')?D(CONTROLLER_NAME):D($tableName);
		$key = ($key=='' || $key=='undefined')?'pid':$key;
		if(is_int($id)){
			$this->ajaxReturn(array('state'=>3,'msg'=>'非法访问！'));
		}
		$list = $model->where(array($key=>$id))->select();
		if(count($list) > 0){
			$this->ajaxReturn(array('state'=>1,'msg'=>'列表获取成功！','list'=>json_encode($list)));
		}else{
			$this->ajaxReturn(array('state'=>2,'msg'=>'列表获取失败！'));
		}
	}*/
	/*public function upload(){
		$upload = new \Think\Upload();// 实例化上传类
		$upload->maxSize   =     3145728 ;// 设置附件上传大小
		$upload->exts      =     array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
		$upload->rootPath  =     './Public/Uploads/'; // 设置附件上传根目录
		// 上传文件 
		$info   =   $upload->upload();
		if(!$info) {// 上传错误提示错误信息
			$this->ajaxReturn(array('status'=>'101','msg'=>$upload->getError()));
		}else{// 上传成功
			foreach($info as $k=>$file){
				$info[$k]['imgurl'] = '/Public/Uploads/'.$file['savepath'].$file['savename'];
			}
			$this->ajaxReturn(array('status'=>'100','msg'=>'上传成功!','data'=>$info));
		}
	}*/
	
	/*public function upload_b64(){
		exit();
		header('Access-Control-Allow-Headers: X-Requested-With,X_Requested_With'); //设置允许的跨域header
		date_default_timezone_set("Asia/chongqing");
		error_reporting(E_ERROR);
		header("Content-Type: text/html; charset=utf-8");
        $config = array(
            "pathFormat" => '/Public/Uploads/'.date('Ymd').'/',
            "maxSize" => 3145728,
            "oriName" => "scrawl.png"
        );
		$file = new Uploader('upfile',$config,'base64');
		$info = $file->getFileInfo();
		$this->ajaxReturn(array('status'=>'100','msg'=>'上传成功!','data'=>$info));
 	 }*/
    /**
	 *$sql:查询条件
	 * $pageNum 没页显示几条
	 * $name 打印到页面的参数
	 */
	function pageDisPlay($sql,$pageNum,$name,$ary) {
		$m = M();
		$list = $m->execute($sql,$ary);
		$count = $list;// 查询满足要求的总记录数
		$page = new \Think\Page($count,$pageNum);// 实例化分页类 传入总记录数和每页显示的记录数(25)
		$show = $page->show();// 分页显示输出// 进行分页数据查询 注意limit方法的参数要使用Page类的属性
		$sql = $sql . " LIMIT $page->firstRow,$page->listRows";
		$list = $m->query($sql,$ary);
		$this->assign($name,$list);// 赋值数据集
		$this->assign('page',$show);// 赋值分页输出$this->display(); // 输出模板
		if($list) {
			$this->ajaxReturn(array('state' => 1, 'msg' => '增加成功！', 'href' => U(CONTROLLER_NAME . '/Index')));
		}else{

			$this->ajaxReturn(array('state'=>5, 'msg'=>'增加失败！'));
		}
		$this->display();
	}
	/*
	 * 修改数据
	 *
	 *
	 * */

	
}