<?php

declare(strict_types=1);

namespace app\admin\model;

use app\admin\model\TixianYongjin as Tixian;

class TixianYongjin extends \think\Model
{
    public $name = 'user_brokerage_withdraw';

    /**
     * 显示佣金提现列表
     */
    public static function showlist()
    {
        $data = Tixian::select();
        if ($data->isEmpty()) {
            return false;
        }
        return $data;
    }

   





    /**
     * 显示mod列表
     */
    // public static function show($store_id)
    // {
    //     $data = Tixian::where('store_id', $store_id)->find();
    //     if ($data->isEmpty()) {
    //         return false;
    //     }
    //     return $data;
    // }

    /**
     * mod资源列表
     */
    // public static function mod($store_id, $store_name, $real_name)
    // {
    //     // $user = Tixian::find($store_id);
    //     // $user->store_name = $store_name;
    //     // $user->real_name = $real_name;
    //     // if (false !== $user->save()) {
    //     //     return true;
    //     // }
    //     // return false;
    //     $shop = Tixian::where('store_id', $store_id)->find();
    //     if ($shop == null) return false;
    //     $shop->setAttr('store_name', $store_name);
    //     $shop->setAttr('real_name', $real_name);
    //     return $shop->save();
    // }              

    /**
     * 查看详情
     */
    // public static function show_details($store_id)
    // {
    //     $data = Tixian::where('store_id', $store_id)->find();
    //     if ($data->isEmpty()) {
    //         return false;
    //     }
    //     return $data;
    // }

    /**
     * 删除门店
     */
    // public static function del_shop($store_id)
    // {
    //     // $user = Tixian::find($store_id);
    //     // return $user->delete();
    //     $shop = Tixian::where('store_id', $store_id)->find();
    //     if ($shop == null) return false;
    //     $shop->setAttr('is_del', 1);
    //     return $shop->save();
    // }
    
}
