<?php
/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 2023/7/24
 * Time: 10:29
 */

namespace app\controller;
use app\exception\ChatGPT;
use app\exception\KeyPool;
use app\model\ChatKeyModel;
use app\model\MatchForcastLogModel;
use app\BaseController;
use app\model\UserOrderModel;
use think\Exception;
use think\facade\Db;
use think\facade\Cache;

class Matchforcast extends BaseController
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

    public function startFootball(){
        set_time_limit(0);
        try {
            //获取生成标签描述指令配置
            $orderModel = new UserOrderModel();
            $order = $orderModel->getByType(3);
            if (empty($order)){
                throw new \think\Exception('未配置生成赛事预测相关指令', 401);
            }
            //获取链接池秘钥
            $keyModel = new ChatKeyModel();
            $keys = $keyModel->getAll();
            if (count($keys) <= 0){
                throw new \think\Exception('无可用key,请上传可用key', 401);
            }
            //获取可用key
            $keyPool = new KeyPool($keys,$keyModel);
            $key = $keyPool->getAvailableKey();
            if ($key === false){
                throw new \think\Exception('无可用key,请上传可用key', 401);
            }

            $model = new MatchForcastLogModel();
            $order = $model->replaceOrder($order);
            if ($order === false){
                throw new \think\Exception('赛事详情数据错误', 401);
            }
            //调用gpt
            $gpt = new ChatGPT($key);
            $res = $gpt->sendRequest($order['order']);
            if (!isset($res['choices']) || is_null($res)){
                $model->update(['id'=>$order['log_id'],'return'=>json_encode($res)]);
                throw new \think\Exception(json_encode($res), 401);
            }

            $forecast = '';
            //如果结果还未输出完全，则继续输出
            while (true){
                if ($res['choices'][0]['finish_reason'] == 'length'){
                    $forecast .= $res['choices'][0]['message']['content'];
                    $newOrder = $forecast.',接着往下写';
                    $res = $gpt->sendRequest($newOrder);
                    if (!isset($res['choices']) || is_null($res)){
                        $model->update(['id'=>$order['log_id'],'return'=>json_encode($res)]);
                        throw new \think\Exception(json_encode($res), 401);
                    }
                }else{
                    $forecast .= $res['choices'][0]['message']['content'] ?? '';
                    break;
                }
            }

            //gpt结果写入数据
            Db::connect('compDataDb')->name('football_match')
                ->where('id',$order['match_id'])->update(['forecast'=>$forecast]);

            //将日志写入生成对阵预测日志
            $model->update(['id'=>$order['log_id'],'return'=>json_encode($res)]);
            $this->apiSuccess("生成成功");
        }catch (Exception $e){
            $this->apiError($e->getCode(),$e->getMessage());
        }
    }

    public function startBasketball(){
        set_time_limit(0);
        try {
            //获取生成标签描述指令配置
            $orderModel = new UserOrderModel();
            $order = $orderModel->getByType(3);
            if (empty($order)){
                throw new \think\Exception('未配置生成赛事预测相关指令', 401);
            }
            //获取链接池秘钥
            $keyModel = new ChatKeyModel();
            $keys = $keyModel->getAll('id ASC');
            if (count($keys) <= 0){
                throw new \think\Exception('无可用key,请上传可用key', 401);
            }
            //获取可用key
            $keyPool = new KeyPool($keys,$keyModel);
            $key = $keyPool->getAvailableKey();
            if ($key === false){
                throw new \think\Exception('无可用key,请上传可用key', 401);
            }

            $model = new MatchForcastLogModel();
            $order = $model->replaceOrder($order,1);
            if ($order === false){
                throw new \think\Exception('赛事详情数据错误', 401);
            }

            //调用gpt
            $gpt = new ChatGPT($key);
            $res = $gpt->sendRequest($order['order']);
            if (!isset($res['choices']) || is_null($res)){
                $model->update(['id'=>$order['log_id'],'return'=>json_encode($res)]);
                throw new \think\Exception(json_encode($res), 401);
            }

            $forecast = '';
            //如果结果还未输出完全，则继续输出
            while (true){
                if ($res['choices'][0]['finish_reason'] == 'length'){
                    $forecast .= $res['choices'][0]['message']['content'];
                    $newOrder = $forecast.',接着往下写';
                    $res = $gpt->sendRequest($newOrder);
                    if (!isset($res['choices']) || is_null($res)){
                        $model->update(['id'=>$order['log_id'],'return'=>json_encode($res)]);
                        throw new \think\Exception(json_encode($res), 401);
                    }
                }else{
                    $forecast .= $res['choices'][0]['message']['content'] ?? '';
                    break;
                }
            }

            //gpt结果写入数据
            Db::connect('compDataDb')->name('basketball_match')
                ->where('id',$order['match_id'])->update(['forecast'=>$forecast]);

            //将日志写入生成对阵预测日志
            $model->update(['id'=>$order['log_id'],'return'=>json_encode($res)]);
            $this->apiSuccess("生成成功");
        }catch (Exception $e){
            $this->apiError($e->getCode(),$e->getMessage());
        }
    }

    /**
     * 由于当前生成赛事预测接口可能3-5分钟跑一次,而赛程同步接口1分钟1次,
     * 可能导致部分数据同步不到,故使用该接口1分钟1次和赛程接口同步,写入缓存
     * @return void
     */
    public function syncMatchIds(){
        //sleep(10);  //防止和同步赛程数据并发导致赛程id队列错误
        $model = new MatchForcastLogModel();
        $model->syncMatchIds();
        $model->syncMatchIds(1);
    }

    /**
     * 设置明天的比赛生成赛程预测
     * @return void
     */
    public function setTomorrowMatch(){
        $model = new MatchForcastLogModel();
        $model->setTomorrowMatch();
        $model->setTomorrowMatch(1);
    }
}