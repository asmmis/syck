<?php
namespace app\mini\service;

use EasyWeChat\Factory;
use app\mini\service\LogService;

/**
 * 微信小程序
 * Class WxminiService
 * @package app\mini\service
 */
class  WxminiService{

    //小程序配置
    protected  $miniConfig;
    //小程序应用
    protected  $miniProgram;

    public function __construct()
    {
        $this->miniConfig = [
            'app_id'  => config('wechat.mini.app_id'),
            'secret'  => config('wechat.mini.secret'),
        ];
        $this->miniProgram = Factory::miniProgram($this->miniConfig);
    }

    //小程序登录获取openid
    public  function getLogin(string $code){
        $result = $this->miniProgram->auth->session($code);
        return $result;
    }

    //小程序消息解密
    public function getDecryptDate($session,$iv,$encryptedData)
    {
        $decryptedData = $this->miniProgram->encryptor->decryptData($session, $iv, $encryptedData);
        return $decryptedData;
    }

    //小程序文本内容安全校验 用于校验一段文本是否含有违法内容。 单个appid调用上限为2000次/分钟，1,000,000次/天
    // // 正常返回 0
    //{
    //    "errcode": "0",
    //    "errmsg": "ok"
    //}
    ////当 $content 内含有敏感信息，则返回 87014
    //{
    //    "errcode": 87014,
    //    "errmsg": "risky content"
    //}
    //
    public function checkText($content)
    {
        $result = $this->miniProgram->content_security->checkText($content);
        if ($result['errcode'] == 0) {
            return true;
        } else{
            return false;
        }
    }


    /**
     * 小程序二维码 带参数UID
     * @param $uid  用户ID
     * @param string $pageurl   跳转小程序页面
     * @param $filename 图片文件名称 例如 10000027pages_index_index.png ，设用于小程序活动码
     * @return bool
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\RuntimeException
     */
    public function getQrcodeParam($uid,$pageurl='pages/index/index',$filename)
    {
        $response =  $this->miniProgram->app_code->getUnlimit($uid, [
            'page'  => $pageurl
        ]);
        $path = config('mini.uidQrcode');//邀请码存放路径
        //$filename = $uid.str_replace('/','_',$pageurl).'.png';//保存图片重命名 规则
        if ($response instanceof \EasyWeChat\Kernel\Http\StreamResponse) {
            $filename = $response->saveAs($path, $filename);
            return  true;
        }
        LogService::writeLog('uidqrcode','fail.log',$response);//记录失败信息
        return false;
    }
}
