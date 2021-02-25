<?php
declare (strict_types = 1);

namespace app\mini\controller\v1;

use think\facade\Db;

/**
 * 小程序首页
 * Class Index
 * @package app\mini\controller
 *
 */

class Index extends Base
{
//    /**
//     * 控制前中间件 改用路由中间件
//     * 需要检验登录token  apiCheckToken => ['only' => '']
//     * 不需要请求校验  apiCheckApi => ['except'=> '']
//     * @var \string[][]
//     */
//    protected $middleware = [
//        'apiCheckApi',
//        'apiCheckToken' => ['only' => 'test,testSuccess']
//    ];


    //小程序首页
    public function index()
    {
        //首页轮播 商品图
        $banner = Db::name('goods_banner')->field(['banner,goods_id'])->where(['is_show'=>1])->select()->toarray();
        //服务分类


    }



    public function testSuccess()
    {
        $userinfo = [111,222];
        return $this->ret_success('测试成功',$userinfo);
    }

    public function testFaild()
    {
        $userinfo = [111,222];
        return $this->ret_faild('测试失败',$userinfo);
    }



}
