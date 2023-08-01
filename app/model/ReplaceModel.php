<?php

namespace app\model;

use think\Model;

class ReplaceModel extends Model
{

    const NEWSTYPE = 1;
    const LABELTYPE = 2;
    const MATCHFORCAST =3;

    /**
     * @param $param
     * @param $site
     * @param $limit
     * @return \think\Paginator
     * @throws \think\db\exception\DbException
     */
    public function getList($uid,$param,$site = '',$limit = 10){
        if (empty($uid)){
            return [];
        }
        $query = self::where("uid",$uid);
        if (!empty($site)){
            $query->where('site_id',$site);
        }

        return $query->paginate($limit, false, ['query' => $param]);
    }
}