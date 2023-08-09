<?php
/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 2023/7/24
 * Time: 10:29
 */

namespace app\controller;
use app\model\UserCommandModel;
use app\BaseController;
use app\model\UserOrderModel;
use think\exception\ValidateException;
use think\facade\Db;


class command extends BaseController
{

    //站点列表
    public function datalist(){
        $model = new UserOrderModel();
        $param = get_params();
        $where = [];
        if (!empty($param['keywords'])) {
            $where[] = ['title|content', 'like', '%' . $param['keywords'] . '%'];
        }
        $list = $model->datalist($where,$param);
        foreach ($list['data'] as $k=>$v){
            $list['data'][$k]['createdAt'] = date("Y-m-d H:i:s",$v['createdAt']);
            $list['data'][$k]['updatedAt'] = date("Y-m-d H:i:s",$v['updatedAt']);
        }
        $this->apiSuccess("success",$list);
    }


    //站点编辑
    public function edit(){
        $param = get_params();
        $model = (new UserOrderModel());
        if(isset($param['id']) && $param['id']){
            $row = $model->where("id",$param['id'])->find();
        }else{
            $row = $model;
        }
        $row->title = $param['title'];
        $row->content = $param['content'];
        $row->status = $param['status'];
        $row->type = $param['type'];
        $row->title_len = $param['title_len'];
        $row->content_len = $param['content_len'];
        $row->updatedAt = time();
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
            if((new UserOrderModel())->where("id","in",$param["id"])->delete()){
                $this->apiSuccess("删除成功");
            }else{
                $this->apiSuccess("删除失败");
            }
        }else{
            if((new UserOrderModel())->where("id",$param["id"])->delete()){
                $this->apiSuccess("删除成功");
            }else{
                $this->apiSuccess("删除失败");
            }
        }
    }
}