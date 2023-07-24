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


class siteModel extends Model
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


    public function edit($param){

        $exist = self::where("site_name",$param['site_name'])->find();
        if(empty($param['id'])){
            if($exist){
                returnToJson(0,$param['site_name']."已存在");
            }
        }else{
            if($exist && $param['id']!=$exist->id){
                returnToJson(0,$param['site_name']."已存在");
            }
        }
        if(isset($param['id']) && $param['id']){
            $row = self::find($param['id']);
        }else{
            $row = new self();
        }
        $row->username = $param['username'];
        $row->password = $param['password'];
        $row->ip = $param['ip'];
        $row->status = $param['status'];
        $row->site_name = $param['site_name'];
        if(empty($param['id'])){
            $row->createdAt = time();
        }
        Db::startTrans();
        try{
            $row->save();
            Db::commit();
            returnToJson(1,'编辑成功',$row);
        }catch (ValidateException $e) {
            Db::rollback();
            returnToJson(0,$e->getError());
        }
    }

}