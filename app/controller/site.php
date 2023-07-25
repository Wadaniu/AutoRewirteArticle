<?php
/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 2023/7/24
 * Time: 10:29
 */

namespace app\controller;
use app\model\siteModel;
use app\BaseController;
use think\facade\Db;


class site extends BaseController
{

    //站点列表
    public function datalist(){
        $model = new siteModel();
        $list = $model->datalist([],get_params());
        $this->apiSuccess("success",$list);
    }


    //站点编辑
    public function edit(){
        $param = get_params();
        $model = (new siteModel());
        $exist = $model->where("site_name",$param['site_name'])->find();
        if(isset($param['id']) && $param['id']){
            if($exist){
                $this->apiError($param['site_name']."已存在");
            }
        }else{
            if($exist && $param['id']!=$exist->id){
                $this->apiError($param['site_name']."已存在");
            }
        }
        if(isset($param['id']) && $param['id']){
            $row = $model->where("id",$param['id'])->find();
        }else{
            $row = $model;
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
            if((new siteModel())->where("id","in",$param["id"])->delete()){
                $this->apiSuccess("删除成功");
            }else{
                $this->apiSuccess("删除失败");
            }
        }else{
            if((new siteModel())->where("id",$param["id"])->delete()){
                $this->apiSuccess("删除成功");
            }else{
                $this->apiSuccess("删除失败");
            }
        }
    }
}