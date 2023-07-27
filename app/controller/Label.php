<?php

namespace app\controller;

use app\BaseController;
use app\model\LabelModel;
use app\model\siteModel;
use app\model\UserOrderModel;
use think\Exception;

class Label extends BaseController
{
    public function index(){
        $params = get_params();

        try {
            $model = new LabelModel();
            $data = $model->getList($params,$params['site']);
            $this->apiSuccess($data);
        }catch (Exception $e){
            $this->apiError($e->getCode(),$e->getMessage());
        }
    }

    public function start(){
        $params = get_params();

        try {
            $login_user = get_login_user();
            if (empty($login_user)) {
                throw new \think\Exception('请重新登录', 300);
            }

            //获取生成标签描述指令配置
            $orderModel = new UserOrderModel();
            $order = $orderModel->getByUser($login_user['id'],2);

            //获取站点配置
            $siteModel = new SiteModel();
            $sites = $siteModel->getDataByUser($params['site'],$login_user['id']);


        }catch (Exception $e){
            $this->apiError($e->getCode(),$e->getMessage());
        }
    }
}