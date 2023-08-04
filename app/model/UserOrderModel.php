<?php

namespace app\model;

use think\Model;

class UserOrderModel extends Model
{
    protected $name = 'user_order';
    public function getByUser($user_id = 0,$type = 1){
        if (empty($user_id)){
            return [];
        }

        return self::where(['uid' => $user_id,'type'=>$type])->findOrEmpty();
    }

    public function getByType($type = 1){
        return self::where('type',$type)->findOrEmpty();
    }
}