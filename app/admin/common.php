<?php
// 这是系统自动生成的公共文件
use think\facade\Config;

/*
*返回统一格式
*/
function returnMsg($code, $data = [])
{
    $msg = Config::get('code.' . $code);
    $arr = [
        'code' => $code,
        'msg' => $msg,
        'data' => $data,
    ];
    return $arr;
}
