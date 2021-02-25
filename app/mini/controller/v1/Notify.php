<?php
declare (strict_types = 1);

namespace app\mini\controller\v1;

/**
 * 回调
 * Class Notify
 * @package app\mini\controller
 */
class Notify extends Base
{
    //微信支付回调
    public function channel()
    {
        return 'success1';
        //不要了放在payment里面
    }

}