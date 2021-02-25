<?php

namespace app\mini\http;

/**
 * 高德地图API
 * Class GaodeApi
 */
class GaodeApi
{

    const KEY ='ba9da2c0043a68fd1ad2ac3b9bfec259';//高德应用 key



    /**
     * 逆地理编码 根据经纬度获取地理位置
     * @param $longitude 经度
     * @param $latitude 纬度
     * @return mixed
     */
    public static function getAddress($longitude,$latitude)
    {
        //https://restapi.amap.com/v3/geocode/regeo?output=json&location=116.310003,39.991957&key=b2f20184545gdfgd1bc962c480cd&radius=1000&extensions=all
        $url = 'https://restapi.amap.com/v3/geocode/regeo';
//        $latitude = "36.659962";//纬度36.659962
//        $longitude = "113.799176";//经度113.799176

        $local = "$longitude,$latitude";

        $regeo_url = "https://restapi.amap.com/v3/geocode/regeo";

        $address_location = $regeo_url . "?output=JSON&location=$local&key=".self::KEY;

        $data_location = file_get_contents($address_location);

        $result_local = json_decode($data_location, true);

        return $result_local;
       // dump($result_local);
        //^ array:4 [▼
        //  "status" => "1"
        //  "regeocode" => array:2 [▼
        //    "addressComponent" => array:12 [▼
        //      "city" => "杭州市"
        //      "province" => "浙江省"
        //      "adcode" => "330105"
        //      "district" => "拱墅区"
        //      "towncode" => "330105004000"
        //      "streetNumber" => array:5 [▶]
        //      "country" => "中国"
        //      "township" => "和睦街道"
        //      "businessAreas" => array:2 [▶]
        //      "building" => array:2 [▶]
        //      "neighborhood" => array:2 [▶]
        //      "citycode" => "0571"
        //    ]
        //    "formatted_address" => "浙江省杭州市拱墅区和睦街道化纤新村杭州汽车北站"
        //  ]
        //  "info" => "OK"
        //  "infocode" => "10000"
        //]
    }
}

