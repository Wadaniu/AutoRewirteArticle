<?php

namespace app\controller;

use app\BaseController;
use app\exception\ChatGPT;
use app\exception\KeyPool;
use app\exception\PDOConnect;
use app\model\ChatKeyModel;
use app\model\LabelModel;
use app\model\siteModel;
use app\model\UserOrderModel;
use think\Exception;

class Label extends BaseController
{
    public function index(){
        $params = get_params();

        try {
            $login_user = get_login_user();
            $model = new LabelModel();
            $data = $model->getList($login_user['id'],$params,$params['site']);
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

            //获取链接池秘钥
            $keyModel = new ChatKeyModel();
            $keys = $keyModel->getKeysByUser();
            if (count($keys) <= 0){
                throw new \think\Exception('参数错误,无可用key', 401);
            }
            //获取可用key
            $keyPool = new KeyPool($keys,$keyModel);
            $key = $keyPool->getAvailableKey();

            $gpt = new ChatGPT($key);
            //遍历站点配置
            foreach ($sites as $site){
                $config = [
                    'host'  =>  $site['ip'],
                    'db'    =>  $site['db'],
                    'username'  =>  $site['username'],
                    'password'  =>  $site['password'],
                ];
                $pdo = new PDOConnect($config);
                //操作数据库，获取20条数据库标签
                $sql = `SELECT * FROM fb_label WHERE status = 1 AND desc IS NULL LIMIT 20`;
                $labels = $pdo->query($sql);

                $gpt->sendMessage();
            }

        }catch (Exception $e){
            $this->apiError($e->getCode(),$e->getMessage());
        }
    }
}