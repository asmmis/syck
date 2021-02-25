<?php


namespace app\hhr\middleware;


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
            if($path==$url) {
                $ismatch=true;
                break;
            }
        }
        if(!$ismatch){
                        //进行登陆判定
                        try {
                            if(!session('hhr_user_id'))
                                throw new Exception();
                        } catch (Exception $e) {
                            //redirect('http://www.iumanager.com/')->send();
                            return ret_faild('请登陆');
                        }
                    }
        return $next($request);
    }
}