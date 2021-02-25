<?php
declare (strict_types = 1);

namespace app\business\controller;
use app\BaseController;
use app\business\model\Admin;
use app\business\model\Admin as AdminModel;
use app\business\model\Business;
use app\business\model\ServiceLog;
use app\business\model\ServiceOrder;
use mysql_xdevapi\Collection;
use mysql_xdevapi\ColumnResult;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Helper\Sample;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use think\Exception;

class Index extends BaseController
{
    public function index()
    {
        return '您好！这是一个[business]示例应用';
    }

    public function testsql(){
        $admin=AdminModel::where('id',1)->select();
        var_dump($admin);
    }

    public function info(){
        echo phpinfo();
    }

    public function md5jm($s){
        echo md5($s);
    }

    /**
     * 首页展示统计数据
     * @return \think\response\Json
     */
    public function getStatistics(){
        try {
            $ret = array();
            $ret['total_order'] = ServiceOrder::getMyStoreServiceOrderCount();
            $ret['today_order'] = ServiceOrder::getMyStoreServiceOrderCount(strtotime('today'), strtotime('tomorrow'));
            $ret['total_money'] = ServiceOrder::getMyStoreServiceOrderMoney();
            $ret['today_money'] = ServiceOrder::getMyStoreServiceOrderMoney(strtotime('today'), strtotime('tomorrow'));
            return $this->ret_success('成功', $ret);
        }catch (Exception $e){
            return $this->ret_faild($e->getMessage());
        }
    }


    public function apiHasMessage(){
        try{
            return $this->ret_success('成功',ServiceLog::hasNew());
        }catch(Exception $e){
            return $this->ret_faild($e->getMessage());
        }
    }
    public function apiGetNewMessageCount(){
        try {
            $count=ServiceLog::getNewMessageCount();
            return $this->ret_success('成功',$count);
        } catch (Exception $e) {
            return $this->ret_faild($e->getMessage());
        }
    }
    public function apiGetNewMessage($page=0,$pagesize=10){
        try {
            $msgs=ServiceLog::getNewMessage($page, $pagesize);
            $ret=array();
            $ret['count']=count($msgs);
            $info=array();
            $ret['info']=&$info;
            foreach ($msgs as $msg){
                $t=$msg->toArray();
                $service=\app\business\model\Service::getServiceById($msg->service_id);
                $t['service']=$service->getAttr('name');
                if($msg->c_typeid==1)
                    $uc=Admin::getAdminById($msg->c_id);
                else if($msg->c_typeid==2)
                    $uc=Business::getUserById($msg->c_id);
                $t['cname']=$uc->getAttr('account');
                $info[]=$t;
            }
            return $this->ret_success('成功',$ret);
        } catch (Exception $e) {
            return $this->ret_faild($e->getMessage());
        }
    }

    /**
     * @param $ids 已读的id 用,隔开
     * @return \think\response\Json
     */
    public function apiReadMessage($ids){
        try {
            $idsA=explode(',',$ids);
            foreach ($idsA as $id)
                ServiceLog::readMessage($id);
            return $this->ret_success('成功');
        } catch (Exception $e) {
            return $this->ret_faild($e->getMessage());
        }
    }

    public function apiReadAllMessage(){
        try{
            ServiceLog::readAllMessage();
            return $this->ret_success('成功');
        } catch (Exception $e) {
            return $this->ret_faild($e->getMessage());
        }
    }

    public static function saveExcel(){
        $orders=ServiceOrder::listMyStoreServiceOrder(-1,-1,1);
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
        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $filename=$_SERVER['DOCUMENT_ROOT'].'/temp'.'/'.random_int(1000,9999).'.xlsx';
        $writer->save($filename);
        return $filename;
    }
    public function testZip(){
        $names=array();
        for($i=0;$i<2;$i++)
            $names[]=self::saveExcel();

        $zip=new \ZipArchive();
        $zipFilePath=$_SERVER['DOCUMENT_ROOT'].'/temp'.'/'.random_int(1000,9999).'.zip';
        $zipStatus=$zip->open($zipFilePath,\ZipArchive::CREATE|\ZipArchive::OVERWRITE);
        if($zipStatus!==TRUE)
            return $this->ret_faild('创建zip文件失败');
        foreach ($names as $name){
            $zip->addFile($name,pathinfo($name)['basename']);
        }
//        $zip->addFile('D:/phpstudy_pro/WWW/iumanageradmin/syck_xcx/public/temp/test.jpg');
        $zip->close();
        ob_start();
        $filesize=readfile($zipFilePath);
        header('Content-Type:application/x-zip-compressed');
        header('Content-Disposition: attachment;filename="财务流水'.random_int(1000,9999).'.zip"');
        header('Accept-Ranges:  bytes');
        header( "Accept-Length: " .$filesize);
//        header('Cache-Control: max-age=0');
//        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
//        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); // always modified
//        header('Cache-Control: cache, must-revalidate'); // HTTP/1.1
//        header('Pragma: public'); // HTTP/1.0

        foreach ($names as $name){
            unlink($name);
        }
        unlink($zipFilePath);
    }
}
