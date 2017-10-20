<?php
/**
 * 首页控制器
 */
namespace Home\Controller;

use Think\Controller;

class IndexController extends CommonController
{

    /**
     * 首页视图
     */
    Public function index()
    {
        //实例化微博视图模型
        $db = D('WeiboView');

        //去的当前当前用户端ID与当前用户所有关注好友的ID
        $uid = array(session('uid'));
        $where = array('fans' => session('uid'));
        //存在分组ID则加入条件
        if (isset($_GET['gid'])) {
            $gid = I('get.gid', '', 'int');
            $where['gid'] = $gid;
            $uid = array();
        }

        //获取我关注的人的ID
        $result = M('follow')->field('follow')->where($where)->select();
        if (is_null($result)) {
            $this->weibo = null;
            $this->page = null;
        } else {
            if ($result) {
                foreach ($result as $v) {
                    $uid[] = $v['follow'];
                }
            }

            //组合WHERE条件,条件为当前用户自身ID与当前用户关注的所有好友的ID
            $where = array('uid' => array('IN', $uid));

            //统计数据总条数、用于分页
            $count = $db->where($where)->count();
            $page = new \Think\Page($count, 10);
            $limit = $page->firstRow . ',' . $page->listRows;

            //读取所有微博
            $result = $db->getAll($where, $limit);
            $this->weibo = $result;
            $this->page = $page->show();
        }
        $this->display();
    }

    /**
     * 异步删除微博
     */
    Public function delWeibo()
    {
        if (!IS_AJAX) {
            halt('页面不存在');
        }
        //获取删除微博ID
        $wid = I('post.wid', '', 'int');
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
            echo 1;
        } else {
            echo 0;
        }
    }


    /**
     * 微博发布
     */
    Public function sendWeibo()
    {

        $uid = session('uid');
        if (!IS_POST) {
            halt('页面不存在');
        }
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

            $this->success('发布成功', $_SERVER['HTTP_REFERER']);
        } else {
            $this->error('发布失败请重试...');
        }
    }

    /**
     * @用户处理
     */
    Private function _atmHandel($content, $wid, $type)
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
                    //TODO 要修改成友盟推送
                    //TODO 插入数据库
                    $atme->data($data)->add();
                }
            }
        }
    }

    /**
     * 异步获取评论内容
     */
    Public function getComment()
    {
        if (!IS_AJAX) {
            halt('页面不存在');
        }
        //获取当前微博ID
        $wid = I('post.wid', '', 'int');
        $where = array('wid' => $wid);

        //数据的总条数
        $count = M('comment')->where($where)->count();
        //数据可分的总页数
        $total = ceil($count / 5);
        $page = isset($_POST['page']) ? I('post.page', '', 'int') : 1;
        //拼接limit条件
        $limit = $page < 2 ? '0,5' : (5 * ($page - 1)) . ',5';

        //获取该微博ID的所有评论
        $result = D('CommentView')->where($where)->order('time DESC')->limit($limit)->select();

        if ($result) {
            $str = '';
            //组合评论样式字符串返回
            foreach ($result as $v) {
                $str .= '<dl class="comment_content">';
                $str .= '<dt><a href="' . U('/User/' . $v['uid']) . '">';
                $str .= '<img src="';
                $str .= __ROOT__;
                if ($v['face']) {
                    $str .= '/Uploads/Face/' . $v['face'];
                } else {
                    $str .= '/Public/Images/noface.gif';
                }
                $str .= '" alt="' . $v['username'] . '" width="30" height="30"/>';
                $str .= '</a></dt><dd>';
                $str .= '<a href="' . U('/User/' . $v['uid']) . '" class="comment_name">';
                $str .= '@' . $v['username'] . '</a> : ' . replace_weibo($v['content']);
                $str .= '&nbsp;&nbsp;( ' . time_format($v['time']) . ' )';
                $str .= '<div class="reply">';
                $str .= '<a href="">回复</a>';
                $str .= '</div></dd></dl>';

            }

            //组合评论分页
            if ($total > 1) {
                $str .= '<dl class="comment-page">';
                switch ($page) {
                    case $page > 1 && $page < $total:
                        $str .= '<dd wid="' . $wid . '" page="' . ($page - 1) . '">上一页</dd>';
                        $str .= '<dd wid="' . $wid . '" page="' . ($page + 1) . '">下一页</dd>';
                        break;
                    case $page < $total:
                        $str .= '<dd wid="' . $wid . '" page="' . ($page + 1) . '">下一页</dd>';
                        break;
                    case $page == $total:
                        $str .= '<dd wid="' . $wid . '" page="' . ($page - 1) . '">上一页</dd>';
                        break;
                }
                $str .= '</dl>';
            }
            echo $str;
        } else {
            return false;
        }
    }

    /**
     * 转发微博
     */
    Public function turn()
    {
        if (!IS_POST) {
            halt('页面不存在');
        }
        $id = I('post.id', '', 'int');
        $tid = I('post.tid', '', 'int');

        $data = array(
            'content' => I('post.content'),
            'isturn' => $tid ? $tid : $id,        //判断是否多重转发
            'time' => time(),
            'uid' => session('uid')
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

            //点击同时评论后将内容插入到评论表
            if (isset($_POST['becomment'])) {

                $parent_uid = M('weibo')->where(array('id' => $id))->getField('uid');

                $data = array(
                    'content' => I('post.content'),
                    'time' => time(),
                    'pid' => $parent_uid,
                    'uid' => session('uid'),
                    'wid' => $id
                );

                //插入评论数据后给原微博评论次数+1
                if (M('comment')->data($data)->add()) {
                    $db->where(array('id' => $id))->setInc('comment');
                }
            }

            //处理@用户
            $this->_atmHandel($data['content'], $wid);

            $this->success('转发成功', $_SERVER['HTTP_REFERER']);
        } else {
            $this->error('转发微博失败请重试...');
        }
    }


    /**
     * 收藏微博功能
     */
    Public function keep()
    {
        if (!IS_AJAX) {
            halt('页面不存在');
        }

        $wid = I('post.wid', '', 'int');
        $uid = session('uid');
        $db = M('keep');

        //检测用户是否已经收藏该微博
        $where = array('wid' => $wid, 'uid' => $uid);
        if ($db->where($where)->getField('id')) {
            echo -1;
            exit();
        }

        $data = array(
            'uid' => $uid,
            'time' => $_SERVER['REQUEST_TIME'],
            'wid' => $wid,
        );
        if ($db->data($data)->add()) {
            //收藏成功
            M('weibo')->where(array('id' => $wid))->setInc('keep');
            echo 1;
        } else {
            echo 0;
        }

    }

    /**
     * 微博评论功能
     * @return [type] [description]
     */
    Public function comment()
    {
        if (!IS_AJAX) {
            halt('页面不存在');
        }
        //提取评论数据

        $parent_uid = M('weibo')->where(array('id' => I('post.wid', '', 'int')))->getField('uid');

        $data = array(
            'content' => I('post.content'),
            'time' => time(),
            'uid' => session('uid'),
            'wid' => I('wid', '', 'int'),
            'pid' => $parent_uid
        );

        if (M('comment')->data($data)->add()) {
            //读取评论用户信息
            $field = array('username', 'face50' => 'face', 'uid');
            $where = array('uid' => $data['uid']);
            $user = M('userinfo')->where($where)->field($field)->find();

            //被评论微博的发布者用户名
            $uid = I('post.uid', '', 'int');
            $username = M('userinfo')->where(array('uid' => $uid))->getField('username');

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


            //组合评论样式字符串返回
            $str = '';
            $str .= '<dl class="comment_content">';
            $str .= '<dt><a href="' . U('/User/' . $data['uid']) . '">';
            $str .= '<img src="';
            $str .= __ROOT__;
            if ($user['face']) {
                $str .= '/Uploads/Face/' . $user['face'];
            } else {
                $str .= '/Public/Images/noface.gif';
            }
            $str .= '" alt="' . $user['username'] . '" width="30" height="30"/>';
            $str .= '</a></dt><dd>';
            $str .= '<a href="' . U('/User/' . $data['uid']) . '" class="comment_name">';
            $str .= '@' . $user['username'] . '</a> : ' . replace_weibo($data['content']);
            $str .= '&nbsp;&nbsp;( ' . time_format($data['time']) . ' )';
            $str .= '<div class="reply">';
            $str .= '<a href="">回复</a>';
            $str .= '</div></dd></dl>';

            //写入消息推送
            set_msg($weibo_uid, 1);
            //TODO 友盟推送
            echo $str;
        } else {
            return false;
        }

    }

    /**
     * 用户退出登录
     */
    Public function loginOut()
    {
        //卸载SESSION
        session_unset();
        session_destroy();

        //删除用于自动登录的COOKIE
        @setcookie('auto', '', time() - 3600, '/');
        redirect(U('Index/index'));
    }

}


?>