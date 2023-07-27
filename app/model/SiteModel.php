<?php
/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 2023/7/24
 * Time: 10:12
 */

namespace app\model;
use think\Model;
use think\facade\Db;


class SiteModel extends Model
{
    protected $name = 'chat_site';
    protected $pk = 'id';

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

    public function getDataByUser($id = 0,$user_id = 0){
        if (empty($user_id)){
            return [];
        }

        $query = self::where('uid',$user_id)->where('status',1);
        if (!empty($id)){
            $query->where('id',$id);
        }

        return $query->select()->toArray();
    }


}