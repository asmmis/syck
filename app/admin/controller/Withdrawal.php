<?php
declare (strict_types = 1);

namespace app\admin\controller;
use app\BaseController;
use app\admin\model\TixianYongjin as tixianYongjin;
use app\admin\model\TixianBalance as tixianBalance;

use think\Request;

class Withdrawal extends BaseController
{
    /**
     * 显示余额提现列表
     */
    public function balance_list()
    {
        if($data = tixianBalance::showlist())
        return returnMsg(0,$data);
        return returnMsg(1,'无数据');
    }


     /**
     * 显示佣金列表
     */
    public function yongjin_list()
    {
        if($data = tixianYongjin::showlist())
        return returnMsg(0,$data);
        return returnMsg(1,'请求失败');
    }

    /**
     * 显示商家提现列表
     */
    public function shangjia()
    {
        if($data = tixianYongjin::showlist())
        return returnMsg(0,$data);
        return returnMsg(1,'请求失败');
    }
   

    
  
}
