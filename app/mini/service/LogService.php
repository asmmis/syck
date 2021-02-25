<?php
namespace app\mini\service;


/**
 * 日志服务
 * Class LogService
 * @package app\mini\service
 */
class LogService
{

    //写入日志
    public static function writeLog($filetype,$filename,$content)
    {
        //先创建 文件夹
        $dir_path = self::makeDirDate($filetype);
        //生成完成日志路径 示例：public/curl/2020/12/15/20201215.txt
        $path = $dir_path.'/'.$filename;
        $content = date('Y-m-d H:i:s').'=='.json_encode($content,JSON_UNESCAPED_UNICODE).PHP_EOL;
        file_put_contents($path,$content,FILE_APPEND | LOCK_EX);


    }
    //生成年月日文件夹
    public static function makeDirDate($dir_path)
    {
        //日志文件
        $log_path = config('mini.apiLog');
        $dir_path = $log_path.$dir_path;
        if(!file_exists($dir_path))
        {
            //iconv防止中文名乱码
             mkdir(iconv("UTF-8", "GBK", $dir_path),0757,true);
        }
        $dir_path = $dir_path.'/'.date('Y').'/'.date('m').'/'.date('d');
        if(!is_dir($dir_path)){
            mkdir($dir_path,0757,true);
        }
//        //年份
//        $dir_path = $dir_path.'/'.date('Y');
//        if(!file_exists($dir_path))
//        {
//             mkdir($dir_path,0757,true);
//        }
//        //月份
//        $dir_path = $dir_path.'/'.date('m');
//        if(!file_exists($dir_path))
//        {
//            mkdir($dir_path,0757,true);
//        }
//        //日期
//        $dir_path = $dir_path.'/'.date('d');
//        if(!file_exists($dir_path))
//        {
//            mkdir($dir_path,0757,true);
//        }
        return $dir_path;
    }

}
