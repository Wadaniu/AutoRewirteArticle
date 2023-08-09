<?php
/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 2023/7/24
 * Time: 10:29
 */

namespace app\controller;
use app\model\ReplaceModel;
use app\BaseController;
use think\exception\ValidateException;
use think\facade\Db;


class Replace extends BaseController
{

    //站点列表
    public function datalist(){
        $model = new ReplaceModel();
        $param = get_params();
        $where = [];
        if (!empty($param['keywords'])) {
            $where[] = ['name|replace', 'like', '%' . $param['keywords'] . '%'];
        }
        $list = $model->datalist($where,$param);
        foreach ($list['data'] as $k=>$v){
            $list['data'][$k]['addtime'] = date("Y-m-d H:i:s",$v['addtime']);
        }
        $this->apiSuccess("success",$list);
    }


    //站点编辑
    public function edit(){
        $param = get_params();
        $model = (new ReplaceModel());
        if(isset($param['id']) && $param['id']){
            $row = $model->where("id",$param['id'])->find();
        }else{
            $row = $model;
        }
        $row->name = $param['name'];
        $row->replace = $param['replace'];
        $row->status = $param['status'];
        $row->type = $param['type'];
        $row->order_type = $param['order_type'];
        if(empty($param['id'])){
            $row->addtime = time();
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
            if((new ReplaceModel())->where("id","in",$param["id"])->delete()){
                $this->apiSuccess("删除成功");
            }else{
                $this->apiSuccess("删除失败");
            }
        }else{
            if((new ReplaceModel())->where("id",$param["id"])->delete()){
                $this->apiSuccess("删除成功");
            }else{
                $this->apiSuccess("删除失败");
            }
        }
    }

    public function getByType(): array
    {
        $model = new ReplaceModel();
        $param = get_params();
        if (isset($param['type']) && !empty($param['type'])){
            $model = $model->where('type','=',$param['type']);
        }
        $list = $model->select()->toArray();
        return $this->apiSuccess("success",$list);
    }
}