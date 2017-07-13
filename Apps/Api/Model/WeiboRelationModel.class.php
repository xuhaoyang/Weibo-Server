<?php

namespace Api\Model;

use Think\Model\RelationModel;

/**
 * Created by PhpStorm.
 * User: xuhaoyang
 * Date: 2017/7/12
 * Time: 下午5:43
 */
class WeiboRelationModel extends RelationModel
{
    //定义主表名称
    protected $tableName = 'weibo';

    protected $autoCheckFields = false;                //关闭虚拟模型

    protected $_link = array(
        'maps' => array(
            'mapping_type' => self::HAS_ONE,
            'foreign_key' => 'wid',
            'mapping_fields' => 'id,name,address,longitude,latitude,cityname,citycode'
        ),
        'userinfo' => array(
            'mapping_type' => self::BELONGS_TO,
            'foreign_key' => 'uid',
            'parent_key' => 'id',
        ),
        'picture' => array(
            'mapping_type' => self::HAS_ONE,
            'foreign_key' => 'wid'
        )
    );

    /**
     * 返回查询所有记录
     * @param $where
     * @param $limit
     */
    public function getAll($where, $limit)
    {
        $result = $this->relation(true)->where($where)->limit($limit)->order('time desc')->select();
        //重组数组集数组，得到转发微博
        if ($result) {
            foreach ($result as $k => $v) {
                //判断是否存在转发 不存在(已删除)赋为-1
                if ($v['isturn']) {
                    $tmp = $this->relation(true)->find($v['isturn']);
                    $result[$k]['isturn'] = $tmp ? $tmp : -1;
                }
            }
        }
        return $result;
    }

}