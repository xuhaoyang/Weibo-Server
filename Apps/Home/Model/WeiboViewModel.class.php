<?php

namespace Home\Model;
use Think\Model\ViewModel;

/**
 * 读取微博视图模型
 */
class WeiboViewModel extends ViewModel {

	protected $autoCheckFields =false;				//关闭虚拟模型

	Protected $viewFields =array(
		'weibo'     =>array(
			'id','content','isturn','time','turn','keep','comment','uid',
			'_type'  =>'LEFT'
			),
		'userinfo'  =>array(
			'username','face50' =>'face',
			'_on'   =>'weibo.uid = userinfo.uid',
			'_type' =>'LEFT'
			),
		'picture'  =>array(
			'mini','medium','max',
			'_on'  =>'weibo.id = picture.wid'
			),
		);

	/**
	 * 返回查询所有记录
	 * @param  [type] $where [description]
	 * @return [type]        [description]
	 */
	Public function getAll($where,$limit){
		$result = $this->where($where)->limit($limit)->order('time desc')->select();
		
		//重组数组集数组，得到转发微博
		if($result){
			foreach ($result as $k => $v) {
				//判断是否存在转发 不存在(已删除)赋为-1
				if($v['isturn']){
					$tmp = $this->find($v['isturn']);
					$result[$k]['isturn'] = $tmp ? $tmp : -1;
				}
			}
		}
		return $result;
	}
}


?>