<?php
declare (strict_types = 1);

namespace app\mini\controller\v1;

use app\mini\service\SmsService;
use app\mini\service\RegularService;
use think\facade\Db;
use think\facade\Validate;
use app\mini\http\GaodeApi;
/**
 * 开放性的接口
 * Class Overt
 * @package app\mini\controller
 */
class Overt extends Base
{
    /**
     * 发送短信验证码
     * codetype  0=绑定手机号  1=设置交易支付密码
     */
    public function sendSmsCode()
    {

        $codetype =  $this->request->post('codetype'); //发送验证码类型
        $phone = $this->request->post('mobile');
        $checkphone = RegularService::phoneRegular($phone);
        if (!$checkphone)  return $this->ret_faild('请输入正确格式的手机号');
        $is_find = Db::name('user')->where(['phone'=>$phone])->find();
        switch ($codetype){
            case 0: //绑定手机号
                if($is_find) return $this->ret_faild('该手机号已被绑定');
                break;
            case 1: //设置验证码
                if(!$is_find) return $this->ret_faild('该手机号未被注册');
                break;
            default:
                return $this->ret_faild('验证码发送类型错误');
                break;
        }
        if (cache('smsMobile'.$phone.$codetype) )  return $this->ret_faild('请在一分钟后重新获取验证码');
        cache('smsMobile'.$phone.$codetype,$phone,60);
        $code = mt_rand(100000,999999);//验证码
        $send_content = "【莱美牙】亲爱的小主，您的验证码为".$code."，验证码将在10分钟后失效，请尽快验证喔~";
        $sendres = SmsService::smsSend($phone,$send_content);
        if($sendres){
            //发送成功 只记录成功的，失败的查看日志public/log/sms/年/月/日/smscodeerror.log
            $sms_ins = [
                'phone' => $phone,
                'template_id' => $codetype,//验证码类型
                'content' => $send_content,
                'code' => $code,
                'expiration' => time()+600,//十分钟后过期
                'send_time' => time(),
            ];
            $res = Db::name('sms_log')->insertGetId($sms_ins);
            return $this->ret_success('验证码发送成功');
        }
        return $this->ret_faild('验证码发送失败');
    }

    /**
     *  验证码单独校验接口
     *  设置交易密码之前
     */
    public function smsCodeValid()
    {
        $phone = $this->request->param('phone');
        $code = $this->request->param('smscode/d');//验证码
        $checkphone = RegularService::phoneRegular($phone);
        if (!$checkphone)  return $this->ret_faild('请输入正确格式的手机号');
        $userphone = $this->request->userinfo['phone'];
        if($phone != $userphone)  return $this->ret_faild('验证手机号和绑定手机号不一致');
        //验证码 校验
        $find = Db::name('sms_log')->field('expiration')->where(['phone'=>$phone,'code'=>$code])->find();
        if (!$find)  return $this->ret_faild('验证码错误');
        if ($find['expiration']<time()) return $this->ret_faild('验证码已过期');
        return $this->ret_success('验证通过');
    }

    /**
     * 获取省市区
     */
    public function getRegion()
    {
        $level = $this->request->param('level/d',0);
        $where['level'] = $level;
        $region = Db::name('region')->field(['id','name'])->where($where)->select()->toArray();
        return $this->ret_success('获取区域成功',$region);
    }
    /**
     * 获取省市区 下级
     */
    public function getRegionChild()
    {
        $id = $this->request->param('regionid/d',100000);//默认全国
        $where['pid'] = $id;
        $region = Db::name('region')->field(['id','name'])->where($where)->select()->toArray();
        return $this->ret_success('获取下级成功',$region);
    }

    /**
     * 单张图片上传
     */
    public function uploadImg()
    {
        // 获取表单上传文件
        $file = $this->request->file('file');
        $filetype = $this->request->param('filetype');
        if (!in_array($filetype, ['service_leave', 'store', 'feedback'])) {
            return $this->ret_faild('filetype错误');
        }
        if (!Validate::fileSize($file, 1024 * 1024 * 2)) {
            return $this->ret_faild('图片过大');
        }
        if (!Validate::fileExt($file, 'jpg,jpeg,png')) {
            return $this->ret_faild('图片格式错误');
        }
        $savename = \think\facade\Filesystem::disk('photo')->putFile($filetype, $file);
        $path = '/upload/images/' . $savename;//图片完整路径
        return $this->ret_success('上传成功', ['imgpath' => $path]);
    }

    /**
     * 逆地理编码
     * 根据经纬度获取用户的省市区
     */
    public function geocoderRgeo()
    {
//        $latitude = "30.315762";//纬度36.659962
//        $longitude = "120.113119";//经度113.799176
        $longitude = $this->request->param('longitude/s');//用户经度
        $latitude = $this->request->param('latitude/s');//用户纬度
        if(!$longitude || !$latitude)  return $this->ret_faild('参数丢失');
        $res = GaodeApi::getAddress($longitude,$latitude);
        if($res['status']==1){
            $province = $res['regeocode']['addressComponent']['province'];//省
            $city = $res['regeocode']['addressComponent']['city'];//市
            $district = $res['regeocode']['addressComponent']['district'];//区
            $find = Db::name('region')->where(['name'=>$district])->find();
            if(!$find) return $this->ret_faild('地址出错');
            $path_str = $find['path'];
            $path_arr = explode(',',$path_str);
            $province_id = $path_arr[2]; //省ID
            $city_id = $path_arr[3]; //市ID
            $district_id = $path_arr[4]; //区ID
            $list = [
                'province' => $province,
                'city' => $city,
                'district' => $district,
                'province_id' => $province_id,
                'city_id' => $city_id,
                'district_id' => $district_id,
            ];
            return $this->ret_success('获取成功', $list);
        }
        return $this->ret_faild('访问出错');

    }

}