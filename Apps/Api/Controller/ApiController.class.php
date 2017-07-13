<?php
namespace Api\Controller;

use Think\Controller;

/**
 * Class ApiController
 * @package Api\Controller
 * 以后防护数据code字段只要成功访问数据库 为200
 */
class ApiController extends Controller
{

    /**
     * 获取ACCESSTOKEN
     */
    public function getUserAccessToken()
    {
        $encryptKey = 'weiboAppKey!@)$*)!$&)!$^!@^$(!H!@R!R';
        $encryptCode = 'WEIBOTOKEN' . ',' . time();
        $accessToken = encrypt($encryptCode, $encryptKey);

        if ($accessToken) {
            $return['code'] = 200;
            $return['info'] = array(
                'access_token' => $accessToken,
                'expires_in' => 7200
            );
            S($accessToken, 1, 7200);
        } else {
            $return['code'] = 0;
            $return['info'] = '获取access_token失败';
        }
        $this->ajaxReturn($return);
    }

    /**
     * 上传用户的图片
     * HTTPPOST表单提交过来 默认接受$_FILES
     *
     */
    public function uploadUserPic()
    {
        $getToken = I('get.token');
        $width = I('get.width');
        $height = I('get.height');
        $isToken = S($getToken);
        if ($isToken) {
            if ($_FILES) {
                //由于3.2.3不支持文件缩略图，故引用了旧版本文件
                $obj = new \Think\UploadFile();                             //实例化上传类        
                $obj->maxSize = C('UPLOAD_MAX_SIZE');                       //图片最大上传大小
                $obj->savePath = C('UPLOAD_PATH') . 'Pic' . '/';    //图片保存路径
                $obj->saveRule = 'uniqid';  //保存文件名
                $obj->uploadReplace = true; //覆盖同名文件
                $obj->allowExts = C('UPLOAD_EXTS'); //允许上传文件的后缀名
                $obj->thumb = true; //生成缩略图
                $obj->thumbMaxWidth = $width . ',' . $width / 2 . ',' . $width / 4;   //缩略图宽度
                $obj->thumbMaxHeight = $height . ',' . $height / 2 . ',' . $height / 4; //缩略图高度
                $obj->thumbPrefix = 'max_,medium_,mini_';   //缩略图后缀名
                $obj->thumbPath = $obj->savePath . date('Y_m') . '/';   //缩略图保存图径
                $obj->thumbRemoveOrigin = true; //删除原图
                $obj->autoSub = true;   //使用子目录保存文件
                $obj->subType = 'date'; //使用日期为子目录名称
                $obj->dateFormat = 'Y_m';   //使用 年_月 形式

                if (!$obj->upload()) {
                    $return['code'] = 0;
                    $return['error'] = $obj->getErrorMsg();
                } else {
                    $info = $obj->getUploadFileInfo();
                    $pic = explode('/', $info[0]['savename']);
                    $return = array(
                        'code' => 200,
                        'info' => array(
                            'max' => $pic[0] . '/max_' . $pic[1],
                            'medium' => $pic[0] . '/medium_' . $pic[1],
                            'mini' => $pic[0] . '/mini_' . $pic[1]
                        )
                    );
                }
            } else {
                $return['code'] = 0;
                $return['error'] = '没有上传的图片';
            }
        } else {
            $return['code'] = -1;
            $return['error'] = 'access_token已过期或者不存在';
        }

        $this->ajaxReturn($return);
    }

    /**
     * 登陆Api
     * 判定账号密码是否正确
     * userLogin.html?account=用户账号&password=用户密码
     */
    public function userLogin()
    {
        //$getToken = I('get.token');
//        $isToken = S($getToken);
//    	if($isToken)
//    	{
        $encryptKey = 'weiboAppKey!@)$*)!$&)!$^!@^$(!H!@R!R';
        $encryptCode = 'WEIBOTOKEN' . ',' . time();

        $account = I('post.account');
        $password = I('post.password', '', 'md5');
        $map['account'] = $account;
        $map['password'] = $password;
        $map['lock'] = 0;

        $userInfo = M('user')->field('id,account,registime,lock')->where($map)->find();
        $return['code'] = 200;
        if ($userInfo) {
            $accessToken = encrypt($encryptCode, $encryptKey);

            $return['status'] = 'success';
            $return['msg'] = '登录成功';
            $return['info'] = $userInfo + array(
                    'token' => $accessToken,
                    'expires_in' => 7200
                );
            /**
             * 为了照顾格式access_token 改成token
             */
            S($accessToken, $userInfo['id'], 7200);
        } else {
            $return['status'] = 'fail';
            $return['msg'] = '不存在该用户或账号密码错误';
        }
//    	}
//    	else
//    	{
//    		$return['code'] = -1;
//    		$return['info']	= 'access_token已过期或者不存在';
//    	}

        $this->ajaxReturn($return);
    }

    /**
     * 注册Api
     * APP进行数据确定 该API提供插入数据
     * userRegister.html?account=用户账号&password=用户密码&uname=用户昵称
     */
    public function userRegister()
    {
//        $getToken = I('get.token');
//        $isToken = S($getToken);


        $data = array(
            'account' => I('post.account'),
            'password' => I('post.password', '', 'md5'),
            'registime' => $_SERVER['REQUEST_TIME'],
            'userinfo' => array(
                //userinfo关联表 数据
                'username' => I('post.uname')
            )
        );
        $count = M('user')->where(array('account' => $data['account']))->count();
        if (!$count) {
            if (empty($data['account'] || empty($data['uname']))) {
                $return['code'] = 0;
                $return['info'] = '请填写所注册的用户信息';
            } else {
                $id = D('Home/UserRelation')->insert($data);
                if ($id) {
                    $return['code'] = 200;
                    $return['status'] = 'success';
                    $return['msg'] = "注册成功";
                } else {
                    $return['code'] = 200;
                    $return['status'] = 'fail';
                    $return['msg'] = '注册失败';
                }
            }

        } else {
            $return['code'] = 200;
            $return['status'] = 'fail';
            $return['msg'] = '已存在该用户';
        }


        $this->ajaxReturn($return);
    }


    /**
     * 获取转发列表
     *
     * @param  int $wid 所需要获取转发列表的微博ID
     * @param  int $page 1页10条
     */
    public function getWeiboTurnList()
    {
        $getToken = I('post.token');
        $isToken = S($getToken);
        if ($isToken) {
            $wid = I('get.wid');
//            $limit = I('get.limit');
            $page = isset($_GET['page']) ? I('get.page', '', 'int') : 1;
            //拼接limit条件
            $limit = $page < 2 ? '0,10' : (10 * ($page - 1)) . ',10';
            $map['isturn'] = $wid;

            $count = M('weibo')->where($map)->join('hd_userinfo ON hd_weibo.uid = hd_userinfo.id')->count();
            $total = ceil($count / 10);
            $field = array('hd_weibo.id', 'hd_weibo.content', 'hd_weibo.time', 'hd_weibo.uid',
                'hd_userinfo.username', 'hd_userinfo.face180' => 'face');

            $result = M('weibo')->where($map)->join('hd_userinfo ON hd_weibo.uid = hd_userinfo.id')
                ->limit($limit)->field($field)->select();

            if ($result) {
                $return['code'] = 200;
                $return['status'] = 'success';
                $return['msg'] = '获取成功';
                $return['info'] = $result;
                $return['totalPage'] = $total;
            } else {
                $return['code'] = 200;
                $return['status'] = 'fail';
                $return['msg'] = "没有哦";
            }
        } else {
            $return['code'] = -1;
            $return['status'] = 'fail';
            $return['msg'] = 'access_token已过期或者不存在';
        }

        $this->ajaxReturn($return);
    }

    /**
     * 往内存写入推送消息或清空推送消息
     *
     * @param [int]     $uid [用户ID号]
     * @param [int]     $type [1.评论 2.私信 3.@用户]
     * @param [boolean] $flush [是否清0]
     */
    public function setMsg()
    {
        $getToken = I('post.token');
        $isToken = S($getToken);
        if ($isToken) {
            $uid = I('post.uid');
//            $type = I('post.type');
            $flush = I('post.flush', '0');
//            if (empty($type) || empty($uid)) {
            if (empty($uid)) {
                $return['code'] = 0;
                $return['status'] = 'fail';
                $return['msg'] = '必要参数缺失';
            } else {
                set_msg($uid, 1, $flush);
                set_msg($uid, 2, $flush);
                set_msg($uid, 3, $flush);
                $return['code'] = 200;
                $return['status'] = 'success';
                if ($flush) {
                    $return['msg'] = '清空成功';
                } else {
                    $return['msg'] = '设置成功';
                }
            }
        } else {
            $return['code'] = -1;
            $return['status'] = 'fail';
            $return['msg'] = 'access_token已过期或者不存在';
        }


        $this->ajaxReturn($return);

    }


    /**
     * 获取定时轮询推送
     *
     * @param  int $uid 用户ID
     */
    public function getMsg()
    {
        $getToken = I('post.token');
        $isToken = S($getToken);
        if ($isToken) {
            $uid = I('get.uid', '0', 'int');
            $msg = S('usermsg' . $uid);
            if ($msg) {
                if ($msg['comment']['total']) {

                    $return['info']['comment'] = $msg['comment']['total'];
                    //清空
//                    set_msg($uid, 1, 1);
                }

                if ($msg['letter']['total']) {

                    $return['info']['letter'] = $msg['letter']['total'];
                    //清空
//                    set_msg($uid, 2, 1);
                }

                if ($msg['atme']['total']) {
                    $return['info']['atme'] = $msg['atme']['total'];
                    //清空
//                    set_msg($uid, 3, 1);
                }
//                if ($msg['comment']['status']) {
//                    $msg['comment']['status'] = 0;
//
//                    /S('usermsg' . $uid, $msg, 0);/评论
//                    $return['info']['comment'] = $msg['comment']['total'];
//
//                }
//                if ($msg['letter']['status']) {
//                    $msg['letter']['status'] = 0;
//                    S('usermsg' . $uid, $msg, 0);
//
//                    $return['info']['letter'] = $msg['letter']['total'];
//                    //私信
//
//
//                }
//                if ($msg['atme']['status']) {
//                    $msg['atme']['status'] = 0;
//                    S('usermsg' . $uid, $msg, 0);
//                    //@人
//                    $return['info']['atme'] = $msg['atme']['total'];
//                }
//                if ($return)
                $return['code'] = 200;
                if (count($return) == 1) {
                    $return['code'] = 0;
                    $return['status'] = 'fail';
                    $return['msg'] = '没有新消息';
                } else {
                    $return['msg'] = '有新的消息';
                    $return['status'] = 'success';
                }

            } else {
                $return['code'] = 0;
                $return['status'] = 'fail';
                $return['msg'] = '没有新消息';
            }

        } else {
            $return['code'] = -1;
            $return['status'] = 'fail';
            $return['msg'] = 'access_token已过期或者不存在';
        }
        $this->ajaxReturn($return);
    }


    /**
     * 发布微博内容
     * 纯文字微博：sendWeibo.html?uid=9&content=23333333312074012742174284243
     * 带图片微博（拼接上URL）：&max=2016_03/max_56ecfd07d9bfe.jpg&medium=2016_03/medium_56ecfd07d9bfe.jpg&mini=2016_03/mini_56ecfd07d9bfe.jpg
     */
    public function sendWeibo()
    {
        $getToken = I('post.token');
        $isToken = S($getToken);
        if ($isToken) {

            $uid = I('post.uid', '0', 'int');
            $data = array(
                'content' => I('post.content'),
                'time' => time(),
                'uid' => $uid
            );

            //插入weibo数据 成功进入
            if ($wid = M('weibo')->data($data)->add()) {
                if (!empty($_POST['max'])) {
                    //判断是否存在上传的图片
                    $img = array(
                        'mini' => I('post.mini'),
                        'medium' => I('post.medium'),
                        'max' => I('post.max'),
                        'wid' => $wid
                    );
                    //存在则添加图片地址，通过weibo的ID进行与picture的外键wid关联
                    M('picture')->data($img)->add();
                }

                //热门话题
                if (preg_match_all('/#(.*)#/', $_POST['content'], $hots)) {
                    $map['keyword'] = array('like', $hots[1][0] . '%');
                    $count = M('hots')->where($map)->count();
                    if ($count) {
                        M('hots')->where($map)->setInc('count');
                    } else {
                        $data = array(
                            'keyword' => $hots[1][0],
                            'wid' => $wid
                        );
                        M('hots')->data($data)->add();
                    }

                }

                //增加发布+1
                M('userinfo')->where(array('uid' => session('uid')))->setInc('weibo');

                //处理@用户
                $this->_atmHandel($data['content'], $wid);

                $return['code'] = 200;
                $return['status'] = 'success';
                $return['msg'] = '发布微博成功';

            } else {
                $return['code'] = 200;
                $return['status'] = 'fail';
                $return['msg'] = '发布微博失败';
            }
        } else {
            $return['code'] = -1;
            $return['status'] = 'fail';
            $return['info'] = 'access_token已过期或者不存在';
        }

        $this->ajaxReturn($return);
    }

    /**
     * weibo地图信息测试
     */
    public function weiboTest()
    {
        $Weibo = D('WeiboRelation');
        $uid = I('post.uid');

        $where = array('fans' => $uid);
        $result = M('follow')->field('follow')->where($where)->select();
        if ($result) {
            $uid = array();
            foreach ($result as $key => $v) {
                $uid[] = $v['follow'];
            }
        }

        $where = array('id' => 34);

        $db = D('Home/WeiboView');
        $result2 = $db->where($where)->select();
        $result = $Weibo->relation(true)->where($where)->select();
        $this->ajaxReturn($result);


    }

    /**
     * 获取当前用户的微博内容
     * weiboList.html?uid=用户ID&gid=分组ID（可不填则获取全部）&limit=数量限制（默认0，10）
     */
    public function weiboList()
    {

        $getToken = I('post.token');
        $isToken = S($getToken);
        if ($isToken) {
//            $db = D('Home/WeiboView');
            $db = D('WeiboRelation');


            if ($_POST['type']) {
                $uid = I('post.uid');
            } else {
                //去的当前当前用户端ID与当前用户所有关注好友的ID
                $uid = I('post.uid');
                //            $limit = I('post.limit');
                $where = array('fans' => $uid);

                //存在分组ID则加入条件
                if (isset($_POST['gid'])) {
                    $gid = I('post.gid', 0, 'int');
                    if ($gid) {
                        $where['gid'] = $gid;
                    }
                    $uid = array();
                }

                //获取我关注的人的ID
                $result = M('follow')->field('follow')->where($where)->select();
//                $this->ajaxReturn($result);

                if (is_null($result)) {
                    $return['code'] = 200;
                    $return['status'] = 'fail';
                    $return['msg'] = '没有啦';
                    $this->ajaxReturn($return);
                } else {
                    if ($result) {
                        $uid = array();
                        foreach ($result as $key => $v) {
                            $uid[] = $v['follow'];
                        }
                    }

                    if (isset($_POST['gid'])) {
                        $gid = I('post.gid', 0, 'int');
                        if (!$gid) {
                            $uid[] = I('post.uid');
                        }
                    } else {
                        $uid[] = I('post.uid');
                    }
                }
            }
            //组合WHERE条件,条件为当前用户自身ID与当前用户关注的所有好友的ID
            $where = array('uid' => array('IN', $uid));

            $keepResult = M('keep')->field('id,uid,wid')->where($where)->select();

//            $this->ajaxReturn($keepResult);

            //统计数据总条数、用于分页
            $count = $db->where($where)->count();
            $total = ceil($count / 10);

            $page = isset($_POST['page']) ? I('post.page', '', 'int') : 1;
            //拼接limit条件
            $limit = $page < 2 ? '0,10' : (10 * ($page - 1)) . ',10';

            //读取所有微博
            $dbresult = $db->getAll($where, $limit);
//            $this->ajaxReturn($dbresult);

            $result = null;
            foreach ($dbresult as $r) {
                //判断微博是否已经被收藏
                foreach ($keepResult as $k) {

                    /**
                     * 这里逻辑出现过错误
                     */
                    if ($r['isKeep']) {
                        break;
                    }

                    if ($r['id'] == $k['wid']) {
                        $r['isKeep'] = true;
                    } else {
                        $r['isKeep'] = false;
                    }
                }
                if (is_array($r['isturn'])) {
                    $r['status'] = $r['isturn'];
                    unset($r['isturn']);
                }
                $result[] = $r;
            }
//            $this->ajaxReturn($testresult);

            $return['code'] = 200;
            if ($result) {
                $return['status'] = 'success';
                $return['msg'] = '获取成功';
                $return['info'] = $result;
                $return['totalPage'] = $total;
            } else {
                $return['status'] = 'fail';
                $return['msg'] = '没有啦';
            }

        } else {
            $return['code'] = -1;
            $return['status'] = 'fail';
            $return['msg'] = 'access_token已过期或者不存在';
        }
        $this->ajaxReturn($return);
    }

    /**
     * 获取当前微博的评论内容
     * getComment.html?wid=11
     * 限制当前分页：&page=2
     */
    public function getStatusOnlyComment()
    {

        $getToken = I('post.token');
        $isToken = S($getToken);
        if ($isToken) {

            //获取当前微博ID
            $wid = I('get.wid', '', 'int');
            $where = array('wid' => $wid);

            //数据的总条数
            $count = M('comment')->where($where)->count();
            //数据可分的总页数
            $total = ceil($count / 10);
            $page = isset($_GET['page']) ? I('get.page', '', 'int') : 1;
            //拼接limit条件
            $limit = $page < 2 ? '0,10' : (10 * ($page - 1)) . ',10';

            //获取该微博ID的所有评论
            $result = D('Home/CommentView')->where($where)->order('time DESC')->limit($limit)->select();

            if ($result) {
                $return['code'] = 200;
                $return['status'] = 'success';
                $return['msg'] = '获取评论成功';
                $return['info'] = $result;
                $return['totalPage'] = $total;
            } else {
                $return['code'] = 200;
                $return['status'] = 'fail';
                $return['msg'] = '没有更多的内容了';
            }

//            $this->ajaxReturn($result);
//            if ($result) {
//                $str = '';
//                //组合评论样式字符串返回
//                foreach ($result as $v) {
//                    $str .= '<dl class="comment_content">';
//                    $str .= '<dt><a href="' . U('/' . $v['uid']) . '">';
//                    $str .= '<img src="';
//                    $str .= __ROOT__;
//                    if ($v['face']) {
//                        $str .= '/Uploads/Face/' . $v['face'];
//                    } else {
//                        $str .= '/Public/Images/noface.gif';
//                    }
//                    $str .= '" alt="' . $v['username'] . '" width="30" height="30"/>';
//                    $str .= '</a></dt><dd>';
//                    $str .= '<a href="' . U('/' . $v['uid']) . '" class="comment_name">';
//                    $str .= '@' . $v['username'] . '</a> : ' . replace_weibo($v['content']);
//                    $str .= '&nbsp;&nbsp;( ' . time_format($v['time']) . ' )';
//                    $str .= '<div class="reply">';
//                    $str .= '<a href="">回复</a>';
//                    $str .= '</div></dd></dl>';
//
//                }
//
//                //组合评论分页
//                if ($total > 1) {
//                    $str .= '<dl class="comment-page">';
//                    switch ($page) {
//                        case $page > 1 && $page < $total:
//                            $str .= '<dd wid="' . $wid . '" page="' . ($page - 1) . '">上一页</dd>';
//                            $str .= '<dd wid="' . $wid . '" page="' . ($page + 1) . '">下一页</dd>';
//                            break;
//                        case $page < $total:
//                            $str .= '<dd wid="' . $wid . '" page="' . ($page + 1) . '">下一页</dd>';
//                            break;
//                        case $page == $total:
//                            $str .= '<dd wid="' . $wid . '" page="' . ($page - 1) . '">上一页</dd>';
//                            break;
//                    }
//                    $str .= '</dl>';
//                }
//                $return['code'] = 200;
//                $return['info'] = $str;
//            } else {
//                $return['code'] = 0;
//                $return['info'] = '获取评论内容失败';
//            }
        } else {
            $return['code'] = -1;
            $return['status'] = 'fail';
            $return['msg'] = 'access_token已过期或者不存在';
        }

        $this->ajaxReturn($return);
    }

    /**
     * 删除评论
     * delComment?cid=评论的ID&wid=被评论的微博ID
     */
    Public function delComment()
    {

        $getToken = I('get.token');
        $isToken = S($getToken);
        if ($isToken) {

            $cid = I('get.cid', '', 'int');
            $wid = I('get.wid', '', 'int');

            if (M('comment')->delete($cid)) {
                M('weibo')->where(array('id' => $wid))->setDec('comment');
                $return['code'] = 200;
                $return['info'] = '删除成功';
            } else {
                $return['code'] = 0;
                $return['info'] = '删除失败';
            }
        } else {
            $return['code'] = -1;
            $return['info'] = 'access_token已过期或者不存在';
        }
        $this->ajaxReturn($return);
    }

    /**
     * 进行对单条微博的评论
     * setComment.html?uid=用户ID&wid=被评论微博ID&content=评论内容
     */
    public function setComment()
    {

        $getToken = I('post.token');
        $isToken = S($getToken);
        if ($isToken) {

            $parent_uid = M('weibo')->where(array('id' => I('post.pwid', '', 'int')))->getField('uid');
//            $this->ajaxReturn($parent_uid);

            //提取评论数据
            $data = array(
                'content' => I('post.content'),
                'time' => time(),
                'pid' => $parent_uid,
                'uid' => I('post.uid'),
                'wid' => I('post.wid', '', 'int')
            );

            if (M('comment')->data($data)->add()) {
                //读取评论用户信息
                $field = array('username', 'face50' => 'face', 'uid');
                $where = array('uid' => $data['uid']);
                $user = M('userinfo')->where($where)->field($field)->find();

                //被评论微博的发布者用户名
                $uid = I('post.uid', '', 'int');
                $username = M('userinfo')->where(array('uid' => $uid))->getField('username');

                set_msg(I('post.uid'), 1);

                //微博评论处理
                $db = M('weibo');
                //评论数+1
                $db->where(array('id' => $data['wid']))->setInc('comment');

                $weibo_uid = $db->where(array('id' => $data['wid']))->getField('uid');

                if ($_POST['isturn']) {
                    //读取转发微博ID与内容
                    $field = array('id', 'content', 'isturn');

                    //根据转发微博ID 数据库读取微博内容
                    $weibo = $db->field($field)->find($data['wid']);

                    //拼接转发微博内容
                    $content = $weibo['isturn'] ? $data['content'] . ' // @' . $username . ' : '
                        . $weibo['content'] : $data['content'];

                    $cons = array(
                        'content' => $content,
                        'isturn' => $weibo['isturn'] ? $weibo['isturn'] : $data['wid'],
                        'time' => $data['time'],
                        'uid' => $data['uid']
                    );

                    if ($db->data($cons)->add()) {
                        $db->where(array('id' => $weibo['id']))->setInc('turn');
                    }
                    echo 1;
                    exit();

                }

//                $this->ajaxReturn($data);
                //组合评论样式字符串返回
//                $str = '';
//                $str .= '<dl class="comment_content">';
//                $str .= '<dt><a href="' . U('/User/' . $data['uid']) . '">';
//                $str .= '<img src="';
//                $str .= __ROOT__;
//                if ($user['face']) {
//                    $str .= '/Uploads/Face/' . $user['face'];
//                } else {
//                    $str .= '/Public/Images/noface.gif';
//                }
//                $str .= '" alt="' . $user['username'] . '" width="30" height="30"/>';
//                $str .= '</a></dt><dd>';
//                $str .= '<a href="' . U('/User/' . $data['uid']) . '" class="comment_name">';
//                $str .= '@' . $user['username'] . '</a> : ' . replace_weibo($data['content']);
//                $str .= '&nbsp;&nbsp;( ' . time_format($data['time']) . ' )';
//                $str .= '<div class="reply">';
//                $str .= '<a href="">回复</a>';
//                $str .= '</div></dd></dl>';

                //写入消息推送
                set_msg($weibo_uid, 1);

                $return['code'] = 200;
                $return['status'] = 'success';
                $return['msg'] = "发布评论成功";
            } else {
                $return['code'] = 200;
                $return['status'] = 'fail';
                $return['msg'] = '评论失败';
            }
        } else {
            $return['code'] = -1;
            $return['status'] = 'fail';
            $return['msg'] = 'access_token已过期或者不存在';
        }
        $this->ajaxReturn($return);
    }

    /**
     * 转发微博
     * turnWeibo.html?uid=用户ID&wid=转发的微博ID&content=转发内容
     * &tid=父级微博ID（用于多重转发内部记录）
     */
    public function turnWeibo()
    {

        $getToken = I('post.token');
        $isToken = S($getToken);
        if ($isToken) {

            $id = I('post.wid', '', 'int');
            $tid = I('post.tid', '', 'int');
            $uid = I('post.uid', '', 'int');

            $data = array(
                'content' => I('post.content'),
                'isturn' => $tid ? $tid : $id,        //判断是否多重转发
                'time' => time(),
                'uid' => $uid
            );

            //插入数据进微博表
            $db = M('weibo');
            if ($wid = $db->data($data)->add()) {
                //原微博转发数+1
                $db->where(array('id' => $id))->setInc('turn');
                //转发微博转发数+1
                if ($tid) {
                    $db->where(array('id' => $tid))->setInc('turn');
                }

                //用户发布微博数+1
                M('userinfo')->where(array('uid' => session('uid')))->setInc('weibo');

                //热门话题
                if (preg_match_all('/#(.*)#/', $_GET['content'], $hots)) {
                    $map['keyword'] = array('like', $hots[1][0] . '%');
                    $count = M('hots')->where($map)->count();
                    if ($count) {
                        M('hots')->where($map)->setInc('count');
                    } else {
                        $data = array(
                            'keyword' => $hots[1][0],
                            'wid' => $wid
                        );
                        M('hots')->data($data)->add();
                    }

                }

                //点击同时评论后将内容插入到评论表
                if (isset($_GET['becomment'])) {
                    $data = array(
                        'content' => I('post.content'),
                        'time' => time(),
                        'uid' => $uid,
                        'wid' => $id
                    );

                    //插入评论数据后给原微博评论次数+1
                    if (M('comment')->data($data)->add()) {
                        $db->where(array('id' => $id))->setInc('comment');
                    }
                }

                //处理@用户
                $this->_atmHandel($data['content'], $wid);

                $return['code'] = 200;
                $return['status'] = 'success';
                $return['msg'] = '转发微博成功';
            } else {
                $return['code'] = 200;
                $return['status'] = 'fail';
                $return['msg'] = '转发微博失败';
            }
        } else {
            $return['code'] = -1;
            $return['status'] = 'fail';
            $return['msg'] = 'access_token已过期或者不存在';
        }

        $this->ajaxReturn($return);
    }

    /**
     * 收藏微博
     * keepWeibo.html?uid=用户ID&wid=被收藏的微博ID
     */
    public function keepWeibo()
    {

        $getToken = I('post.token');
        $isToken = S($getToken);
        if ($isToken) {

            $wid = I('post.wid', '', 'int');
            $uid = I('post.uid', '', 'int');
            $db = M('keep');

            //检测用户是否已经收藏该微博
            $where = array('wid' => $wid, 'uid' => $uid);
            $return['code'] = 200;
            if ($db->where($where)->getField('id')) {
                $return['status'] = 'fail';
                $return['msg'] = '已收藏该条微博';
            } else {
                $data = array(
                    'uid' => $uid,
                    'time' => $_SERVER['REQUEST_TIME'],
                    'wid' => $wid,
                );
                if ($db->data($data)->add()) {
                    //收藏成功
                    M('weibo')->where(array('id' => $wid))->setInc('keep');
                    $return['status'] = 'success';
                    $return['msg'] = '收藏成功';
                } else {
                    $return['status'] = 'fail';
                    $return['msg'] = '收藏失败';
                }
            }
        } else {
            $return['code'] = -1;
            $return['status'] = 'fail';
            $return['msg'] = 'access_token已过期或者不存在';
        }
        $this->ajaxReturn($return);
    }

    /**
     * 取消收藏微博
     * delKeep.html?kid=收藏的ID&wid=被收藏的微博ID
     */
    public function delKeep()
    {
        $getToken = I('post.token');
        $isToken = S($getToken);
        if ($isToken) {
            $uid = I('post.uid', '', 'int');
            $wid = I('post.wid', '', 'int');

            $map['uid'] = $uid;
            $map['wid'] = $wid;
            $status = M('keep')->where($map)->delete();
            $return['code'] = 200;
            if ($status) {
                M('weibo')->where(array('id' => $wid))->setDec('keep');
                $return['status'] = 'success';
                $return['msg'] = '取消收藏成功';
            } else {
                $return['status'] = 'fail';

                $return['msg'] = '取消收藏失败';
            }
        } else {
            $return['code'] = -1;
            $return['status'] = 'fail';
            $return['msg'] = 'access_token已过期或者不存在';
        }
        $this->ajaxReturn($return);
    }

    /**
     * 删除单条微博
     * delWeibo.html?wid=44
     */
    public function delWeibo()
    {

        $getToken = I('post.token');
        $isToken = S($getToken);
        if ($isToken) {

            //获取删除微博ID
            $wid = I('get.wid', '', 'int');
            if (M('weibo')->delete($wid)) {
                //如果含有图片
                $db = M('picture');
                $img = $db->where(array('wid' => $wid))->find();

                //对图片表记录进行删除
                if ($img) {
                    $db->delete($img['id']);
                    @unlink('./Uploads/Pic' . $img['mini']);
                    @unlink('./Uploads/Pic' . $img['medium']);
                    @unlink('./Uploads/Pic' . $img['max']);
                }
                //删除微博条数
                M('userinfo')->where(array('uid' => session('uid')))->setDec('weibo');
                M('comment')->where(array('wid' => $wid))->delete();
                M('hots')->where(array('wid' => $wid))->delete();

                $return['code'] = 200;
                $return['status'] = 'success';
                $return['msg'] = '删除微博成功';
            } else {
                $return['code'] = 200;
                $return['status'] = 'fail';
                $return['msg'] = '删除微博失败';
            }
        } else {
            $return['code'] = -1;
            $return['status'] = 'fail';
            $return['msg'] = 'access_token已过期或者不存在';
        }

        $this->ajaxReturn($return);
    }

    /**
     * 添加用户关注
     *
     * @param int $uid 用户ID
     * @param int $follow 关注用户的ID
     * @param int $fans 粉丝用户的ID
     * @param int $gid 所属关注分组的ID
     */
    public function addFollow()
    {
        $getToken = I('post.token');
        $isToken = S($getToken);
        if ($isToken) {

            $data = array(
                'follow' => I('post.follow', '', 'intval'),
                'fans' => I('post.uid'),
                'gid' => I('post.gid', '', 'intval')
            );
            if (M('follow')->data($data)->add()) {
                $db = M('userinfo');
                $db->where(array('uid' => $data['follow']))->setInc('fans');
                $db->where(array('uid' => $data['fans']))->setInc('follow');

                $return['code'] = 200;
                $return['status'] = 'success';
                $return['msg'] = "关注成功";

            } else {
                $return['code'] = 200;
                $return['status'] = 'fail';
                $return['msg'] = "关注失败请重试";
            }
        } else {
            $return['code'] = -1;
            $return['status'] = 'fail';
            $return['msg'] = 'access_token已过期或者不存在';
        }
        $this->ajaxReturn($return);
    }

    /**
     * 取消用户关注
     *
     * @param int $currentUserId 当前的用户ID
     * @param int $beUid 被取消的用户ID
     */
    public function delFollow()
    {
        $getToken = I('post.token');
        $isToken = S($getToken);
        if ($isToken) {
            $currentUserId = I('post.current_uid', '', 'int');
            $beUid = I('post.be_uid', '', 'int');
            $type = I('post.type', '', 'int');

            $where = $type ? array('follow' => $beUid, 'fans' => $currentUserId)
                : array('fans' => $beUid, 'follow' => $currentUserId);
//            $this->ajaxReturn($where);


            if (M('follow')->where($where)->delete()) {
                $db = M('userinfo');
                if ($type) {
                    $db->where(array('uid' => $currentUserId))->setDec('follow');
                    $db->where(array('uid' => $beUid))->setDec('fans');
                } else {
                    $db->where(array('uid' => $currentUserId))->setDec('fans');
                    $db->where(array('uid' => $beUid))->setDec('follow');
                }
                $return['code'] = 200;
                $return['status'] = 'success';
                $return['msg'] = "取消成功";
            } else {
                $return['code'] = 200;
                $return['status'] = 'fail';
                $return['msg'] = "取消失败";
            }
        } else {
            $return['code'] = -1;
            $return['status'] = 'fail';
            $return['msg'] = 'access_token已过期或者不存在';
        }
        $this->ajaxReturn($return);
    }

    /**
     * 获取当前用户的分组
     * getGroup.html?uid=用户ID
     */
    public function getGroup()
    {

        $getToken = I('post.token');
        $isToken = S($getToken);
        if ($isToken) {

            $uid = I('post.uid', '0', 'int');//用户账户id
            $return['code'] = 200;
            if ($uid > 0) {


                $result = M('group')->where(array('uid' => $uid))->field('id,name')->select();
                if ($result) {
                    $return['status'] = 'success';
                    $return['msg'] = "获得用户微博分组成功";
                    $return['info'] = $result;
                } else {
                    $return['status'] = 'success';
                    $return['msg'] = '没有其他分组';
                    $return['info'] = '';
                }
            } else {
                $return['status'] = 'fail';
                $return['msg'] = '获取用户分组失败或不存在分组信息';
            }
        } else {
            $return['code'] = -1;
            $return['status'] = 'fail';
            $return['msg'] = 'access_token已过期或者不存在';
        }

        $this->ajaxReturn($return);
    }

    /**
     * 创建当前用户的分组
     * addGroup.html?uid=用户ID&name=用户组名
     */
    public function addGroup()
    {

        $getToken = I('get.token');
        $isToken = S($getToken);
        if ($isToken) {

            $data = array(
                'name' => I('get.name'),
                'uid' => I('get.uid')
            );
            if ($gid = M('group')->data($data)->add()) {
                $data['code'] = 200;
                $data['info'] = $gid;
            } else {
                $data['code'] = 0;
                $data['info'] = '创建失败';
            }
        } else {
            $return['code'] = -1;
            $return['info'] = 'access_token已过期或者不存在';
        }
        $this->ajaxReturn($data);
    }

    /**
     * 用户信息修改
     * @method post
     * @param token
     * @param username 昵称
     * @param truename 真实名字
     * @param sex 性别
     * @param intro 介绍
     *
     */
    public function setUserInfo()
    {

        $getToken = I('post.token');
        $isToken = S($getToken);

        if ($isToken) {
            $uid = I('post.uid');
            $where = array('uid' => $uid);
            $data = array();

            if (isset($_POST['username'])) {
                $data['username'] = I('post.username');
            }

            if (isset($_POST['truename'])) {
                $data['truename'] = I('post.truename');
            }

            if (isset($_POST['sex'])) {
                $data['sex'] = I('post.sex');
            }

            if (isset($_POST['intro'])) {
                $data['intro'] = I('post.intro');
            }

            $User = D("userinfo");
            $return['code'] = 200;
            if ($User->where($where)->save($data)) {
                $return['status'] = 'success';
                $return['msg'] = '更新成功';
            } else {
                $return['status'] = 'fail';
                $return['msg'] = '更新失败';
            }

        } else {
            $return['code'] = -1;
            $return['status'] = 'fail';
            $return['msg'] = 'access_token已过期或者不存在';
        }
        $this->ajaxReturn($return);
    }

    /**
     * 获取用户信息
     * getUserInfo.html?uid=用户ID
     *
     * userid 查询用户,查询uid是不是userid关注的
     */
    public function getUserInfo()
    {

        $getToken = I('post.token');
        $isToken = S($getToken);
        if ($isToken) {
            $where = array();
            if (isset($_POST['uid'])) {
                if (I('post.uid')) {
                    $where = array('uid' => I('post.uid'));
                }
            }
            if (isset($_POST['username'])) {
                if (I('post.username')) {
                    $where = array('username' => I('post.username'));
                }
            }

            $field = array('username', 'face180' => 'face', 'follow', 'fans', 'weibo', 'uid',
                'truename', 'sex', 'intro');
            $userinfo = M('userinfo')->where($where)->field($field)->find();

            if (isset($_POST['userid']) && I('post.userid')) {
                $sql = '(SELECT `follow` FROM  `hd_follow` WHERE `follow` = ' . $userinfo['uid'] . ' AND 
            `fans` = ' . I('post.userid') . ' ) UNION (SELECT `follow` FROM  `hd_follow` WHERE
            `follow` = ' . I('post.userid') . ' AND `fans` = ' . $userinfo['uid'] . ')';
                $mutual = M('follow')->query($sql);
//                $this->ajaxReturn($mutual);
                if (count($mutual) == 2) {
                    $userinfo['mutual'] = 1;
                    $userinfo['followed'] = 1;
                } else {
                    $userinfo['mutual'] = 0;
                    $where = array(
                        'follow' => $userinfo['uid'],
                        'fans' => I('post.userid')
                    );
                    $count = M('follow')->where($where)->count();
                    //大于0都为真
                    if ($count > 0) {
                        $userinfo['followed'] = 1;
                    } else {
                        $userinfo['followed'] = 0;
                    }
                }
            }
            $return['code'] = 200;
            if ($userinfo) {
                $return['status'] = 'success';
                $return['msg'] = '获取成功';
                $return['info'] = $userinfo;

            } else {
                $return['code'] = 0;
                $return['status'] = 'fail';
                $return['msg'] = '获取失败';
            }
        } else {
            $return['code'] = -1;
            $return['status'] = 'fail';
            $return['msg'] = 'access_token已过期或者不存在';
        }
        $this->ajaxReturn($return);
    }

    /**
     * 修改密码
     * post
     * @param old_pwd
     * @param new_pwd
     * @param confirm_pwd
     * @param token
     */
    public function changePwd()
    {
        $getToken = I('post.token');
        $uid = S($getToken);//能读出当前token用户id
        if ($uid) {
            $old_pwd = I("post.old_pwd", '', 'md5');
            $new_pwd = I("post.new_pwd", '', 'md5');


            $User = M('user');
            $where = array('id' => $uid);
            $old = $User->field('password')->where($where)->find();

            $return['code'] = 200;
            if ($old['password'] != $old_pwd) {
                $return['status'] = 'fail';
                $return['msg'] = '原密码错误';
            } else {
                if ($_POST['new_pwd'] != $_POST['confirm_pwd']) {
                    $return['status'] = 'fail';
                    $return['msg'] = '请确保两次密码一致';
                } elseif ($_POST['new_pwd'] == $_POST['old_pwd']) {
                    $return['status'] = 'fail';
                    $return['msg'] = '新密码不能与旧密码相通';
                } else {
                    $data = array('password' => $new_pwd);
                    if ($User->where($where)->save($data)) {
                        $return['status'] = 'success';
                        $return['msg'] = '密码修改成功';
                    } else {
                        $return['status'] = 'fail';
                        $return['msg'] = '密码修改失败';
                    }
                }
            }


        } else {
            $return['code'] = -1;
            $return['status'] = 'fail';
            $return['msg'] = 'access_token已过期或者不存在';
        }
        $this->ajaxReturn($return);
    }

    /**
     * 获取关注者的ID与粉丝者的ID列表
     * getUserFollowList.html?uid=9
     *
     * uid 查询来自用户id
     * page 查询多少页[默认10条一页]
     * token 验证请求正确性
     * keyword 关键字查询
     * type: 1:关注 0:粉丝
     */

    public function getUserFollowList()
    {
        $getToken = I('post.token');
        $isToken = S($getToken);
        if ($isToken) {

            $db = M('follow');
            $uid = I('post.uid', '', 'int');
            $keyword = I('post.keyword', false);
            $page = isset($_POST['page']) ? I('post.page', '', 'int') : 1;
            //拼接limit条件
            $limit = $page < 2 ? '0,10' : (10 * ($page - 1)) . ',10';

            $type = I('post.type', '', 'int');
            //区分 type: 1:关注 0:粉丝

            if ($type == 1) {
                $where = array('fans' => $uid);
                $uids = $db->where($where)->select();
            } else {
                $where = array('follow' => $uid);
                $uids = $db->where($where)->select();
            }

            if ($uids) {
                foreach ($uids as $k => $v) {
                    $uids[$k] = $type ? $v['follow'] : $v['fans'];
                }
                $where = array('uid' => array('IN', $uids));
                if ($keyword) {
                    $where['username'] = array('LIKE', '%' . $keyword . '%');
                }
                $field = array('face180' => 'face', 'username', 'sex', 'intro', 'location', 'follow', 'fans', 'weibo', 'uid');

                $users = M('userinfo')->where($where)->field($field)->limit($limit)->select();
                $count = M('userinfo')->where($where)->count();
                $total = ceil($count / 10);
            }


//            if ($uids) {
//                //重新组合为一维数组
//                foreach ($uids as $k => $v) {
//                    $uids[$k] = $type ? $v['follow'] : $v['fans'];
//                }
//                $where = array('uid' => array('IN', $uids));
//                $field = array('face180' => 'face', 'username', 'sex', 'location', 'follow', 'fans', 'weibo', 'uid');
//                //取得关注/粉丝数据
//                $users = M('userinfo')->where($where)->field($field)->select();
//            }

//            $where = array('fans' => $uid);
//            $follow = $db->field('follow')->where($where)->select();
//
//            if ($follow) {
//                foreach ($follow as $k => $v) {
//                    $follow[$k] = $v['follow'];
//                }
//            }
//
//            $where = array('follow' => $uid);
//            $fans = $db->field('fans')->where($where)->select();
//            if ($fans) {
//                foreach ($fans as $k => $v) {
//                    $fans[$k] = $v['fans'];
//                }
//            }

//            if ($fans && $follow) {
            if ($users) {
                $data['code'] = 200;
                $data['status'] = 'success';
                $data['info'] = $users;
                $data['msg'] = '搜索完成';
                $data['totalPage'] = $total;
            } else {
                $data['code'] = 200;
                $data['status'] = 'fail';
                $data['msg'] = '没有啦';
            }
        } else {
            $data['code'] = -1;
            $data['status'] = 'fail';
            $data['msg'] = 'access_token已过期或者不存在';
        }

        $this->ajaxReturn($data);
    }

    /**
     * 获取感兴趣的朋友
     * getFriend.html?uid=用户ID
     */
    public function getFriend()
    {

        $getToken = I('get.token');
        $isToken = S($getToken);
        if ($isToken) {

            $db = M('follow');
            $uid = I('get.uid');
            $where = array('fans' => I('get.uid'));
            $follow = $db->where($where)->field('follow')->select();
            if ($follow == "") {
                $data['code'] = 0;
                $data['info'] = '没有感兴趣的朋友';
            } else {
                foreach ($follow as $k => $v) {
                    $follow[$k] = $v['follow'];
                }
                $sql = 'SELECT `uid` , `username` ,`face50` AS  `face`,COUNT(f.`follow`) AS `count` FROM `hd_follow` f LEFT JOIN `hd_userinfo` u ON f.`follow` = u.`uid` WHERE f.`fans` IN (' . implode(',', $follow) . ') AND f.`follow` NOT IN (' . implode(',', $follow) . ') AND f.`follow` <> ' . $uid . ' GROUP BY  f.`follow` ORDER BY `count` DESC LIMIT 4';

                $friend = $db->query($sql);
                if ($friend) {
                    $data['code'] = 200;
                    $data['info'] = $friend;
                } else {
                    $data['code'] = 0;
                    $data['info'] = '没有感兴趣的朋友';
                }

            }
        } else {
            $data['code'] = -1;
            $data['info'] = 'access_token已过期或者不存在';
        }
        $this->ajaxReturn($data);
    }

    /**
     * 获取热门话题
     * getHots.html
     */
    public function getHots()
    {

        $getToken = I('post.token');
        $isToken = S($getToken);
        if ($isToken) {

            $hots = M('hots')->limit(15)->order('count desc')->select();
            if ($hots) {
                $data['code'] = 200;
                $data['status'] = 'success';
                $data['msg'] = '获取热门话题成功';
                $data['info'] = $hots;
            } else {
                $data['code'] = 200;
                $return['status'] = 'fail';
                $data['msg'] = '获取热门话题失败';
            }
        } else {
            $data['code'] = -1;
            $return['status'] = 'fail';
            $data['msg'] = 'access_token已过期或者不存在';
        }
        $this->ajaxReturn($data);
    }

    /**
     * 获取@的你的人列表
     * getAtmList.html?uid=9
     */
    public function getAtmList()
    {

        $getToken = I('post.token');
        $isToken = S($getToken);
        if ($isToken) {

            $uid = I('post.uid', '', 'int');
            $page = isset($_GET['page']) ? I('get.page', '', 'int') : 1;
            //拼接limit条件
            $limit = $page < 2 ? '0,10' : (10 * ($page - 1)) . ',10';

            $where = array('uid' => $uid);
            $wid = M('atme')->where($where)->field('wid')->select();
            if ($wid) {
                foreach ($wid as $k => $v) {
                    $wid[$k] = $v['wid'];
                }
            }

            if ($wid == "") {
                $return['code'] = 0;
                $return['status'] = 'fail';
                $return['msg'] = '没有@你的人';
            } else {
                $where = array('id' => array('IN', $wid));
                $dbresult = D('Home/WeiboView')->getAll($where, $limit);

                $count = D('Home/WeiboView')->where($where)->count();
                $total = ceil($count / 10);

                $where = array('uid' => $uid);;
                $keepResult = M('keep')->field('id,uid,wid')->where($where)->select();

                $result = null;
                foreach ($dbresult as $r) {
                    //判断微博是否已经被收藏
                    foreach ($keepResult as $k) {
                        if ($r['id'] == $k['wid']) {
                            $r['isKeep'] = true;
                        } else {
                            $r['isKeep'] = false;
                        }
                    }
                    if (is_array($r['isturn'])) {
                        $r['status'] = $r['isturn'];
                        unset($r['isturn']);
                    }
                    $result[] = $r;
                }

                if ($result) {
                    $return['code'] = 200;
                    $return['msg'] = '获取成功';
                    $return['status'] = 'success';
                    $return['info'] = $result;
                    $return['totalPage'] = $total;
                } else {
                    $return['code'] = 0;
                    $return['status'] = 'fail';
                    $return['msg'] = '没有啦';
                }
            }
        } else {
            $return['code'] = -1;
            $return['status'] = 'fail';
            $return['msg'] = 'access_token已过期或者不存在';
        }
        $this->ajaxReturn($return);
    }

    /**
     * 获取评论列表
     * getCommentList.html?uid=9&limit=0,10
     */
    public function getCommentList()
    {

        $getToken = I('post.token');
        $isToken = S($getToken);
        if ($isToken) {

            $uid = I('get.uid');
//            $limit = I('get.limit', '0,10');
            $page = isset($_GET['page']) ? I('get.page', '', 'int') : 1;
            //拼接limit条件
            $limit = $page < 2 ? '0,10' : (10 * ($page - 1)) . ',10';

            $where['uid'] = array('EQ', $uid);
            $where['pid'] = array('EQ', $uid);
            $where['_logic'] = 'or';

//            $count = M('comment')->where($where)->count();
            $count = D('Home/CommentView')->where($where)->order('time DESC')->count();
            $total = ceil($count / 10);

            $comment = D('Home/CommentView')->where($where)->limit($limit)
                ->order('time DESC')
                ->select();

            if ($comment) {
                $return['code'] = 200;
                $return['msg'] = '获取成功';
                $return['status'] = 'success';
                $return['info'] = $comment;
                $return['totalPage'] = $total;
            } else {
                $return['code'] = 0;
                $return['status'] = 'fail';
                $return['msg'] = '没有了';
            }
        } else {
            $return['code'] = -1;
            $return['status'] = 'fail';
            $return['msg'] = 'access_token已过期或者不存在';
        }

        $this->ajaxReturn($return);
    }

    /**
     * 获取私信列表
     * getLetterList.html?uid=用户ID
     */
    public function getLetterList()
    {

        $getToken = I('get.token');
        $isToken = S($getToken);
        if ($isToken) {

            $uid = I('get.uid');
            $limit = I('get.limit', '0,10');

            $count = M('letter')->where(array('uid' => $uid))->count();

            $where = array('letter.uid' => $uid);
            $letter = D('Home/LetterView')->where($where)->order('time DESC')
                ->limit($limit)->select();

            if ($letter) {
                $data['code'] = 200;
                $data['info'] = $letter;
            } else {
                $data['code'] = 0;
                $data['info'] = '没有私信';
            }
        } else {
            $data['code'] = -1;
            $data['info'] = 'access_token已过期或者不存在';
        }
        $this->ajaxReturn($data);
    }

    /**
     * 获取收藏列表
     * getKeepList.html?uid=9&limit=0,10
     */
    public function getKeepList()
    {

        $getToken = I('post.token');
        $isToken = S($getToken);
        if ($isToken) {

            $uid = I('post.uid', '', 'int');

            $page = isset($_POST['page']) ? I('post.page', '', 'int') : 1;
            //拼接limit条件
            $limit = $page < 2 ? '0,10' : (10 * ($page - 1)) . ',10';


            $count = M('keep')->where(array('uid' => $uid))->count();
            $total = ceil($count / 10);

            $where = array('keep.uid' => $uid);

            $weibo = D('Home/KeepView')->getAll($where, $limit);

            $result = null;
            foreach ($weibo as $r) {
                //判断微博是否已经被收藏
                $r['isKeep'] = true;
                if (is_array($r['isturn'])) {
                    $r['status'] = $r['isturn'];
                    unset($r['isturn']);
                }
                $result[] = $r;
            }

            if ($weibo) {
                $return['code'] = 200;
                $return['msg'] = '获取成功';
                $return['status'] = 'success';
                $return['info'] = $result;
                $return['totalPage'] = $total;
            } else {
                $return['code'] = 200;
                $return['status'] = 'fail';
                $return['msg'] = '没有了';
            }

        } else {
            $return['code'] = -1;
            $return['status'] = 'fail';
            $return['msg'] = 'access_token已过期或者不存在';
        }

        $this->ajaxReturn($return);
    }

    /**
     * 搜索用户列表
     * getSearchUser.html?keyword=admin
     * &limit=0,10
     */
    public function getSearchUser()
    {

        $getToken = I('post.token');
        $isToken = S($getToken);
        if ($isToken) {

            $keyword = I('get.keyword', false);
//            $limit = I('post.limit', '0,10');
            $page = isset($_GET['page']) ? I('get.page', '', 'int') : 1;
            //拼接limit条件
            $limit = $page < 2 ? '0,10' : (10 * ($page - 1)) . ',10';
            $uid = I('get.uid');
            if ($keyword) {
                //检索出除自己外昵称含有关键字的用户
                $where = array(
                    'username' => array('LIKE', '%' . $keyword . '%'),
                    'uid' => array('NEQ', $uid)
                );
                $field = array('username', 'sex', 'location', 'intro', 'face180' => 'face', 'follow', 'fans',
                    'weibo', 'uid');

                $db = M('userinfo');
                $result = $db->where($where)->field($field)->limit($limit)->select();
                //重新组合结果集，得到是否已关注与是否互相关注
//                $this->ajaxReturn($result);

                $result = $this->_getMutual($result, $uid);
//                $this->ajaxReturn($result);

                //统计数据总条数、用于分页
                $count = $db->where($where)->count();
                $total = ceil($count / 10);

                if ($result) {
                    $return['code'] = 200;
                    $return['msg'] = '获取成功';
                    $return['status'] = 'success';
                    $return['info'] = $result;
                    $return['totalPage'] = $total;
                } else {
                    $return['code'] = 0;
                    $return['status'] = 'fail';
                    $return['msg'] = '没有啦';
                }
            } else {
                $return['code'] = 0;
                $return['status'] = 'fail';
                $return['msg'] = '搜索失败';
            }
        } else {
            $return['code'] = -1;
            $return['status'] = 'fail';
            $return['msg'] = 'access_token已过期或者不存在';
        }
        $this->ajaxReturn($return);
    }

    /**
     * 搜索微博列表
     * getSearchWeibo.html?keyword=麦当劳
     * &limit=0,10
     */
    public function getSearchWeibo()
    {

        $getToken = I('post.token');
        $isToken = S($getToken);
        if ($isToken) {
            $keyword = I('get.keyword', false);
            $page = isset($_GET['page']) ? I('get.page', '', 'int') : 1;
            //拼接limit条件
            $limit = $page < 2 ? '0,10' : (10 * ($page - 1)) . ',10';

            if ($keyword) {
                $where = array('content' => array('LIKE', '%' . $keyword . '%'));
                $db = D('Home/WeiboView');
                $weibo = $db->getAll($where, $limit);

                //统计数据总条数、用于分页
                $count = $db->where($where)->count();
                $total = ceil($count / 10);

                //查询收藏
                foreach ($weibo as $w) {
                    $wid[] = $w['id'];
                }

                if ($wid) {
                    $whereuid = array('wid' => array('IN', $wid));
                    $keepResult = M('keep')->field('id,uid,wid')->where($whereuid)->select();
                }


                foreach ($weibo as $r) {
                    if ($keepResult) {
                        foreach ($keepResult as $k) {
                            if ($r['id'] == $k['wid']) {
                                $r['isKeep'] = true;
                            } else {
                                $r['isKeep'] = false;
                            }
                        }
                    }
                    if (is_array($r['isturn'])) {
                        $r['status'] = $r['isturn'];
                        unset($r['isturn']);
                    }
                    $result[] = $r;
                }

                if ($result) {
                    $return['code'] = 200;
                    $return['msg'] = '获取成功';
                    $return['status'] = 'success';
                    $return['info'] = $result;
                    $return['totalPage'] = $total;
                } else {
                    $return['code'] = 0;
                    $return['status'] = 'fail';
                    $return['msg'] = '没有啦';
                }

            } else {
                $return['code'] = 0;
                $return['status'] = 'fail';
                $return['msg'] = '搜索失败';
            }
        } else {
            $return['code'] = -1;
            $return['status'] = 'fail';
            $return['msg'] = 'access_token已过期或者不存在';
        }

        $this->ajaxReturn($return);
    }


    Private function _atmHandel($content, $wid)
    {
        $preg = '/@(\S+?)\s/is';
        preg_match_all($preg, $content, $arr);

        if (!empty($arr[1])) {
            $db = M('userinfo');
            $atme = M('atme');
            foreach ($arr[1] as $v) {
                $uid = $db->where(array('username' => $v))->getField('uid');
                if ($uid) {
                    $data = array(
                        'wid' => $wid,
                        'uid' => $uid
                    );

                    //写入消息推送
                    set_msg($uid, 3);

                    $atme->data($data)->add();
                }
            }
        }
        return true;
    }

    /**
     * 重组结果集得到是否互相关注与是否已关注
     * @param  [Array] $result [需要处理的结果集]
     * @return [Array]         [处理完成后的结果集]
     */
    Private function _getMutual($result, $uid)
    {
        if (!$result) return false;

        $db = M('follow');

        foreach ($result as $k => $v) {
            //是否互相关注
            $sql = '(SELECT `follow` FROM  `hd_follow` WHERE `follow` = ' . $v['uid'] . ' AND 
            `fans` = ' . $uid . ' ) UNION (SELECT `follow` FROM  `hd_follow` WHERE
            `follow` = ' . $uid . ' AND `fans` = ' . $v['uid'] . ')';
            $mutual = $db->query($sql);

            if (count($mutual) == 2) {
                $result[$k]['mutual'] = 1;
                $result[$k]['followed'] = 1;
            } else {
                $result[$k]['mutual'] = 0;

                //未互相关注是检索是否已关注
                $where = array(
                    'follow' => $v['uid'],
                    'fans' => $uid
                );
                //大于0都为真
                $result[$k]['followed'] = $db->where($where)->count();
            }

        }
        return $result;

    }

}