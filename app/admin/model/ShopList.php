<?php

declare(strict_types=1);

namespace app\admin\model;

use app\admin\model\ShopList as Shopmod;

class ShopList extends \think\Model
{
    public $name = 'store';

    /**
     * 显示店铺列表
     */
    public static function showindex()
    {
        $data = Shopmod::where('is_del', 0)->where('status', 1)->field('*')->select();
        if ($data->isEmpty()) {
            return false;
        }
        return $data;
    }

    /**
     * 显示审核拒绝店铺列表
     */
    public static function refuseindex()
    {
        $data = Shopmod::where('status', 2)->field('*')->select();
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
    //     $data = Shopmod::where('store_id', $store_id)->find();
    //     if (!$data) {
    //         return false;
    //     }
    //     return $data;
    // }

    /**
     * mod资源列表
     */
    // public static function mod($store_id, $level1, $level2)
    // {
    //     // $user = Shopmod::find($store_id);
    //     // $user->store_name = $store_name;
    //     // $user->real_name = $real_name;
    //     // if (false !== $user->save()) {
    //     //     return true;
    //     // }
    //     // return false;
    //     if (!empty($level1)) {
    //         $shop = Shopmod::where('store_id', $store_id)->find();
    //         if ($shop == null) return false;
    //         $shop->setAttr('level', $level1);
    //         // $shop->setAttr('real_name', $real_name);
    //         return $shop->save();
    //     } else {
    //         $shop = Shopmod::where('store_id', $store_id)->find();
    //         if ($shop == null) return false;
    //         $shop->setAttr('level', $level2);
    //         // $shop->setAttr('real_name', $real_name);
    //         return $shop->save();
    //     }
    // }

    /**
     * 查看详情
     */
    public static function show_details($store_id)
    {
        $data = Shopmod::where('store_id', $store_id)->find();
        if ($data->isEmpty()) {
            return false;
        }
        return $data;
    }

    /**
     * 删除门店
     */
    public static function del_shop($store_id)
    {
        // $user = Shopmod::find($store_id);
        // return $user->delete();
        $shop = Shopmod::where('store_id', $store_id)->find();
        if ($shop == null) return false;
        $shop->setAttr('is_del', 1);
        return $shop->save();
    }
}
