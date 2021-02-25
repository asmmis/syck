<?php

declare(strict_types=1);

namespace app\admin\controller;

use app\admin\model\GoodsCategory;
use think\facade\Db;
use function EasyWeChat\Kernel\data_to_array;

class Goods
{
    /**
     * 显示资源列表
     *
     */
    public function index()
    {
        $info = Db::name('goods')
            ->where('is_del', 0)
            ->field('goods_id,goods_name,price,cost,price_0,price_1,price_2,brokerage_2,brokerage_1,brokerage_bl_1
                    ,brokerage_bl_2,is_show,goods_info,keyword,goods_img,goods_imgs,goods_code,goods_video,unit_name,label_str
                    ,label_ids,add_time,is_del, is_hot, is_index, is_new, is_pink, is_special, postage')
            ->limit(20)
            ->select();
        $count = Db::name('goods')->where('is_del', 0)->count();
        $data = [
            'info' => $info,
            'count' => $count,
        ];
        if ($data) {
            return returnMsg(0, $data, '请求成功！');
        } else {
            return returnMsg(1, '请求失败！');
        }
    }

    /**
     * 显示编辑资源表单页
     */
    public function editshow()
    {
        $id = $_GET['goods_id'];
        $info = Db::name('goods')
            ->where('goods_id', $id)
            ->field('goods_id,goods_name,price,cost,price_0,price_1,price_2,brokerage_2,brokerage_1,brokerage_bl_1
                    ,brokerage_bl_2,is_show,goods_info,keyword,goods_img,goods_imgs,goods_code,goods_video,unit_name,label_str
                    ,label_ids,add_time,is_del, is_hot, is_index, is_new, is_pink, is_special,cate_id, postage')
            ->limit(20)
            ->find();
        $label = Db::name('goods_label')->select(); //标签
        $img = json_decode($info['goods_imgs']);
        $cate = Db::name('goods_category')->field('*')->where('id', $info['cate_id'])->find(); //分类id 二级

        $cate_id = Db::name('goods_category')->field('*')->where('id', $cate['pid'])->find(); //分类id 一级
        // halt($cate_id);
        $data = [
            'cate' => $cate,
            'cate_id' => $cate_id,
            'info' => $info,
            'img' => $img,
            'label' => $label,
        ];
        return returnMsg(200, $data, '请求成功！');
    }

    //显示商品一级分类
    public function showLevel()
    {
        $data = Db::name('goods_category')
            ->field('id,name,pic,pid,sort,level')
            ->where('level', 1)
            ->where('is_del', 0)
            ->select();
        return returnMsg(200, $data, '请求成功！');
    }
    //商品二级分类
    public function showLevelChild()
    {
        $id = $_POST['cate_id'];
        $data = Db::name('goods_category')
            ->field('id,name,pic,pid,sort,level')
            ->where('level', 2)
            ->where('pid', $id)
            ->where('is_del', 0)
            ->select();
        return returnMsg(200, $data, '请求成功！');
    }

    /**
     * add商品 添加推荐商品
     */
    public function add_goods()
    {
        $info = Db::name('goods')->where('is_del', 0)->where('goods_name', $_POST['goods_name'])->find();
        if ($info) {
            return returnMsg(201, '该商品已添加，请勿重复添加!');
        }
        //分类id
        $cate_id = $_POST['cate_id'];
        // halt($_POST['label_ids']);
        $data = [
            'goods_name' => addslashes($_POST['goods_name']),
            'price_1' => $_POST['price_1'],
            'price_0' => floatval($_POST['price_0']),
            'price_2' => $_POST['price_2'],
            'is_show' => $_POST['is_show'],
            'brokerage_1' => $_POST['brokerage_1'],
            'brokerage_2' => $_POST['brokerage_2'],
            'brokerage_bl_1' => $_POST['brokerage_bl_1'],
            'brokerage_bl_2' => $_POST['brokerage_bl_2'],
            'goods_info' => $_POST['goods_info'],
            'keyword' => $_POST['keyword'],
            'label_ids' => $_POST['label_ids'],
            'cate_id' => $cate_id,
            'goods_img' => $_POST['image_arr'][0]['img'],
            'goods_imgs' => json_encode($_POST['image_arr']),
            'add_time' => time(),
            'is_del' => 0,
        ];
        //标签
        $label_str = "";
        $ids = explode(",", $data['label_ids']);
        foreach ($ids as $id) {
            if ($id == "")
                continue;
            $label_str .= \app\admin\model\GoodsLabel::getLabelById($id)->getAttr("name") . ',';
        }
        $data['label_str'] = $label_str;
        // 启动事务
        Db::startTrans();
        try {
            Db::name('goods')->insert($data);
            $infoId = Db::name('goods')->getLastInsID();
            $data_attr_sku = [
                'goods_id' => $infoId,
                'price_1' => $_POST['price_1'],
                'price_0' => floatval($_POST['price_0']),
                'price_2' => $_POST['price_2'],
                'brokerage_1' => $_POST['brokerage_1'],
                'brokerage_2' => $_POST['brokerage_2'],
                'goods_code' => '未添加',
                'cost' => '成本价',
            ];
            Db::name('goods_sku')->insert($data_attr_sku); //商品属性详细表SKU
            // $lab_arr = [
            //     'name' => $_POST['label_str'],
            //     'add_time' => time(),
            // ];
            // Db::name('goods_label')->insert($lab_arr); //商品标签
            // $labId = Db::name('goods_label')->getLastInsID();
            // $goods_labids = ['label_ids' => $labId,];
            // Db::name('goods')->where('goods_id', $infoId)->update($goods_labids);
            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            // halt($e);
            // 回滚事务
            Db::rollback();
            return returnMsg(201, '提交失败！');
        }
        return returnMsg(200, '提交成功！');
    }

    /**
     * 保存更新的资源
     */
    public function mod_goods()
    {
        $imgs_arr = json_decode($_POST['image_arr'], true);
        $img = $imgs_arr[0]['img'];
        $data = [
            'goods_name' => $_POST['goods_name'],
            'price_0' => $_POST['price_0'],
            'price_1' => $_POST['price_1'],
            'price_2' => $_POST['price_2'],
            'brokerage_1' => $_POST['brokerage_1'],
            'brokerage_2' => $_POST['brokerage_2'],
            'brokerage_1' => $_POST['brokerage_1'],
            'brokerage_2' => $_POST['brokerage_2'],
            'goods_info' => $_POST['goods_info'],
            'keyword' => $_POST['keyword'],
            'goods_img' => $img,
            'goods_imgs' => $imgs_arr,
            // 'is_show' => $_POST['is_show'],
        ];
        $res = Db::name('goods')->where('goods_id', $_POST['goods_id'])->update($data);
        if ($res) {
            return returnMsg(200, '提交成功！');
        } else {
            return returnMsg(201, '提交失败！');
        }
    }

    /**
     * 保存更新的商品详情资源
     */
    public function mod_goods_details()
    {
        $data = [
            'is_hot' => intval($_POST['is_hot']),
            'is_index' => intval($_POST['is_index']),
            'is_new' => intval($_POST['is_new']),
            'is_pink' => intval($_POST['is_pink']),
            'is_special' => intval($_POST['is_special']),
            'postage' => $_POST['postage'],
        ];
        $res = Db::name('goods')->where('goods_id', $_POST['goods_id'])->update($data);
        if ($res) {
            return returnMsg(200, '提交成功！');
        } else {
            return returnMsg(201, '提交失败！');
        }
    }

    /**
     * 商品展示状态
     */
    public function change()
    {
        $info = Db::name('goods')->where('goods_id', $_POST['goods_id'])->find();
        if ($info['is_show'] == 1) {
            $data = ['is_show' => 0];
            $res = Db::name('goods')->where('goods_id', $_POST['goods_id'])->update($data);
        } elseif ($info['is_show'] == 0) {
            $data1 = ['is_show' => 1];
            $res = Db::name('goods')->where('goods_id', $_POST['goods_id'])->update($data1);
        }
        if ($res) {
            return returnMsg(200, '提交成功！');
        } else {
            return returnMsg(201, '提交失败！');
        }
    }

    /**
     * 图片上传 所有商品图片保存在goods_imgs
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
        $pic_path = $_SERVER['DOCUMENT_ROOT'] . "/upload/images/goods_imgs/";
        if (is_dir($pic_path) && !file_exists($pic_path))
            mkdir($pic_path, 0777, true);
        $pic_path = "upload/images/goods_imgs/" . $pics;
        //移动到指定目录，上传图片
        $res = move_uploaded_file($img['upload_file0']['tmp_name'], $pic_path);
        if ($res) {
            return returnMsg(200, '/' . $pic_path, '上传成功！');
        } else {
            return returnMsg(201, '上传失败！');
        }
    }

    /**
     * 接收商品详情入库 可添加图片
     */
    public function UploadImgs()
    {
        if ($_POST['txt'] == null) {
            return returnMsg(201, '请输入要提交的内容~~！');
        }
        $img_arr = $_POST['txt'];
        $data = [
            'goods_id' => $_POST['id'],
            'description' => $img_arr,
        ];
        $res = Db::name('goods_description')->insert($data);
        if ($res) {
            return returnMsg(200, '添加成功！');
        } else {
            return returnMsg(201, '网络繁忙，请稍后再试！');
        }
    }

    /**
     * 删除指定资源
     */
    public function del()
    {
        $info = Db::name('goods')->where('goods_id', $_POST['goods_id'])->find();
        if ($info['is_show'] == 1) {
            return returnMsg(201, '上架商品不能删除，请先下架改商品!');
        }
        // , 'is_show' => 0
        $data = ['is_del' => 1];
        $res = Db::name('goods')->where('goods_id', $_POST['goods_id'])->update($data);
        if ($res) {
            return returnMsg(200, '删除成功！');
        } else {
            return returnMsg(201, '网络繁忙,请稍后再试！');
        }
    }

    /**
     * 商品分类列表显示
     */
    public function sortlist()
    {
        $info = Db::name('goods_category')
            ->where('is_del', 0)
            ->field('id,pid,name,is_show,pic,add_time')
            ->select()->toArray();
        for ($i = 0; $i < count($info); $i++) {
            $parent = GoodsCategory::getParentCategory($info[$i]);
            $info[$i]['parent_name'] = ($parent) ? $parent->getAttr('name') : "";
        }
        $count = Db::name('goods_category')
            ->where('is_del', 0)->count();
        $data = [
            'info' => $info,
            'count' => $count
        ];
        if ($data) {
            return returnMsg(200, $data, '请求成功！');
        } else {
            return returnMsg(201, '请求失败！');
        }
    }

    /**
     * 商品分类编辑显示
     */
    public function sort_list($goods_id)
    {
        $data = Db::name('goods_category')
            ->where('is_del', 0)
            ->where('id', $goods_id)
            ->field('id,pid,name,is_show,pic,add_time')
            ->find();
        if ($data) {
            return returnMsg(200, $data, '请求成功！');
        } else {
            return returnMsg(201, '请求失败！');
        }
    }

    /**
     * 添加商品分类显示一级
     */
    public function goods_sort()
    {
        //服务分类
        $info = Db::name('goods_category')
            ->field('id,level,name,id,pid,sort,add_time,pic')
            ->where('is_del', 0)
            ->where('level', 1)
            ->order('sort', 'asc')
            ->select();
        $data = [
            'info' => $info,
        ];
        return returnMsg(200, $data, '请求成功！');
    }

    /**
     * 添加商品分类
     */
    public function add_sort($pid)
    {
        // halt($pid);
        $info = Db::name('goods_category')->where('name', $_POST['name'])->find();
        if ($info) {
            return returnMsg(200, '已成功添加!');
        }
        if (!empty($pid)) {
            $data['level'] = 2;
            $data['pid'] = $pid;
        }
        $pic = str_replace('"', '', $_POST['image_arr']);
        $data['name'] = $_POST['name'];
        $data['is_show'] = $_POST['is_show'];
        $data['pic'] = $pic;
        $data['add_time'] = time();

        $res = Db::name('goods_category')->insert($data);
        if ($res) {
            return returnMsg(200, '添加成功！');
        } else {
            return returnMsg(201, '添加失败！');
        }
    }

    /**
     * 编辑商品分类
     */
    public function mod_sort()
    {
        $img = $_POST['image_arr'][0]['img'];
        // var_dump($img);
        $data = [
            'pic' => $img,
            'name' => $_POST['name'],
            'is_show' => $_POST['is_show'],
        ];
        $res = Db::name('goods_category')->where('id', $_POST['id'])->update($data);
        if ($res) {
            return returnMsg(200, '修改成功！');
        } else {
            return returnMsg(201, '修改失败！');
        }
    }

    /**
     * 分类标签
     */
    public function sort_change()
    {
        $info = Db::name('goods_category')->where('id', $_POST['id'])->find();
        if ($info['is_show'] == 1) {
            $data = ['is_show' => 0];
            $res = Db::name('goods_category')->where('id', $_POST['id'])->update($data);
        } elseif ($info['is_show'] == 0) {
            $data1 = ['is_show' => 1];
            $res = Db::name('goods_category')->where('id', $_POST['id'])->update($data1);
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
    public function sortdel()
    {
        $data = ['is_del' => 1];
        $res = Db::name('goods_category')->where('id', $_POST['id'])->update($data);
        if ($res) {
            return returnMsg(200, '删除成功！');
        } else {
            return returnMsg(201, '网络繁忙,请稍后再试！');
        }
    }

    /**
     * 商品标签
     */
    public function label()
    {

        $info = Db::name('goods_label')->where('is_del', 0)->field('id,name,add_time,is_show')->select();
        $count = Db::name('goods_label')->where('is_del', 0)->count();
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
     * 添加商品标签
     */
    public function add_label()
    {
        $data = [
            'name' => $_POST['name'],
            'add_time' => time(),
        ];
        $res = Db::name('goods_label')->insert($data);
        if ($res) {
            return returnMsg(200, '添加成功！');
        } else {
            return returnMsg(201, '添加失败！');
        }
    }

    /**
     * 添加商品标签
     */
    public function mod_label()
    {
        $data = [
            'name' => $_POST['name'],
            // 'is_show' => $_POST['is_show'],
        ];
        $res = Db::name('goods_label')->where('id', $_POST['id'])->update($data);
        if ($res) {
            return returnMsg(200, '修改成功！');
        } else {
            return returnMsg(201, '修改失败！');
        }
    }

    /**
     * 标签
     */
    public function label_change()
    {
        $info = Db::name('goods_label')->where('id', $_POST['goods_id'])->find();
        if ($info['is_show'] == 1) {
            $data = ['is_show' => 0];
            $res = Db::name('goods_label')->where('id', $_POST['goods_id'])->update($data);
        } elseif ($info['is_show'] == 0) {
            $data1 = ['is_show' => 1];
            $res = Db::name('goods_label')->where('id', $_POST['goods_id'])->update($data1);
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
    public function del_label()
    {
        $data = ['is_del' => 1];
        $res = Db::name('goods_label')->where('id', $_POST['goods_id'])->update($data);
        if ($res) {
            return returnMsg(200, '删除成功！');
        } else {
            return returnMsg(201, '网络繁忙,请稍后再试！');
        }
    }

    /**
     * 商品标签
     */
    public function rulelist()
    {

        $data = Db::name('goods_rule')->field('*')->limit(20)->select();
        if ($data) {
            return returnMsg(200, $data, '请求成功！');
        } else {
            return returnMsg(201, '请求失败！');
        }
    }

    /**
     * 商品标签
     */
    public function rule()
    {
        header('Access-Control-Allow-Origin: *');
        $data = Db::name('goods_rule')->where('id', $_GET['id'])->find();
        if ($data) {
            return returnMsg(200, $data, '请求成功！');
        } else {
            return returnMsg(201, '请求失败！');
        }
    }

    /**
     * 添加商品标签
     */
    public function add_rule()
    {
        $data = [
            'rule_vale' => $_POST['rule_vale'],
            'rule_name' => $_POST['rule_name'],
        ];
        $res = Db::name('goods_rule')->insert($data);
        if ($res) {
            return returnMsg(200, '添加成功！');
        } else {
            return returnMsg(201, '添加失败！');
        }
    }

    /**
     * 添加商品标签
     */
    public function mod_rule()
    {
        $data = [
            'rule_vale' => $_POST['rule_vale'],
            'rule_name' => $_POST['rule_name'],
        ];
        $res = Db::name('goods_rule')->where('id', $_POST['id'])->update($data);
        if ($res) {
            return returnMsg(200, '修改成功！');
        } else {
            return returnMsg(201, '修改失败！');
        }
    }


    /**
     * 商品推荐
     */
    public function recommend()
    {
        $data = Db::name('goods')->where('is_del', 0)->where('is_index', 1)->field('*')->limit(20)->select();
        if ($data) {
            return returnMsg(200, $data, '请求成功！');
        } else {
            return returnMsg(201, '请求失败！');
        }
    }

    /**
     * 商品推荐
     */
    public function mod_recommend()
    {
        $data = [
            'goods_name' => $_POST['goods_name'],
            'price_0' => $_POST['price_0'],
            'price_1' => $_POST['price_1'],
            'price_2' => $_POST['price_2'],
            'brokerage_daiyan' => $_POST['brokerage_daiyan'],
            'brokerage_hehuo' => $_POST['brokerage_hehuo'],
            'goods_info' => $_POST['goods_info'],
            'keyword' => $_POST['keyword'],
            // 'is_show' => $_POST['is_show'],
        ];
        $res = Db::name('goods')->where('goods_id', $_POST['goods_id'])->update($data);
        if ($res == true) {
            return returnMsg(0, '修改成功');
        } else {
            return returnMsg(1, '修改失败！');
        }
    }
}
