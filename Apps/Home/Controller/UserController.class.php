<?php
namespace Home\Controller;
use Think\Controller;

class UserController extends CommonController {

	/**
	 * 用户个人视图
	 */
	Public function index(){
		if(isset($_GET['username'])){
			$username = I('get.username');
			$where = array('username' => $username);
		} else {
			$id    = I('get.id','');		//用户ID
			$where = array('uid' => $id);
		}
		
		$userinfo = M('userinfo')->where($where)->field('truename,face50,face80,style',true)
								 ->find();
		$id = $userinfo['id'];

		if(!$userinfo){
			header('Content-Type:text/html;Charset=UTF-8');
			redirect(__ROOT__,3,'用户不存在,正在为您跳转至首页...');
			exit();
		}

		$this->userinfo = $userinfo;
		
		//读取用户发布的微博
		$where = array('uid'=>$id);
		$count = M('weibo')->where($where)->count();
		$page  = new \Think\Page($count,4);
		$limit = $page->firstRow . ',' . $page->listRows;

		$this->weibo = D('WeiboView')->getAll($where,$limit);
		$this->page  = $page->show();

		//获得我的关注
		if(S('follow_'.$id)){
			//缓存成功
			$follow = S('follow_' . $id);
		} else {
			$where  = array('fans' =>$id);
			$follow = M('follow')->where($where)->field('follow')->select();
			foreach($follow as $k =>$v){
				$follow[$k] = $v['follow'];
			}
			if($follow == ""){
			
			} else {
				$where = array('uid' => array('IN',$follow));
				$field = array('username','face50'=>'face','uid');
				$follow = M('userinfo')->field($field)->where($where)->limit(8)->select();
				S('follow_'.$id , $follow , 3600);
			}

		}

		//我的粉丝
		if(S('fans'.$id)){
			//缓存成功
			$follow = S('fans_' . $id);
		} else {
			$where  = array('follow' =>$id);
			$fans = M('follow')->where($where)->field('fans')->select();
			foreach($fans as $k =>$v){
				$fans[$k] = $v['fans'];
			}
			if($fans == ""){
			
			}else {
				$where = array('uid' => array('IN',$fans));
				$field = array('username','face50'=>'face','uid');
				$fans = M('userinfo')->field($field)->where($where)->limit(8)->select();
				S('fans_'.$id , $fans , 3600);
			}
		}
		
		$this->fans   = $fans;
		$this->follow = $follow;
		$this->display();
	}

	/**
	 * 用户关注与粉丝列表
	 */
	Public function followList(){
		$db   = M('follow');
		$uid  = I('get.uid','','int');
		$type = I('get.type','','int');
		//区分 type: 1:关注 0:粉丝
		$where = $type ? array('fans' => $uid) : array('follow' => $uid);
		$field = $type ? 'follow' : 'fans';
		$count = $db->where($where)->count();

		$page  = new \Think\Page($count,20);
		$limit = $page->firstRow . ',' . $page->listRows;

		$uids  = $db->field($field)->where($where)->limit($limit)->select();
		if($uids){
			//重新组合为一维数组
			foreach ($uids as $k => $v) {
				$uids[$k] = $type ? $v['follow'] : $v['fans'];
			}
			$where = array('uid' =>array('IN',$uids));
			$field = array('face50' =>'face','username','sex','location','follow','fans','weibo','uid');
			//取得关注/粉丝数据
			$users = M('userinfo')->where($where)->field($field)->select();

			$this->users = $users;
		}

		$where = array('fans' =>session('uid'));
		$follow = $db->field('follow')->where($where)->select();

		if($follow){
			foreach ($follow as $k => $v) {
				$follow[$k] = $v['follow'];
			}
		}

		$where = array('follow'=>session('uid'));
		$fans  = $db->field('fans')->where($where)->select();
		if($fans){
			foreach ($fans as $k => $v) {
				$fans[$k] = $v['fans'];
			}


		}

		$this->follow = $follow;
		$this->fans   = $fans;
		$this->type   = $type;
		$this->count  = $count;
		$this->display();
	}

	/**
	 * 收藏列表
	 */
	Public function Keep(){
		$uid   = session('uid');
		$count = M('keep')->where(array('uid'=>$uid))->count();
		$page  = new \Think\Page($count,20);
		$limit = $page->firstRow . ',' . $page->listRows;
		$where = array('keep.uid' =>$uid);
		$weibo = D('KeepView')->getAll($where,$limit);
		$this->weibo = $weibo;
		$this->page  = $page->show();
		$this->display('weiboList');
	}

	/**
	 * 异步取消收藏
	 */
	Public function cancelKeep(){
		if(!IS_AJAX){
			halt('页面不存在');
		}
		$kid = I('post.kid','','int');
		$wid = I('post.wid','','int');

		if(M('keep')->delete($kid)){
			M('weibo')->where(array('id' =>$wid))->setDec('keep');
			echo 1;
		} else {
			echo 0;
		}
	}

	/**
	 * 私信列表
	 */
	Public function letter(){
		
		$uid   = session('uid');
		set_msg($uid,2,true);

		$count = M('letter')->where(array('uid' =>$uid))->count();
		$page  = new \Think\Page($count,20);
		$limit = $page->firstRow . ',' . $page->listRows;

		$where  = array('letter.uid' =>$uid);
		$letter = D('LetterView')->where($where)->order('time DESC')
								 ->limit($limit)->select();

		$fans = M('follow')->where(array('follow' => $uid))->select();
		foreach ($fans as $key => $value) {
			$fans[$key] = M('userinfo')->where(array('id' => $value['fans']))->getField('username');
		}

		$this->fans   = $fans;
		$this->letter = $letter;
		$this->count  = $count;
		$this->page   = $page->show();

		$this->display();
	}

	/**
	 * 私信发送表单处理
	 */
	Public function letterSend(){
		if(!IS_POST){
			halt('页面不存在');
		}
		$name  = I('post.name');
		$where = array('username' =>$name);
		$uid   = M('userinfo')->where($where)->getField('uid');
		if(!$uid){
			$this->error('用户不存在');
		}

		$data = array(
			'from'     => session('uid'),
			'content'  => I('post.content'),
			'time'	   => time(),
			'uid'	   => $uid
			);

		if(M('letter')->data($data)->add()){

			set_msg($uid,2);
			$this->success('私信已发送',U('letter'));
		} else {
			$this->error('发送失败请重试...');
		}

	}

	/**
	 * 异步删除私信
	 */
	Public function delLetter(){
		if(!IS_AJAX){
			halt('页面不存在');
		}
		$lid = I('post.lid','','int');

		if(M('letter')->delete($lid)){
			echo 1;
		} else {
			echo 0;
		}
	}

	/**
	 * 评论功能
	 */
	Public function comment(){
		set_msg(session('uid') , 1 , true);
		$page  = new \Think\Page($count,20);
		$where['uid'] = array('EQ', session('uid'));
		$where['pid'] = array('EQ', session('uid'));
		$where['_logic'] = 'or';

		$count = M('comment')->where($where)->count();
		$limit = $page->firstRow . ',' . $page->listRows;

		$comment = D('CommentView')->where($where)->limit($limit)
								   ->order('time DESC')
								   ->select();

		$this->count = $count;
		$this->page  = $page->show();
		$this->comment = $comment;

		$this->display();
	}

	/**
	 * 评论回复
	 */
	Public function reply(){
		if(!IS_AJAX){
			halt('页面不存在');	
		}

		$parent_uid = M('weibo')->where(array('id' => I('post.wid','','int')))->getField('uid');

		$data = array(
			'content' => I('post.content'),
			'time'	  => time(),
			'pid'	  => $parent_uid,
			'uid'	  => session('uid'),
			'wid'     => I('post.wid','','int')
			);
		if(M('comment')->data($data)->add()){
			M('weibo')->where(array('id'=>$wid))->setInc('comment');
			echo 1;
		} else {
			echo 0;
		}
	}

	/**
	 * 删除评论
	 */
	Public function delComment(){
		if(!IS_AJAX){
			halt('页面不存在');
		}
		$cid = I('post.cid','','int');
		$wid = I('post.wid','','int');

		if(M('comment')->delete($cid)){
			M('weibo')->where(array('id'=>$wid))->setDec('comment');
			echo 1;
		} else {
			echo 0;
		}
	}

	/**
	 * @提到我的
	 */
	Public function atme(){
		set_msg(session('uid'),3,true);
		$where = array('uid' =>session('uid'));
		$wid   = M('atme')->where($where)->field('wid')->select();
		if($wid){
			foreach ($wid as $k => $v) {
				$wid[$k] = $v['wid'];
			}
		}
		if($wid == ""){
		
		} else {
			$count = count($wid);
			$page  = new \Think\Page($count,20);
			$limit = $page->firstRow . ',' . $page->listRows;

			$where = array('id'=>array('IN',$wid));
			$weibo = D('WeiboView')->getAll($where,$limit);
			$this->weibo = $weibo;
			$this->page  = $page->show();
		}
		$this->atme  = 1;
		$this->display('weiboList');
	}


	/**
	 * 空操作
	 */
	Public function _empty($name){
		$this->_getUrl($name);
	}

	/**
	 * 处理用户名空操作、获得用户ID、跳转至用户个人页
	 */
	Private function _getUrl($name){
		$name = htmlspecialchars($name);
		$where = array('username' => $name);
		//获取该@的用户ID
		$uid = M('userinfo') ->where($where) ->getField('uid');

		if(!$uid){
			redirect(U('Index/index'));
		} else {
			redirect(U('index',array('id' =>$uid)));
		}
	}
	
}


?>