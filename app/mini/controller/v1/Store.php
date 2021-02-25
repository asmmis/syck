<?php
declare (strict_types = 1);

namespace app\mini\controller\v1;

use think\facade\Db;
use app\mini\validate\StoreValidate;


/**
 * 门店操作
 * Class StoreValidate
 * @package app\mini\controller\v1
 */
class Store extends Base
{
    //店铺入驻 审核中
    public function storeJoin()
    {
        $data = $this->request->post();
        unset($data['token']);
        //数据验证器
        try {
            validate(StoreValidate::class)
                ->scene('add') //验证场景
                ->check($data);
        } catch (\think\exception\ValidateException $e) {
            // 验证失败 输出错误信息
            //dump($e->getError());
            return $this->ret_faild($e->getError());
        }
        //店铺名称检测
        $where[] = [
            ['store_name','=',$data['store_name']],
            ['is_del','=',0],
        ];
        $find = Db::name('store')->where($where)->find();
        if($find) return $this->ret_faild('店铺名称已存在');
        //验证通过添加数据
        $r = Db::name('store')->insertGetId($data);
        if($r)  return $this->ret_success('入驻成功，等待审核');
        return $this->ret_faild('店铺入驻失败');
    }

    //点击查看具体门店信息
    public function storeInfo()
    {
        $uid = $this->request->userinfo['uid'];
        $store_id = $this->request->param('store_id/d');
        $field = ['store_id','store_name','store_imgs','info','week_time','day_time','address','phone','status'];
        $find = Db::name('store')->field($field)->where(['store_id'=>$store_id,'is_del'=>0])->find();
        if (!$find) return $this->ret_faild('门店不存在');
        if ($find['status']!==1) return $this->ret_faild('门店状态异常');
        $find['store_imgs'] = json_decode($find['store_imgs']);
        //门店是否收藏
        $find['is_collec'] = Db::name('user_collections_store')->where(['uid'=>$uid,'store_id'=>$store_id,'is_del'=>0])->count();
        return $this->ret_success('获取门店详情成功',$find);
    }
    //门店服务留言列表
    public function storeLeaveList()
    {
        $store_id = $this->request->param('store_id/d',0);
        $is_tui = $this->request->param('is_tui/d',0);//是否推荐留言
        $page = $this->request->param('page/d',1);//页码

        $find = Db::name('store')->where(['store_id'=>$store_id,'is_del'=>0])->find();
        if (!$find) return $this->ret_faild('门店不存在');
        if ($find['status']!==1) return $this->ret_faild('门店状态异常');
        if ($is_tui)  $where[] = ['sl.is_tui','=',1];//是推荐留言
        $where[] = ['sl.store_id','=',$store_id];//门店
        $where[] = ['sl.is_show','=',1];//是否显示
        $filed = ['u.nickname,u.avatar,sl.content,sl.imgs,sl.add_time'];
        $list = Db::name('service_leave')
            ->alias('sl')
            ->join('user u','u.uid=sl.uid','left')
            ->field($filed)
            ->where($where)
            ->page($page,$this->plimit)
            ->select()
            ->each(function ($item,$key){
                $item['nickname'] = show_nickname($item['nickname']);
                $item['imgs'] =  $item['imgs'] ? json_decode($item['imgs']) : '';
                $item['add_time'] = date('Y-m-d H:i:s',$item['add_time']);
                return $item;
            });
        $count = Db::name('service_leave')->where(['store_id'=>$store_id,'is_show'=>1])->count();
        if($list->isEmpty()){
            return $this->ret_success('暂时没有更多数据了',['is_request'=>1,'count'=>$count,'list'=>$list]);//不需要在请求了
        }
        return $this->ret_success('获取门店留言成功',['is_request'=>0,'count'=>$count,'list'=>$list]);
    }
    //查看门店的所有一级分类
    public function storeCategory()
    {
        $store_id = $this->request->param('store_id/d');
        $find = Db::name('store')->where(['store_id'=>$store_id,'is_del'=>0])->find();
        if (!$find) return $this->ret_faild('门店不存在');
        if ($find['status']!==1) return $this->ret_faild('门店状态异常');
        $list = Db::name('service_category_store')
            ->field('cate_id, name,pic')
            ->where(['store_id'=>$store_id,'is_del'=>0,'level'=>1])
            ->select();
        return $this->ret_success('获取门店一级服务分类成功',$list);
    }
    //查看门店一级分类的下级分类
    public function categoryChild()
    {
        $store_id = $this->request->param('store_id/d');
        $cate_id = $this->request->param('cate_id/d'); //一级分类ID
        if(!$cate_id) return $this->ret_faild('参数缺失');
        $find = Db::name('store')->where(['store_id'=>$store_id,'is_del'=>0])->find();
        if (!$find) return $this->ret_faild('门店不存在');
        if ($find['status']!==1) return $this->ret_faild('门店状态异常');
        $list = Db::name('service_category_store')
            ->field('cate_id, name,pic')
            ->where(['store_id'=>$store_id,'is_del'=>0,'pid'=>$cate_id])
            ->select();
        return $this->ret_success('获取门店下级服务分类成功',$list);
    }
    //门店点击下级分类展示分类下的所有服务
    public function serviceList()
    {
        $store_id = $this->request->param('store_id/d');
        $cate_id = $this->request->param('cate_id/d'); //二级分类ID
        $page =  $this->request->param('page/d',1); //页码
        if(!$cate_id) return $this->ret_faild('参数缺失');
        $find = Db::name('store')->where(['store_id'=>$store_id,'is_del'=>0])->find();
        if (!$find) return $this->ret_faild('门店不存在');
        if ($find['status']!==1) return $this->ret_faild('门店状态异常');
        $list = Db::name('service')
            ->field('id,img,name,price,rule,remark')
            ->where(['store_id'=>$store_id,'cate_id'=>$cate_id,'is_del'=>0,'is_show'=>1,'status'=>1])
            ->page($page,$this->plimit)
            ->select();
        if($list->isEmpty()){
            return $this->ret_success('暂时没有更多数据了',['is_request'=>1,'list'=>$list]);//不需要在请求了
        }
        return $this->ret_success('获取门店服务分类下的服务列表成功',['is_request'=>0,'list'=>$list]);
    }

}