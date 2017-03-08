<?php
/**
 * 用户设置控制器
 */
namespace Home\Controller;
use Think\Controller;

class UserSettingController extends CommonController {
	/**
	 * 用户基本信息视图
	 */
	Public function index(){

		$User = M("userinfo"); 
		$where = array('uid' => session('uid'));
		$field = array('username','truename','sex','location','constellation','intro','face180');
		$Us_msg = $User->where($where)->field($field)->find();
		$this ->assign('user',$Us_msg);
		$this -> display();
	}
	/**
	 * 修改用户基本信息
	 */
	Public function editBasic(){
		if(!IS_POST){
			error('页面不存在','');
		}
		header("Content-type:text/html;charset=utf-8");
		$data = array(
				'username'	=>	I('post.nickname'),
				'truename'	=>	I('post.truename'),
				'sex'		=>	I('post.sex'),
				'location'	=>	I('post.province')." ".I('post.city'),
				'constellation'	=>	I('post.night'),
				'intro'		=>	I('post.intro')	
			);
		$where = array('uid' => session('uid'));
		$User = D('userinfo');	
		if($User -> where($where) ->save($data)){
			$this -> success('修改成功','');
		} else {
			$this -> error('修改失败','');
		}
		// dump($_POST);
	}

	/**
	*修改密码
	*/
	Public function editPwd(){
		
		if(!IS_POST){
			$this ->error('页面不存在');
		}


		$db = M('user');
		$where = array('id' => session('uid'));
		$old   =  $db -> field('password') -> where($where) ->find();
		if($old['password'] != I('post.old','','md5')){
			$this ->error('密码错误');
		}	
		if($_POST['new'] != $_POST['newed']){
			$this ->error('请确保两次密码一致','');
		}

		$newPwd = I('post.new','','md5');
		$data   = array('password' => $newPwd);
		if($db -> where($where) -> save($data)){
			$this ->success('修改成功','');
		} else {
			$this ->error('修改失败','');
		}
	}
}







?>