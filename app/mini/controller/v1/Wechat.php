<?php
declare (strict_types=1);

namespace app\mini\controller\v1;

use app\mini\service\WxminiService;
use app\mini\service\RegularService;
use app\mini\service\LogService;
use think\facade\Db;

/**
 * 微信信息
 * Class Wechat
 * @package app\mini\controller
 */
class Wechat extends Base
{

    /**
     * 微信登录授权 获取微信用户信息
     */
    public function wxLogin()
    {
        $code = $this->request->post('code');
        $iv = $this->request->post('iv');
        $encryptedData = $this->request->post('encryptedData');
        if (!$code || !$iv || !$encryptedData )  return $this->ret_faild('登录参数缺失');
        $spread_uid = $this->request->post('spreaduid/d',0);//推广人/邀请人ID 默认无

        //获取登录openid session_key
        $Wxmini = new WxminiService();
        $result = $Wxmini->getLogin($code);
        if(!isset($result['openid']))  return $this->ret_faild($result['errmsg']);//code失效
        $openid = $result['openid'];//openid下面接口可以获取到
        $session_key = $result['session_key'];
        //解密数据 获取微信用户个人信息
        $decryptedData = $Wxmini->getDecryptDate($session_key, $iv, $encryptedData);
        //用户是否已存在
        $find_opnied = Db::name('wechat_user')->where('openid', $openid)->find();
        if (!$find_opnied) {
            //用户不存在  整理数据 入库
            $token = $this->wxuser_add($decryptedData,$session_key,$spread_uid) ;
            if(!$token) return $this->ret_faild('登录错误');//添加数据库事务失败
            $phone = '';
        } else {
            $find_user = Db::name('user')
                ->alias('u')
                ->join('user_token ut','u.uid=ut.uid','left')
                ->field('ut.token,u.phone')
                ->where(['u.wx_uid'=>$find_opnied['id'],'ut.source'=>1])//小程序用户的token
                ->find();
            $token = $find_user['token'];
            $phone = $find_user['phone'];
        }
        $data = [
            'openid'=>$openid,
            'token'=>$token,
            'is_bind_mobile'=>$phone ? 1:0,//是否绑定手机号 1绑定了 0未绑定
        ];
        return $this->ret_success('微信登录成功',$data);
    }

    /**
     * 供上面调用
     * 微信登录新用户 添加数据
     * @param $decryptedData
     * @param $session_key
     * @param $spread_uid
     */
    public function wxuser_add($decryptedData,$session_key,$spread_uid)
    {
        //微信昵称问题
        $decryptedData['nickName'] = json_encode($decryptedData['nickName']);
        //$decryptedData['nickName'] = preg_replace('/[\x{10000}-\x{10FFFF}]/u', '',  $decryptedData['nickName']);
        // 启动事务
        Db::startTrans();
        try {
            $now_time = time();
            $ins_data = [
                'openid' => $decryptedData['openId'],
                'nickname' => $decryptedData['nickName'], //微信昵称
                'sex' => $decryptedData['gender'], //性别 0保密 1男 2女
                'language' => $decryptedData['language'], //语言 zh_CN
                'city' => $decryptedData['city'], //城市
                'province' => $decryptedData['province'],//省份
                'country' => $decryptedData['country'],//国家
                'headimgurl' => $decryptedData['avatarUrl'],//头像
                'subscribe' => 1,//是否关注 小程序没有
                'subscribe_time' => $now_time,//关注时间
                'session_key' => $session_key,//小程序会话密钥
            ];
            $wx_uid = Db::name('wechat_user')->insertGetId($ins_data);
            $user_ins = [
                'wx_uid' => $wx_uid,
                'phone' => '',
                'mark' => '小程序用户',
                'user_type' => 0,//默认普通用户
                'nickname' => $decryptedData['nickName'],
                'avatar' => $decryptedData['avatarUrl'],
                'sex' => $decryptedData['gender'], //性别 0保密 1男 2女
                'add_time' => $now_time,
                'spread_uid' => $spread_uid,//邀请人ID
                'spread_time' => $now_time,
            ];

            $new_uid = Db::name('user')->insertGetId($user_ins);
            //用户token数据 添加
            $token = create_usertoken($new_uid, 1);//token 生成
            $usertoken_ins = [
                'uid' => $new_uid,
                'token' => $token,
                'create_time' => date('Y-m-d H:i:s', $now_time),
                'login_ip' => $this->request->ip(),
                'source' => 1,//小程序来源
            ];
            Db::name('user_token')->insertGetId($usertoken_ins);
            // 提交事务
            Db::commit();
            return $token;
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            dump($decryptedData);
            dd($e);
            return false;

        }
    }


    /**
     * 绑定手机号 用户注册
     */
    public function bindMobile()
    {
        $openid = $this->request->post('openid');
        $phone = $this->request->post('mobile');
        $code = $this->request->post('smscode'); //短信验证码
        if (!$code || !$phone || !$openid )  return $this->ret_faild('绑定参数缺失');
        //手机号 校验
        $checkphone = RegularService::phoneRegular($phone);
        if (!$checkphone)  return $this->ret_faild('请输入正确格式的手机号');

        //用户校验
        $wx_uid = Db::name('wechat_user')->where('openid',$openid)->value('id');
        if (!$wx_uid)  return $this->ret_faild('请先授权登录');

        //验证码 校验
        $find = Db::name('sms_log')->field('expiration')->where(['phone'=>$phone,'code'=>$code])->find();
        if (!$find)  return $this->ret_faild('验证码错误');
        if ($find['expiration']<time()) return $this->ret_faild('验证码已过期');

        $user = Db::name('user')->field(['uid,spread_uid,nickname'])->where(['wx_uid'=>$wx_uid])->find();

        //老用户 拉新 积分赠送
        $this->old_to_new($user['uid'],$user['spread_uid'],time(),$user['nickname']);
        //手机号绑定
        Db::name('user')->where(['uid'=>$user['uid']])->update(['phone'=>$phone,'bind_time'=>time()]);
        return $this->ret_success('手机号绑定成功');
    }

//    /**
//     * 校验手机号验证码
//     * @param $phone
//     * @param $code
//     */
//    public function checkCode($phone,$code)
//    {
//        $checkphone = RegularService::phoneRegular($phone);
//        if (!$checkphone)  return $this->ret_faild('请输入正确格式的手机号');
//        $find = Db::name('sms_log')->field('expiration')->where(['phone'=>$phone,'code'=>$code])->find();
//        if (!$find)  return $this->ret_faild('验证码错误');
//        if ($find['expiration']<time()) return $this->ret_faild('验证码已过期');
//    }

    /**
     * 上面调用
     * 邀请新人注册小程序  新人注册手机号
     * 老带新 赠送积分
     * @param $new_uid    新用户 uid
     * @param $spread_uid  老用户 UID
     * @param $nowtime  当前时间
     * @param $new_nickname 新用户微信昵称

     */
    public function old_to_new($new_uid,$spread_uid,$nowtime,$new_nickname)
    {
        if ($spread_uid>0) {
            $spread_find = Db::name('user')->where(['uid'=>$spread_uid,'status'=>0])->find();
            if($spread_find){
                // 启动事务
                Db::startTrans();
                try {
                    //真实存在邀请人
                    $integral = get_sysconfig('sys', 'spread_integral');//获取积分奖励
                    //添加邀请记录
                    $spreadlog_ins = [
                        'uid' => $new_uid, //新用户被邀请人
                        'spread_uid' => $spread_uid,//老用户 邀请人
                        'add_time' => $nowtime,
                        'remark' => $spread_find['nickname'] . '邀请的新用户：' . $new_nickname.' 完成绑定',
                    ];
                    $user_spread_id = Db::name('user_spread')->insertGetId($spreadlog_ins);

                    //如果邀请人是 普通用户 不做处理
                    //如果邀请人是 代言人 新用户的代言人就是邀请人  新用户的合伙人就是邀请人的合伙人
                    if($spread_find['user_type'] == 1 ){
                        $user_update['sopke_uid'] = $spread_find['uid'];//新用户的代言人就是邀请人
                        $user_update['partner_uid'] = $spread_find['partner_uid'];//新用户的合伙人就是代言人的合伙人
                    }
                    //如果邀请人是 合伙人  新用户就没有代言人，新用户的合伙人就是邀请人
                    if($spread_find['user_type'] == 2 ){
                        $user_update['partner_uid'] = $spread_find['uid'];//新用户的合伙人就是邀请人
                    }
                    $user_update['integral_active'] = $spread_find['$spread_find']+$integral;

                    //两个人增加 积分 待激活积分
                    Db::name('user')->where(['uid' => $new_uid])->update($user_update);
                    Db::name('user')->where(['uid' => $spread_uid])->inc('integral_active', $integral)->update();
                    //两个人增加积分记录
                    //新用户积分记录 被邀请人
                    $uid_log_ins = [
                        'uid' => $new_uid,
                        'remark' => '新用户邀请注册赠送积分',
                        'typeid' => 1,//收入
                        'amonut' => $integral,
                        'amount_before' => 0,//新用户开始为0
                        'amount_after' => $integral,
                        'change_time' => $nowtime,
                        'link_type' => 2,//被邀请
                        'link_id' => $user_spread_id,
                    ];
                    Db::name('user_integral_log')->insert($uid_log_ins);
                    //老用户积分记录  邀请人
                    $spread_uid_log_ins = [
                        'uid' => $spread_uid,
                        'remark' => '邀请新用户：' . $new_nickname . '赠送积分',
                        'typeid' => 1,//收入
                        'amonut' => $integral,
                        'amount_before' => $spread_find['integral'] + $spread_find['integral_active'],//老用户积分 总的
                        'amount_after' => $integral + $spread_find['integral'] + $spread_find['integral_active'],//变化之后总积分
                        'change_time' => $nowtime,
                        'link_type' => 1,//主要邀请他人
                        'link_id' => $user_spread_id,
                    ];
                    Db::name('user_integral_log')->insert($spread_uid_log_ins);
                    // 提交事务
                    Db::commit();
                } catch (\Exception $e) {
                    // 回滚事务
                    Db::rollback();
                    return $this->ret_faild('绑定失败');
                }
            }

        }
    }

    //测试
    public function test()
    {

        return $this->ret_success('测试请求成功',['openid'=>'$openid']);

    }

    //获取邀请码 必须小程序发布 待测试
    public function getuidQrcode()
    {
        $path = config('mini.uidQrcode').'123.jpg';
        dump($path);
        if (file_exists($path)) {
            return '图片存在:'.$path;
        }

        $Wxmini = new WxminiService();
        $result = $Wxmini->getQrcodeParam('uid');
        dump($result);
    }


}