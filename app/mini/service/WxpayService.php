<?php
namespace app\mini\service;

use EasyWeChat\Factory;
use think\facade\Db;
use \app\mini\service\LogService;

/**
 * 微信支付
 * Class WxpayService
 * @package app\mini\service
 */
class WxpayService
{
    //支付名不填也没关系
    protected $paymentName = 'chikepay';
    //支付参数配置
    protected $paymentConfig;
    //支付应用
    protected $payment;

    public function __construct()
    {
        $this->paymentConfig = [
            // 必要配置
            'app_id'             => config('wechat.payment.app_id'),
            'mch_id'             => config('wechat.payment.mch_id'),
            'key'                => config('wechat.payment.key'),   // API 密钥
            // 如需使用敏感接口（如退款、发送红包等）需要配置 API 证书路径(登录商户平台下载 API 证书)
            'cert_path'          => config('wechat.payment.cert_path'), // XXX: 绝对路径！！！！
            'key_path'           => config('wechat.payment.key_path'),  // XXX: 绝对路径！！！
            'notify_url'         => config('wechat.payment.notify_url'),   // 你也可以在下单时单独设置来想覆盖它
        ];
        //
        $this->payment = Factory::payment($this->paymentConfig);
    }

    //统一下单
    public  function createOrder(array $data)
    {
//    'body' => '腾讯充值中心-QQ会员充值',
//    'out_trade_no' => '20150806125346',
//    'total_fee' => 88,
//    'spbill_create_ip' => '123.12.12.123', // 可选，如不传该参数，SDK 将会自动获取相应 IP 地址
//    'notify_url' => 'https://pay.weixin.qq.com/wxpay/pay.action', // 支付结果通知网址，如果不设置则会使用配置里的默认地址
//    'trade_type' => 'JSAPI', // 请对应换成你的支付方式对应的值类型
//    'openid' => 'oUpF8uMuAJO_M2pxb1Q9zNjWeS6o',
        $order = [];
        $order['body'] = $data['body'];
        $order['out_trade_no'] = $data['order_sn'];
        $order['total_fee'] = $data['total_fee']*100; //单位分
        $order['trade_type'] = $data['trade_type'];
        $order['openid'] = $data['openid'];
        $result = $this->payment->order->unify($order);
        return $result;
    }

    //查询订单号（微信订单号/商户订单号）
    public function selectOrderSn(string $order_sn)
    {
        $result = $this->payment->order->queryByOutTradeNumber($order_sn);
        return $result;
    }

    //关闭订单 最短在五分钟之后  （微信订单号/商户订单号）
    public function closeOrder(string $order_sn)
    {
        $result = $this->payment->order->close($order_sn);
        return $result;
    }


    //小程序支付参数返回
    //下单接口返回的 预支付ID
    public function jssdkPay(string $prepayId)
    {
        $config = $this->payment->jssdk->bridgeConfig($prepayId, false); // 返回数组
        return $config;

    }

    //支付回调通知
    public function handNotify()
    {
        //$result  = [];
        $response = $this->payment->handlePaidNotify(function ($message, $fail) {
            LogService::writeLog('payment','paynotify.log',$message);//写入日志
            $order_sn = $message['out_trade_no'];
            $find_pay = Db::name('service_order_pay')->where(['order_sn'=>$order_sn])->find();
            if(!$find_pay || $find_pay['pay_status']!=0) return true; // 不存在的订单 或者订单已经关闭/处理了 告诉微信不要推送了
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
                return true;
            } catch (\Exception $e) {
                // 回滚事务
                Db::rollback();
                LogService::writeLog('payment','paynotify_error.log',['errorinfo'=>$e->getMessage()]);//写入日志
                return $fail('fail');
            }
            //return $message;
        });

        $response->send(); // Laravel 里请使用：return $response;
    }


}
