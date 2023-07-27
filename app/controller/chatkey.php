<?php
/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 2023/7/24
 * Time: 10:29
 */

namespace app\controller;
use app\model\ChatKeyModel;
use app\BaseController;
use think\facade\Db;


class chatkey extends BaseController
{

    //站点列表
    public function datalist(){
        $model = new ChatKeyModel();
        $list = $model->datalist([],get_params());
        foreach ($list['data'] as $k=>$v){
            $list['data'][$k]['createdAt'] = date("Y-m-d H:i:s",$v['createdAt']);
            $list['data'][$k]['updateAt'] = date("Y-m-d H:i:s",$v['updateAt']);
        }
        $this->apiSuccess("success",$list);
    }


    //站点编辑
    public function edit(){
        $param = get_params();
        $model = (new ChatKeyModel());
        $exist = $model->where("key",$param['key'])->find();
        if(isset($param['id']) && $param['id']){
            if($exist){
                $this->apiError($param['key']."已存在");
            }
        }else{
            if($exist && $param['id']!=$exist->id){
                $this->apiError($param['key']."已存在");
            }
        }
        if(isset($param['id']) && $param['id']){
            $row = $model->where("id",$param['id'])->find();
        }else{
            $row = $model;
        }
        $row->key = $param['key'];
        $row->status = $param['status'];
        $row->updateAt = time();
        if(empty($param['id'])){
            $row->createdAt = $row->updateAt;
        }
        Db::startTrans();
        try{
            $row->save();
            Db::commit();
            $this->apiSuccess('编辑成功');
        }catch (ValidateException $e) {
            Db::rollback();
            $this->apiError($e->getError());
        }
    }

    //站点删除
    public function del(){
        $param = get_params();
        if(is_array($param["id"])){
            if((new ChatKeyModel())->where("id","in",$param["id"])->delete()){
                $this->apiSuccess("删除成功");
            }else{
                $this->apiSuccess("删除失败");
            }
        }else{
            if((new ChatKeyModel())->where("id",$param["id"])->delete()){
                $this->apiSuccess("删除成功");
            }else{
                $this->apiSuccess("删除失败");
            }
        }
    }
}