<?php
/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 2023/7/24
 * Time: 10:29
 */

namespace app\controller;
use app\model\MatchForcastLogModel;
use app\BaseController;
use think\facade\Db;
use think\facade\Cache;

class matchforcast extends BaseController
{

    //站点列表
    public function datalist(){
        $model = new MatchForcastLogModel();
        $param = get_params();
        $where = [];
        if (!empty($param['keywords'])) {
            $where[] = ['title|return', 'like', '%' . $param['keywords'] . '%'];
        }
        $list = $model->datalist($where,$param);
        foreach ($list['data'] as $k=>$v){
            $list['data'][$k]['addtime'] = date("Y-m-d H:i:s",$v['addtime']);
        }
        $this->apiSuccess("success",$list);
    }



    //站点删除
    public function del(){
        $param = get_params();
        if(is_array($param["id"])){
            if((new MatchForcastLogModel())->where("id","in",$param["id"])->delete()){
                $this->apiSuccess("删除成功");
            }else{
                $this->apiSuccess("删除失败");
            }
        }else{
            if((new MatchForcastLogModel())->where("id",$param["id"])->delete()){
                $this->apiSuccess("删除成功");
            }else{
                $this->apiSuccess("删除失败");
            }
        }
    }
}