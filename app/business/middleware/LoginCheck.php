<?php


namespace app\business\middleware;


use app\business\model\Business;
use think\Exception;
use think\facade\Log;

class LoginCheck
{
    public $EXPECT_PATHS=array('User/apiLoginState','User/apiLogin');
    public function handle($request, \Closure $next)
    {
        $url=$request->pathinfo();
        $ismatch=false;
        foreach ($this->EXPECT_PATHS as $path){
//            if(preg_match(path,$url)==1) {
            if($path==$url){
                $ismatch=true;
                break;
            }
        }
        if(!$ismatch){
                        //进行登陆判定
                        try {
                            Business::loginCheck();
                        } catch (Exception $e) {
                            //redirect('http://www.iumanager.com/')->send();
                            return ret_faild('请登陆');
                        }
                    }
        return $next($request);
    }
}