<?php

declare(strict_types=1);

namespace app\admin\model;

use app\admin\model\TixianBalance as TxBalance;

class TixianBalance extends \think\Model
{
    public $name = 'user_money_withdraw';

    /**
     * 显示余额提现列表
     */
    public static function showlist()
    {
        $data = TxBalance::select();
        if ($data->isEmpty()) {
            return false;
        }
        return $data;
    }





    
}
