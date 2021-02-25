<?php

declare(strict_types=1);

namespace app\admin\controller;

use think\Request;
use think\facade\Db;

class Other
{
    /**
     * 显示所有服务轮播图
     */
    public function service_banner()
    {
        $s_info = Db::name('service_banner') //服务轮播
            ->field('id,banner,is_show,is_del,add_time,is_type')
            ->where('is_del', 0)
            ->select()->toArray();

        $s_count = Db::name('service_banner') //
            ->where('is_del', 0)
            ->count();

        $g_info = Db::name('goods_banner') //商品轮播
            ->field('id,banner,is_show,is_del,add_time,is_type')
            ->where('is_del', 0)
            ->select()->toArray();

        $g_count = Db::name('goods_banner') //
            ->where('is_del', 0)
            ->count();

        $count = $s_count + $g_count;
        $info = array_merge($s_info, $g_info);
        $data = [
            'count' => $count,
            'info' => $info,
        ];
        if ($data) return returnMsg(200, $data, '请求成功!');
        return returnMsg(201, $data, '失败!');
    }

    /**
     * 显示所有门店
     */
    public function show_index()
    {
        $data = Db::name('store')
            ->field('store_id,store_name,is_del,status')
            ->where('is_del', 0)
            ->where('status', 1)
            ->select();
        if ($data) return returnMsg(200, $data, '请求成功!');
        return returnMsg(201, $data, '失败!');
    }


    /**
     * 添加服务轮播图
     */
    public function add_service_img($service_img, $id, $is_show)
    {
        $imageSrc_a = $this->getpics($service_img);
        $data = [
            'banner' => $imageSrc_a,
            'store_id' => $id,
            'is_show' => $is_show,
            'add_time' => time()
        ];
        $res = Db::name('service_banner')->insert($data);
        if ($res) {
            return returnMsg(200, $data, '请求成功');
        } else {
            return returnMsg(201, '网络繁忙,请稍后再试！');
        }
    }

    /**
     * 显示所有商品
     */
    public function show_goods()
    {
        $data = Db::name('goods')
            ->field('goods_id,goods_name,is_del')
            ->where('is_del', 0)
            ->select();
        if ($data) return returnMsg(200, $data, '请求成功!');
        return returnMsg(201, $data, '失败!');
    }

    /**
     * 添加商品轮播图
     */
    public function add_goods_img($goods_img, $id, $is_show)
    {
        $imageSrc_a = $this->getpics($goods_img);
        $data = [
            'banner' => $imageSrc_a,
            'goods_id' => $id,
            'is_show' => $is_show,
            'add_time' => time()
        ];
        $res = Db::name('goods_banner')->insert($data);
        if ($res) {
            return returnMsg(200, $data, '请求成功');
        } else {
            return returnMsg(201, '网络繁忙,请稍后再试！');
        }
    }


    /**
     * 编辑店铺商品或服务轮播图
     */
    public function mod_img($goods_img, $id, $is_show)
    {
        $imageSrc_a = $this->getpics($goods_img);
        $data = [
            'banner' => $imageSrc_a,
            'goods_id' => $id,
            'is_show' => $is_show,
            'add_time' => time()
        ];
        $res = Db::name('goods_banner')->insert($data);
        if ($res) {
            return returnMsg(200, $data, '请求成功');
        } else {
            return returnMsg(201, '网络繁忙,请稍后再试！');
        }
    }


    /**
     * 编辑时显示轮播图信息
     */
    public function show_banner($id)
    {
        $s_info = Db::name('service_banner') //服务轮播
            ->field('id,banner,is_show,is_del,add_time,is_type')
            ->where('id', $id)
            ->where('is_del', 0)
            ->select()->toArray();

        $g_info = Db::name('goods_banner') //商品轮播
            ->field('id,banner,is_show,is_del,add_time,is_type')
            ->where('id', $id)
            ->where('is_del', 0)
            ->select()->toArray();

        if (!$s_info && $g_info) {
            $data = [];
            $data = $g_info;
        } elseif ($s_info && !$g_info) {
            $data = [];
            $data = $s_info;
        }
        if ($data) return returnMsg(200, $data, '请求成功!');
        return returnMsg(201, $data, '失败!');
    }


    /**
     * 编辑时显示轮播图信息
     */
    public function mod_banner($id, $image_arr)
    {
        $data = ['banner' => $image_arr[0]['img']];
        // halt($image_arr);
        $res = Db::name('service_banner')->where('id', $id)->update($data);
        if (!$res) {
            $res = Db::name('goods_banner')->where('id', $id)->update($data);
        }
        if ($res) return returnMsg(200, '修改成功!');
        return returnMsg(201, $data, '失败!');
    }

    /**
     * 改变上下架状态
     */
    public function change($id)
    {
        $info = Db::name('service_banner')->where('id', $id)->find();
        if (!$info) {
            $info = Db::name('service_banner')->where('id', $id)->find();
        }
        if ($info['is_show'] == 1) {
            $data = ['is_show' => 0];
            $res = Db::name('service_banner')->where('id', $id)->update($data);
            if (!$res) {
                $res = Db::name('goods_banner')->where('id', $id)->update($data);
            }
            if ($res) return returnMsg(200, '修改成功!');
            return returnMsg(201, $data, '失败!');
        } else if ($info['is_show'] == 0) {
            $data = ['is_show' => 1];
            $res = Db::name('service_banner')->where('id', $id)->update($data);
            if (!$res) {
                $res = Db::name('goods_banner')->where('id', $id)->update($data);
            }
            if ($res) return returnMsg(200, '修改成功!');
            return returnMsg(201, $data, '失败!');
        }
    }


    /**
     * 删除一条记录
     */
    public function del_banner($id)
    {
        $data = ['is_del' => 1, 'is_show' => 0];
        $res = Db::name('service_banner')->where('id', $id)->update($data);
        if (!$res) {
            $res = Db::name('goods_banner')->where('id', $id)->update($data);
        }
        if ($res) return returnMsg(200, '刪除成功!');
        return returnMsg(201, $data, '失败!');
    }


    /**
     * 保存所有轮播图图片 all_banner
     */
    public function getpics($img)
    {
        //设置图片名称
        $imageName = "25220_" . date("His", time()) . "_" . rand(1111, 9999) . '.png';
        //判断是否有逗号 如果有就截取后半部分
        if (strstr($img, ",")) {
            $img = explode(',', $img);
            $img = $img[1];
        }
        //设置图片保存路径
        $path = "./upload/images/all_banner/all_banner" . date("Ymd", time());

        //判断目录是否存在 不存在就创建
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }
        //图片路径
        $imageSrc = $path . "/" . $imageName;

        //生成文件夹和图片
        $r = file_put_contents($imageSrc, base64_decode($img));
        $newsrc = ltrim($imageSrc, '.');
        return $newsrc;
    }
}
