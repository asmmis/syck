<?php
declare (strict_types = 1);

namespace app\mini\middleware;

use think\facade\Db;

class CheckToken
{
    /**
     * 处理请求
     * @param \think\Request $request
     * @param \Closure       $next
     * @return Response
     */
    public function handle($request, \Closure $next)
    {
//        //限制一下重复请求 根据时间戳判断
//        $timeStamp  =  $request->header('timeStamp');
//        $act = $request->pathinfo();//请求地址
//        if(cache($act) == $timeStamp){
//            return ret_json('0','操作太频繁了');
//        }
//        cache($act,$timeStamp,1);//一秒钟

        $token = $request->post('token');
        if(!$token){
            return ret_json(-1,'缺少token');
        }
        $field = ['u.uid','u.user_type','u.phone','u.real_name','u.nickname','u.avatar','u.sex','u.birthday','u.status','u.now_money','u.brokerage_price','u.integral','u.integral_active','u.pay_password','u.spread_uid','u.sopke_uid','u.partner_uid','uw.openid'];
        $userinfo = Db::name('user')
            ->alias('u')
            ->join('user_token ut','u.uid=ut.uid','left')
            ->join('wechat_user uw','u.wx_uid=uw.id','left')
            ->field($field)
            ->where('ut.token',$token)
            //->cache('userinfo',3600)
            ->find();
        if(!$userinfo) {
            return ret_json(-1,'无效token');
        }
        if(!$userinfo['phone']) {
            return ret_json(-1,'请先绑定手机号');
        }
        if($userinfo['status'] != 0) {
            return ret_json(-1,'账号异常');
        }
        //保存下来用户信息
        unset($userinfo['status']);
        $userinfo['nickname'] = show_nickname($userinfo['nickname']);//用户昵称显示
        $request->userinfo = $userinfo;

        return $next($request);
    }
}
