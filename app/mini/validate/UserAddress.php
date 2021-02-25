<?php
namespace app\mini\validate;

use think\Validate;

class UserAddress extends Validate
{
    protected $rule =   [
        'id'    =>  'require',
        'real_name'  => 'require|max:25',
        'phone'   => 'mobile',
        'province' => 'require',
        'city' => 'require',
        'district' => 'require',
        'province_id' => 'require',
        'city_id' => 'require',
        'district_id' => 'require',
        'datail' => 'require',
        'is_default' => 'in:0,1',

    ];

    protected $message  =   [
        'id.require' => 'ID必须',
        'real_name.require' => '收件人名称必须',
        'real_name.max'     => '收件人名称最多不能超过25个字符',
        'phone.mobile'   => '请输入正确的手机号',
        'province.require'  => '省份必选',
        'city.require'  => '城市必选',
        'district.require'  => '区域必选',
        'province_id.require'  => '省ID必选',
        'city_id.require'  => '市ID必选',
        'district_id.require'  => '区ID必选',
        'datail.require'  => '详细地址必填',
    ];

    protected $scene = [
        'add'  =>  ['real_name','phone','province','city','district','province_id','city_id','district_id','datail','is_default'],
        'edit'  =>  ['id','real_name','phone','province','city','district','province_id','city_id','district_id','datail','is_default'],
        'del'  =>  ['id'],
        'setdefault'  =>  ['id'],
    ];
}