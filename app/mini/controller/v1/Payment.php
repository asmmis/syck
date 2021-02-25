<?php
declare (strict_types = 1);

namespace app\mini\controller\v1;

//use EasyWeChat\Factory;
use think\App;
use think\facade\Db;
use app\mini\service\WxpayService;
use app\mini\service\LogService;
/**
 * 支付
 * Class Payment
 * @package app\mini\controller
 */
class Payment extends Base
{
//    //支付参数配置
//    protected $paymentConfig;
//    //支付应用
//    protected $payment;
//
//    public function __construct(App $app)
//    {
//        parent::__construct($app);
//        $this->paymentConfig = [
//            // 必要配置
//            'app_id'             => config('wechat.payment.app_id'),
//            'mch_id'             => config('wechat.payment.mch_id'),
//            'key'                => config('wechat.payment.key'),   // API 密钥
//            // 如需使用敏感接口（如退款、发送红包等）需要配置 API 证书路径(登录商户平台下载 API 证书)
//            'cert_path'          => config('wechat.payment.cert_path'), // XXX: 绝对路径！！！！
//            'key_path'           => config('wechat.payment.key_path'),  // XXX: 绝对路径！！！
//            'notify_url'         => config('wechat.payment.notify_url'),   // 你也可以在下单时单独设置来想覆盖它
//        ];
//        //
//        $this->payment = Factory::payment($this->paymentConfig);
//    }


    //根据订单号 支付
    public function serviceOrderPay()
    {
        $userinfo = $this->request->userinfo;
        $order_sn = $this->request->param('order_sn/s','');//支付订单号 可能是支付
        $pay_type = $this->request->param('pay_type/d',0);//支付方式 1=微信 2=余额
        $pay_password = $this->request->param('pay_password/d',0);//六位数字支付密码
        $pay_price = $this->request->param('pay_price/s');//支付金额
        $find_pay = Db::name('service_order_pay')->where(['order_sn'=>$order_sn])->find();
        if(!$find_pay) return $this->ret_faild('订单号不存在');
        if($find_pay['pay_status']!=0)  return $this->ret_faild('订单取消或已支付');
        if($pay_price !== $find_pay['cancel_price']) return $this->ret_faild('订单金额错误');
        if($pay_price == 0){
            //0元支付，直接支付成功，处理订单完成
            $exp =[
                'pay_type'=>2,//余额支付
                'pay_time'=>time(),
                'pay_price'=>0,
                'transaction_id'=>'yue'.time()//流水号 余额
            ];
            // 启动事务
            Db::startTrans();
            try {
                service_order_payok($order_sn,$exp);
                // 提交事务
                Db::commit();
                return $this->ret_success('支付成功');
            } catch (\Exception $e) {
                // 回滚事务
                Db::rollback();
                return $this->ret_faild('支付失败',['error'=>$e->getMessage()]);
            }

        }
        if($pay_type==1){
            //微信支付 微信下单
            $data = [];
            $data['body'] =  $find_pay['body'];
            $data['order_sn'] =  $find_pay['order_sn'];
            $data['total_fee'] =  $find_pay['cancel_price'];
            $data['trade_type'] =  'JSAPI'; //小程序支付
            $data['openid'] =  $userinfo['openid'];//用户openid
            $WxpayService = new WxpayService();
            $res = $WxpayService->createOrder($data);//微信下单
            if($res['return_code'] == 'SUCCESS' && $res['result_code'] == 'SUCCESS'){
                $r = $WxpayService->jssdkPay($res['prepay_id']);//返回小程序所需的参数
                return $this->ret_success('微信下单成功',$r);
            }else{
                //记录错误信息 下单失败
                LogService::writeLog('payment','fail.log',$res);
                return $this->ret_faild('微信下单失败',$res['err_code_des']);
            }
        }elseif($pay_type==2){
            //余额支付
            if(!$userinfo['pay_password']) return $this->ret_faild('请先设置交易密码');
            if(!$pay_password) return  $this->ret_faild('请输入交易密码');
            if($pay_price>$userinfo['now_money']) return $this->ret_faild('用户余额不足，请更换支付方式');

            $pay_password_encode = create_pay_password($userinfo['uid'],$pay_password);//密码加密后判断
            if($pay_password_encode!==$userinfo['pay_password'])  return $this->ret_faild('交易密码错误');
            //余额支付成功处理订单完成
            $exp =[
                'pay_type'=>2,//余额支付
                'pay_time'=>time(),
                'pay_price'=>(float) $pay_price,
                'transaction_id'=>'yue'.time()//流水号 余额
            ];
            // 启动事务
            Db::startTrans();
            try {
                service_order_payok($order_sn,$exp);//订单处理完成
                service_order_payok_userlog($userinfo,$exp,$find_pay['id']);//用户余额增减记录
                // 提交事务
                Db::commit();
                return $this->ret_success('支付成功');
            } catch (\Exception $e) {
                // 回滚事务
                Db::rollback();
                return $this->ret_faild('支付失败',['error'=>$e->getMessage()]);
            }
        }else{
            return $this->ret_faild('请选择交易方式');
        }

    }


    //支付回调地址 微信回调地址
    public function notifyChannel()
    {
        $WxpayService = new WxpayService();
        return $WxpayService->handNotify();//处理支付回调
    }

}