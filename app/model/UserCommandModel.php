<?php

namespace app\model;

use think\Model;

class UserCommandModel extends Model
{

    protected $name = 'user_command';
    protected $pk = 'id';

    const NEWSTYPE = 1;
    const LABELTYPE = 2;
    const MATCHFORCAST = 3;

    /**
     * @param $param
     * @param $site
     * @param $limit
     * @return \think\Paginator
     * @throws \think\db\exception\DbException
     */
    public function datalist($where,$param){
        $rows = empty($param['limit']) ? 10 : $param['limit'];
        $page = empty($param['page'])?1:$param['page'];
        $order = empty($param['order']) ? 'id desc' : $param['order'];
        $query = self::where($where);
        $count = $query->count();
        $list = $query->limit($page*$rows-$rows,$rows)->order($order)->select()->toArray();
        $res = [
            'total' => $count,
            'data'  => $list,
            'per_page'=>$rows,
            'current_page'=>$page
        ];
        return $res;
    }
}