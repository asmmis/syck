<?php
declare (strict_types = 1);

namespace app\admin\controller;

use think\Request;

class Test 
{
    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index()
    {
      $s = './upload/store/identity20201229/25220_172339_8754.png';
      dd(ltrim($s,'.'));
    }
}
