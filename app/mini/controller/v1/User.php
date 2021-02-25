<?php
declare (strict_types = 1);

namespace app\mini\controller\v1;

use app\mini\service\RegularService;
use app\mini\service\WxminiService;
use think\facade\Db;
use app\mini\validate\UserAddress;
use think\exception\ValidateException;
/**
 * 小程序用户 相关
 * Class User
 * @package app\mini\controller
 */
class User extends Base
{

    //获取用户信息
    public function userInfo()
    {
        //用户信息 头像 昵称
        $userinfo = $this->request->userinfo;
        unset($userinfo['real_name']);//不需要真是姓名
        unset($userinfo['spread_uid']); //不需要上级人ID
        unset($userinfo['sopke_uid']); //不需要代言人
        unset($userinfo['partner_uid']); //不需要合伙人
        $uid = $userinfo['uid'];
        $userinfo['birthday'] = $userinfo['birthday'] ? date('Y-m-d',$userinfo['birthday']) : '';
        $userinfo['phone_desen'] = desensitize($userinfo['phone'] ,3,4);//手机号脱敏显示
        //服务券 数量 待使用
        $userinfo['service_coupon_num'] = Db::name('coupon_user')->where(['uid'=>$uid,'cate_id'=>2,'status'=>0])->count();
        //用户总积分 = 可使用+待激活
        $userinfo['integral_all'] = $userinfo['integral']+$userinfo['integral_active'];
        //待付款 数量
        $userinfo['daifukuan'] = Db::name('goods_order')->where(['uid'=>$uid,'status'=>1])->count();
        //待发货 数量
        $userinfo['daifahuo'] = Db::name('goods_order')->where(['uid'=>$uid,'status'=>2])->count();
        //待收货 数量
        $userinfo['daishouhuo'] = Db::name('goods_order')->where(['uid'=>$uid,'status'=>3])->count();
        //售后 数量
        $userinfo['shouhou'] = Db::name('goods_order')->where(['uid'=>$uid,'status'=>4])->count();
        //支付密码是否设置
        $userinfo['pay_password'] = $userinfo['pay_password'] ? true : false;
        return $this->ret_success('获取成功',$userinfo);
    }

    //用户信息编辑
    public function userInfoEdit()
    {
        $uid = $this->request->userinfo['uid'];
        $sex = $this->request->param('sex/d');//性别
        $birthday = $this->request->param('birthday/s');//生日
        $birthday = $birthday ?  strtotime($birthday) : '';
        if(!in_array($sex,[0,1,2])) return $this->ret_faild('sex错误');
        $data = [
            'sex' =>$sex,
            'birthday' => $birthday,
        ];
        $r = Db::name('user')->where(['uid'=>$uid])->update($data);
        if($r !== false) {
            return $this->ret_success('修改成功');
        }
        return $this->ret_faild('修改失败');
    }

    //获取用户商品订单列表
    public function  goodsOrderList()
    {
        $uid = $this->request->userinfo['uid']??1;
        $status = $this->request->param('status/d'); //订单状态
        $page = $this->request->param('page/d',1);//页码 默认第一页
        $where['uid'] = $uid;
        if($status !== null){
            $where['status'] = $status;
        }
        $list = Db::name('goods_order_copy')->where($where)->page($page,$this->plimit)->select()->toArray();
        if (empty($list)){
            return $this->ret_success('暂无数据');
        }
        return $this->ret_success('获取订单成功',$list);
    }

    //我的拼团
    public function myCombination()
    {

    }

    //我的服务订单
    public function serviceOrderList()
    {

    }

    //我的收藏 1=商品 2=门店 3=服务
    public function myCollections()
    {
        $uid = $this->request->usseinfo['uid'];
        $typeid = $this->request->param('typeid/d',1);
        $page = $this->request->param('page/d',1);//第一页
        switch ($typeid) {
            case 1:
                //商品收藏
                $list = [];
                break;
            case 2:
                //门店收藏
                $list = [];
                break;
            case 3:
                //服务收藏
                $list = [];
                break;
            default:
                return $this->ret_faild('typeid错误');
                break;
        }
        return $this->ret_success('获取收藏列表成功',$list);
    }
    //我的团队 列表  1=下级，2=平级，3=上级
    public function myTeam()
    {
        $userinfo = $this->request->userinfo;
        $typeid = $this->request->param('typeid/d',2);//默认平级
        $page = $this->request->param('page/d',1);//第一页
        switch ($typeid) {
            case 1: //下级
                $where['spread_uid'] = $userinfo['uid']; //我的下级
                $where['user_type'] = $userinfo['user_type']-1; //并且低我一级
                $typemsg = '下级';
                break;
            case 2://平级
                $where['uid'] = ['<>',$userinfo['uid']];//并且不包含自己
                $where['user_type'] = $userinfo['user_type']; //和我平级
                $typemsg = '平级';
                break;
            case 3: //上级
                $where['uid'] = $userinfo['spread_uid']; //我的上级
                $where['user_type'] = $userinfo['user_type']+1; //并且高我一级
                $typemsg = '上级';
                break;
            default:
                return $this->ret_faild('typeid错误');
                break;
        }
        $where['status'] = 0; // 用户正常
        $list = Db::name('user')
            ->field('uid,nickname,phone,avatar,add_time')
            ->where($where)
            ->order('uid','desc')
            ->page($page,$this->plimit)
            ->select()
            ->each(function ($item,$key){
                $item['add_time'] = date('Y-m-d H:i:s',$item['add_time']);
                return $item;
            })
            ->toArray();
        if(empty($list)){
            return $this->ret_success('暂无数据',['list'=>[],'is_request'=>1]);//不需要在请求了
        }
        return $this->ret_success($typemsg.'获取成功',['list'=>$list,'is_request'=>0]);
    }

    //获取某个用户的下级
    public function userChild()
    {
        $cllick_uid = $this->request->param('uid/d');//点击的用户ID
        $page = $this->request->param('page/d',1);//页码
        Db::name('user')
            ->field('uid,nickname,avatar,phone,add_time')
            ->where(['spread_uid'=>$cllick_uid,'status'=>0])
            ->page($page,$this->plimit)
            ->order('uid','desc')
            ->select()
            ->each(function ($item,$key){
                $item['add_time'] = date('Y-m-d H:i:s',$item['add_time']);
                return $item;
            })
            ->toArray();
        if(empty($list)){
            return $this->ret_success('暂无下级数据',['list'=>[],'is_request'=>1]);//不需要在请求了
        }
        return $this->ret_success('获取用户下级成功',['list'=>$list,'is_request'=>0]);
    }

    //我的消息
    public function myMsg()
    {
        $uid = $this->request->userinfo['uid'] ?? 1;
        $msg = [];
        $msg['system'] = Db::name('sys_message')
            ->field('title text,send_time')
            ->order('id',"desc")
            ->limit(1)
            ->select()
            ->each(function ($item,$key){
                $item['send_time'] = date('Y-m-d H:i:s',$item['send_time']);
                return $item;
            })
            ->toArray();
        $msg['goods'] = DB::name('user_message')
            ->field('content text,send_time')
            ->where(['uid'=>$uid,'typeid'=>1])
            ->order('id','desc')
            ->limit(1)
            ->select()
            ->each(function ($item,$key){
                $item['send_time'] = date('Y-m-d H:i:s',$item['send_time']);
                return $item;
            })
            ->toArray();
        $msg['service'] = DB::name('user_message')
            ->field('content text,send_time')
            ->where(['uid'=>$uid,'typeid'=>2])
            ->order('id','desc')
            ->limit(1)
            ->select()
            ->each(function ($item,$key){
                $item['send_time'] = date('Y-m-d H:i:s',$item['send_time']);
                return $item;
            })
            ->toArray();
        return $this->ret_success('获取我的消息成功',$msg);

    }
    //我的消息 具体列表
    public function myMsgList()
    {
        $uid = $this->request->userinfo['uid'] ?? 1;
        $typeid = $this->request->param('typeid/d',1);//1=系统消息 2=商品消息 3=服务消息
        $page = $this->request->param('page/d',1);
        switch ($typeid) {
            case 1:  //系统消息
                $list = Db::name('sys_message')
                    ->field('title,content,send_time')
                    ->order('id','desc')
                    ->page($page,$this->plimit)
                    ->select()
                    ->each(function ($item,$key){
                        $item['send_time'] = date('Y-m-d H:i:s',$item['send_time']);
                        return $item;
                    })
                    ->toArray();
                break;
            case 2:  //商品消息消息
                $list = Db::name('user_message')
                    ->field('title,content,send_time')
                    ->where(['uid'=>$uid,'typeid'=>1])
                    ->order('id','desc')
                    ->page($page,$this->plimit)
                    ->select()
                    ->each(function ($item,$key){
                        $item['send_time'] = date('Y-m-d H:i:s',$item['send_time']);
                        return $item;
                    })
                    ->toArray();
                break;
            case 3:  //服务消息
                $list = Db::name('user_message')
                    ->field('title,content,send_time')
                    ->where(['uid'=>$uid,'typeid'=>2])
                    ->order('id','desc')
                    ->page($page,$this->plimit)
                    ->select()
                    ->each(function ($item,$key){
                        $item['send_time'] = date('Y-m-d H:i:s',$item['send_time']);
                        return $item;
                    })
                    ->toArray();
                break;
            default:
                return $this->ret_faild('typeid错误');
                break;
        }
        if (empty($list)) {
            return $this->ret_success('暂无更多消息',['list'=>[],'is_request'=>1]);//不要再请求了
        }
        return $this->ret_success('获取消息列表成功',['list'=>$list,'is_request'=>0]);
     }

    //我的推广二维码 邀请码
    public function  myQrcode()
    {
        $uid = $this->request->userinfo['uid'] ;
        $pageurl = $this->request->param('pageurl/s','pages/index/index');//跳转到小程序的页面

        $filename = $uid.str_replace('/','_',$pageurl).'.png';//保存图片重命名 规则
        $filepath =  config('mini.uidQrcode').$filename;//图片存放路径 完整路径
        if(file_exists($filepath)){
            //图片存在直接返回
            return $this->ret_success('获取推广码成功',['qrcode'=> config('mini.uidQrcodeShow').$filename]);
        }else{
            //图片不存在
            $Wxmini = new WxminiService();
            $res = $Wxmini->getQrcodeParam($uid,$pageurl,$filename);
            if($res){
                return $this->ret_success('获取推广码成功',['qrcode'=> config('mini.uidQrcodeShow').$filename]);
            }else{
                //获取微信小程序码失败
                return $this->ret_faild('获取推广码失败');
            }
        }
    }

    //用户意见反馈
    public function userBack()
    {
        $user = $this->request->userinfo;
        $imgs =  $this->request->param('imgs');//多图 数组
        $content = $this->request->param('content');
        if (empty($content)) return $this->ret_faild('反馈内容不能为空');
        if (strlen($content)>999) return $this->ret_faild('反馈内容过长');
        if (!is_array($imgs))  return $this->ret_faild('imgs参数错误');

        //小程序 文本内容安全校验
        $Wxmini = new WxminiService();
        $result = $Wxmini->checkText($content);
        if(!$result) return $this->ret_faild('存在敏感信息');
        $data = [
            'uid'       =>  $user['uid'],
            'real_name' =>  $user['real_name'] ?? $user['nickname'],
            'phone'     =>  $user['phone'],
            'content'   =>  $content,
            'imgs'      =>  json_encode($imgs),//图片数组json
        ];
        $r = Db::name('user_feedback')->insert($data);
        if($r) {
            return $this->ret_success('反馈成功');
        }
        return $this->ret_faild('反馈异常');
    }

    //用户设置交易密码 需要先验证短信验证码
    public function setPayPassword()
    {
        $uid = $this->request->userinfo['uid'];
        $new_password = $this->request->param('new_password/s');
        $new_password2 = $this->request->param('new_password2/s');
        if (!$new_password || !$new_password2) {
            return $this->ret_faild('请输入支付密码');
        }
        if ($new_password !== $new_password2) {
            return $this->ret_faild('两次密码不一致');
        }
        if (strlen($new_password)!=6){
            return $this->ret_faild('请输入六位支付密码');
        }
        $pay_password = create_pay_password($uid,$new_password);
        $r = Db::name('user')->where(['uid'=>$uid])->update(['pay_password'=>$pay_password]);
        if($r !== false) {
            return $this->ret_success('设置成功');
        }
        return $this->ret_faild('设置失败');
    }

    //我的余额记录
    public function moneyLog()
    {
        $uid = $this->request->userinfo['uid'];
        $page = $this->request->param('page/d',1);
        $list = Db::name('user_money_log')
            ->field(['change_time','remark','amonut','typeid'])
            ->where(['uid'=>$uid])
            ->order('id','desc')
            ->page($page,$this->plimit)
            ->select()
            ->each(function ($item,$key){
                $item['change_time'] = date('Y-m-d H:i:s',$item['change_time']);
                return $item;
            })
            ->toArray();
        if (empty($list)) {
            return $this->ret_success('暂无更多记录',['list'=>[],'is_request'=>1]);//不要再请求了
        }
        return $this->ret_success('获取余额记录成功',['list'=>$list,'is_request'=>0]);
    }

    //我的佣金记录
    public function brokerageLog()
    {
        $uid = $this->request->userinfo['uid'];
        $page = $this->request->param('page/d',1);
        $list = Db::name('user_brokerage_log')
            ->field(['change_time','remark','amonut','typeid','link_type','link_id'])
            ->where(['uid'=>$uid])
            ->order('id','desc')
            ->page($page,$this->plimit)
            ->select()
            ->each(function ($item,$key){
                $item['change_time'] = date('Y-m-d H:i:s',$item['change_time']);
                return $item;
            })
            ->toArray();
        if (empty($list)) {
            return $this->ret_success('暂无更多记录',['list'=>[],'is_request'=>1]);//不要再请求了
        }
        return $this->ret_success('获取佣金记录成功',['list'=>$list,'is_request'=>0]);
    }

    //我的佣金记录 详情  未完成
    public function brokerageLogInfo()
    {

    }


    //我的积分记录
    public function integralLog()
    {
        $uid = $this->request->userinfo['uid'];
        $page = $this->rrokerageequest->param('page/d',1);
        $list = Db::name('user_integral_log')
            ->field(['change_time','remark','amonut','typeid'])
            ->where(['uid'=>$uid])
            ->order('id','desc')
            ->page($page,$this->plimit)
            ->select()
            ->each(function ($item,$key){
                $item['change_time'] = date('Y-m-d H:i:s',$item['change_time']);
                return $item;
            })
            ->toArray();
        if (empty($list)) {
            return $this->ret_success('暂无更多记录',['list'=>[],'is_request'=>1]);//不要再请求了
        }
        return $this->ret_success('获取积分记录成功',['list'=>$list,'is_request'=>0]);
    }
    //用户余额 转赠
    public function moneyDonate()
    {
        $userinfo = $this->request->userinfo;
        $money = $this->request->param('money');//赠送余额
        $phone = $this->request->param('phone');//接收方账号手机号
        $pay_password = $this->request->param('paypassword');//交易密码
        $pay_password_mi = create_pay_password($userinfo['uid'],$pay_password);
        $checkphone = RegularService::phoneRegular($phone);
        if (!$checkphone)  return $this->ret_faild('请输入正确格式的手机号');
        if(!isAmount($money)) return $this->ret_faild('请输入合法金额');
        if($money>$userinfo['now_money']) return $this->ret_faild('金额超出可用余额');
        if($phone == $userinfo['phone'])  return $this->ret_faild('不能转赠给自己');
        $finduser = Db::name('user')->where(['phone'=>$phone])->find();
        if(!$finduser) return $this->ret_faild('对方账户未找到');
        if($pay_password_mi!=$userinfo['pay_password']) return $this->ret_faild('交易密码错误');
        //校验完成 开始转赠
        //自己的余额记录
        $user_log = [];
        $user_log['uid']            = $userinfo['uid'];
        $user_log['remark']         = '转赠余额给:'.$phone;
        $user_log['typeid']         = 2;//支出
        $user_log['amonut']         = $money;
        $user_log['amount_before']  = $userinfo['now_money'];
        $user_log['amount_after']   = $userinfo['now_money']-$money;
        $user_log['change_time']    = time();
        $user_log['change_status']  = 1;
        $user_log['link_type']      = 6; //转赠他人;
        //他人的余额记录
        $finduser_log = [];
        $finduser_log['uid']            = $finduser['uid'];
        $finduser_log['remark']         = '获得余额转赠';
        $finduser_log['typeid']         = 1;//收入
        $finduser_log['amonut']         = $money;
        $finduser_log['amount_before']  = $finduser['now_money'];
        $finduser_log['amount_after']   = $finduser['now_money']+$money;
        $finduser_log['change_time']    = time();
        $finduser_log['change_status']  = 1;
        $finduser_log['link_type']      = 5; //收到转赠;
        Db::startTrans();
        try {
            $user_update['now_money'] = Db::raw('now_money-'.$money);
            $finduser_update['now_money'] = Db::raw('now_money+'.$money);
            Db::name('user')->where(['uid'=>$userinfo['uid']])->update($user_update);//自己减
            Db::name('user')->where(['uid'=>$finduser['uid']])->update($finduser_update);//对方加
            Db::name('user_money_log')->insert($user_log); //自己转赠记录
            Db::name('user_money_log')->insert($finduser_log);//对方 收到转赠记录
            // 提交事务
            Db::commit();
            return $this->ret_success('赠送成功');
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return $this->ret_faild('赠送失败');
        }
    }

    //用户余额 提现
    public function moneyOut()
    {

    }

    //用户佣金 转换成余额  1:1转换
    public function brokerageToMoney()
    {
        $userinfo = $this->request->userinfo;
        $money = $this->request->param('money');//转换金额
        if(!isAmount($money)) return $this->ret_faild('请输入合法金额');
        if($money>$userinfo['brokerage_price']) return $this->ret_faild('佣金超出可用佣金');
        //校验完成 开始转换
        //自己的佣金记录 支出
        $user_brokerage_log = [];
        $user_brokerage_log['uid']            = $userinfo['uid'];
        $user_brokerage_log['remark']         = '佣金转换成余额';
        $user_brokerage_log['typeid']         = 2;//支出
        $user_brokerage_log['amonut']         = $money;
        $user_brokerage_log['amount_before']  = $userinfo['brokerage_price'];
        $user_brokerage_log['amount_after']   = $userinfo['brokerage_price']-$money;
        $user_brokerage_log['change_time']    = time();
        $user_brokerage_log['change_status']  = 1;
        $user_brokerage_log['link_type']      = 6; //佣金转换成余额
        //自己的余额记录 收入
        $user_money_log = [];
        $user_money_log['uid']            = $userinfo['uid'];
        $user_money_log['remark']         = '佣金转换成余额';
        $user_money_log['typeid']         = 1;//收入
        $user_money_log['amonut']         = $money;
        $user_money_log['amount_before']  = $userinfo['now_money'];
        $user_money_log['amount_after']   = $userinfo['now_money']+$money;
        $user_money_log['change_time']    = time();
        $user_money_log['change_status']  = 1;
        $user_money_log['link_type']      = 7; //佣金转换成余额
        Db::startTrans();
        try {
            //修改自己的佣金 余额
            $update = [];
            $update['now_money'] = Db::raw('now_money+'.$money);
            $update['brokerage_price'] = Db::raw('brokerage_price-'.$money);
            Db::name('user')->where(['uid'=>$userinfo['uid']])->update($update);//自己佣金减 余额加
            Db::name('user_brokerage_log')->insert($user_brokerage_log);//自己佣金支出记录
            Db::name('user_money_log')->insert($user_money_log); //自己余额收入记录
            // 提交事务
            Db::commit();
            return $this->ret_success('转换成功');
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return $this->ret_faild('转换失败');
        }
    }

    //用户佣金 提现
    public function brokerageOut()
    {
        $userinfo = $this->request->userinfo;
        $money = $this->request->param('money/f');//提现余额
        $account = $this->request->param('account');//提现账号
        if(empty($account))   return $this->ret_faild('提现账号不能为空');
        if(!isAmount($money)) return $this->ret_faild('请输入合法金额');
        if($money>$userinfo['brokerage_price']) return $this->ret_faild('金额超出可用佣金');
        //校验通过 开始添加记录
        Db::startTrans();
        try {
            //佣金提现记录开始
            $brokerage_withdraw_log = [];
            $brokerage_withdraw_log['uid'] = $userinfo['uid'];
            $brokerage_withdraw_log['nickname'] = $userinfo['nickname'];
            $brokerage_withdraw_log['phone'] = $userinfo['phone'];
            $brokerage_withdraw_log['real_name'] = $userinfo['real_name'];
            $brokerage_withdraw_log['account'] = $account;
            $brokerage_withdraw_log['amount'] = $money;
            $brokerage_withdraw_log['add_time'] = time();
            $brokerage_withdraw_log['status'] = 0;//审核中
            $link_id = Db::name('user_brokerage_withdraw')->insertGetId($brokerage_withdraw_log);
            //佣金变化记录
            $user_brokerage_log = [];
            $user_brokerage_log['uid']            = $userinfo['uid'];
            $user_brokerage_log['remark']         = '提现到:'.$account;
            $user_brokerage_log['typeid']         = 2;//支出
            $user_brokerage_log['amonut']         = $money;
            $user_brokerage_log['amount_before']  = $userinfo['brokerage_price'];
            $user_brokerage_log['amount_after']   = $userinfo['brokerage_price']-$money;
            $user_brokerage_log['change_time']    = time();
            $user_brokerage_log['change_status']  = 3;//审核中
            $user_brokerage_log['link_type']      = 3; //佣金提现;
            $user_brokerage_log['link_id']        = $link_id; //佣金提现记录id;
            Db::name('user_brokerage_log')->insertGetId($user_brokerage_log);
            //自己的佣金余额减少
            Db::name('user')->where(['uid'=>$userinfo['uid']])->dec('brokerage_price',$money)->update();
            // 提交事务
            Db::commit();
            return $this->ret_success('提现申请成功');
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return $this->ret_faild('提现申请失败');
        }
    }

    //用户佣金 转赠
    public function brokerageDonate()
    {
        $userinfo = $this->request->userinfo;
        $money = $this->request->param('money/f');//赠送余额
        $phone = $this->request->param('phone');//接收方账号手机号
        $checkphone = RegularService::phoneRegular($phone);
        if (!$checkphone)  return $this->ret_faild('请输入正确格式的手机号');
        if(!isAmount($money)) return $this->ret_faild('请输入合法金额');
        if($money>$userinfo['brokerage_price']) return $this->ret_faild('金额超出可用佣金');
        if($phone == $userinfo['phone'])  return $this->ret_faild('不能转赠给自己');
        $finduser = Db::name('user')->where([['phone','=',$phone],['user_type','>',0]])->find(); //普通用户没有佣金
        if(!$finduser) return $this->ret_faild('对方账户未找到');
        //校验完成 开始转赠
        //自己的佣金记录
        $user_log = [];
        $user_log['uid']            = $userinfo['uid'];
        $user_log['remark']         = '转赠佣金给:'.$phone;
        $user_log['typeid']         = 2;//支出
        $user_log['amonut']         = $money;
        $user_log['amount_before']  = $userinfo['brokerage_price'];
        $user_log['amount_after']   = $userinfo['brokerage_price']-$money;
        $user_log['change_time']    = time();
        $user_log['change_status']  = 1;
        $user_log['link_type']      = 5; //转赠他人;
        //他人的佣金记录
        $finduser_log = [];
        $finduser_log['uid']            = $finduser['uid'];
        $finduser_log['remark']         = '获得佣金转赠';
        $finduser_log['typeid']         = 1;//收入
        $finduser_log['amonut']         = $money;
        $finduser_log['amount_before']  = $finduser['brokerage_price'];
        $finduser_log['amount_after']   = $finduser['brokerage_price']+$money;
        $finduser_log['change_time']    = time();
        $finduser_log['change_status']  = 1;
        $finduser_log['link_type']      = 4; //收到转赠;
        Db::startTrans();
        try {
            Db::name('user')->where(['uid'=>$userinfo['uid']])->dec('brokerage_price', $money)->update();//自己减
            Db::name('user')->where(['uid'=>$finduser['uid']])->inc('brokerage_price', $money)->update();//对方加
            Db::name('user_brokerage_log')->insert($user_log); //自己转赠记录
            Db::name('user_brokerage_log')->insert($finduser_log);//对方 收到转赠记录
            // 提交事务
            Db::commit();
            return $this->ret_success('赠送成功');
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return $this->ret_faild('赠送失败');
        }
    }

    //用户积分 转赠
    public function integralDonate()
    {

    }

    //获取用户收货地址
    public function getAddress()
    {
        $uid = $this->request->userinfo['uid'] ?? 1;//用户iD
        $where['uid'] = $uid;
        $where['is_del'] = 0;//未删除
        $field = ['id','real_name','phone','province_id','city_id','district_id','province','city','district','datail','is_default'];
        $sort = ['is_default'=>'desc','id'=>'desc'];
        $list = Db::name('user_address')
            ->field($field)
            ->where($where)
            ->order($sort)
            ->select();
        return $this->ret_success('获取用户收货地址成功',$list);//排序第一条是默认收货地址
    }
    //操作用户收货地址
    public function actAddress()
    {
        $uid = $this->request->userinfo['uid'] ?? 1;//用户iD
        $act = $this->request->param('act/s');//用户操作什么
        $data = $this->request->post();//传入数据
        $data['uid'] = $uid;
        unset($data['token'],$data['act']);//移除不必要的数据
        switch ($act){
            case 'add'://新增收货地址
                $find_count = Db::name('user_address')->where(['uid'=>$uid,'is_del'=>0])->count();
                if($find_count>=10)  return $this->ret_faild('收货地址不能超过十个');
                $msg = '新增地址';
                $data['add_time'] = time();
                break;
            case 'edit'://修改收货地址
                if($data['is_default']==1){ //修改时设为默认收货地址
                    $where[] = [
                        ['id','<>',$data['id']],
                        ['uid','=',$uid],
                        ['is_del','=',0],
                    ];
                    Db::name('user_address')
                        ->where($where)
                        ->update(['is_default'=>0]); //把其他的默认改为非默认
                }
                $msg = '修改地址';
                break;
            case 'del'://删除收货地址
                $data['is_del'] = 1;
                $msg = '删除地址';
                break;
            case 'setdefault'://设置默认
                $where[] = [
                    ['id','<>',$data['id']],
                    ['uid','=',$uid],
                    ['is_del','=',0],
                ];
                Db::name('user_address')
                    ->where($where)
                    ->update(['is_default'=>0]); //把其他的默认改为非默认
                $msg = '设为默认地址';
                $data['is_default'] = 1;
                break;
            default:
                return $this->ret_faild('act错误');
                break;
        }
        //数据验证器
        try {
            validate(UserAddress::class)
                ->scene($act) //验证场景
                ->check($data);
        } catch (ValidateException $e) {
            // 验证失败 输出错误信息
            //dump($e->getError());
            return $this->ret_faild($e->getError());
        }
        $r = Db::name('user_address')->save($data); //把其他的默认改为非默认
        if ($r)   return $this->ret_success($msg.'成功');
        return $this->ret_faild($msg.'失败');
    }

}