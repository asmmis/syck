<?php

use think\facade\Db;
// 这是系统自动生成的公共文件


//自定义 返回
function ret_json($state,$msg="",$data=[])
{
    $ret=array();
    $ret['state']=$state;
    $ret['msg']=$msg;
    $ret['data']=$data;
    //return json_encode($ret,JSON_UNESCAPED_UNICODE );
    return json($ret);
}
//成功返回 0成功
 function ret_success($msg,$data=[])
 {
    return ret_json(0,$msg,$data);
}
// 失败返回 1失败
 function ret_faild($msg,$data=[])
 {
    return ret_json(1,$msg,$data);
}

/**
 * 生成用户token
 * @param $uid
 * @param $source 来源 1=小程序
 * @return string
 */
function create_usertoken($uid,$source)
{
    $str = $uid.time().$source.'chike#';
    return md5($str);
}

/**
 * 生成用户支付密码
 *  用户ID   用户支付密码明文
 */
function create_pay_password($uid,$password)
{
    return md5($uid.$password);
}
/**
 * 获取配置表的数据
 * 积分奖励，佣金比例等
 */
function get_sysconfig($groupname,$configname)
{
    $sys = Db::name('sys_config')
        ->field(['config_name','config_value'])
        ->where(['config_group'=>$groupname])
        ->cache('sysconfig',60) //暂时60秒
        ->select();
    $arr= [];
    foreach ($sys as $val) {
        $arr[$val['config_name']] = $val['config_value'];
    }
    return $arr[$configname];
}

/**
 * 商品订单 唯一订单号  32位以内
 */
function create_goods_ordersn($uid,$i=0)
{
    //至少25位订单号
    //$i 主要是防止订单拆分循环时 时间uid随机数一样
    $sn = date('ymdHis').$uid.mt_rand(1000,9999).$i;
    return $sn;
}

/**
 * 服务订单 唯一订单号 32位以内
 * 服务订单带 s 在回调时区分订单
 */
function create_service_ordersn($uid,$i=0)
{
    //至少25位订单号
    //$i 主要是防止订单拆分循环时 时间uid随机数一样
    $sn = 's'.date('ymdHis').$uid.mt_rand(1000,9999).$i;
    return $sn;
}

/**
 * 手机号 姓名 银行卡号 身份证 重要信息脱敏显示
 * @param $string
 * @param int $start
 * @param int $length
 * @param string $re
 * @return string
 */
function desensitize($string, $start = 0, $length = 0, $re = '*'){
    if(empty($string) || empty($length) || empty($re)) return $string;
    $end = $start + $length;
    $strlen = mb_strlen($string);
    $str_arr = array();
    for($i=0; $i<$strlen; $i++) {
        if($i>=$start && $i<$end)
            $str_arr[] = $re;
        else
            $str_arr[] = mb_substr($string, $i, 1);
    }
    return implode('',$str_arr);
}

/**
 * 金额校验函数
 * @param $value
 * @param bool $isZero
 * @param bool $negative
 * @return bool
 */
function isAmount($value, $isZero=false, $negative=false){
    // 必须是整数或浮点数，且允许为负
    if (!preg_match("/^[-]?\d+(.\d{1,2})?$/", $value)){
        return false;
    }
    // 不为 0
    if (!$isZero && empty((int)($value*100))){
        return false;
    }
    // 不为负数
    if (!$negative && (int)($value * 100) < 0){
        return false;
    }
    return true;
}

//$lat1  商家经度
//$lng1 商家纬度
//$lat2 用户纬度
//$lng2用户经度
///**
// * @param $lat1  商家纬度
// * @param $lng1   商家经度
// * @param $lat2 用户纬度
// * @param $lng2 用户经度
// * @return false|float
// */
//function get_distance($lat1, $lng1, $lat2, $lng2)
//{
//    $earthRadius = 6367000;
//    $lat1 = $lat1 * pi() / 180;
//    $lng1 = $lng1 * pi() / 180;
//    $lat2 = $lat2 * pi() / 180;
//    $lng2 = $lng2 * pi() / 180;
//    $calcLongitude = $lng2 - $lng1;
//    $calcLatitude = $lat2 - $lat1;
//    $stepOne = pow(sin($calcLatitude / 2), 2) + cos($lat1) * cos($lat2) * pow(sin($calcLongitude / 2), 2);
//    $stepTwo = 2 * asin(min(1, sqrt($stepOne)));
//    $calculatedDistance = $earthRadius * $stepTwo;
//    return round($calculatedDistance/1000,2);//千米 保留两位小数
//}
//
///**
// *求两个已知经纬度之间的距离,单位为千米
// *@param lng1,lng2 经度
// *@param lat1,lat2 纬度
// *@return float 距离，单位千米   没有保留两位小数
// **/
// function distance($lng1,$lat1,$lng2,$lat2)//根据经纬度计算距离
//{
//    //将角度转为弧度
//    $radLat1=deg2rad($lat1);
//    $radLat2=deg2rad($lat2);
//    $radLng1=deg2rad($lng1);
//    $radLng2=deg2rad($lng2);
//    $a=$radLat1-$radLat2;//两纬度之差,纬度<90
//    $b=$radLng1-$radLng2;//两经度之差纬度<180
//    $s=2*asin(sqrt(pow(sin($a/2),2)+cos($radLat1)*cos($radLat2)*pow(sin($b/2),2)))*6378.137;
//    return $s;
//}

/**
 * 拼接图片返回完整路径
 * 数据库存储的是 /upload/images/....
 */
function getimgurl($path)
{
    return think\facade\Request::domain().$path;
}


/**
 * 处理服务订单支付成功
 * @param $order_sn 内部订单号
 * @param $exp 示例 ['pay_type'=>1,'pay_time'=>1234567890,'pay_price'=>100.21,'transaction_id'=>23123123213123123]
 */
function service_order_payok($order_sn,$exp)
{
    $find_pay = Db::name('service_order_pay')->where(['order_sn'=>$order_sn])->find();
    if(!$find_pay || $find_pay['pay_status']!=0) return false; // 不存在的订单 或者订单已经关闭/处理了
    //修改支付表完成
    $update = [];
    $update['pay_price'] = $exp['pay_price'];//支付金额
    $update['pay_time'] = $exp['pay_time'];//支付时间
    $update['pay_type'] = $exp['pay_type'];//支付方式
    $update['pay_status'] = 1;//已支付
    $update['transaction_id'] = $exp['transaction_id'];//第三方流水号 内部的话没有流水号
    Db::name('service_order_pay')->where(['order_sn'=>$order_sn])->update($update);
    //修改服务订单表完成
    $update = [];
    $update['pay_price'] = $exp['pay_price'];//支付金额
    $update['pay_time'] = $exp['pay_time'];//支付时间
    $update['pay_type'] = $exp['pay_type'];//支付方式
    $update['pay_status'] = 1;//已支付
    $update['status'] = 1;//已支付 待核销
    Db::name('service_order')->where(['order_sn'=>$order_sn])->update($update);
    //发送服务消息？
}

/**
 * 处理服务订单支付成功 余额支付才有
 * @param $userinfo 用户信息
 * @param $exp 示例 ['pay_type'=>1,'pay_time'=>1234567890,'pay_price'=>100.21,'transaction_id'=>23123123213123123]
 */
function service_order_payok_userlog($userinfo,$exp,$link_id)
{
    //用户余额 减去支付 金额
    $update = [];
    $update['now_money'] = Db::raw('now_money-'.$exp['pay_price']);//余额减去购买金额
    $update['pay_service_count'] =  Db::raw('pay_service_count+1');//购物服务次数+1
    Db::name('user')->where(['uid'=>$userinfo['uid']])->update($update);
    //用户余额记录 新增一条记录
    $insert = [];
    $insert['uid'] = $userinfo['uid'];
    $insert['remark'] = '服务消费';
    $insert['typeid'] = 2;
    $insert['amonut'] = $exp['pay_price'];//变化金额
    $insert['amount_before'] = $userinfo['now_money'];//变化前
    $insert['amount_after'] = $userinfo['now_money']-$exp['pay_price'];//变化后金额
    $insert['change_time'] = $exp['pay_time'];
    $insert['real_time'] = $exp['pay_time'];
    $insert['change_time'] = $exp['pay_time'];
    $insert['change_status'] = 1;//已完成
    $insert['link_type'] = 4;//余额购买服务
    $insert['link_id'] = $link_id;//这里是服务支付表的ID service_order_pay 表的iD
    Db::name('user_money_log')->insert($insert);

}

/**
 * 常用金额保留两位小数
 * @param $money
 * @param int $typeid
 * @return float|int|string
 */
function keeptwodecimal($money,$typeid=1){
    switch($typeid){
        case 1: //四舍五入
            $str = sprintf("%.2f",$money);
            break;
        default: //不四舍五入
            $str = floor($money*100)/100;
            break;
    }
    return $str;
}