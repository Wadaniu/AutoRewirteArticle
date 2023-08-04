<?php
/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 2023/7/24
 * Time: 10:12
 */

namespace app\model;
use think\facade\Cache;
use think\Model;
use think\facade\Db;


class MatchForcastLogModel extends Model
{
    protected $name = 'match_forcast_log';
    protected $pk = 'id';

    public function datalist($where,$param){
        $rows = empty($param['limit']) ? 10 : $param['limit'];
        $page = empty($param['page'])?1:$param['page'];
        $order = empty($param['order']) ? 'id desc' : $param['order'];
        $query = self::where($where);
        $count = $query->count();
        $list = $query->limit($page*$rows-$rows,$rows)->order($order)->select()->toArray();
        $res = [
            'total' => $count,
            'data'  => $list,
            'per_page'=>$rows,
            'current_page'=>$page
        ];
        return $res;
    }



    /**
     * 由于当前生成赛事预测接口可能3-5分钟跑一次,而赛程同步接口1分钟1次,
     * 可能导致部分数据同步不到,故使用该接口1分钟1次和赛程接口同步,写入缓存
     * @return void
     */
    public function syncMatchIds($type = 0){
        if ($type == 1){
            $cacheName = 'syncBasketballMatchInfoList';
            $matchTable = 'basketball_match';
        }else{
            $cacheName = 'syncFootballMatchInfoList';
            $matchTable = 'football_match';
        }

        //获取赛程同步ids
        $Ids = Db::connect('compDataDb')->name('sphere_query_update')
            ->where('id',$cacheName)
            ->value('parmars');
        $Ids = json_decode($Ids,true);

        if (!empty($Ids)){
            //判断是否已生成过赛事预判
            $checkedFootballIds = Db::connect('compDataDb')->name($matchTable)
                ->where('id','in',$Ids)
                ->where('forecast','null')
                ->column('id');
            //不为空则追加进缓存
            if (!empty($checkedFootballIds)){
                $cache = Cache::get($cacheName,[]);
                $idList = array_unique(array_merge($checkedFootballIds,$cache));
                Cache::set($cacheName,$idList);
            }
        }
    }

    public function setTomorrowMatch($type = 0){
        if ($type == 1){
            $cacheName = 'syncBasketballMatchInfoList';
            $matchTable = 'basketball_match';
        }else{
            $cacheName = 'syncFootballMatchInfoList';
            $matchTable = 'football_match';
        }

        $startTime = strtotime(date('Y-m-d 00:00:00',time()));
        $endTime = strtotime(date('Y-m-d 23:59:59',time()));
        //获取隔天所有赛事
        $matchs = Db::connect('compDataDb')->name($matchTable)
            ->where('match_time','BETWEEN',[$startTime,$endTime])
            ->where('forecast','null')
            ->column('id');

        //不为空则追加进缓存
        if (!empty($matchs)){
            $cache = Cache::get($cacheName,[]);
            $idList = array_unique(array_merge($cache,$matchs));
            Cache::set($cacheName,$idList);
        }
    }

    public function replaceOrder($order,$type = 0){
        if (empty($order)){
            return false;
        }

        if ($type == 1){
            $cacheName = 'syncBasketballMatchInfoList';
            $matchInfoTable = 'basketball_match_info';
            $matchInfoField = 'history,info';
        }else{
            $cacheName = 'syncFootballMatchInfoList';
            $matchInfoTable = 'football_match_info';
            $matchInfoField = 'history,info,home_zr,away_zr';
        }
        //获取一个缓存
        $cache = Cache::get($cacheName,[]);
        if (empty($cache)){
            return false;
        }
        //获取第一个id
        $matchId = array_splice($cache,0,1);
        //将id重新保存
        Cache::set($cacheName,$cache);
        //根据id获取历史交锋数据以及队伍排名
        $matchInfo = Db::connect('compDataDb')->name($matchInfoTable)
            ->field($matchInfoField)
            ->where('match_id',$matchId[0])
            ->find();

        $info = json_decode($matchInfo['info'],true);

        //遍历历史交锋记录
        $vsStr = '';

        if (!empty($info)){
            $vsStr .= '主场球队'.$info['home_team_text'].'赛季排名为'.$info['home_position'].
                    ',客场球队'.$info['away_team_text'].'赛季排名为'.$info['away_position'];

            //遍历阵容数据,仅足球有阵容数据
            if ($type == 0){
                if (!is_null($matchInfo['home_zr'])){
                    $homeFirst = '以下是'.$info['home_team_text'].'主场首发阵容{';
                    foreach (json_decode($matchInfo['home_zr'],true) as $home_zr){
                        $homeFirst .= $home_zr['name'].',';
                    }
                    $vsStr .= $homeFirst.'}';
                }
                if (!is_null($matchInfo['away_zr'])){
                    $awayFirst = '以下是'.$info['away_team_text'].'客场首发阵容{';
                    foreach (json_decode($matchInfo['away_zr'],true) as $away_zr){
                        $awayFirst .= $away_zr['name'].',';
                    }
                    $vsStr .= $awayFirst.'}';
                }
            }

            //遍历历史数据
            $history = json_decode($matchInfo['history'],true);
            if (!empty($history['vs'])){
                $vsHistoryStr = '以下为两队历史交锋数据{';
                foreach ($history['vs'] as $vs){
                    //对阵数据字符串
                    $matchStr = $this->formatVsHistory($vs,$type);
                    $vsHistoryStr .= $matchStr;
                }
                $vsStr .= $vsHistoryStr.'}。';
            }
            if (!empty($history['home'])){
                $homeHistoryStr = '以下为'.$info['home_team_text'].'近期比赛数据{';
                foreach ($history['home'] as $home){
                    $matchStr = $this->formatVsHistory($home,$type);
                    $homeHistoryStr .= $matchStr;
                }
                $vsStr .= $homeHistoryStr.'}。';
            }
            if (!empty($history['away'])){
                $awayHistoryStr = '以下为'.$info['away_team_text'].'近期比赛数据{';
                foreach ($history['home'] as $home){
                    $matchStr = $this->formatVsHistory($home,$type);
                    $awayHistoryStr .= $matchStr;
                }
                $vsStr .= $awayHistoryStr.'}。';
            }
            $gptOrder = str_replace(['data', 'len'], [$vsStr,$order->content_len], $order->content_order);
            //将log写入日志
            $log = [
                'title' =>  $info['home_team_text'] . 'VS' . $info['away_team_text'],
                'addtime'   =>  time(),
                'competion' =>  $info['competition_text'],
                'bat_time'  =>  date('Y-m-d H:i:s',$info['match_time']),
                'gpt_order' =>  $gptOrder
            ];

            $model = self::create($log);
            return [
                'log_id'    =>  $model->id,
                'order' =>  $gptOrder,
                'match_id'  =>  $matchId[0]
            ];
        }

        return false;
    }

    private function formatVsHistory($vs,$type): string
    {
        $matchDate = date('Y年m月d日',$vs['match_time']);

        if ($type == 0){
            list($score,$half,$redCard,$yellowCard,$cornerKick,$overtime,$penaltyKick) = $vs['home_scores'];
            $homeScoresStr = $vs['home_team_text'].'在该场对阵中成绩为,总得分为'.$score.'半场得分为'.$half.'获得红牌数为'.$redCard.'获得黄牌数为'.$yellowCard.
                '角球得分为'.$cornerKick.'加时得分为'.$overtime.'点球大战得分为'.$penaltyKick.',当时队伍赛季排名为'.$vs['home_position'].'。';

            list($score,$half,$redCard,$yellowCard,$cornerKick,$overtime,$penaltyKick) = $vs['away_scores'];
            $awayScoresStr = $vs['away_team_text'].'在该场对阵中成绩为,总得分为'.$score.'半场得分为'.$half.'获得红牌数为'.$redCard.'获得黄牌数为'.$yellowCard.
                '角球得分为'.$cornerKick.'加时得分为'.$overtime.'点球大战得分为'.$penaltyKick.',当时队伍赛季排名为'.$vs['away_position'].'。';
        }else{
            $score = array_sum($vs['home_scores']);
            $homeScoresStr = $vs['home_team_text'].'在该场对阵中总得分为'.$score.',当时队伍赛季排名为'.$vs['away_position'].'。';
            $score = array_sum($vs['away_scores']);
            $awayScoresStr = $vs['away_team_text'].'在该场对阵中总得分为'.$score.',当时队伍赛季排名为'.$vs['away_position'].'。';
        }

        $matchStr = '('.$vs['competition_text'].$matchDate.$vs['home_team_text'].' VS '.$vs['away_team_text'].
            '的比赛中'.$homeScoresStr.$awayScoresStr.'),';

        return $matchStr;
    }
}