<?php


namespace app\business\controller;


use app\BaseController;
use app\business\model\Business;
use app\business\model\ServiceCategory;
use app\business\model\ServiceCoupon;
use app\business\model\ServiceLog;
use app\business\model\ServiceMessage;
use app\business\model\ServiceOrder;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use think\Exception;
use app\business\model\Service as ServiceModel;
use think\exception\ValidateException;
use think\facade\Request;
use PhpOffice\PhpSpreadsheet\Helper\Sample;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class Service extends BaseController
{

    /**
     * 列出当前店铺的服务
     */
    public  function apiListMyStoreService($page=0,$pagesize=10){
        try{
        return $this->ret_success('成功',ServiceModel::getMyStoreService($page,$pagesize));
        }catch (Exception $e){
            return $this->ret_faild($e->getMessage());
        }
    }

    public function apiGetServiceById($id){
        try{
            $ret=ServiceModel::getServiceById($id);
            if($ret==null||$ret->isEmpty())
                throw  new Exception('查不到服务');
            $ret=$ret->toArray();
            //添加上级
            $cate=ServiceCategory::getServiceCategoryById($ret['cate_id']);
            $ret['serviceCate1Id']=ServiceCategory::getServiceCategoryById($cate->pid)->id;
            $ret['cate']=$cate->getAttr('name');
            return $this->ret_success('成功',$ret);
        }catch (Exception $e){
            return $this->ret_faild($e->getMessage());
        }
    }

    /**
     * @param $id 新增为-1
     * @param $name
     * @param $cateid
     * @param $rule
     * @param $price
     * @param $info
     * @param $keyword
     * @return \think\response\Json
     */
    public  function apiSaveService($id,$name,$cateid,$rule,$price,$info,$keyword){
        try{
            $img=Request::file("img");
            //这里因为同时有编辑和新增，所以要分场景进行验证
            /*
            $this->validate(
                array('name'=>$name,'cateid'=>$cateid,'rule'=>$rule,'price'=>$price,'info'=>$info,'keyword'=>$keyword,'img'=>$img),
                array('name'=>'require','cateid'=>'require','rule'=>'require','price'=>'require','info'=>'require','keyword'=>'require','img'=>[
                        'require',
                        'file',
                        'image',
                        'fileSize'=>200*1024
                    ]),
            array('name.require'=>'服务名称为空','cateid.require'=>'未选择分类','rule.require'=>'规格不能为空','price.require'=>'普通用户价格为空','info.require'=>'服务简介为空',
                'keyword.require'=>'服务关键词为空','img.require'=>'服务背景图片为空','img.file'=>'服务背景图要是一个文件',
                'img.image'=>'服务背景图要是一个图片','img.fileSize'=>'服务背景图片大小要小于200k'));
                */
            ServiceModel::saveService($id,$name,$cateid,$rule,$price,$info,$keyword,$img);
            return $this->ret_success('成功');
        }catch (ValidateException $validateException){
            return $this->ret_faild($validateException->getMessage());
        }catch (Exception $e){
            return $this->ret_faild($e->getMessage());
        }
    }

    /**
     * 获取服务分类树
     */
    public function apiGetServiceCateTree(){
        try{
            $tree=ServiceCategory::getServiceCategoryTree(0);
            return $this->ret_success('成功',$tree);
        }catch (Exception $e){
            return $this->ret_faild($e->getMessage());
        }
    }

    public function apiGetLevelServiceCategory($level){
        try{
            $ret=ServiceCategory::getServiceCategoryByLevel($level);
            return $this->ret_success('成功',$ret);
        }catch (Exception $e){
            return $this->ret_faild($e->getMessage());
        }
    }

    public function apiGetServiceCategoryByParent($parentCategory){
        try{
            $ret=ServiceCategory::getServiceCategoryByParentId($parentCategory);
            return $this->ret_success('成功',$ret);
        }catch (Exception $e){
            return $this->ret_faild($e->getMessage());
        }
    }

    public function apiDelService($id){
        try{
            if(\app\business\model\Service::delService($id))
                return $this->ret_success('成功');
            return $this->ret_faild('失败');
        }catch (Exception $e){
            return $this->ret_faild($e->getMessage());
        }
    }

    /**
     * @param int $page
     * @param int $pagesize
     * @param bool $isMoneyLog 是否是moneylog请求的
     * @param null $date 查询范围起
     * @param null $endDate 查询范围止
     * @return \think\response\Json
     */
    public function apiListMyStoreServiceOrder($page=0,$pagesize=10,$isMoneyLog=false,$date=null,$endDate=null){
        try{
            $ret=\app\business\model\ServiceOrder::listMyStoreServiceOrder($page,$pagesize,$isMoneyLog?1:null,empty($date)?null:$date,empty($endDate)?null:$endDate);
            return $this->ret_success('成功',$ret);
        }catch (Exception $e){
            return $this->ret_faild($e->getMessage());
        }
    }

    public function apiListMyStoreServiceMessage($page=0,$pagesize=10){
        try{
            $ret=ServiceMessage::listMyStoreServiceMessage($page,$pagesize);
            return $this->ret_success('成功',$ret);
        }catch (Exception $e){
            return $this->ret_faild($e->getMessage());
        }
    }

    public function apiSetServiceMessageIsShow($id,$isShow){
        try {
            if(ServiceMessage::setServiceMessageIsShow($id,$isShow))
                return $this->ret_success('成功');
            return $this->ret_faild('失败');
        }catch (Exception $e){
            return $this->ret_faild($e->getMessage());
        }
    }
    public function apiSetServiceMessageIsTui($id,$isTui){
        try {
            if(ServiceMessage::setServiceMessageIsTui($id,$isTui))
                return $this->ret_success('成功');
            return $this->ret_faild('失败');
        }catch (Exception $e){
            return $this->ret_faild($e->getMessage());
        }
    }
    public function apiDelServiceMessageById($id){
        try{
            if(ServiceMessage::delServiceMessage($id))
                return $this->ret_success('成功');
            return $this->ret_faild('失败');
        }catch (Exception $e){
            return $this->ret_faild($e->getMessage());
        }
    }


    /**
     * 财务流水导出
     * @param null $date
     * @param null $endDate
     */
    public function apiOutputServiceOrder($date=null,$endDate=null){
        $orders=ServiceOrder::listMyStoreServiceOrder(-1,-1,1,$date,$endDate);
        $orders=$orders['info'];

        $helper = new Sample();
        if ($helper->isCli()) {
            $helper->log('This example should only be run from a Web Browser' . PHP_EOL);
            return;
        }
// Create new Spreadsheet object
        $spreadsheet = new Spreadsheet();
// Set document properties
        $spreadsheet->getProperties()->setCreator('彼信科技')
            ->setLastModifiedBy('彼信科技')
            ->setTitle('财务流水')
            ->setSubject('财务流水')
            ->setDescription('财务流水')
            ->setKeywords('财务流水')
            ->setCategory('财务流水');
// Add some data
        $row=1;
        $spreadsheet->setActiveSheetIndex(0)
            ->setCellValue('A'.$row,'订单号')
            ->setCellValue('B'.$row,'用户')
            ->setCellValue('C'.$row,'用户类型')
            ->setCellValue('D'.$row,'服务')
            ->setCellValue('E'.$row,'总价')
            ->setCellValue('F'.$row,'支付价格')
            ->setCellValue('G'.$row,'支付时间');

        foreach ($orders as $order){
            $row++;
            switch($order['user_type']) {
                case 0:
                    $userType = '普通用户';
                    break;
                case 1:
                    $userType = '代言人';
                    break;
                case 2:
                    $userType = '合伙人';
                    break;
            }
            $spreadsheet->setActiveSheetIndex(0)
                ->setCellValueExplicit('A'.$row,$order['order_sn'],DataType::TYPE_STRING)
                ->setCellValue('B'.$row,$order['user'])
                ->setCellValue('C'.$row,$userType)
                ->setCellValue('D'.$row,$order['service'])
                ->setCellValue('E'.$row,$order['total_price'])
                ->setCellValue('F'.$row,$order['pay_price'])
                ->setCellValue('G'.$row,date('Y/m/d h:m:s',$order['pay_time']));
        }
//        $toCol = $spreadsheet->getActiveSheet()->getColumnDimension($spreadsheet->getActiveSheet()->getHighestColumn())->getColumnIndex();
//        for($i = 'A'; $i !== $toCol; $i++) {
//            $calculatedWidth = $spreadsheet->getActiveSheet()->getColumnDimension($i)->getWidth();
//            $spreadsheet->getActiveSheet()->getColumnDimension($i)->setWidth((int) $calculatedWidth * 1.05);
//            $spreadsheet->getActiveSheet()->getColumnDimension($i)->setAutoSize(true);
//        }
// Miscellaneous glyphs, UTF-8
//        $spreadsheet->setActiveSheetIndex(0)
//            ->setCellValue('A4', 'Miscellaneous glyphs')
//            ->setCellValue('A5', 'éàèùâêîôûëïüÿäöüç');

// Rename worksheet
//        $spreadsheet->getActiveSheet()->setTitle('Simple');

// Set active sheet index to the first sheet, so Excel opens this as the first sheet
//        $spreadsheet->setActiveSheetIndex(0);

// Redirect output to a client’s web browser (Xlsx)
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
//        $appendName="";
//        if($date)
//            $appendName.=date('Y/m/d',$date);
//        if($endDate)
//            $appendName.='-'.date('Y/m/d',$endDate);
        header('Content-Disposition: attachment;filename="财务流水'.random_int(1000,9999).'.xlsx"');
        header('Cache-Control: max-age=0');
// If you're serving to IE 9, then the following may be needed
//        header('Cache-Control: max-age=1');

// If you're serving to IE over SSL, then the following may be needed
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); // always modified
        header('Cache-Control: cache, must-revalidate'); // HTTP/1.1
        header('Pragma: public'); // HTTP/1.0

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save('php://output');
    }

    public function serviceOrderVerify($service_order_id,$verify_code){
        try{
            if(ServiceOrder::verify($service_order_id,$verify_code))
                return $this->ret_success('成功');
            return $this->ret_faild('失败');
        }catch (Exception $e){
            return $this->ret_faild($e->getMessage());
        }
    }

    public function apiListServiceCoupon($page=0,$pagesize=10){
        try {
            $ret=array();
            $ret['count']=ServiceCoupon::getAllServiceCouponCount();
            $coupons=ServiceCoupon::listServiceCoupon($page,$pagesize);
            foreach ($coupons as $coupon){
                $ret['info'][]=$coupon->toArray();
            }
            return  $this->ret_success('成功',$ret);
        } catch (Exception $e) {
            return $this->ret_faild($e->getMessage());
        }
    }

    public function apiSaveServiceCoupon($id=-1,$title,$info,$typeid,$uservalue,$value,$expiration){
        try {
                if(ServiceCoupon::saveService($id,$title,$info,$typeid,$uservalue,$value,$expiration))
                    return $this->ret_success('成功');
                return  $this->ret_faild('保存失败');
        } catch (Exception $e) {
            return $this->ret_faild($e->getMessage());
        }
    }
}