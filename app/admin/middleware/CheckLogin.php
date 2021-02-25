<?php
// php think make:middleware app\admin\middleware\***
declare(strict_types=1);

namespace app\admin\middleware;


class CheckLogin
{
    /**
     * 处理请求
     *
     * @param \think\Request $request
     * @param \Closure       $next
     * @return Response
     */
    public function handle($request, \Closure $next)
    {
        //  if (!session('?userid')) {
        //     //  未登录重定向到登陆页面
        //     return redirect('/admin/login');
        // }
        return $next($request);
    }
}
