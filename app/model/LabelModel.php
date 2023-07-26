<?php

namespace app\model;

use think\Model;

class LabelModel extends Model
{

    /**
     * @param $param
     * @param $site
     * @param $limit
     * @return \think\Paginator
     * @throws \think\db\exception\DbException
     */
    public function getList($param,$site = '',$limit = 10){
        $query = self::where("1 = 1");
        if (!empty($site)){
            $query->where('site_id',$site);
        }

        return $query->paginate($limit, false, ['query' => $param]);
    }
}