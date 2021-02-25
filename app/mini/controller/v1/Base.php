<?php
declare (strict_types = 1);

namespace app\mini\controller\v1;

use think\App;
use think\exception\ValidateException;
use think\Validate;
//use think\facade\Db;


/**
 * 控制器基础类
 */
abstract class Base
{
    /**
     * @var int 分页每页十条
     */
    public $plimit = 10;

    /**
     * @var string
     */
    protected $appkey = 'chikexiaochengxu#';
    /**
     * Request实例
     * @var \think\Request
     */
    protected $request;

    /**
     * 应用实例
     * @var \think\App
     */
    protected $app;

    /**
     * 是否批量验证
     * @var bool
     */
    protected $batchValidate = false;

    /**
     * 控制器中间件
     * @var array
     */
    protected $middleware = [
        //'apiCheckApi'
    ];

    /**
     * 构造方法
     * @access public
     * @param  App  $app  应用对象
     */
    public function __construct(App $app)
    {
        $this->app     = $app;
        $this->request = $this->app->request;

        // 控制器初始化
        $this->initialize();
    }

    // 初始化
    protected function initialize()
    {
        //每次请求校验签名
        // $this->checkSign();
        //请求校验token
        //$this->checkToken();
        //都用TP6了 搞一手中间件

    }


    /**
     * 验证数据
     * @access protected
     * @param  array        $data     数据
     * @param  string|array $validate 验证器名或者验证规则数组
     * @param  array        $message  提示信息
     * @param  bool         $batch    是否批量验证
     * @return array|string|true
     * @throws ValidateException
     */
    protected function validate(array $data, $validate, array $message = [], bool $batch = false)
    {
        if (is_array($validate)) {
            $v = new Validate();
            $v->rule($validate);
        } else {
            if (strpos($validate, '.')) {
                // 支持场景
                [$validate, $scene] = explode('.', $validate);
            }
            $class = false !== strpos($validate, '\\') ? $validate : $this->app->parseClass('validate', $validate);
            $v     = new $class();
            if (!empty($scene)) {
                $v->scene($scene);
            }
        }

        $v->message($message);

        // 是否批量验证
        if ($batch || $this->batchValidate) {
            $v->batch(true);
        }

        return $v->failException(true)->check($data);
    }

    //成功返回 0是成功
    public function ret_success($msg,$data=[]){
        return $this->ret_json(0,$msg,$data);
    }

     // 失败返回 1是失败
    public function ret_faild($msg,$data=[]){
        return $this->ret_json(1,$msg,$data);
    }
    //返回
    public function ret_json($state,$msg="",$data=[]){
        $ret=array();
        $ret['state']=$state;
        $ret['msg']=$msg;
        $ret['data']=$data;
        return json($ret);
    }
}
