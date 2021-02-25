<?php

declare(strict_types=1);

namespace app\admin\controller;


use app\admin\model\ShopList;
use app\BaseController;
use think\facade\Db;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use \PhpOffice\PhpSpreadsheet\IOFactory;

class Shop extends BaseController
{
    /**
     * 显示店铺列表
     */
    public function index($page)
    {
        $info = Db::name('store')
            ->where('is_del', 0)
            ->where('status', 1)
            ->limit(max(0, ($page - 1) * 20), 20)
            ->field('store_id,store_name,keyword,store_img,real_name,phone,province_id,city_id,district_id,addr,
                     address,info,idcard_a,idcard_b,business_license,license,is_del,longitude,latitude,week_time,day_time,other_img,is_index')
            ->select();
        $count = Db::name('store')->where('is_del', 0)->count();
        $data = [
            'info' => $info,
            'count' => $count,
        ];
        if ($data) return returnMsg(200, $data, '请求成功!');
        return returnMsg(201, $data, '失败!');
    }


    /**
     * 添加门店 || 单图片
     */
    public function add_shop($store_name, $img_arr, $info, $real_name, $phone, $address, $is_index, $longitude, $latitude, $store_img, $business_license, $license, $province, $city, $district, $province_txt, $city_txt, $district_txt, $week_time, $day_time, $keyword)
    {
        // $data = $this->request->post();
        //数据验证器
        // try {
        //     validate(ShopValidate::class)
        //         ->scene('add') //验证场景
        //         ->check($data);
        // } catch (\think\exception\ShopValidate $e) {
        //     // 验证失败 输出错误信息
        //     return returnMsg($e->getError());
        // }
        // var_dump($img_arr);

        //不允许添加重复
        $store_data = Db::name('store')->where(['store_name' => $store_name])->where(['is_del' => 0])->find();
        if (!empty($store_data)) {
            return returnMsg(201, '该店铺已添加了，请勿重复添加!');
        }
        $dizhi = $province_txt . ',' . $city_txt . ',' . $district_txt;
        $store_img = $this->getpics($store_img);
        $business_license = $this->getpics($business_license);
        $license = $this->getpics($license);
        $data = [
            'store_name' => addslashes($store_name),
            'keyword' => str_replace('，', ',', $keyword),
            'info' => $info,
            'real_name' => $real_name,
            'phone' => trim($phone),
            'address' => $address,
            'store_img' => $store_img,
            'store_imgs' => json_encode($img_arr),
            'business_license' => $business_license,
            'license' => $license,
            'longitude' => $longitude,
            'latitude' => $latitude,
            'addr' => $dizhi,
            'province_id' => $province,
            'city_id' => $city,
            'district_id' => $district,
            'is_index' => $is_index,
            'week_time' => $week_time,
            'day_time' => $day_time,
            'status' => 0
        ];
        $res = Db::name('store')->insert($data);
        if ($res) return returnMsg(200, 'success');
        return returnMsg(201, 'error');
    }

    /**
     * 添加门店轮播图图片上传
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
        $pic_path = $_SERVER['DOCUMENT_ROOT'] . "/upload/images/store_imgs/";
        if (is_dir($pic_path) && !file_exists($pic_path))
            mkdir($pic_path, 0777, true);
        $pic_path = "upload/images/store_imgs/" . $pics;
        //移动到指定目录，上传图片
        $res = move_uploaded_file($img['upload_file0']['tmp_name'], $pic_path);
        if ($res) {
            return returnMsg(200, '/' . $pic_path, '上传成功！');
        } else {
            return returnMsg(201, '上传失败！');
        }
    }


    /**
     * 省
     */
    public function getpro($level)
    {
        $data = Db::name('region')->where('level', $level)->select();
        if ($data) return returnMsg(200, $data);
        return returnMsg(201, 'error');
    }

    /**
     * 市
     */
    public function getcity($level, $pro_id)
    {
        $data = Db::name('region')->where('level', $level)->where('pid', $pro_id)->select();
        if ($data) return returnMsg(200, $data);
        return returnMsg(201, 'error');
    }

    /**
     * 区
     */
    public function getplace($level, $city_id)
    {
        $data = Db::name('region')->where('level', $level)->where('pid', $city_id)->select();
        if ($data) return returnMsg(200, $data);
        return returnMsg(201, 'error');
    }

    /**
     *添加门店单图上传
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
        $path = "./upload/images/store/identity" . date("Ymd", time());

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

    /**
     * 手机号搜索
     */
    public function searchByPhone($phone, $page)
    {
        $info = Db::name('store')
            ->where('is_del', 0)
            ->where('phone', $phone)
            ->field('store_id,store_name,keyword,store_img,real_name,phone,province_id,city_id,district_id,addr,
                     address,info,idcard_a,idcard_b,business_license,license,is_del,longitude,latitude,week_time,day_time,other_img,is_index')
            ->limit(max(0, ($page - 1) * 20), 20)
            ->select();
        $count = Db::name('store')->where('is_del', 0)->count();
        $data = [
            'info' => $info,
            'count' => $count,
        ];
        if ($data) return returnMsg(200, $data, '请求成功!');
        return returnMsg(201, $data, '失败!');
    }

    /**
     * 门店搜索关键词
     */
    public function searchBykeyword($keyword, $page)
    {
        $info = Db::name('store')
            ->where('is_del', 0)
            ->where('keyword', 'like', '%' . $keyword . '%')
            ->field('store_id,store_name,keyword,store_img,real_name,phone,province_id,city_id,district_id,addr,
                     address,info,idcard_a,idcard_b,business_license,license,is_del,longitude,latitude,week_time,day_time,other_img,is_index')
            ->limit(max(0, ($page - 1) * 20), 20)
            ->select();
        $count = Db::name('store')->where('is_del', 0)->count();
        $data = [
            'info' => $info,
            'count' => $count,
        ];
        if ($data) return returnMsg(200, $data, '请求成功!');
        return returnMsg(201, $data, '失败!');
    }


    //显示一级分类
    public function showLevel1()
    {
        $data = Db::name('service_category')
            ->field('id,name,pic,path,pid,sort,level')
            ->where('level', 1)
            ->where('is_del', 0)
            ->select();
        return returnMsg(200, $data, '请求成功！');
    }
    //所有二级分类
    public function showLevelChild()
    {
        $id = $_POST['cate_id'];
        $data = Db::name('service_category')
            ->field('id,name,pic,path,pid,sort,level')
            ->where('level', 2)
            ->where('pid', $id)
            ->where('is_del', 0)
            ->select();
        return returnMsg(200, $data, '请求成功！');
    }

    /**
     *审核列表编辑店铺服务 level1_id 一级服务id, level2_id 二级id 店铺服务分类cate_store
     *店铺一级下面添加二级，不允许重复添加
     */
    public function mod_cate($store_id, $level1_id, $level2_id)
    {
        $level2_arr = explode(',', $level2_id); //二级id
        $cate1_data = Db::name('service_category')->where('id', $level1_id)->find(); //一级服务信息 name
        foreach ($level2_arr as $v) {
            $cate2_data = Db::name('service_category')->where('id', $v)->find(); //二级服务信息
            $store_cate2 = Db::name('service_category_store')->where('name', $cate1_data['name'])->where('level', 2)->find(); //一级下的二级分类
            if (!empty($store_cate2) && !empty($cate1_data)) { //当数据存在
                return returnMsg(200, '已成功提交！');
            } else {
                $level_info2 = [
                    'pid'  => $cate2_data['pid'],
                    'path' => $level1_id . ',' . $v,
                    'cate_id' => $cate2_data['id'],
                    'store_id' => $store_id,
                    'name' => $cate2_data['name'],
                    'level' => $cate2_data['level'],
                    'add_time' => time()
                ];
                $res = Db::name('service_category_store')->insert($level_info2);
            }
            if ($res) return returnMsg(200, '请求成功！');
            return returnMsg(201, '请求失败！');
        }
    }



    /**
     * 显示审核拒绝店铺列表
     */
    public function refuselist()
    {
        if ($data = ShopList::refuseindex())
            return returnMsg(200, $data, '请求成功！');
        return returnMsg(201, '请求失败！');
    }


    /**
     *编辑列表
     */
    public function mod_shop1($store_id, $level1_id, $level2_id)
    {

        if (!empty($level1_id)) {
            $cate_data = Db::name('service_category')->where('id', $level1_id)->find();
            $cate1_data = Db::name('service_category')->where('id', $level2_id)->find();
            $store_cate_data = Db::name('service_category_store')->find();
            if (!empty($cate_data['id'] == $store_cate_data['cate_id'])) {
                return returnMsg(200, '提交成功！');
            }
            $level_info1 = [
                'pid'  => $cate_data['pid'],
                'path' => $cate_data['path'],
                'cate_id' => $cate_data['id'],
                'store_id' => $store_id,
                'name' => $cate_data['name'],
                'level' => $cate_data['level'],
                'add_time' => time()
            ];
            $level_info2 = [
                'pid'  => $cate1_data['pid'],
                'path' => $cate1_data['path'],
                'cate_id' => $cate1_data['id'],
                'store_id' => $store_id,
                'name' => $cate1_data['name'],
                'level' => $cate1_data['level'],
                'add_time' => time()
            ];
            $res1 = Db::name('service_category_store')->insert($level_info1); //一级
            $res = Db::name('service_category_store')->insert($level_info2); //二级
            if ($res && $res1) return returnMsg(200, '请求成功！');
            return returnMsg(201, '请求失败！');
        }
    }


    /**
     *查看详情
     */
    public function details($store_id)
    {
        $data = Db::name('store')->where('store_id', $store_id)->field('*')->select();
        if ($data) {
            return returnMsg(200, $data, '请求成功！');
        } else {
            return returnMsg(201, '请求失败！');
        }
    }

    /**
     * 删除门店
     */
    public function del($store_id)
    {
        if (ShopList::del_shop($store_id))
            return returnMsg(200, '请求成功！');
        return returnMsg(201, '请求失败！');
    }

    /**
     * 显示待审核列表
     */
    public function exam_list()
    {
        $info = Db::name('store')->where('is_del', 0)->where('status', 0)->field('*')->select(); //显示审核中的门店
        $count = Db::name('store')->where('is_del', 0)->where('status', 0)->count(); //显示审核中的门店
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
     * 审核通过
     */
    public function pass()
    {
        // 启动事务
        Db::startTrans();
        try {
            $data = ['status' => 1];
            $res = Db::name('store')->where('store_id', $_POST['store_id'])->update($data);
            $info = Db::name('store')->where('store_id', $_POST['store_id'])->find();
            $business_data = [
                'account' => $info['phone'],
                'password' => '123456',
                'store_id' => $_POST['store_id'],
                'role_id' => 1,
            ];
            Db::name('business')->insert($business_data);
            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
        }
        if ($res) {
            return returnMsg(200, $res, '审核通过！');
        } else {
            return returnMsg(201, '网络繁忙,请稍后再试!');
        }
    }

    /**
     * 审核拒绝
     */
    public function refuse()
    {

        $data1 = ['status' => 2];
        $res = Db::name('store')->where('store_id', $_POST['store_id'])->update($data1);
        if ($res) {
            return returnMsg(200, $res, '拒绝审核！');
        } else {
            return returnMsg(201, '网络繁忙,请稍后再试!');
        }
    }

    /**
     * excel导入服务表 ck_service 线上
     */
    public function import($store_id)
    {
        // include '../vendor/phpexcel/phpexcel/Classes/PHPExcel.php';
        // include '../vendor/phpexcel/phpexcel/Classes/PHPExcel/IOFactory.php';
        // // 接收文件
        // $filename = $_FILES['file']['tmp_name'];
        // $name = $_FILES['file']['name'];
        // $info = substr($name, (strrpos($name, ".") + 1)); //后缀
        // switch ($info) {
        //     case 'xlsx': {
        //             $objReader = \PHPExcel_IOFactory::createReader('Excel2007');/*excel2007 for 2007*/
        //         }
        //         break;
        //     case 'xls': {
        //             $objReader = \PHPExcel_IOFactory::createReader('Excel5');/*Excel5 for 2003*/
        //         }
        //         break;
        //     case 'csv': {
        //             $objReader = \PHPExcel_IOFactory::createReader('CSV');/*Csv for csv*/
        //         }
        //         break;
        // }
        // // $objPHPExcel = $objReader->load('upload_pic/' . $filename, $encode = 'utf-8'); //加载表格文件
        // $objPHPExcelReader = $objReader->load($filename);
        // $sheet = $objPHPExcelReader->getSheet(0); //获取工作表1的对象
        // $highestRow = $sheet->getHighestRow(); // 取得总行数
        // $highestColumn = $sheet->getHighestColumn(); // 取得总列数
        // $highestColumnIndex = \PHPExcel_Cell::columnIndexFromString($highestColumn);/*取单元数据进数组*/

        // if ($highestRow > 1000) {
        //     return returnMsg(201, '一次最多导入1000条');
        // }

        // $excelData = array();
        // for ($row = 1; $row <= $highestRow; ++$row) {
        //     for ($col = 0; $col <= $highestColumnIndex; ++$col) {
        //         $excelData[$row][] = $sheet->getCellByColumnAndRow($col, $row)->getValue();
        //     }
        // }
        // halt($excelData);


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
            for ($column = 0; $arr[$column] != 'Z'; $column++) {
                $val = $sheet->getCellByColumnAndRow($column, $row)->getValue();
                $row_arr[] = $val;
            }
            $res_arr[] = $row_arr;
        }
        unset($res_arr[0]);
        // halt($res_arr);
        // echo "32333";
        foreach ($res_arr as $k => $v) {
            if ($v[13] == "是") {
                $is_health = 1;
            } else {
                $is_health = 0;
            }
            //服务表
            $info = [
                'unique' => $v[1],
                'yiji_name' => $v[2],
                'yiji_id' => $v[3],
                'erji_name' => $v[4],
                'erji_id' => $v[5],
                'name' => $v[6],
                'rule' => $v[7],
                'price' => $v[8],
                'bl_name' => $v[9],
                'remark' => $v[10],
                'yiyuan_bl' => $v[11],
                'yiyuan_money' => $v[12],
                'store_id' => $store_id,
                'is_health' => $is_health,
                'cate_id' => $v[5],
                'status' => 1,
                'is_show' => 1,
                'add_time' => time(),

            ];
            Db::name('service')->insert($info);
        }
        return returnMsg(200, 'success');
    }



    /**
     * excel导出 上传服务表数据模板
     */
    public function doingexport($store_id)
    {
        $spreadsheet = new Spreadsheet();
        $worksheet = $spreadsheet->getActiveSheet();

        $worksheet->mergeCells('A1:J2');
        $spreadsheet->setActiveSheetIndex(0)->setCellValue('A1', "****医院示例模板");

        $worksheet->mergeCells('K2:M2');
        $spreadsheet->setActiveSheetIndex(0)->setCellValue('K2', "不调用的数据（仅供人工核对使用的数据）");
        $worksheet->getStyle('K2')->getFont()->getColor()->setARGB(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_RED);


        $title = ['项目编号', '一级分类', '一级分类ID', '二级名称', '二级名称ID', '三级项目名称', '规格', '项目单元价', '佣金比例分配代号', '备注', '医院回款比例', '医院回款金额', '是否对接医保'];

        //设置表头
        foreach ($title as $key => $value) {
            // 单元格内容写入
            $worksheet->setCellValueByColumnAndRow($key + 1, 3, $value);
        }


        $store_e = Db::name('service_category_store')->where('store_id', $store_id)->where('level', 2)->select();
        $rows = 4;
        foreach ($store_e as $k => $v) {
            $spreadsheet->setActiveSheetIndex(0)

                ->setCellValue('D' . $rows, $v['name'])
                ->setCellValue('E' . $rows, $v['cate_id']);
            //设置填充的样式和背景色
            $spreadsheet->getActiveSheet()->getStyle('A' . $rows . ':J' . $rows . '')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('CCEEFF');
            $styleArray = [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN //细边框
                    ]
                ]
            ];
            $worksheet->getStyle('A' . $rows . ':J' . $rows . '')->applyFromArray($styleArray);
            $rows++;
        }

        $store_l = Db::name('service_category_store')->where('store_id', $store_id)->where('level', 1)->select();
        $rows = 4;
        foreach ($store_l as $k => $val) {
            $spreadsheet->setActiveSheetIndex(0)
                ->setCellValue('B' . $rows, $val['name'])
                ->setCellValue('C' . $rows, $val['cate_id']);
            //设置填充的样式和背景色
            $spreadsheet->getActiveSheet()->getStyle('A' . $rows . ':J' . $rows . '')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('CCEEFF');
            $styleArray = [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN //细边框
                    ]
                ]
            ];
            $worksheet->getStyle('A' . $rows . ':J' . $rows . '')->applyFromArray($styleArray);
            $rows++;
        }


        $arr = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z');
        //设置宽度为true
        $length = count($arr);
        $m = 0;
        for ($m = 0; $m < $length; $m++) {
            $spreadsheet->getActiveSheet()->getColumnDimension($arr[$m])->setWidth(16);
        }

        $worksheet->getStyle('A1:J3')->getFont()->setBold(true);

        $styleArray = [
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER, //水平居中
                'vertical' => Alignment::VERTICAL_CENTER, //垂直居中
            ],
        ];
        $worksheet->getStyle('A1:J1')->applyFromArray($styleArray);

        $name = time();
        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save($_SERVER['DOCUMENT_ROOT'] . '/upload/excel/' . $name . '.xlsx');
        $url = 'upload/excel/' . $name . '.xlsx';
        $arr_url = array(
            'code' => 200,
            'url' => $url,
        );
        // return returnMsg(200, $url, '请求成功！');
        echo json_encode($arr_url);
    }


    /**
     * excel导入服务表服务  本地测试
     */
    public function importing($store_id)
    {
        header("Content-type:text/html;charset=utf-8");
        include '../vendor/phpexcel/phpexcel/Classes/PHPExcel.php';
        include '../vendor/phpexcel/phpexcel/Classes/PHPExcel/IOFactory.php';
        ini_set('memory_limit', '1024M');
        if ($this->request->isPost()) {
            //接收前台传过来的execl文件
            $file = request()->file('file');
            // $id = $_POST['store_id'];
            // var_dump($this->request->isPost());
            $objPHPExcelReader = \PHPExcel_IOFactory::load($file);

            $sheet = $objPHPExcelReader->getSheet(0);        // 读取第一个工作表(编号从 0 开始)  
            $highestRow = $sheet->getHighestRow();           // 取得总行数  
            $highestColumn = $sheet->getHighestColumn();     // 取得总列数  

            $arr = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z');
            // 一次读取一列  
            $res_arr = array();
            for ($row = 4; $row <= $highestRow; $row++) {
                $row_arr = array();
                for ($column = 0; $arr[$column] != 'N'; $column++) {
                    $val = $sheet->getCellByColumnAndRow($column, $row)->getValue();
                    $row_arr[] = $val;
                }
                $res_arr[] = $row_arr;
            }

            foreach ($res_arr as $k => $v) {
                if ($v[12] === "否") {
                    $is_health = 0;
                } elseif ($v[12] === "是") {
                    $is_health = 1;
                }
                if ($v[8] == '') {
                    $v[8] = 0;
                }

                if ($v[9] == '') {
                    $v[9] = 0;
                }
                //服务表
                $info = [
                    'unique' => $v[0],
                    'name' => $v[5],
                    'yiji_id' => $v[2],
                    'erji_id' => $v[4],
                    'cate_id' => $v[4],
                    'yiji_name' => $v[1],
                    'erji_name' => $v[3],
                    'rule' => $v[6],
                    'price' => $v[7],
                    'bl_name' => $v[8],
                    'remark' => $v[9],
                    'yiyuan_bl' => $v[10],
                    'yiyuan_money' => $v[11],
                    'store_id' => $store_id,
                    'is_health' => $is_health,
                    'is_show' => 1,
                    'status' => 1,
                    'add_time' => time(),

                ];
                Db::name('service')->insert($info);
            }
            return returnMsg(200, 'success');
        }
    }

    /**
     * 获取店铺信息
     * @param $id
     * @return \think\response\Json
     */
    public function getStoreInfoByStoreId($id)
    {
        try {
            $store = ShopList::show_details($id)->toArray();
            $province = Db::name('region')->where('id', $store['province_id'])->find();
            if (isset($province['name']))
                $store['province'] = $province['name'];
            $city = Db::name('region')->where('id', $store['city_id'])->find();
            if (isset($city['name']))
                $store['city'] = $city['name'];
            $district = Db::name('region')->where('id', $store['district_id'])->find();
            if (isset($district['name']))
                $store['district'] = $district['name'];
            return returnMsg('成功', $store);
        } catch (\Exception $e) {
            return returnMsg($e->getMessage());
        }
    }

    /**
     *编辑店铺信息
     */
    public function editStore($storeid, $store_name, $info, $real_name, $phone,$keyword, $address, $longitude, $latitude, $is_index, $province_id, $city_id, $district_id, $province_txt, $city_txt, $district_txt)
    {
        $dizhi = $province_txt . ',' . $city_txt . ',' . $district_txt;
        $data = [
            'store_name' => addslashes($store_name),
            'info' => $info,
            'real_name' => $real_name,
            'phone' => trim($phone),
            'keyword' => str_replace('，', ',', $keyword),
            'address' => $address,
            // 'store_imgs' => json_encode($img_arr),
            'longitude' => $longitude,
            'latitude' => $latitude,
            'addr' => $dizhi,
            'province_id' => $province_id,
            'city_id' => $city_id,
            'district_id' => $district_id,
            'is_index' => $is_index,
            // 'week_time' => $week_time,
            // 'day_time' => $day_time,
        ];
        $store_img = $this->request->file('store_img'); //门店封面图
        if ($store_img) {
            $store_img1 = $this->getpics($store_img); //上传封面图
            $data['store_img'] =  $store_img1;
        }

        $business_license = $this->request->file('business_license');
        if ($business_license) {
            $business_license1 = $this->getpics($store_img);
            $data['business_license'] =  $business_license1;
        }

        $license = $this->request->file('license');
        if ($license) {
            $license1 = $this->getpics($store_img);
            $data['license'] =  $license1;
        }

        // halt($data);
        // echo "222";
        $res = Db::name('store')->where('store_id', $storeid)->update($data);

        if ($res) {
            return returnMsg(200, 'success');
        } else {
            return returnMsg(201, 'error');
        }
    }
}
