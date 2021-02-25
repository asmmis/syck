<?php
declare (strict_types = 1);

namespace app\mini\middleware;

class CheckApi{
    /**
     * 处理请求
     * @param \think\Request $request
     * @param \Closure       $next
     * @return Response
     */
    public function handle($request, \Closure $next)
    {
        $timeStamp  =  $request->header('timeStamp');
        $randStr    =  $request->header('randStr');
        $apikey     =  $request->header('apikey');
        $sign       =  $request->header('sign');
        if(!$timeStamp || !$randStr || !$sign)
            return ret_json(-2,'header缺少必要参数');
        if(strlen($randStr)!== 9)
            return ret_json(-2,'randStr必须9位');
        if($apikey)
            return ret_json(-2,'apikey请勿传递');
        $apikey = config('mini.apiKey');
        //固定格式：apikey+时间戳+随机数
        $getSign = md5($apikey.$timeStamp.$randStr);
        if($sign != $getSign){
            return ret_json(-2,'sign校验错误');
        }

//        //限制一下重复请求 根据时间戳判断
//        $timeStamp  =  $request->header('timeStamp');
//        $act = $request->pathinfo();//请求地址
//        if(cache($act) == $timeStamp){
//            return ret_json('0','操作太频繁了');
//        }
//        cache($act,$timeStamp,1);//一秒钟

        return $next($request);
    }
}