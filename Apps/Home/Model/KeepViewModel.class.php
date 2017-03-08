<?php
/**
 * 收藏列表模型
 */

namespace Home\Model;
use Think\Model\ViewModel;

Class keepViewModel extends ViewModel{


	Protected $viewFields = array(
		'keep'	  =>array(
			'id' =>'kid','time' =>'ktime',
			'_type' =>'INNER'
			),
		'weibo'   =>array(
			'id','content','isturn','time','turn','keep','comment','uid',
			'_on'     =>'keep.wid = weibo.id',
			'_type'   =>'LEFT'
			),
		'picture' =>array(
			'mini','medium','max',
			'_on'    =>'weibo.id = picture.wid',
			'_type'  =>'LEFT'
			),
		'userinfo'=>array(
			'username','face50'=>'face',
			'_on' => 'weibo.uid = userinfo.uid'
			)
		);


	Public function getAll($where,$limit){
		$result = $this->where($where)->order('ktime DESC')->limit($limit)->select();

		$db = D('Home/WeiboView');		//跨模型调用
		foreach ($result as $k => $v) {
			//如果存在转发则重组数据
			if($v['isturn'] > 0 ){
				$result[$k]['isturn'] = $db->find($v['isturn']);
			}
		}
		return $result;

	}

}