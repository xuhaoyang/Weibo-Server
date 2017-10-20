<?php
/**
 * 异位或加密字符串
 * @param  [string]  $value [需要加密的字符串]
 * @param  [integer] $type  [加密解密（0：加密，1：解密）]
 * @return [string]         [加密或解密后的字符串]
 */
function encryption($value, $type = 0)
{
    $key = md5(C('ENCTYPTION_KEY'));

    if (!$type) {
        //加密
        return str_replace('=', '', base64_encode($value ^ $key));
    }

    $value = base64_decode($value);
    return $value ^ $key;
}

/**
 * 格式化时间
 * @param  [type] $time [description]
 * @return [type]       [description]
 */
function time_format($time)
{
    //当前时间
    $now = time();
    //获取今天零时零分零秒
    $today = strtotime(date('y-m-d', $now));
    //传递时间与当前时秒相差的秒数
    $diff = $now - $time;
    $str = '';
    switch ($time) {
        case $diff < 60:
            $str = $diff . '秒前';
            break;
        case $diff < 3600:
            $str = floor($diff / 60) . '分钟前';
            break;
        case $diff < (3600 * 8):
            $str = floor($diff / 3600) . '小时前';
        case $time > $today:
            $str = '今天' . date('H:i', $time);
            break;
        default:
            $str = date('Y-m-d H:i:s', $time);
            break;
    }
    return $str;
}


/**
 * 往内存写入推送消息
 * @param [int] $uid [用户ID号]
 * @param [int] $type [1.评论 2.私信 3.@用户]
 * @param [boolean] $flush [是否清0]
 */
function set_msg($uid, $type, $flush = false)
{
    $name = '';
    switch ($type) {
        case 1:
            $name = 'comment';
            break;
        case 2:
            $name = 'letter';
            break;
        case 3:
            $name = 'atme';
            break;
    }

    if ($flush) {
        $data = S('usermsg' . $uid);
        $data[$name]['total'] = 0;
        $data[$name]['status'] = 0;
        S('usermsg' . $uid, $data, 0);
        return;
    }

    if (S('usermsg' . $uid)) {
        //内存数据已存时相应+1
        $data = S('usermsg' . $uid);
        $data[$name]['total']++;
        $data[$name]['status'] = 1;
        S('usermsg' . $uid, $data, 0);
    } else {
        //内存数据不存在时，初始化用户数据并写入到内存
        $data = array(
            'comment' => array('total' => 0, 'status' => 1),
            'letter' => array('total' => 0, 'status' => 1),
            'atme' => array('total' => 0, 'status' => 1),
        );
        $data[$name]['total']++;
        $data[$name]['status'] = 1;
        S('usermsg' . $uid, $data, 0);
    }
}

/**
 * 友盟推送
 * @param $data
 */
function push_msg_youmeng($uid)
{

    //atme type 1 提到你 2 评论你
    //atme status 0未推送;1已推送;2推送失败
    base64_encode();
    //TODO 查询content内容和对象Uid+名字
    //TODO 构建推送数据
    //TODO 调用友盟推送


}

/**
 * 推送插入数据库
 * @param $suid 推送发起者
 * @param $ruid 推送接收者
 * @param $content 内容
 * @param $type 1发送微博;2评论微博;3转发微博
 */
function push_message($suid, $ruid, $content, $type)
{

    /**
     * TODO 发微博@人/评论中@[除开作者和发起者以外的第三人] (接受信息者)主title:xxx 提到你;副title:内容
     * TODO 评论微博(评论+@) (接受信息者)主title:@[评论者的名字];副title: ->评论你:内容
     * TODO 转发微博 (接受信息者)主title:@[评论者的名字];副title: ->提到你:内容
     */

    $push_message_db = M('pushmessage');
    /**
     * id suid ruid content type status
     */
    $time = date("Y-m-d H:i:s");
    $data = array(
        'suid' => $suid,
        'ruid' => $ruid,
        'content' => $content,
        'type' => $type,
        'status' => 0,
        'createAt' => $time,
        'updateAt' => $time
    );
    $result = $push_message_db->data($data)->add();
    return $result;

}


/**
 * 替换微博内容的URL地址、@用户与表情
 * @param  [String] $content [需要处理的微博字符串]
 * @return [String]          [处理完成后的字符串]
 */
function replace_weibo($content)
{
    if (empty($content)) return;

    //给URL地址加上<a>标签 正则匹配http跟www网址
    $preg = '/(?:http:\/\/)?([\w.]+[\w\/]*\.[\w.]+[\w\/]*\??[\w=\&\+\%]*)/is';
    $content = preg_replace($preg, '<a href="http://\\1" target="_blank">\\1</a>', $content);

    //給所有@用户加上<a>标签
    $preg = '/@(\S+)\s/is';
    $content = preg_replace($preg, '<a href="' . __APP__ . '/UserAtme/\\1">@\\1</a>', $content);

    if (ACTION_NAME != 'sechWeibo') {
        $preg = '/#(.*)#/';
        $content = preg_replace($preg, '<a href="' . __APP__ . '/Home/Search/sechWeibo?keyword=\\1">#\\1#</a>', $content);
    }


    //提取微博内容中的所有表情标签
    $preg = '/\[(\S+?)\]/is';
    preg_match_all($preg, $content, $arr);

    //载入表情包数组文件
    $phiz = include './Public/Data/phiz.php';
    //判断是否存在表情标签
    if (!empty($arr[1])) {
        foreach ($arr[1] as $k => $v) {
            //根据表情名查找数组中的键值
            $name = array_search($v, $phiz);
            if ($name) {
                //存在则替换为表情
                $content = str_replace($arr[0][$k],
                    '<img src="' . __ROOT__ . '/Public/Images/phiz/' . $name . '.gif" title="' . $v . '"/>', $content);
            }
            //不存在则不修改 返回原样字符
        }
    }
    return str_replace(C('FILTER'), '***', $content);
}


?>