<?php
/**
 * 公共方法模型
 */
namespace Home\Controller;
use Think\Controller;

class CommonController extends Controller {

	/**
	 * 自动运行的方法
	 */
	Public function _initialize() {
			//处理自动登陆
			if(isset($_COOKIE['auto']) && session('?uid')){
				$value = explode('|', encryption($_COOKIE['auto'], 1 ));
				$ip = get_client_ip();
			}

			//判断登陆ip是否与现在相同
			if($ip==$value[1]){
				$account = $value[0];	//用户名
				$where = array('account' => $account);
				$User = D('user') -> where($where) -> field('id,lock') ->find();

				//若该用户未锁定则session存储用户id
				if($User && !$User['lock']){
					session('uid',$User['id']);
				}
			}

			//判断用户是否已登陆
			if(!session('?uid'))
				   $this->redirect('Login/login');
		
		
	}

	/**
	*异步创建新分组
	*/
	Public function addGroup(){
		if(!IS_AJAX){
			halt('页面不存在');
		}
		$data = array(
			'name' => I('post.name'),
			'uid'  =>session('uid')
			);
		if(M('group')->data($data)->add()){
			echo json_encode(array('status' => 1 , 'msg' => '创建成功'));
		} else {
			echo json_encode(array('status' => 0 , 'msg' => '创建失败，请重试...'));
		}
	}

	/**
	*异步添加关注
	*/
	Public function addFollow(){
		// if(!IS_AJAX){
		// 	half('页面不存在');
		// }
		$data = array(
			'follow' =>I('post.follow' , '' , 'intval'),
			'fans'   =>(int)session('uid'),
			'gid'	 =>I('gid','','intval')
			);
		if(M('follow') ->data($data) ->add()){
			$db = M('userinfo');
			$db ->where(array('uid' =>$data['follow'])) ->setInc('fans');
			$db ->where(array('uid' =>session('uid')))  ->setInc('follow');
			echo json_encode(array('status' => 1 , 'msg' => '关注成功'));
		} else {
			echo json_encode(array('status' => 0 , 'msg' => '关注失败请重试...'));
		}
	}

	/**
	 * 异步移除关注与粉丝
	 */
	Public function delFollow(){
		if(!IS_AJAX){
			half('页面不存在');
		}
		$uid  = I('post.uid','','int');
		$type = I('post.type','','int');

		$where = $type ? array('follow' =>$uid,'fans'=>session('uid'))
					   : array('fans'   =>$uid,'follow'=>session('uid'));

		if(M('follow')->where($where)->delete()){
			$db = M('userinfo');
			if($type){
				$db->where(array('uid'=>session('uid')))->setDec('follow');
				$db->where(array('uid'=>$uid))->setDec('fans');
			} else {
				$db->where(array('uid'=>session('uid')))->setDec('fans');
				$db->where(array('uid'=>$uid))->setDec('follow');
			}
			echo 1;
		} else {
			echo 0;
		}

	}

	/**
	*头像上传
	*/
	Public function uploadFace(){
		if(!IS_POST){
			half('页面不存在');
		}
		$upload = $this->_upload('Face', '180,80,50', '180,80,50');
		echo json_encode($upload);
	}

	/**
	*微博图片上传
	**/
	Public function uploadPic(){
		if(!IS_POST){
			half('页面不存在');
		}
		$upload = $this->_upload('Pic' ,'800,380,120','800,380,120');
		echo json_encode($upload);
	}

	/**
	 * 异步修改模板风格
	 */
	Public function editStyle(){
		if(!IS_AJAX){
			halt('页面不存在');
		}
		$style = I('post.style');
		$where = array('uid' =>session('uid'));
		if(M('userinfo')->where($where)->save(array('style'=>$style))){
			echo 1;
		} else {
			echo 0;
		}
	}

	/**
	 * 异步轮询推送消息
	 */
	Public function getMsg(){
		if(!IS_AJAX){
			halt('页面不存在');
		}
		$uid = session('uid');
		$msg =  S('usermsg' . $uid);
		if($msg){
			if($msg['comment']['status']){
				$msg['comment']['status'] = 0;
				S('usermsg' .$uid,$msg,0);
				echo json_encode(array(
					'status'  =>1,
					'total'   =>$msg['comment']['total'],
					'type'	  =>1
					));
				exit();
			}
			if($msg['letter']['status']){
				$msg['letter']['status'] = 0;
				S('usermsg' .$uid,$msg,0);
				echo json_encode(array(
					'status'  =>1,
					'total'   =>$msg['letter']['total'],
					'type'	  =>2
					));
				exit();
			}
			if($msg['atme']['status']){
				$msg['atme']['status'] = 0;
				S('usermsg' .$uid,$msg,0);
				echo json_encode(array(
					'status'  =>1,
					'total'   =>$msg['atme']['total'],
					'type'	  =>3
					));
				exit();
			}
		} 
		echo json_encode(array('status' =>0));
	}

	
	/**
	*@param  图片上传处理
	*@param  string $path 		保存文件夹名称
	*@param  string $height     缩略图高度多个用逗号分隔
	*@return array      图片上传信息
	*/
	Public function _upload($path , $width , $height){
		
		//由于3.2.3不支持文件缩略图，故引用了旧版本文件
		$obj = new \Think\UploadFile();								//实例化上传类		
		$obj->maxSize = C('UPLOAD_MAX_SIZE');						//图片最大上传大小
		$obj->savePath = C('UPLOAD_PATH') . $path . '/';	//图片保存路径
		$obj->saveRule = 'uniqid';	//保存文件名
		$obj->uploadReplace = true;	//覆盖同名文件
		$obj->allowExts = C('UPLOAD_EXTS');	//允许上传文件的后缀名
		$obj->thumb = true;	//生成缩略图
		$obj->thumbMaxWidth = $width;	//缩略图宽度
		$obj->thumbMaxHeight = $height;	//缩略图高度
		$obj->thumbPrefix = 'max_,medium_,mini_';	//缩略图后缀名
		$obj->thumbPath = $obj->savePath . date('Y_m') . '/';	//缩略图保存图径
		$obj->thumbRemoveOrigin = true;	//删除原图
		$obj->autoSub = true;	//使用子目录保存文件
		$obj->subType = 'date';	//使用日期为子目录名称
		$obj->dateFormat = 'Y_m';	//使用 年_月 形式

		if(!$obj->upload()) {
			return array('status' => 0 , 'msg' => $obj->getErrorMsg());
		} else {
			$info = $obj ->getUploadFileInfo();
			$pic  = explode('/', $info[0]['savename']);
			return array(
					'status' => 1,
					'path'	 => array(
							'max' 	 => $pic[0] . '/max_' . $pic[1],
							'medium' => $pic[0] . '/medium_' . $pic[1],
							'mini'   => $pic[0] . '/mini_' . $pic[1]
						)
				);
		}

	}

	Public function editFace(){
		if(!IS_POST){
			$this -> error('页面不存在','');
		}
		$db = M('userinfo');
		$where = array('uid' => session('uid'));
		$field = array('face50','face80','face180');
		$old = $db ->where($where)->field($field)->find();
		if($db ->where($where)->save($_POST)){
			if(!empty($old['face180'])){
				@unlink('./Uploads/Face/' . $old['face180']);
				@unlink('./Uploads/Face/' . $old['face80']);
				@unlink('./Uploads/Face/' . $old['face50']);
			}
			$this-> success('修改成功' , U('index'));
		} else {
			$this -> error('修改失败，请重试...');
		}
	}




}


?>