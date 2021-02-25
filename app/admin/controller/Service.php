<?php

declare(strict_types=1);

namespace app\admin\controller;


use app\BaseController;
use think\facade\Db;
use app\business\controller\Store;

use think\Controller;
use \PhpOffice\PhpSpreadsheet\IOFactory;



class Service extends BaseController
{
    /**
     * 显示服务列表
     *
     */
    public function index($page)
    {
        $info = Db::name('service')
            ->where('is_del', 0)
            ->field('*')
            ->limit(max(0, ($page - 1) * 20), 20)
            ->select();
        $count = Db::name('service')->where('is_del', 0)->count();
        $data = [
            'info' => $info,
            'count' => $count,
        ];
        if ($data) {
            return returnMsg(200, $data, '请求成功！');
        } else {
            return returnMsg(201, '请求失败！');
        }
    }

    /**
     *添加服务
     */
    public function add_service()
    {
        $data = [
            'name' => addslashes($_POST['name']),
            'price_1' => $_POST['price_1'],
            'info' => '',
            'price_0' => floatval($_POST['price_0']),
            'price_2' => $_POST['price_2'],
            'is_show' => $_POST['is_show'],
            'kerword' => $_POST['kerword'],
            'img' => $_POST['image_arr'][0]['img'],
            'add_time' => time(),
            'is_del' => 0,
        ];
        $res = Db::name('service')->insert($data);
        if ($res) {
            return returnMsg(200, $res, '请求成功！');
        } else {
            return returnMsg(201, '请求失败！');
        }
    }

    /**
     * 上传服务图标
     */
    public function uploadImg()
    {
        header('Access-Control-Allow-Origin: *');

        $img = $_FILES;
        //获取上图片后缀
        $type = strstr($img['upload_file0']['name'], '.');
        $rand = rand(1000, 9999);
        //命名图片名称
        $pics = date("YmdHis") . $rand . $type;
        //上传路径
        $pic_path = "upload/images/service_img/" . $pics;
        //移动到指定目录，上传图片
        $res = move_uploaded_file($img['upload_file0']['tmp_name'], $pic_path);
        if ($res) {
            return returnMsg(200, $pic_path, '上传成功！');
        } else {
            return returnMsg(201, '上传失败！');
        }
    }


    /**
     * 显示编辑表单页
     */
    public function editshow()
    {
        $id = $_GET['id'];
        // var_dump($id);
        $info = Db::name('service')->where('id', $id)->field('*')->find();
        return returnMsg(200, $info, '请求成功！');
    }

    /**
     * 编辑服务列表
     */
    public function mod_service()
    {
        $data = [
            'name' => $_POST['name'],
            // 'kerword' => $_POST['kerword'],
            'img' => $_POST['img_arr'][0]['img'],
            'is_show' => $_POST['is_show'],
        ];
        $res = Db::name('service')->where('id', $_POST['id'])->update($data);
        if ($res) {
            return returnMsg(200, '提交成功！');
        } else {
            return returnMsg(201, '提交失败！');
        }
    }

    /**
     * 服务上下架状态
     */
    public function change()
    {
        $info = Db::name('service')->where('id', $_POST['id'])->find();
        if ($info['is_show'] == 1) {
            $data = ['is_show' => 0];
            $res = Db::name('service')->where('id', $_POST['id'])->update($data);
        } elseif ($info['is_show'] == 0) {
            $data1 = ['is_show' => 1];
            $res = Db::name('service')->where('id', $_POST['id'])->update($data1);
        }
        if ($res) {
            return returnMsg(200, '提交成功！');
        } else {
            return returnMsg(201, '提交失败！');
        }
    }

    /**
     * 删除指定资源
     */
    public function del()
    {
        $data = ['is_del' => 1, 'is_show' => 0];
        $res = Db::name('service')->where('id', $_POST['id'])->update($data);
        if ($res) {
            return returnMsg(200, '删除成功！');
        } else {
            return returnMsg(201, '网络繁忙,请稍后再试！');
        }
    }

    /**
     * 服务
     */
    public function service_level($level)
    {
        $info = Db::name('service_category_store')
            ->where('level', $level)
            ->field('id,level,name,id,pid,path,add_time')
            ->order('path', 'asc')
            ->select();
        $data = [
            'info' => $info,
        ];
        return returnMsg(200, $data, '请求成功！');
    }



    /**
     * 显示服务分类
     */
    public function service_class($id)
    {
        $store = Db::name('store')->where('is_del', 0)->where('status', 1)->field('*')->select();
        $info = Db::name('service_category')
            ->field('id,level,name,id,pid,pic,path,add_time')
            ->where('is_del', 0)
            ->where('id', $id)
            ->find();
        $data = [
            'store' => $store,
            'info' => $info,
        ];
        return returnMsg(200, $data, '请求成功！');
    }


    /**
     * 显示服务分类
     */
    public function service_class1()
    {
        $store = Db::name('store')->where('is_del', 0)->where('status', 1)->field('*')->select();
        //服务分类
        $info = Db::name('service_category')
            ->field('id,level,name,id,pid,path,add_time,pic')
            ->where('is_del', 0)
            // ->where('level', 1)
            ->order('path', 'asc')
            ->select()
            ->each(function ($item, $key) {
                $str = '';
                // for ($i = 2; $i <= $item['level']; $i++) {
                //     $str .= '| —';
                // }
                $item['name'] = $str . $item['name'];
                return $item;
            });
        $data = [
            'store' => $store,
            'info' => $info,
        ];
        return returnMsg(200, $data, '请求成功！');
    }

    /**
     * 医院服务审核
     */
    public function exam_service()
    {
        $data = Db::name('service')
            ->where('status', 2)
            ->field('*')
            ->select();
        return returnMsg(200, $data, '请求成功！');
    }

    /**
     * 审核通过
     */
    public function pass()
    {
        // 启动事务
        Db::startTrans();
        try {
            $data = ['status' => 1, 'is_show' => 1, 'audio_time' => time()];
            Db::name('service')->where('id', $_POST['id'])->update($data);
            $s_store = Db::name('service')->where('id', $_POST['id'])->find();
            $admin_id = session('userid');
            $admin_name = session('admin_name');
            // halt($admin_name);
            $log_arr = [
                'store_id' => $s_store['store_id'],
                'typeid' => 3,
                'c_typeid' => 1,
                'service_id' => $s_store['id'],
                'c_id' => $admin_id,
                'content' => $admin_name . '在' . date('Y-m-d h:m:s', time()) . '审核通过了 ' . $s_store['name'] . ' 该服务！',
                'add_time' => time(),
            ];
            // halt($log_arr);
            Db::name('service_log')->insert($log_arr);
            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return returnMsg(201, '网络繁忙,请稍后再试!');
        }
        return returnMsg(200, '审核通过！');
    }

    /**
     * 医院删除服务列表
     */
    public function del_service_list()
    {
        $info = Db::name('service')
            ->where('status', 1)
            ->where('is_del', 1)
            ->where('is_show', 0)
            ->field('*')
            ->select();
        $count = Db::name('service')
            ->where('status', 1)
            ->where('is_del', 1)
            ->where('is_show', 0)
            ->count();
        $data = [
            'info' => $info,
            'count' => $count,
        ];
        return returnMsg(200, $data, '请求成功！');
    }

    /**
     * 审核拒绝
     */
    public function refuse()
    {

        $data1 = ['status' => 3, 'is_show' => 0, 'audio_time' => time()];
        $res = Db::name('service')->where('id', $_POST['id'])->update($data1);
        if ($res) {
            return returnMsg(200, $res, '拒绝审核！');
        } else {
            return returnMsg(201, '网络繁忙,请稍后再试!');
        }
    }


    /**
     * 添加服务分类
     */
    public function add_class()
    {
        $data = [];
        $data['pid'] = $_POST['pid'];
        $data['name'] = $_POST['name'];
        $data['pic'] = $_POST['img_arr'][0]['img'];
        $data['add_time'] = time();
        $data['level'] = 1;
        $path = '';
        if ($_POST['pid']) {
            //选择了上级分类
            $find =  Db::name('service_category')->where(['id' => $_POST['pid']])->find();
            $data['level'] = $find['level'] + 1;
            $path = $find['path'];
        }
        //不允许添加重复
        $category_data = Db::name('service_category')->where(['name' => $_POST['name']])->find();
        if (!empty($category_data)) {
            return returnMsg(201, '该服务已添加了，请勿重复添加!');
        }
        $new_id = Db::name('service_category')->insertGetId($data);
        $new_path = $path . $new_id . ',';
        $res = Db::name('service_category')->where(['id' => $new_id])->update(['path' => $new_path]);
        if ($res != false) {
            return returnMsg(200, '添加成功');
        } else {
            return returnMsg(201, '网络繁忙,请稍后再试！');
        }
    }

    /**
     * mod服务分类
     */
    public function mod_class()
    {
        $pic = $_POST['image_arr'];
        foreach ($pic as $v) {
            $data = [
                'pic' => '/' . $v['img'],
                'name' => $_POST['name'],
                'edit_time' => time(),
            ];
            // halt($data);
            $res = Db::name('service_category')->where('id', $_POST['id'])->update($data);
            if ($res) {
                return returnMsg(200, '修改成功');
            } else {
                return returnMsg(201, '网络繁忙,请稍后再试！');
            }
        }
    }

    /**
     * 删除指定资源
     */
    public function del_class()
    {
        $data = ['is_del' => 1];
        $res = Db::name('service_category')->where('id', $_POST['id'])->update($data);
        if ($res) {
            return returnMsg(200, '删除成功！');
        } else {
            return returnMsg(201, '网络繁忙,请稍后再试！');
        }
    }

    /**
     * 删除指定资源 (没有删除字段) 服务订单
     */
    public function order_list()
    {
        $info = Db::name('service_order')
            ->where('is_del', 0)
            ->field('id,order_sn,uid,user_type,real_name,phone,total_price,create_time,status,pay_status,pay_type,pay_time,is_return,return_time')
            ->select();
        $count = Db::name('service_order')->count();
        $data = [
            'info' => $info,
            'count' => $count,
        ];
        if ($data) {
            return returnMsg(200, $data, '请求成功');
        } else {
            return returnMsg(201, '网络繁忙,请稍后再试！');
        }
    }

    /**
     * 服务轮播图 显示所有门店
     */
    // public function stores_banner()
    // {
    //     $info = Db::name('store')
    //         ->where('is_del', 0)
    //         ->where('status', 1)
    //         ->field('store_id,store_name,keyword,store_img,real_name,phone,province_id,city_id,district_id,addr,
    //                  address,info,idcard_a,idcard_b,business_license,license,is_del,longitude,latitude,week_time,day_time,other_img,is_index')
    //         ->select();
    //     $count = Db::name('store')->where('is_del', 0)->count();
    //     $data = [
    //         'info' => $info,
    //         'count' => $count,
    //     ];
    //     if ($data) return returnMsg(200, $data, '请求成功!');
    //     return returnMsg(201, $data, '失败!');
    // }

    /**
     * 添加服务轮播图
     */
    // public function serviceimg($service_img, $store_id)
    // {
    //     $imageSrc_a = $this->getpics($service_img);
    //     $data = [
    //         'banner' => $imageSrc_a,
    //         'store_id' => $store_id,
    //         'add_time' => time()
    //     ];
    //     $res = Db::name('service_banner')->insert($data);
    //     if ($res) {
    //         return returnMsg(200, $data, '请求成功');
    //     } else {
    //         return returnMsg(201, '网络繁忙,请稍后再试！');
    //     }
    // }

    /**
     * 查看服务轮播图
     */
    // public function service_show($store_id)
    // {
    //     $data = Db::name('service_banner')->where('is_del', 0)->where('store_id', $store_id)->select();
    //     if ($data) {
    //         return returnMsg(200, $data, '请求成功');
    //     } else {
    //         return returnMsg(201, '网络繁忙,请稍后再试！');
    //     }
    // }

    /**
     * 保存所有轮播图图片 all_banner
     */
    // public function getpics($img)
    // {
    //     //设置图片名称
    //     $imageName = "25220_" . date("His", time()) . "_" . rand(1111, 9999) . '.png';
    //     //判断是否有逗号 如果有就截取后半部分
    //     if (strstr($img, ",")) {
    //         $img = explode(',', $img);
    //         $img = $img[1];
    //     }
    //     //设置图片保存路径
    //     $path = "./upload/images/service/all_banner" . date("Ymd", time());

    //     //判断目录是否存在 不存在就创建
    //     if (!is_dir($path)) {
    //         mkdir($path, 0777, true);
    //     }
    //     //图片路径
    //     $imageSrc = $path . "/" . $imageName;

    //     //生成文件夹和图片
    //     $r = file_put_contents($imageSrc, base64_decode($img));
    //     $newsrc = ltrim($imageSrc, '.');
    //     return $newsrc;
    // }

    /**
     * 佣金比例
     */
    public function import()
    {
        // 有Xls和Xlsx格式两种
        $objReader = IOFactory::createReader('Xlsx');

        if (empty($objReader)) {
            $objReader = IOFactory::createReader('Xls');
            $file = request()->file('file');
        }
        $file = request()->file('file');
        // $filename = $_FILES['myfile']['tmp_name'];
        $objPHPExcel = $objReader->load($file);  //$filename可以是上传的表格，或者是指定的表格
        $sheet = $objPHPExcel->getSheet(0);   //excel中的第一张sheet
        $highestRow = $sheet->getHighestRow();       // 取得总行数
        // $highestColumn = $sheet->getHighestColumn();   // 取得总列数

        $arr = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z');
        // 一次读取一列  
        $res_arr = array();
        for ($row = 3; $row <= $highestRow; $row++) {
            $row_arr = array();
            for ($column = 0; $arr[$column] != 'H'; $column++) {
                $val = $sheet->getCellByColumnAndRow($column, $row)->getValue();
                $row_arr[] = $val;
            }
            $res_arr[] = $row_arr;
        }

        // halt($res_arr);

        foreach ($res_arr as $v) {
            if ($v[3] + $v[4] + $v[5] + $v[6] != 1) {
                continue;
            }
            $info = [
                'unique' => $v[1],
                'name' => $v[2],
                'bl_store' => $v[3],
                'bl_daiyan' => $v[4],
                'bl_hehuo' => $v[5],
                'bl_pingtai' => $v[6],
                'add_time' => time(),
            ];
            $res = Db::name('service_bl')->insert($info);
        }
        if ($res) {
            return returnMsg(200, '上传成功！');
        } else {
            return returnMsg(201, '上传失败！');
        }
    }
}
