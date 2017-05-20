<?php
namespace app\home\controller;

use app\common\controller\Tplus;
use app\system\model\Section_list;
use think\Db;
use think\Session;
use app\system\model\Vip as Vipmodel;
use app\system\model\Order as Ordermodel;
use app\system\model\Section as Sectionmodel;
use app\system\model\Drugs as Drugsmodel;

class Order extends Tplus
{
    //获取用户ID
    public function checklogin(){
        $vid = $this->request->param('id');
//        $vid = 1;
        if(empty($vid)){
            $re['code'] = 2000;
            $re['msg'] = '请登录！';
            echo json_encode($re);
            exit;
        }else{
            return $vid;
        }
    }
    //创建订单
    public function createorder(){
        $data = $this->request->param();

        foreach($data as $key=>$value){
            $this->addtxt($key.":".gettype($value).":".json_encode($value));
        }

//        $this->addtxt(json_encode($data));
//        if(isset($data['car'])){
//            $this->addtxt(gettype($data['car']));
//        }

        $field = array('id','target','type');
        foreach($field as $value){
            if(empty($data[$value])){
                return json(['code'=>1000,'msg'=>'参数错误！','data'=>$value]);
            }
        }
        if($data['target'] == 3){       //买药
            if(empty($data['car'])){
                return json(['code'=>1001,'msg'=>'购物车无数据！','data'=>'']);
            }else{
                $drugs = new Drugsmodel();
                $money = 0.00;
                $belong_id = 0;
                foreach($data['car'] as $key=>$value){
                    $drugsinfo = $drugs->getinfo($value[0]);
                    if(empty($drugsinfo)){
                        return json(['code'=>1002,'msg'=>'无效的药品ID！','data'=>'']);
                    }
                    $car[] = [$value[0],$value[1],sprintf("%.2f", $drugsinfo['price'])];
                    $money += $drugsinfo['price'] * $value[1];
                    $belong_id = $drugsinfo['belong_id'];
                }
                $data['belong_id'] = $belong_id;
                $data['money'] = $money;
                $data['info'] = serialize($car);
                unset($data['target_id']);
                unset($data['hospitalization']);
            }
            if($data['type'] == 2 && (empty($data['address_id']))){         //买药，需配送    自取不做判断
                return json(['code'=>1003,'msg'=>'请选择配送地址和配送时间！','data'=>'']);
            }
        }elseif($data['target'] == 1 || $data['target'] == 2 ){                          //看病
            if(empty($data['target_id'])){
                return json(['code'=>1004,'msg'=>'无病种信息！','data'=>'']);
            }else{
                $section = new Sectionmodel();
                $sectioninfo = $section->getinfo($data['target_id']);
                if(empty($sectioninfo)){
                    return json(['code'=>1005,'msg'=>'错误的病种ID！','data'=>'']);
                }else{
                    $money = 0;
                    if(empty($data['hospitalization'])){
                        $money = $sectioninfo['price'];
                    }elseif($data['hospitalization'] == 1){
                        $money = $sectioninfo['price_hospitalization'];
                    }else{
                        $money = $sectioninfo['price'];
                    }
                    $data['money'] = $money;
                    $data['belong_id'] = $sectioninfo['belong_id'];
                }
            }
            if(empty($data['appoint_time'])){
                return json(['code'=>1006,'msg'=>'请选择预约时间！','data'=>'']);       //看病，不管预约还是上门，都需要预约时间
            }
            if($data['type'] == 2 && empty($data['address_id']) ){                       //买药，上门，需上门地址
                return json(['code'=>1007,'msg'=>'请选择医生上门地址！','data'=>'']);
            }
        }else{
            return json(['code'=>1008,'msg'=>'错误的类型ID！','data'=>'']);
        }

        $data['vip_id'] = $data['id'];
        $data['time'] = time();
        unset($data['id'],$data['car']);
        $data['orderNO'] = time().rand(1000,9999);
        $data['status'] = 5;
        $data['redeem_code'] = rand(1000000,9999999).rand(100000,999999);
        //$ordermodel = new Ordermodel();
        //$order = $ordermodel->createorder($data);
        if(isset($data['appoint_time'])){
            $data['appoint_time'] = ceil($data['appoint_time'] / 1000);
        }
        $order_id = Db::name('order')->insertGetId($data);
        $oinfo = Db::name('order')->where(['id'=>$order_id])->field('*')->find();
        if($order_id){
            return  json(['code'=>0,'msg'=>'下单成功，等待支付！','data'=>['oid'=>$order_id,'oinfo'=>$oinfo]]);
        }else{
            return  json(['code'=>1009,'msg'=>'下单失败，请稍后重试！','data'=>'']);
        }
    }
    //获取单个订单信息
    public function orderinfo(){
        $oid = $this->request->param('oid');
        $vid = $this->checklogin();
        $order = new Ordermodel();
        $orderinfo = $order->apporderinfo($oid,$vid);
        if(empty($orderinfo)){
            return  json(['code'=>1001,'msg'=>'参数错误！','data'=>'']);
        }else{
            return  json(['code'=>0,'msg'=>'订单信息！','data'=>$orderinfo]);
        }
    }
    //订单列表
    public function orderlist(){
        $vid = $this->checklogin();
        $page = $this->request->param('page');
        if(!isset($page)){
            $page = 1;
        }
        $pagesize = $this->request->param('pagesize');
        if(!isset($pagesize)){
            $pagesize = 100;
        }
        $status = $this->request->param('status');
        if($status == 1){
            $map = "vip_id = $vid and status < 25";
        }elseif($status == 2){
            $map = "vip_id = $vid and status = 25";
        }else{
            $map = "vip_id = $vid and status > 25";
        }
        $order = new Ordermodel();
        $list = $order->orderlist($map,$page,$pagesize);
        // var_dump($list);exit;
        if(empty($list)){
            return  json(['code'=>1001,'msg'=>'暂无订单！','data'=>'']);
        }else{
            return  json(['code'=>0,'msg'=>'订单列表！','data'=>$list,'length'=>$list['length']]);
        }
    }
    //申请退款
    public function apply_refund(){
        $vid = $this->checklogin();
        $oid = $this->request->param('oid');
        $money = $this->request->param('refund_money');
        $reason = $this->request->param('reason');
        $refund_type = $this->request->param('refund_type');
        $img = $this->request->param('img');
        $type = $this->request->param('type');
        if(!isset($type)){
            $type = 'jpg';
        }
        $orderm = new Ordermodel();
        $oinfo = $orderm->getinfo($oid);
        if($oinfo['status'] == 5){
            return  json(['code'=>1000,'msg'=>'订单尚未支付！','data'=>'']);
        }elseif($oinfo['status'] > 20){
            return  json(['code'=>1001,'msg'=>'订单已确认，不能退款！','data'=>'']);
        }
        if($oinfo['vip_id'] != $vid){
            return  json(['code'=>1002,'msg'=>'非法操作！','data'=>'']);
        }
        if($money > $oinfo['money']){
            return  json(['code'=>1003,'msg'=>'退款金额大于订单金额！','data'=>'']);
        }
        if(!empty($img)){
            header("Content-type: image/jpeg");
            $path = 'public/uploads/'.date("Ymd").'/';
            if (!file_exists($path)){
                mkdir($path);
            }
            $baseimg = $img;
            $baseimg = substr($baseimg, 23);
            $img = base64_decode($baseimg);
            $all_path = $path.time().rand(1000,9999).'.'.$type;
            $a = file_put_contents($all_path, $img);
        }else{
            $all_path = '';
        }

        try{
            $refund_id = Db::name('refund')->insertGetId(['order_id'=>$oid,'money'=>$money,'reason'=>$reason,'apply_img'=>$all_path,'apply_time'=>time(),'unit_id'=>$oinfo['belong_id'],'refund_type'=>$refund_type]);
            Db::name('order')->where(['id'=>$oid])->update(['pristine_status'=>$oinfo['status']]);
//            Db::name('order')->where(['id'=>$oid])->update(['status'=>25,'refund_id'=>$oinfo['refund_id'].','.$refund_id]);
            Db::name('order')->where(['id'=>$oid])->update(['status'=>25,'refund_id'=>$refund_id,'refund_time'=>time()]);
            Db::commit();
            return  json(['code'=>0,'msg'=>'申请成功，待客服审核退款！','data'=>['oid'=>$oid]]);
        } catch (\Exception $e) {
            Db::rollback();
            return  json(['code'=>1004,'msg'=>'申请失败，请重试！','data'=>'']);
        }
    }
    //服务完成，用户确认
    public function order_confirm(){
        $vid = $this->checklogin();
        $oid = $this->request->param('oid');
        $updata = Db::name('order')->where(['id'=>$oid,'vip_id'=>$vid,'status'=>15])->update(['status'=>35,'confirm_time'=>time()]);
        if(empty($updata)){
            return  json(['code'=>1001,'msg'=>'参数错误！','data'=>'']);
        }else{
            return  json(['code'=>0,'msg'=>'处理成功，请评价！','data'=>'']);
        }
    }
    //订单评价
    public function order_comment(){
        $comment['vip_id'] = $this->checklogin();
        $comment['order_id'] = $this->request->param('oid');
        $comment['content'] = $this->request->param('content');
        $orderinfo = Db::name('order')->where(['id'=>$comment['order_id'],'vip_id'=>$comment['vip_id']])->find();
        if(empty($comment['content']) || empty($orderinfo) || ($orderinfo['status'] != 30 && $orderinfo['status'] != 35)){
            return  json(['code'=>1001,'msg'=>'参数错误！','data'=>'']);
        }
        $comment['target_id'] = $orderinfo['belong_id'];
        $comment['target'] = Db::name('unit')->where(['id'=>$comment['target_id']])->value('type');
        $comment['time'] = time();
        $comment['status'] = 1;
        try{
            Db::name('comment')->insertGetId($comment);
            Db::name('order')->where(['id'=>$comment['order_id']])->update(['status'=>40,'comment_time'=>time()]);
            Db::commit();
            return  json(['code'=>0,'msg'=>'评价成功，订单完成！','data'=>'']);
        } catch (\Exception $e) {
            Db::rollback();
            return  json(['code'=>1004,'msg'=>'评价失败，请重试！','data'=>'']);
        }
    }

    public function test()
    {
        $chi = ['3', '10', '35', '30'];
        print_r($chi);
        echo "<hr>";
        print_r(array_splice($chi,0,1));
        echo "<hr>";
        print_r($chi);
    }

    public function addtxt($str){
        $myfile = fopen("newfile.txt", "a");
        fwrite($myfile, $str."\r\n");
        fclose($myfile);
    }
    public function orderpaytest(){
        $vid = $this->checklogin();
        $oid = $this->request->param('orderid');
        $oinfo = Db::name('order')->where(['id'=>$oid,'vip_id'=>$vid])->find();
        if(empty($oinfo)){
            return json(['code'=>1001,'msg'=>'参数错误！','data'=>'']);
        }
        if($oinfo['status'] !== 5){
            return json(['code'=>1002,'msg'=>'非待付款订单！','data'=>'']);
        }
        $pay = Db::name('order')->where(['id'=>$oid])->update(['status'=>10,'pay_time'=>time()]);
        if($pay){
            return json(['code'=>0,'msg'=>'付款成功！','data'=>'']);
        }else{
            return json(['code'=>1002,'msg'=>'付款失败，请稍后再试！','data'=>'']);
        }
    }



}
