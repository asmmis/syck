<?php
use think\facade\Route;

//自定义路由
// =============================== 测试路由功能开始================================
//测试路由
//Route::get("test","v1.test/test");
//测试路由 get
Route::get(":version/test/testroute1",":version.test/testroute1");
//测试路由 post
Route::post(":version.testroutepost",":version.test/testroute2")->middleware([\app\mini\middleware\CheckApi::class,\app\mini\middleware\CheckToken::class]);
//测试V1 v2 版本
Route::any(":version.testindex",":version.test/testindex");
//测试微信
Route::any(":version/wechat/test",":version.wechat/test")->middleware(\app\mini\middleware\CheckApi::class);
// =============================== 测试路由功能结束=================================


//支付回调地址 固定版本  域名/mini/payment/notifychannel
Route::any("payment/notifychannel","v1.payment/notifyChannel");

// ---------------------------------- 微信模块 开始 -------------------------------------------
//微信登录
Route::post(":version/wechat/wxlogin",":version.wechat/wxLogin");
//手机号绑定 新用户注册
Route::post(":version/wechat/bindmobile",":version.wechat/bindMobile");
// ---------------------------------- 微信模块  结束 ------------------------------------------


// ---------------------------------- 公共模块  开始 ------------------------------------------
//发送短信验证码
Route::post(":version/overt/sendsmscode",":version.overt/sendSmsCode");
//验证短信验证码 目前就只有设置交易密码之前用
Route::post(":version/overt/smscodevalid",":version.overt/smsCodeValid")->middleware(\app\mini\middleware\CheckToken::class);
//获取省市区 下级
Route::post(":version/overt/getregionchild",":version.overt/getRegionChild");
//获取省市区
Route::post(":version/overt/getregion",":version.overt/getRegion");
//单张图片上传
Route::post(":version/overt/uploadimg",":version.overt/uploadImg");
//经纬度兑换用户省市区地址
Route::post(":version/overt/geocoderrgeo",":version.overt/geocoderRgeo");
// ---------------------------------- 公共模块  结束 ------------------------------------------


// ---------------------------------- 用户模块  开始 ------------------------------------------
//获取用户个人信息  需要token
Route::post(":version/user/info",":version.user/userInfo")->middleware(\app\mini\middleware\CheckToken::class);
//编辑用户信息
Route::post(":version/user/editinfo",":version.user/userInfoEdit")->middleware(\app\mini\middleware\CheckToken::class);

//获取用户商品订单列表
Route::post(":version/user/orderlist",":version.user/orderList")->middleware(\app\mini\middleware\CheckToken::class);
//我的拼团 -----
Route::post(":version/user/mycombination",":version.user/myCombination")->middleware(\app\mini\middleware\CheckToken::class);
//我的服务订单 -----
Route::post(":version/user/serviceorderlist",":version.user/serviceOrderList")->middleware(\app\mini\middleware\CheckToken::class);
//我的收藏 用户收藏列表
Route::post(":version/user/mycollections",":version.user/myCollections")->middleware(\app\mini\middleware\CheckToken::class);


//我的团队
Route::post(":version/user/myteam",":version.user/myTeam")->middleware(\app\mini\middleware\CheckToken::class);
//我的团队 点击用户进入用户的下级
Route::post(":version/user/userchild",":version.user/userChild")->middleware(\app\mini\middleware\CheckToken::class);
//我的消息
Route::post(":version/user/mymsg",":version.user/myMsg")->middleware(\app\mini\middleware\CheckToken::class);
//我的消息列表
Route::post(":version/user/msglist",":version.user/myMsgList")->middleware(\app\mini\middleware\CheckToken::class);
//我的推广二维码
Route::post(":version/user/myqrcode",":version.user/myQrcode")->middleware(\app\mini\middleware\CheckToken::class);
//意见反馈
Route::post(":version/user/userback",":version.user/userBack")->middleware(\app\mini\middleware\CheckToken::class);
//设置交易密码 之前先验证短信验证码
Route::post(":version/user/setpaypassword",":version.user/setPayPassword")->middleware(\app\mini\middleware\CheckToken::class);

//我的余额记录
Route::post(":version/user/moneylog",":version.user/moneyLog")->middleware(\app\mini\middleware\CheckToken::class);
//我的佣金记录
Route::post(":version/user/brokeragelog",":version.user/brokerageLog")->middleware(\app\mini\middleware\CheckToken::class);

//我的佣金记录 详情
Route::post(":version/user/brokerageinfo",":version.user/brokerageLogInfo")->middleware(\app\mini\middleware\CheckToken::class);

//我的积分记录
Route::post(":version/user/integrallog",":version.user/integralLog")->middleware(\app\mini\middleware\CheckToken::class);
//用户余额 转赠
Route::post(":version/user/moneydonate",":version.user/moneyDonate")->middleware(\app\mini\middleware\CheckToken::class);
//用户余额 提现

//用户佣金 转换成余额
Route::post(":version/user/brokeragetomoney",":version.user/brokerageToMoney")->middleware(\app\mini\middleware\CheckToken::class);
//用户佣金 提现
Route::post(":version/user/brokerageout",":version.user/brokerageOut")->middleware(\app\mini\middleware\CheckToken::class);
//用户佣金 转赠
Route::post(":version/user/brokeragedonate",":version.user/brokerageDonate")->middleware(\app\mini\middleware\CheckToken::class);

//获取用户收货地址列表 最多十个
Route::post(":version/user/getaddress",":version.user/getAddress")->middleware(\app\mini\middleware\CheckToken::class);
//收货地址 新增 编辑 删除 设为默认
Route::post(":version/user/actaddress",":version.user/actAddress")->middleware(\app\mini\middleware\CheckToken::class);

//用户积分 转赠


// ---------------------------------- 用户模块  结束 ------------------------------------------


// ---------------------------------- 商城模块  开始 ------------------------------------------
//商城首页顶部 轮播和一级分类
Route::get(":version/goods/indextop",":version.goods/indexTop");
//商城首页 推荐好物 不同用户展示不同价格
Route::post(":version/goods/indextui",":version.goods/indexTui")->middleware(\app\mini\middleware\CheckToken::class);
//商城首页 拼团活动

//商城首页底部商品推荐
Route::post(":version/goods/indexbottom",":version.goods/indexBottom")->middleware(\app\mini\middleware\CheckToken::class);
//商城一级分类获取二级分类
Route::post(":version/goods/categorychild",":version.goods/categoryChild")->middleware(\app\mini\middleware\CheckToken::class);
//商城获取商品列表
Route::post(":version/goods/goodslist",":version.goods/goodsList")->middleware(\app\mini\middleware\CheckToken::class);
//商城获取商品详情顶部
Route::post(":version/goods/infotop",":version.goods/goodsInfoTop")->middleware(\app\mini\middleware\CheckToken::class);
//商城获取商品详情底部
Route::post(":version/goods/infobottom",":version.goods/goodsInfoBottom")->middleware(\app\mini\middleware\CheckToken::class);

// ---------------------------------- 商城模块  结束 ------------------------------------------

// ---------------------------------- 服务模块  开始 ------------------------------------------
//服务首页 顶部轮播 和 一级分类
Route::get(":version/service/indextop",":version.service/indexTop");
//服务首页 门店列表 包含 搜索、精选好店，排序，
Route::post(":version/service/storelist",":version.service/storeList");
//Route::post(":version/service/storelist",":version.service/storeList")->middleware(\app\mini\middleware\CheckToken::class);
//服务添加购物车
Route::post(":version/service/addcart",":version.service/serviceToCart")->middleware(\app\mini\middleware\CheckToken::class);
//服务购物车列表
Route::post(":version/service/cartlist",":version.service/serviceCartList")->middleware(\app\mini\middleware\CheckToken::class);
//服务购物车删除
Route::post(":version/service/cartdel",":version.service/cartDel")->middleware(\app\mini\middleware\CheckToken::class);
//服务购物车数量加减
Route::post(":version/service/cartchange",":version.service/cartNumchange")->middleware(\app\mini\middleware\CheckToken::class);
//服务购物车选中选择
Route::post(":version/service/cartcheckbox",":version.service/cartCheckbox")->middleware(\app\mini\middleware\CheckToken::class);
//服务购物车结算页面
Route::post(":version/service/cartsettle",":version.service/serviceCartSettle")->middleware(\app\mini\middleware\CheckToken::class);
//服务购物车结算页面下单接口
Route::post(":version/service/createorder",":version.service/createOrder")->middleware(\app\mini\middleware\CheckToken::class);


// ---------------------------------- 服务模块  结束 ------------------------------------------



// ---------------------------------- 优惠券模块  开始 ------------------------------------------
//用户优惠券列表 服务券 商品券
Route::post(":version/coupon/couponlist",":version.coupon/couponList")->middleware(\app\mini\middleware\CheckToken::class);
//用户选择优惠券 不选优惠券 不用了2021.1.11
//Route::post(":version/coupon/couponchange",":version.coupon/couponChange")->middleware(\app\mini\middleware\CheckToken::class);
// ---------------------------------- 优惠券模块  结束 ------------------------------------------




// ---------------------------------- 支付模块  开始 ------------------------------------------
//服务下单支付接口
Route::post(":version/payment/servicepay",":version.payment/serviceOrderPay")->middleware(\app\mini\middleware\CheckToken::class);
// ---------------------------------- 支付模块  结束 ------------------------------------------

// ---------------------------------- 门店模块  开始 ------------------------------------------
//小程序店铺入驻
Route::post(":version/store/join",":version.store/storeJoin")->middleware(\app\mini\middleware\CheckToken::class);
//获取门店信息
Route::post(":version/store/info",":version.store/storeInfo")->middleware(\app\mini\middleware\CheckToken::class);
//获取门店服务留言列表
Route::post(":version/store/leavelist",":version.store/storeLeaveList")->middleware(\app\mini\middleware\CheckToken::class);
//该门店一级分类列表
Route::post(":version/store/category",":version.store/storeCategory")->middleware(\app\mini\middleware\CheckToken::class);
//该门店一级分类下的二级分类
Route::post(":version/store/childcategory",":version.store/categoryChild")->middleware(\app\mini\middleware\CheckToken::class);
//该门店分类下的服务列表
Route::post(":version/store/servicelist",":version.store/serviceList")->middleware(\app\mini\middleware\CheckToken::class);
// ---------------------------------- 门店模块  结束 ------------------------------------------


