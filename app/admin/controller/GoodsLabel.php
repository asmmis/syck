<?php
namespace app\admin\controller;



class GoodsLabel extends \app\BaseController
{
    public function apiGetGoodsLabels(){
        try{
            $ret=array();
            $labels=\app\admin\model\GoodsLabel::getGoodsLabels();
            if(!$labels)
                throw new \think\Exception("获取失败");
            foreach ($labels as $label)
                $ret[]=$label->toArray();
            return $this->ret_success('成功',$ret);
        }catch (\think\Exception $e){
            return $this->ret_faild('失败',$ret);
        }
    }
}