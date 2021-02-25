<?php

namespace app\admin\validate;

use think\Validate;

class ShopValidate extends Validate
{

    protected $rule =   [

        'real_name'  => 'require|max:25',
        'phone'   => 'mobile',
        'store_name'    =>  'require|max:250',
        'province_id' => 'number',
        'city_id' => 'number',
        'district_id' => 'number',
        // 'addr' => 'require',
        'address' => 'require',
        'idcard_a' => 'require',
        'idcard_b' => 'require',
        'business_license' => 'require',
        'license' => 'require',

    ];

    protected $message  =   [
        'real_name.max'     => '联系人姓名最多不能超过25个字符',
        'phone.mobile'   => '请输入正确的手机号',
        'store_name.require' => '店铺名称必须',
        'store_name.max'     => '店铺名称最多不能超过25个字符',
        'province_id.require'  => '省ID是数字',
        'city_id.require'  => '市ID是数字',
        'district_id.require'  => '区ID是数字',
        // 'addr.require'  => '省市区地址必填',
        'address.require'  => '详细地址必填',
        'idcard_a.require'  => '身份证正面照必传',
        'idcard_b.require'  => '身份证反面照必传',
        'business_license.require'  => '营业执照必传',
        'license.require'  => '经营许可证必传',
    ];

    protected $scene = [
        'add'  =>  ['store_name,store_img,real_name,phone, address,info,idcard_a,idcard_b,longitude,latitude,license,business_license'],
    ];
}
