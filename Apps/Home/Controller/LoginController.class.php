<?php
/**
 * 注册与登陆控制器
 */
namespace Home\Controller;
use Think\Controller;
class LoginController extends Controller {

    /**
     * 处理登陆表单页面
     */
    public function index(){
        //$account = I('post.account');
        $pwd     = I('post.pwd','','md5');
        $where = array('account' => I('post.account'));
        $User = D('user') -> where($where) -> find();
        
        //判断用户密码是否正确
        if(!$User || $User['password'] != $pwd ){
            $this -> error('用户不存在');
        }

        //判断用户是否锁定
        if($User['lock']){
             $this ->error('用户被锁定');
        }
        if(isset($_POST['auto'])) {
            $account = $User['account'];
            $ip      = get_client_ip();
            $value = $account . '|' .$ip;
            $value = encryption($value);
            @setcookie('auto' , $value , C('AUTO_LOGIN_TIME') , '/' );
        }
        
        //登陆成功写入SESSION并且跳转到首页
        session('uid',$User['id']);
        redirect(__APP__ , 1 , '登陆成功，正在跳转...');
        
    }
	/**
	 * 登陆页面
	 */
    public function login(){
    	$this->display();
    }
    /**
     * 注册页面
     */
    public function register(){
    	$this->display();
    }
    /**
     * 注册信息提交
     */
    public function runRegis(){
        if(!IS_POST){
            $this -> error('页面不存在','');
        } else {
            //过滤判断
            if($_POST['pwd'] != $_POST['pwded']){
                $this -> error('密码错误','');
            }

            //提取post数据
            $data = array(
                'account'   => I('post.account'),
                'password'  => I('post.pwd','','md5'),
                'registime' => $_SERVER['REQUEST_TIME'],
                'userinfo'  => array(
                    //userinfo关联表 数据
                    'username' => I('post.uname')
                    )
                );
            //创建关联模型
            $id = D('UserRelation')->insert($data);
            if($id){
                //注册成功
                session('uid',$id);
                redirect(__APP__ , 1 , '注册成功，正在为您跳转...');
            } else {
                //注册失败
                $this -> error('注册失败','');
            }
        }
    }
    /**
     * 判断用户名是否存在
     */
    public function checkAccount(){
            if(IS_AJAX){
                $User = D("User");
                $account = I('post.account');                   //获取AJAX的用户名
                $where = array('account' => $account);          //判断条件
                if($User -> where($where) -> getField('id')){
                    //已存在
                    echo 'false';                       
                } else {
                    echo 'true';
                }
            } else {
                
            }
            
    }
    /**
     * 判断昵称是否存在
     */
    public function checkUname(){
       if(IS_AJAX){
                $User = D("userinfo");
                $username = I('post.uname');                   //获取AJAX的用户名
                $where = array('username' => $username);          //判断条件
                if($User -> where($where) -> getField('id')){
                    //已存在
                    echo 'false';                       
                } else {
                    echo 'true';
                }
            } else {
                
            }
    }
    /**
     * 判断验证码是否正确
     */
    public function checkVerify(){
        if(IS_AJAX){
            $verify_check = new \Think\Verify();

            if(!$verify_check->check($_POST['verify'])){
                echo 'false';
            } else {
                echo 'true';
            }
          
        }
    }
        /**
     * 获取验证码
     */
    public function verify(){
        $config =    array(
            'fontSize'    =>    30,    // 验证码字体大小
            'length'      =>    3,     // 验证码位数
            'useNoise'    =>    false, // 关闭验证码杂点
            );
        $Verify = new \Think\Verify($config);
        $Verify->entry();
    }

//classend
}
