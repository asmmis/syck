<?php
declare (strict_types = 1);

namespace app\mini\controller\v1;

use think\facade\Db;

/**
 * 商城板块
 * Class Goods
 * @package app\mini\controller\v1
 */
class Goods extends Base
{

    //商城首页 banner 和 分类
    public function indexTop()
    {
        $list['banner'] = Db::name('goods_banner')
            ->field('banner,goods_id')
            ->where(['is_show'=>1])
            ->order('id','desc')
            ->select();
        $list['category'] = Db::name('goods_category')
            ->field('id,name,pic')
            ->where(['level'=>1,'is_show'=>1])
            ->order('sort','desc')
            ->select();
        return $this->ret_success('获取商城轮播分类成功',$list);
    }
    //商城首页 推荐好物
    public function indexTui()
    {
        $usertype = $this->request->userinfo['user_type'];//用户类型
        $userprice = 'price_'.$usertype;//用户价格字段
        $page = $this->request->param('page/d',1);//页码
        $sort_price = $this->request->param('sort_price/s');//价格排序 方式
        if(in_array($sort_price,['asc','desc'])){
            $sort[$userprice] = $sort_price;
        }
        $field = ['goods_id','goods_name','price',"$userprice as user_price",'goods_img','sales','unit_name'];
        $sort['goods_id'] = 'desc';//默认ID 时间 降序
        $list = Db::name('goods')
            ->field($field)
            ->where(['is_show'=>1,'is_index'=>1])
            ->order($sort)
            ->page($page,$this->plimit)
            ->select()
            ->toArray();
//        dump(Db::name('goods')->getLastSql());
//        dump($list);
//        die();
        if(empty($list)){
            return $this->ret_success('获取好物推荐成功',['is_request'=>1,'list'=>$list]);//不需要在请求了
        }
        return $this->ret_success('获取好物推荐成功',['is_request'=>0,'list'=>$list]);
    }
    //商城首页拼团 活动
    public function indexCombination()
    {

    }

    //商城首页底部商品 最新的商品十条
    public function indexBottom()
    {
        $usertype = $this->request->userinfo['user_type'];//用户类型
        $userprice = 'price_'.$usertype;//用户价格字段
        $field = ['goods_id','goods_name','price',"$userprice as user_price",'goods_img','sales','unit_name'];
        $sort['goods_id'] = 'desc';//默认ID 时间 降序
        $list = Db::name('goods')
            ->field($field)
            ->where(['is_show'=>1])
            ->order($sort)
            ->limit($this->plimit)
            ->select()
            ->toArray();
        if(empty($list)){
            return $this->ret_success('暂时没有更多数据了',['is_request'=>1,'list'=>$list]);//不需要在请求了
        }
        return $this->ret_success('获取首页底部商品成功',['is_request'=>0,'list'=>$list]);
    }

    //根据商品一级分类获取二级分类
    public function categoryChild()
    {
        $cate_id = $this->request->param('cate_id/d',0); //一级分类id
        $list = Db::name('goods_category')
            ->field('id,name')
            ->where(['pid'=>$cate_id,'is_show'=>1])
            ->order('sort','desc')
            ->select();
        if ($list->isEmpty()){
            return $this->ret_success('暂无数据',['is_request'=>1,'list'=>$list]);//不需要在请求了
        }
        return $this->ret_success('获取二级分类成功',['is_request'=>0,'list'=>$list]);
    }


    //商品列表 二级分类 搜索 筛选
    public function goodsList()
    {
        $usertype = $this->request->userinfo['user_type'];//用户类型
        $userprice = 'price_'.$usertype;//用户价格字段

        $page = $this->request->param('page/d',1); //页码 默认1    必选
        //二级分类ID  可选
        $cate_id = $this->request->param('cate_id/d');
        if($cate_id){
            $where[] = ['cate_id','=',$cate_id ];
        }
        $keywords = $this->request->param('keywords/s');//搜索商品关键字 可选
        if($keywords){
            $where[] = ['goods_name','like',"%$keywords%"]; //搜索商品名称
        }
        //价格排序 可选
        $sort_price =  $this->request->param('sort_price');
        if(in_array($sort_price,['asc','desc'])){
            $sort[$userprice] = $sort_price;
        }
        //销量排序 可选
        $sort_sales =  $this->request->param('sort_sales');
        if(in_array($sort_sales,['asc','desc'])){
            $sort['sales'] = $sort_sales;
        }
        $field = ['goods_id','goods_name','price',"f as user_price",'goods_img','sales','unit_name'];
        $sort['goods_id'] = 'desc';//默认ID 时间 降序
        $where[] = ['is_show','=',1];
        $where[] = ['is_del','=',0];
        $list = Db::name('goods')
            ->field($field)
            ->where($where)
            ->order($sort)
            ->page($page,$this->plimit)
            ->select();
        if($list->isEmpty()){
            return $this->ret_success('暂时没有更多数据了',['is_request'=>1,'list'=>$list]);//不需要在请求了
        }
        return $this->ret_success('获取搜索商品成功',['is_request'=>0,'list'=>$list]);
    }

    //商品详情页 头部基本信息
    public function goodsInfoTop()
    {
        $goods_id = $this->request->param('goods_id/d');//商品ID
        $usertype = $this->request->userinfo['user_type'];//用户类型
        $userprice = 'price_'.$usertype;//用户价格字段
        $field = ['goods_id','goods_name','label_str','price',"$userprice as user_price",'goods_imgs','sales','unit_name','is_rule'];
        $find = Db::name('goods')
            ->field($field)
            ->where(['goods_id'=>$goods_id,'is_show'=>1,'is_del'=>0])
            ->find();
        if(!$find) return $this->ret_faild('商品不存在或已下架');
        $find['goods_imgs'] = json_decode($find['goods_imgs']);//商品轮播图
        return $this->ret_success('获取商品详情顶部成功',$find);
    }
    //获取商品详情页 底部信息
    public function goodsInfoBottom()
    {
        $goods_id = $this->request->param('goods_id/d') ?? 18;//商品ID
        $list['details'] = Db::name('goods_description')->where(['goods_id'=>$goods_id])->column('description');
        $list['attr'] = Db::name('goods_attr')->field('attr_name,attr_values')->where(['goods_id'=>$goods_id])->select();
        return $this->ret_success('获取商品详情底部成功',$list);
    }


    //商品结算页面 获取收货地址在用户模块
    public function goodsSettle()
    {

    }


}