<?php

namespace app\model;

use think\Model;

class UserOrderModel extends Model
{
    public function getByUser($user_id = 0,$type = 1){
        if (empty($user_id)){
            return [];
        }

        return self::where(['user_id' => $user_id,'type'=>$type])->find();
    }
}