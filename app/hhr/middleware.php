<?php
// 这是系统自动生成的middleware定义文件
return [
    // Session初始化
    \think\middleware\SessionInit::class,
    //跨域中间件
    \think\middleware\AllowCrossDomain::class,
    \app\hhr\middleware\LoginCheck::class,
];
