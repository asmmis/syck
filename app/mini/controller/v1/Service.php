<?php
declare (strict_types = 1);

namespace app\mini\controller\v1;

use think\facade\Db;

/**
 * 服务板块
 * Class Goods
 * @package app\mini\controller\v1
 */
class Service extends Base
{

    //服务首页 轮播和门店分类
    public function indexTop()
    {
        $list['banner'] = Db::name('service_banner')
            ->field('banner')
            ->where(['is_del'=>0,'is_show'=>1])
            ->select();
            $list['category'] = Db::name('service_category')
            ->field('id,name,pic')
            ->where(['level'=>1])
            ->order('sort','desc')
            ->select();
        return $this->ret_success('获取服务轮播分类成功',$list);
    }
    //首页精选好店 分类 搜索 门店列表
    public function storeList()
    {
        //传用户经纬度计算到门店的距离
        //同时根据用户的经纬度 精确定位 调用第三方返回用户的省市区地址信息 然后把省市区返回给前端 查询城市下的推荐门店
        $user_lng  = $this->request->param('user_lng/s','120.113119') ; //用户经度 120.113119
        $user_lat =  $this->request->param('user_lat/s','30.315762') ; //用户纬度 30.315762
        $user_district_id = $this->request->param('user_district_id/d',0) ; //用户所在城市 区域
        $user_city_id = $this->request->param('user_city_id/d',0) ; //用户所在城市 ID
        $page = $this->request->param('page/d',1) ; //页码
        $is_index = $this->request->param('is_index/d') ; //是否首页精选好店  1=是 0=否  【可选】
        $cate_id = $this->request->param('cate_id/d');//服务分类ID点击进来搜索门店  【可选】
        $keywords =  $this->request->param('keywords/s');//搜索关键字 搜索服务名或者门店名 【可选】
        $sort_juli = $this->request->param('sort_juli/s');//距离默认降序 【可选】
        if($keywords){
            $where[] = ['keyword',"like","%$keywords%"];//关键字搜索
        }
        if($is_index==1){
            $where[] = ['is_index','=',$is_index]; //首页推荐是否
        }
        if($cate_id){
            $store_id_arr = Db::name('service_category_store')->where(['cate_id'=>$cate_id])->column('store_id');
            //$store_id_str = implode(',',$store_id_arr);//符合条件的门店集合字符串
            $where[] = ['store_id','in',$store_id_arr]; //符合条件的门店集合字符串 in (1,2)
        }
        if(in_array($sort_juli,['asc','desc'])){
            $sort['juli'] = $sort_juli; //距离排序
        }
        if($user_district_id){
            $where[] = ['district_id','=',$user_district_id];//城市区域ID
        }
        if($user_city_id){
            $where[] = ['city_id','=',$user_city_id];//城市ID
        }
        $where[] = ['status','=',1]; //审核通过
        $where[] = ['is_del','=',0]; //未删除
        $field = ['store_id','store_img','store_name','address','info','(2 * 6378.137* ASIN(SQRT(POW(SIN(PI()*('. $user_lat.'-latitude)/360),2)+COS(PI()*'.$user_lng.'/180)* COS(longitude * PI()/180)*POW(SIN(PI()*('.$user_lng.'-longitude)/360),2)))) as juli'];
        $sort['store_id'] = 'desc';
        $list = Db::name('store')
            ->field($field)
            ->where($where)
            ->order($sort)
            ->page($page,$this->plimit)
            ->select()
            ->each(function ($item,$key){
                $item['juli'] = round($item['juli'],2);//距离保留两位小数
                return $item;
            });
        if($list->isEmpty()){
            return $this->ret_success('暂无更多记录',['list'=>[],'is_request'=>1]);//不要再请求了
        }
        return $this->ret_success('获取门店成功',['list'=>$list,'is_request'=>0]);
    }

   //服务添加购物车
    public function serviceToCart()
    {
        $uid = $this->request->userinfo['uid'];
        $store_id = $this->request->param('store_id/d');//门店ID
        $service_id = $this->request->param('service_id/d');//服务ID
        $where_find[] = ['store_id','=',$store_id];
        $where_find[] = ['id','=',$service_id];
        $where_find[] = ['is_show','=',1];//服务项目显示
        $find_service = Db::name('service')->where($where_find)->find();
        if(!$find_service) return $this->ret_faild('服务项目不存在');
        if($find_service['status']!=1) return $this->ret_faild('服务项目未通过');
        //用户服务购物车限制99条记录 未购买的记录
        $user_count = Db::name('service_cart')->where(['uid'=>$uid,'is_pay'=>0])->count();
        if ($user_count>=50)  return $this->ret_faild('购物车数量已达上限50');
        //是否存在购物车
        $where[] = ['uid','=',$uid];
        $where[] = ['store_id','=',$store_id];
        $where[] = ['service_id','=',$service_id];
        $where[] = ['is_pay','=',0];//未支付的
        $where[] = ['is_del','=',0];
        $find_cart = Db::name('service_cart')->where($where)->find();
        if($find_cart){
            //单条服务购物车数量限制99
            if($find_cart['num']>=99) return $this->ret_faild('项目数量已达上限99');
            //存在购物车 数量+1
            $r = Db::name('service_cart')->where(['id'=>$find_cart['id']])->inc('num')->update();
        }else{
            //不存在购物车
            $data = [
                'uid' => $uid,
                'store_id' => $store_id,
                'service_id' => $service_id,
                'num' => 1,
                'add_time' => time(),
            ];
            $r = Db::name('service_cart')->insertGetId($data);
        }
        if($r) return $this->ret_success('添加成功');
        return $this->ret_faild('添加购物车失败');

    }

    //服务购物车列表 未购买的
    public function serviceCartList()
    {
        $uid = $this->request->userinfo['uid'];
        $list = Db::name('service_cart')
            ->field('group_concat(a.id) cart_ids,b.store_name,b.store_id')
            ->alias('a')
            ->join('store b','a.store_id=b.store_id','left')
            ->where(['a.uid'=>$uid,'a.is_pay'=>0,'a.is_del'=>0])
            ->group('a.store_id')
            ->order('a.add_time desc')
            ->select()
            ->each(function ($item,$key){
                $where[] = ['c.id','in',$item['cart_ids']];
                $item['infos'] = Db::name('service')
                                ->alias('s')
                                ->field('c.id cart_id,s.name,s.img,s.price,c.num,c.is_now')
                                ->join('service_cart c','s.id=c.service_id','left')
                                ->where($where)
                                ->order('c.add_time desc')
                                ->select();
                //unset($item['cart_ids']);
                return $item;
            });
        if($list->isEmpty()){
            return $this->ret_success('暂无购物车数据',['is_request'=>1,'list'=>$list]);
        }
        return $this->ret_success('获取购物车列表成功',['is_request'=>0,'list'=>$list]);
    }

    //删除购物车
    public function cartDel()
    {
        $uid = $this->request->userinfo['uid'];
        $cart_id = $this->request->param('cart_id/d');
        if(!$cart_id) return $this->ret_faild('参数缺失');
        $find = Db::name('service_cart')->where(['uid'=>$uid,'id'=>$cart_id,'is_del'=>0])->find();
        if(!$find)  return $this->ret_faild('购物车ID不存在');
        $r = Db::name('service_cart')->where(['id'=>$cart_id])->update(['is_del'=>1]);
        if($r) return $this->ret_success('删除成功');
        return $this->ret_faild('删除失败');
    }
    //购物车数量加减 指定数量
    public function cartNumchange()
    {
        $uid = $this->request->userinfo['uid'];
        $cart_id = $this->request->param('cart_id/d');
        $num = $this->request->param('cart_num/d');
        if(!$cart_id) return $this->ret_faild('参数缺失');
        if($num>99 || $num<1) return $this->ret_faild('数量超出限制');
        $find = Db::name('service_cart')->where(['uid'=>$uid,'id'=>$cart_id,'is_del'=>0])->find();
        if(!$find)  return $this->ret_faild('购物车ID不存在');
        $r = Db::name('service_cart')->where(['id'=>$cart_id])->update(['num'=>$num]);
        if($r!=false) return $this->ret_success('修改成功');
        return $this->ret_faild('修改失败');
    }

    //购物车结算选中
    public function cartCheckbox()
    {
        $uid = $this->request->userinfo['uid'];
        $cart_ids = $this->request->param('cart_ids/s','');
        $where[] = ['uid','=',$uid];
        $where[] = ['is_pay','=',0];//未购买
        Db::name('service_cart')->where($where)->update(['is_now'=>0]);//先把所有的改为 非立即购买
        if($cart_ids){
            $where[] = ['id','in',$cart_ids];
        }else{
            return $this->ret_faild('未选择购物车');
        }
        $r = Db::name('service_cart')->where($where)->update(['is_now'=>1]);//立即购买
        if($r!=false) return $this->ret_success('选中成功');
        return $this->ret_faild('选中失败');

    }
    //服务购物车结算页面
    public function serviceCartSettle()
    {
        $uid = $this->request->userinfo['uid'];
        //立即购买的购物车
        $list = Db::name('service_cart')
            ->field('b.store_id,b.store_name,group_concat(a.id) cart_ids')
            ->alias('a')
            ->join('store b','a.store_id=b.store_id','left')
            ->where(['a.uid'=>$uid,'a.is_pay'=>0,'a.is_del'=>0,'a.is_now'=>1])
            ->group('a.store_id')
            ->order('a.add_time desc')
            ->select()
            ->each(function ($item,$key){
                $where[] = ['c.id','in',$item['cart_ids']];
                $item['infos'] = Db::name('service')
                    ->alias('s')
                    ->field('c.id cart_id,c.service_id,s.name,s.img,s.price,c.num')
                    ->join('service_cart c','s.id=c.service_id','left')
                    ->where($where)
                    ->order('c.add_time desc')
                    ->select();
                unset($item['cart_ids']);
                return $item;
            });
        if(count($list)>1)  return $this->ret_faild('暂不支持多家门店下单结算');
        if($list->isEmpty()){
            return $this->ret_success('暂无结算购物车数据',['is_request'=>1,'list'=>$list]);
        }
        return $this->ret_success('获取结算购物车列表成功',['is_request'=>0,'list'=>$list]);
    }

    //服务下单 创建服务订单
    public function createOrder()
    {
        $uid = $this->request->userinfo['uid'];//下单人用户ID
        $userinfo = $this->request->userinfo;//下单人用户信息
        $param = $this->request->param('param');//传json数组

        //要求前端传来的数据
//        $param = [
//            0=> ['store_id'=>1,appo_time'=>'2021-12-12','cu_id'=>0],
//            1=> ['store_id'=>2,'appo_time'=>'2021-12-12','cu_id'=>0]
//        ];
//        dump($param);
//        dump('json转一下');
//        dump(json_decode($param,true));
        $param = json_decode($param,true);
        if(!$param)  return $this->ret_faild('param错误');
        //校验数组
        for($i=0;$i<count($param);$i++){
            if(!isset($param[$i]['store_id']) || !$param[$i]['store_id']){
                return $this->ret_faild('store_id错误');
            }
            if(!isset($param[$i]['appo_time']) || !$param[$i]['appo_time']){
                return $this->ret_faild('appo_time错误');
            }
            if(!isset($param[$i]['cu_id'])){
                return $this->ret_faild('cu_id错误');
            }
        }

        //把超过十五分钟未支付的订单 给关闭
        $wheres[] = ['add_time','<','UNIX_TIMESTAMP(NOW())-900'];
        $wheres[] = ['pay_status','=',0];//未支付
        Db::name('service_order_pay')->where($wheres)->update(['pay_status'=>-1]);//超时订单取消

        //立即购买的购物车数据
        $list = Db::name('service_cart')
            ->field('group_concat(a.id) cart_ids,group_concat(a.service_id) service_ids,b.store_name,b.store_id')
            ->alias('a')
            ->join('store b','a.store_id=b.store_id','left')
            ->where(['a.uid'=>$uid,'a.is_pay'=>0,'a.is_del'=>0,'a.is_now'=>1])
            ->group('a.store_id')
            ->order('a.add_time desc')
            ->select()
            ->each(function ($item,$key){
                $where[] = ['c.id','in',$item['cart_ids']];
                $item['infos'] = Db::name('service')
                    ->alias('s')
                    ->field('c.id cart_id,c.service_id,s.name,s.img,s.bl_name,s.price,c.num')
                    ->join('service_cart c','s.id=c.service_id','left')
                    ->where($where)
                    ->order('c.add_time desc')
                    ->select();
                unset($item['cart_ids']);
                return $item;
            });
         $r = \app\mini\logic\ServiceOrder::createServiceOrder($list,$param,$userinfo);
         if( $r['status']==1){
             return $this->ret_success('下单成功',$r['data']);//返回订单编号，根据订单编号结算支付
         }else{
             return $this->ret_success('下单失败',$r['data']);
         }
    }

    //我的服务订单记录
    public function serviceMyList()
    {
        $uid = $this->request->userinfo['uid'];
        //把超时15分钟未支付的服务订单关闭
        $wheres[] = ['uid','=',$uid];
        $wheres[] = ['create_time','<','UNIX_TIMESTAMP(NOW())-900'];
        $wheres[] = ['status','=',0];//待付款
        $wheres[] = ['pay_status','=',0];//未支付
        Db::name('service_order')->where($wheres)->update(['pay_status'=>-1,'status'=>-1]);//超时订单取消

        //全部 待使用
        //$status = $this->request->param('')



    }

}

