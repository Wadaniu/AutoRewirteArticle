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
                throw new \think\Exception('无可用key,请上传可用key', 401);
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
                $sql = "SELECT * FROM `fb_label` WHERE `status` = 1 AND `desc` IS NULL LIMIT 20";
                $labels = $pdo->query($sql);

                $updateSql = "INSERT INTO fb_label (`id`,`desc`) VALUES ";
                foreach ($labels as $label){
                    //获取生成标签描述指令
                    $command = $order['content_order'];
                    $command = str_replace('keyword',$label['name'],$command);
                    $command = str_replace('content_len',$order['content_len'],$command);

                    retry:
                    $res = $gpt->sendRequest($command);
                    if ($res === false){
                        //如果返回失败获取下一个可用key,并且重新实例化一个gpt对象
                        $key = $keyPool->getAvailableKey();
                        if ($key === false){
                            throw new \think\Exception('无可用key,请上传可用key', 401);
                        }
                        $gpt = new ChatGPT($key);
                        goto retry;
                    }else{
                        $id = $label['id'];
                        //判断是否最后一个元素
                        if ($label === end($labels)){
                            $updateSql = $updateSql."($id,$res) ";
                        }else{
                            $updateSql = $updateSql."($id,$res),";
                        }
                    }
                }
                $updateSql = $updateSql."ON DUPLICATE KEY UPDATE `desc`=VALUES(desc)";
                //处理完成将数据写回数据库
                $pdo->insert($updateSql);
            }
        }catch (Exception $e){
            $this->apiError($e->getCode(),$e->getMessage());
        }
    }
}