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


class site extends BaseController
{

    //站点列表
    public function datalist(){
        $model = new siteModel();
        $list = $model->datalist([],get_params());
        returnToJson(1,"返回成功",$list);
    }


    //站点编辑
    public function edit(){
        $param = get_params();
        return (new siteModel())->edit($param);
    }

    //站点删除
    public function del(){
        $param = get_params();
        if(is_array($param["id"])){
            if((new siteModel())->where("id","in",$param["id"])->delete()){
                returnToJson(1,'删除成功');
            }else{
                returnToJson(0,'删除失败');
            }
        }else{
            if((new siteModel())->where("id",$param["id"])->delete()){
                returnToJson(1,'删除成功');
            }else{
                returnToJson(0,'删除失败');
            }
        }
    }
}