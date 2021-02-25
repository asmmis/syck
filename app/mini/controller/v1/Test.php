<?php
declare (strict_types = 1);

namespace app\mini\controller\v1;

use app\mini\service\LogService;
use app\mini\service\RegularService;
use app\mini\service\SmsService;
use app\mini\service\WxminiService;
use app\mini\service\WxpayService;
use think\facade\Validate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use think\facade\Db;
use think\Facade\View;
use app\mini\http\GaodeApi;

class Test extends Base
{
    //测试日志
    public function testlog()
    {
        $data = [
            'url'=>'asdasd',
            'res'=>'succ111'
        ];
        LogService::writeLog('test2','test20201215.log',$data);
        echo  123465;
    }

    //测试发送短信
    public function testsms()
    {
        $codetype = 2;
        $smscodetyep = config('mini.smsCodeType');
        $smscodetyep =['asd'=>0,'asd'=>1];
        //dump(config('mini.smsCodeType')[$codetype]);
        if (!in_array($codetype,$smscodetyep)) return $this->ret_faild('验证码发送类型错误');

    }




    //测试接口签名
    public function testsign()
    {
        $apikey = 'chikexiaochengxu#';
        $randStr = 'R1G5O6QF1';
        $time = '1608016517';
        $md5str = $apikey.$time.$randStr;
        $sign = md5($md5str);
        dump('apikey');
        dump($apikey);
        dump('时间戳');
        dump($time);
        dump('随机数');
        dump($randStr);
        dump('加密字符串');
        dump($md5str);
        dump('加密后的签名');
        dump($sign);
    }

    //测试路由
    public function testroute1()
    {
        dump(config('wechat.payment'));
        dump(0?true:false);
        echo 123;
    }
    //测试路由
    public function testroute2()
    {
        echo 123456;
    }

    //测试版本号
    public function testindex(){
        echo 'V1test';
    }

    //测试图片上传 单张
    public function testupimg()
    {

        // 获取表单上传文件
//        $file = request()->file('file');
//        $savename = \think\facade\Filesystem::disk('photo')->putFile( 'service_leave', $file);
//        dump($savename);

        // 获取表单上传文件
        $file = $this->request->file('file');
        if(!Validate::fileSize($file,1024 * 1024 * 5)){
            return $this->ret_faild('图片过大');
        }
        if(!Validate::fileExt($file,'jpg,jpeg,png,gif')){
            return $this->ret_faild('图片格式错误');
        }
        $savename = \think\facade\Filesystem::disk('photo')->putFile( 'service_leave', $file);
        $path = '/public/upload/images/'.$savename;
        dump($savename);
    }

    //小程序文本安全校验
    public function testchecktext()
    {
        $content = '123';
        $Wxmini = new WxminiService();
        $result = $Wxmini->checkText($content);
        if(!$result){
            echo '存在敏感文字';
        }
        echo '内容正常';
    }


    //导出表格测试
    public function testdowmexecl()
    {
        //require_once __DIR__ . '/vendor/autoload.php';

        $data = [
            ['title1' => '111', 'title2' => '222'],
            ['title1' => '111', 'title2' => '222'],
            ['title1' => '111', 'title2' => '222']
        ];
        $title = ['第一行标题', '第二行标题'];

        // Create new Spreadsheet object
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // 方法一，使用 setCellValueByColumnAndRow
        //表头
        //设置单元格内容
        foreach ($title as $key => $value) {
            // 单元格内容写入
            $sheet->setCellValueByColumnAndRow($key + 1, 1, $value);
        }
        $row = 2; // 从第二行开始
        foreach ($data as $item) {
            $column = 1;
            foreach ($item as $value) {
                // 单元格内容写入
                $sheet->setCellValueByColumnAndRow($column, $row, $value);
                $column++;
            }
            $row++;
        }

//        // 方法二，使用 setCellValue
//        //表头
//        //设置单元格内容
//        $titCol = 'A';
//        foreach ($title as $key => $value) {
//            // 单元格内容写入
//            $sheet->setCellValue($titCol . '1', $value);
//            $titCol++;
//        }
//        $row = 2; // 从第二行开始
//        foreach ($data as $item) {
//            $dataCol = 'A';
//            foreach ($item as $value) {
//                // 单元格内容写入
//                $sheet->setCellValue($dataCol . $row, $value);
//                $dataCol++;
//            }
//            $row++;
//        }

//        // Redirect output to a client’s web browser (Xlsx)
//        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
//        header('Content-Disposition: attachment;filename="01simple.xlsx"');
//        header('Cache-Control: max-age=0');
//        // If you're serving to IE 9, then the following may be needed
//       // header('Cache-Control: max-age=1');
//
//        // If you're serving to IE over SSL, then the following may be needed
//        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
//        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); // always modified
//        header('Cache-Control: cache, must-revalidate'); // HTTP/1.1
//        header('Pragma: public'); // HTTP/1.0
//
//        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
//        $writer->save('php://output');
//        exit;
        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
        $path = config('mini.execlpath');
        $writer->save($path.'01simple.xlsx');
        echo '保存成功';
    }

    //测试手机号正则
    public function testphonecheck()
    {
        $phone = '15255132094';
        $checkphone = RegularService::phoneRegular($phone);
        if (!$checkphone)  return $this->ret_faild('请输入正确格式的手机号');
        echo '正常手机号';
    }

    //测试数据库修改
    public function testdbupdate()
    {
        $money = 1;

        dump (Db::name('user')->where(['uid'=>1])->find());
        $update = [
            'now_money' => Db::raw('now_money+'.$money),
            'brokerage_price' => Db::raw('brokerage_price-'.$money),
        ];
        dump($update);
        $r = Db::name('user')->where(['uid'=>1])->update($update);//自己佣金减 余额加
        dump($r);
        dump(Db::name('user')->getLastSql());
        dump(111);
    }
    //测试导入excel
    public function testUpexcel()
    {
//        if($this->request->isPost()){
//            $upload_file = $_FILES['file']['tmp_name'];
//            $ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
//            if ($ext == 'xlsx') {
//                $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
//                $spreadsheet = $reader->load($upload_file);
//            }else if ($ext == 'xls') {
//                $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xls();
//                $spreadsheet = $reader->load($upload_file);
//            }
//            $sheet = $spreadsheet->getActiveSheet();
//            $row_count = $sheet->getHighestRow();//取得总行数
//            $create_time = time();
//            dump($row_count);
//            for ($row = 2; $row <= $row_count+1; $row++) {
//                $a = $sheet->getCell('A'.$row)->getValue();
//                dump($a);
//            }
//        }
//        return  View::fetch('v1/test/testupexcel');

    }

    //测试创建目录
    public function testmkdir()
    {
        dump($_SERVER['DOCUMENT_ROOT']);
        $path = $_SERVER['DOCUMENT_ROOT'].'/upload/test/2020/12/22/test/';
        $filename = 'tt.log';
        if (!is_dir($path)){
            mkdir($path, 0757, true);
        }
        file_put_contents($path.$filename,'$content',FILE_APPEND | LOCK_EX);
        echo 111;
    }

    //测试经纬度 求距离
    public function testaddrkm()
    {
        $user_lat = '120.113119'; //用户 纬度  汽车北站
        $user_lnt = '30.315762'; //用户 经度  汽车北站
        $streo_lat = '120.102305'; //医院 纬度  西城年华
        $streo_lnt = '30.309242'; //医院   西城年华
        //120.100932,30.331543   阳光郡
        $m = get_distance($streo_lat,$streo_lnt,$user_lat,$user_lnt);
        dump($m);
        $km =   distance($streo_lat,$streo_lnt,$user_lat,$user_lnt);
        dump($km);
    }

    //测试经纬度 距离排序sql
    public function testsqlkm()
    {

        $city_lat = '120.113119'; //用户 纬度
        $city_lng = '30.315762'; //用户经度

//        $sql = "select *,(ACOS(SIN(('.$city_lat.' * 3.1415) / 180 ) *SIN((latitude * 3.1415) / 180 ) +COS(('.$city_lat.' * 3.1415) / 180 ) * COS((latitude * 3.1415) / 180 ) *COS(('.$city_lng.' * 3.1415) / 180 - (longitude * 3.1415) / 180 ) ) * 6380) as juli from ck_store order by juli  desc  limit 30";
//        $sql = 'select *,(2 * 6378.137* ASIN(SQRT(POW(SIN(PI()*('. $city_lat.'-latitude)/360),2)+COS(PI()*'.$city_lng.'/180)* COS(longitude * PI()/180)*POW(SIN(PI()*('.$city_lng.'-longitude)/360),2)))) as juli from `ck_store`
//order by juli desc limit 0,20';

        $field = ['store_id','store_name','latitude','longitude','address','info','(2 * 6378.137* ASIN(SQRT(POW(SIN(PI()*('. $city_lat.'-latitude)/360),2)+COS(PI()*'.$city_lng.'/180)* COS(longitude * PI()/180)*POW(SIN(PI()*('.$city_lng.'-longitude)/360),2)))) as juli'];
        $list = Db::name('store')
            ->field($field)
            ->order('juli','asc')
            ->select();
//        $list = Db::query($sql);
        foreach ($list as $k => $v){
            $v['km'] = get_distance($v['latitude'],$v['longitude'],$city_lat,$city_lng);
//            unset($v['latitude']);
//            unset($v['longitude']);
            $list[$k] = $v;
        }
        dump($list);
    }


    //测试普通
    public function test()
    {
//        $len = strlen('wo握1手');
//        dump($len);
//        $t = 0;
//        $a =  $t ?  date('Y-m-d',0) : '';
//        dump($a);

//        $t1=877612312.21; //错误的，这是字符串
//        $r=  isAmount($t1);
//        dump($r);
//
//        $w = date('w',1609048383);
//        dump($w);
//        $phone = '15255132095';
//
//        $where = [
//            ['phone','=',$phone],
//            ['user_type','>',0],
//        ];
//
//        $finduser = Db::name('user')->where($where)->find(); //普通用户没有佣金
//        dump( Db::name('user')->getLastSql());
//        if(!$finduser) return $this->ret_faild('对方账户未找到');
//        dump($finduser);

//        $t=6.01;
//        dump($t);
//        if(is_numeric($t)){
//            dump('是价格');
//            $newt = $t*100;
//            dump($newt);
////            $newt = (int)$newt;
////            dump($newt);
//            if(is_float($newt)){
//                dump('是价格小数');
//            }else{
//                dump('不是价格小数');
//            }
//        }else{
//            dump('不是价格');
//        }



//        $num = 111.11;
//        $rule = '/^(0|[1-9]\d{0,3})(\.\d{1,2})?$/';
//        $res = preg_match($rule, $num);
//        var_dump($res);

//        $url = getimgurl(1);
//        dump($url);

//        $list = Db::name('goods_description')->where('goods_id','=',18)->value('description');
//
//       dd($list) ;
        $str = '无感';
        dump(json_decode($str));
    }

    //测试事务操作
    public function testshiwu()
    {
        $spread_uid = 0;
        $decryptedData = [
            'openId' => '123123',
            'nickName' => '123123',
            'gender' => '1',
            'language' => '123123',
            'city' => '123123',
            'province' => '123123',
            'country' => '123123',
            'avatarUrl' => '123123',
        ];
        $session_key =  'd/7HuUEowioKrhI+DhrYNA==';
//        $r = $this->wxuser_add($decryptedData,$session_key,$spread_uid);
//        if($r){
//            return $this->ret_success('微信登录成功',['openid'=>$decryptedData['openId'],'token'=>$r]);
//        }
//        return $this->ret_faild('微信登录失败');
    }


    //测试小程序创建订单
    public function testcreateorder()
    {
        $data = [
            'body' => '测试商品',
            'order_sn' => 'test1234567890',
            'total_fee' => 0.01,
            'trade_type' => 'JSAPI',
            'openid' => 'oAKIy5bPRVkmKu8SYewGd827Ycy0',
        ];
        $Wxpay = new WxpayService();
        $res = $Wxpay->createOrder($data);
        dump($res);
        //  "return_code" => "SUCCESS"
        //  "return_msg" => "OK"
        //  "appid" => "wx1451314460b6e266"
        //  "mch_id" => "1511874851"
        //  "nonce_str" => "MBu5kZtFVv311q98"
        //  "sign" => "D36DA2DEB780999AA83EE9D4A40B8A7A"
        //  "result_code" => "SUCCESS"
        //  "prepay_id" => "wx291702344911264769eed68be949c30000"
        //  "trade_type" => "JSAPI"
        if($res['return_code'] == 'SUCCESS' && $res['result_code'] == 'SUCCESS'){
            $res = $Wxpay->jssdkPay($res['prepay_id']);
            dump($res);//返回小程序所需的参数
        }else{
            //记录错误信息
            dump($res);//返回小程序所需的参数
        }
    }
    //返回小程序支付参数
    public function testpayconfig($prepay_id)
    {
        $prepay_id = "wx291702344911264769eed68be949c30000";
        $Wxpay = new WxpayService();
        $res = $Wxpay->jssdkPay($prepay_id);
        dump($res);
        //  "appId" => "wx1451314460b6e266"
        //  "timeStamp" => "1609233263"
        //  "nonceStr" => "5feaf36f8993e"
        //  "package" => "prepay_id=wx291702344911264769eed68be949c30000"
        //  "signType" => "MD5"
        //  "paySign" => "980C38089BD9C25BBE490571924520DF"
    }
    //查询订单号
    public function testselorder()
    {
        $order_sn = 'test1234567890';
        $Wxpay = new WxpayService();
        $res = $Wxpay->selectOrderSn($order_sn);
        dump($res);
        //  "return_code" => "SUCCESS"
        //  "return_msg" => "OK"
        //  "appid" => "wx1451314460b6e266"
        //  "mch_id" => "1511874851"
        //  "device_info" => null
        //  "nonce_str" => "1VpFKR7WNk8wm7Pu"
        //  "sign" => "D03478F70130ABDDEB3FE93A155AD41C"
        //  "result_code" => "SUCCESS"
        //  "total_fee" => "1"
        //  "out_trade_no" => "test123456789"
        //  "trade_state" => "NOTPAY"
        //  "trade_state_desc" => "订单未支付"
    }
    //关闭订单号
    public function testcloseorder()
    {
        $order_sn = 'test1234567890';
        $Wxpay = new WxpayService();
        $res = $Wxpay->closeOrder($order_sn);
        dump($res);
        //  "return_code" => "SUCCESS"
        //  "return_msg" => "OK"
        //  "appid" => "wx1451314460b6e266"
        //  "mch_id" => "1511874851"
        //  "sub_mch_id" => null
        //  "nonce_str" => "yQWRaJshqEvq5MSx"
        //  "sign" => "C9D671D084C7DA73FF1A90D2A81C0B9E"
        //  "result_code" => "SUCCESS"
    }
    //测试高德地图
     public function testgaodeapi()
     {
         $latitude = "30.315762";//纬度36.659962
         $longitude = "120.113119";//经度113.799176
         $res = GaodeApi::getAddress($latitude,$longitude);
         dump($res);
     }

     //测试修改数据库的微信用户昵称转成base64
    public function testupuser()
    {
//        $list = Db::name('user')->field('uid,nickname')->select();
//        foreach ($list as $v){
//            $zhong = show_nickname($v['nickname']);
//            $json = json_encode($v['nickname'],JSON_UNESCAPED_UNICODE);
//           // Db::name('user')->where('uid','=',$v['uid'])->update(['nickname'=>$json]);
//            var_dump($v['nickname']);
//            var_dump($zhong);
//            var_dump($json);
//
//        }
        $list = Db::name('wechat_user')->field('id,nickname')->select();
        foreach ($list as $v){
//            $zhong = show_nickname($v['nickname']);
            $json = json_encode($v['nickname'],JSON_UNESCAPED_UNICODE);
            dump($v['nickname']);
//            dump(show_nickname($v['nickname']));
            //昵称去掉“”
            $s = trim($v['nickname'],"\"");
            var_dump($s);
//          Db::name('wechat_user')->where('id','=',$v['id'])->update(['nickname'=>$json]);
        }

    }

    //测试一下
    public function test111()
    {
//        $arr = [
//            0=> [
//                'store_id'=>1,
//                'store_name'=>'wqkejlkjalk',
//                'infos'=>[
//                    0=>['cart_id'=>11,'num'=>2],
//                    1=>['cart_id'=>11,'num'=>2],
//                ],
//            ],
//            1=> [
//                'store_id'=>2,
//                'store_name'=>'wqkejlkjalk',
//                'infos'=>[
//                    0=>['cart_id'=>11,'num'=>2],
//                    1=>['cart_id'=>11,'num'=>2],
//                ],
//            ],
//        ];
//        dump(count($arr));

        $arr = '[{store_id: 100, service_ids: "169", appo_time: "2021-01-08 18:06", cu_id: 0}]';
        dump(json_decode($arr));
    }

    public function testnotify()
    {
        if (strpos($_SERVER['HTTP_USER_AGENT'], 'miniprogram') !== false) {
            return true;//微信来源
        } else {
            return false;
        }
    }

    public function testnotifysend()
    {
        $message = [
            'out_trade_no' =>'s2101081704041000002961500',
            'transaction_id' =>'4200000919202101082293879391',
        ];
        $order_sn = $message['out_trade_no'];
        $find_pay = Db::name('service_order_pay')->where(['order_sn'=>$order_sn])->find();
        if(!$find_pay || $find_pay['pay_status']!=0) return 112; // 不存在的订单 或者订单已经关闭/处理了 告诉微信不要推送了
        // 启动事务
        Db::startTrans();
        try {
            $exp = [
                'pay_type'=>1,//微信支付
                'pay_time'=>time(),
                'pay_price'=>$find_pay['cancel_price'],//实付金额=核销金额
                'transaction_id'=>$message['transaction_id']//流水号 余额
            ];
            service_order_payok($order_sn,$exp);
            // 提交事务
            Db::commit();
            return 11;
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            //LogService::writeLog('payment','paynotify_error.log',['errorinfo'=>$e->getMessage()]);//写入日志
            return 'fail';
        }
    }
    //测试获取小程序推广码
    public function testqrcode()
    {
        $uid = 10000027;
        $pageurl = 'pages/index/index';//跳转到小程序的页面
        $filename = $uid.str_replace('/','_',$pageurl).'.png';//保存图片重命名 规则
        $filepath =  config('mini.uidQrcode').$filename;//图片存放地址
        if(file_exists($filepath)){
            $url = '/static/uidqrcode/'.$filename;
            dump('图片存在');
            dump($url);
        }else{
            dump('图片不存在');
            $Wxmini = new WxminiService();
            $res = $Wxmini->getQrcodeParam($uid,$pageurl,$filename);
            dump($res);
            if($res){
                return config('mini.uidQrcodeShow').$filename;
            }else{
                //获取微信小程序码失败
                return '获取二维码失败';
            }

        }

    }
}
