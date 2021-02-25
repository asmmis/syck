<?php
namespace app\mini\service;

class CurlService
{
    /**
     * get请求
     * @param $url
     * @return bool|string
     */
    public static function curlGet($url){
        $header = array(
            'Accept: application/json',
        );
        $curl = curl_init();
        //设置抓取的url
        curl_setopt($curl, CURLOPT_URL, $url);
        //设置头文件的信息作为数据流输出
        curl_setopt($curl, CURLOPT_HEADER, 0);
        // 超时设置,以秒为单位
        curl_setopt($curl, CURLOPT_TIMEOUT, 1);
        // 超时设置，以毫秒为单位
        // curl_setopt($curl, CURLOPT_TIMEOUT_MS, 500);
        // 设置请求头
        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        //设置获取的信息以文件流的形式返回，而不是直接输出。
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        //执行命令
        $data = curl_exec($curl);

        // 显示错误信息
        if (curl_error($curl)) {
            //print "Error: " . curl_error($curl);
        } else {
            // 打印返回的内容
            //var_dump($data);
            curl_close($curl);
        }
        return $data;
    }



    /**
     * @param $url 是请求的链接
     * @param $postdata 是传输的数据，数组格式
     */
    public static function curl_post( $url, $postdata ) {
        $header = array(
            'Accept: application/json',
        );
        //初始化
        $curl = curl_init();
        //设置抓取的url
        curl_setopt($curl, CURLOPT_URL, $url);
        //设置头文件的信息作为数据流输出
        curl_setopt($curl, CURLOPT_HEADER, 0);
        //设置获取的信息以文件流的形式返回，而不是直接输出。
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        // 超时设置
        curl_setopt($curl, CURLOPT_TIMEOUT, 10);
        // 超时设置，以毫秒为单位
        // curl_setopt($curl, CURLOPT_TIMEOUT_MS, 500);
        // 设置请求头
        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE );
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE );
        //设置post方式提交
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $postdata);
        //执行命令
        $data = curl_exec($curl);
        // 显示错误信息
        if (curl_error($curl)) {
           // print "Error: " . curl_error($curl);
        } else {
            // 打印返回的内容
            //var_dump($data);
            curl_close($curl);
        }
        return $data;
    }

}
