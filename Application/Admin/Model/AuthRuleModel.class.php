<?php
namespace Admin\Model;
use Think\Model;
use Think\Model\RelationModel;
class AuthRuleModel extends RelationModel {
	//数据验证
	protected $_validate = array(
	);
	//关联查询
	protected $_link = array(
         'childNode'  =>  array(
             'mapping_type' => self::HAS_MANY,
             'class_name'   => 'AuthRule',
			 'parent_key'  => 'pid',
			 'mapping_order' => 'id'
         ),
    );
}
?>