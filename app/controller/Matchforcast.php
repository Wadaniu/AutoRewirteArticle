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
use think\App;
use think\Exception;
use think\facade\Db;
use think\facade\Cache;

class Matchforcast extends BaseController
{

    public $model;
    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->model = new MatchForcastLogModel();
    }

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
        $this->start();
    }

    public function startBasketball(){
        $this->start(1);
    }

    public function start($type = 0){
        set_time_limit(0);
        if ($type ==1){
            $order = 'id ASC';
            $matchTable = 'basketball_match';
            $cacheName = 'syncBasketballMatchInfoList';
        }else{
            $order = '';
            $matchTable = 'football_match';
            $cacheName = 'syncFootballMatchInfoList';
        }

        try {
            //获取生成标签描述指令配置
            $orderModel = new UserOrderModel();
            $instruct = $orderModel->getByType(3);
            if (empty($instruct)){
                throw new \think\Exception('未配置生成赛事预测相关指令', 401);
            }
            //获取链接池秘钥
            $keyModel = new ChatKeyModel();
            $keys = $keyModel->getAll($order);
            if (count($keys) <= 0){
                throw new \think\Exception('无可用key,请上传可用key', 401);
            }
            //获取可用key
            $keyPool = new KeyPool($keys,$keyModel);
            $key = $keyPool->getAvailableKey();
            if ($key === false){
                throw new \think\Exception('无可用key,请上传可用key', 401);
            }

            $instruct = $this->model->replaceOrder($instruct,$type);

            //调用gpt
            $gpt = new ChatGPT($key);
            $res = $gpt->sendRequest($instruct['order']);
            if (!isset($res['choices']) || is_null($res)){
                $this->gptError($instruct,$keyPool,$key,$res,$cacheName);
            }

            $forecast = '';
            //如果结果还未输出完全，则继续输出
            while (true){
                if ($res['choices'][0]['finish_reason'] == 'length'){
                    $forecast .= $res['choices'][0]['message']['content'];
                    $newOrder = $forecast.',接着往下写';
                    $res = $gpt->sendRequest($newOrder);
                    if (!isset($res['choices']) || is_null($res)){
                        $this->gptError($instruct,$keyPool,$key,$res,$cacheName);
                    }
                }else{
                    $forecast .= $res['choices'][0]['message']['content'] ?? '';
                    break;
                }
            }

            //gpt结果写入数据
            Db::connect('compDataDb')->name($matchTable)
                ->where('id',$instruct['match_id'])->update(['forecast'=>$forecast,'forecast_time'=>time()]);

            //将日志写入生成对阵预测日志
            $this->model->update(['id'=>$instruct['log_id'],'return'=>json_encode($res)]);
            $this->apiSuccess("生成成功");
        }catch (Exception $e){
            $this->apiError($e->getCode(),$e->getMessage());
        }
    }

    /**
     * @throws Exception
     */
    public function gptError($instruct, $keyPool, $key, $res, $cacheName){
        //获取一个缓存
        $cache = Cache::get($cacheName,[]);
        //如果详情数据有问题入队列
        $cache[] = $instruct['match_id'];
        //将id重新保存
        Cache::set($cacheName,$cache);

        $keyPool->markKeyAsFailed($key);
        $this->model->update(['id'=>$instruct['log_id'],'return'=>json_encode($res)]);
        throw new \think\Exception(json_encode($res), 401);
    }

    /**
     * 由于当前生成赛事预测接口可能3-5分钟跑一次,而赛程同步接口1分钟1次,
     * 可能导致部分数据同步不到,故使用该接口1分钟1次和赛程接口同步,写入缓存
     * @return void
     */
    public function syncMatchIds(){
        sleep(10);  //防止和同步赛程数据并发导致赛程id队列错误
        $this->model->syncMatchIds();
        $this->model->syncMatchIds(1);
    }

    /**
     * 设置明天的比赛生成赛程预测
     * @return void
     */
    public function setHotCompMatch(){
        $this->model->setHotCompMatch();
        $this->model->setHotCompMatch(1);
    }
}