<?php
namespace Home\Model;
use Think\Model\ViewModel;

/**
 * 评论视图模型
 */
Class CommentViewModel extends ViewModel{

	/**
	 * 一般多表查询会用视图模型
	 * $viewFields 属性表示视图模型包含的字段
	 * comment和userinfo是表名，里面相应是其需要的字段
	 * _type LEFF(对下一个表)是JOIN类型定义  _on 关联关系 一般是两个表的外键关联 
	 */
	Protected $viewFields = array(
		'comment'  => array(
			'id','content','time','wid',
			'_type' =>'LEFT'
			),
		'userinfo' => array(
			'username','face50' => 'face' ,'uid',
			'_on' =>'comment.uid = userinfo.uid'
			)
		);


}

?>