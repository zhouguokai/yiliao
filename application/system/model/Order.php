<?php
namespace app\system\model;

use think\Model;
use think\Db;
use app\system\model\Unit as Unitmodel;
/**
 * 单位模型  医院、门诊、药店
 */
class Order extends Model{
	protected $updateTime = false;
	protected $autoWriteTimestamp = false;
	public function getlist($map,$pagesize=10){
		$list = Order::where($map)->field('id')->order('time desc')->paginate($pagesize);
//		dump($list);exit;
		foreach($list as $key=>$value){
			$list[$key] = $this->getorderinfo($value['id']);
		}
		return $list;
	}
	public function getorderinfo($oid){
		$info = Order::where(['id'=>$oid])->field('*,FROM_UNIXTIME(time) as date ,FROM_UNIXTIME(appoint_time) as appoint_date ')->find();
		$info['address_info'] = Db::name('address')->where(['id'=>$info['address_id']])->find();
		$info['vip_name'] = Db::name('vip')->where(['id'=>$info['vip_id']])->value('name');
		$info['belong_name'] = Db::name('unit')->where(['id'=>$info['belong_id']])->value('name');
		switch($info['target']){
			case 1: $info['target_name'] = Db::name('section_list')->where(['id'=>$info['target_id']])->value('name');break;
			case 2: $info['target_name'] = Db::name('section_list')->where(['id'=>$info['target_id']])->value('name');break;
			case 3:
				$drugs = unserialize($info['info']);
				$i = 0;
				$drugslist = array();
				foreach($drugs as $key=>$value){
					$drugsinfo = Db::name('drugs')->where(['id'=>$value[0]])->find();
					$drugslist[$i]['drugname'] = $drugsinfo['name'];
					$drugslist[$i]['drugnum'] = $value[1];
					$i++;
				}
				$info['target_name'] = $drugslist;
				break;
		}
		return $info;
	}
	public function getrefundinfo($oid){
		//$refundinfo = Db::name('refund')->where(['id'=>$refundid])->find();
		$oinfo = Order::where(['id'=>$oid])->field('*,FROM_UNIXTIME(time) as date ,FROM_UNIXTIME(appoint_time) as appoint_date ')->find();
		$oinfo['address_info'] = Db::name('address')->where(['id'=>$oinfo['address_id']])->find();
		$oinfo['vip_name'] = Db::name('vip')->where(['id'=>$oinfo['vip_id']])->value('name');
		$oinfo['belong_name'] = Db::name('unit')->where(['id'=>$oinfo['belong_id']])->value('name');
		$refundinfo = Db::name('refund')->where(['id'=>$oinfo['refund_id']])->find();
		$oinfo['status'] = $refundinfo['refund_status'];
		switch($oinfo['target']){
			case 1: $oinfo['target_name'] = Db::name('section_list')->where(['id'=>$oinfo['target_id']])->value('name');break;
			case 2: $oinfo['target_name'] = Db::name('section_list')->where(['id'=>$oinfo['target_id']])->value('name');break;
			case 3:
				$drugs = unserialize($oinfo['info']);
				$i = 0;
				$drugslist = array();
				foreach($drugs as $key=>$value){
					$drugsinfo = Db::name('drugs')->where(['id'=>$value[0]])->find();
					$drugslist[$i]['drugname'] = $drugsinfo['name'];
					$drugslist[$i]['drugnum'] = $value[1];
					$i++;
				}
				$oinfo['target_name'] = $drugslist;
				break;
		}
		return $oinfo;
	}
	public function endorder($id){
		return Order::where(['id'=>$id])->update(['status'=>3]);
	}
	public function refundorder($data){
		$oid = $data['id'];
		$oinfo = $this->getinfo($oid);
		if($data['examine_result'] == 1){		//同意
			try{
				Order::where(['id'=>$data['id']])->update(['status'=>30]);
				Db::name('refund')->where(['id'=>$data['refund_id']])->update(['examine_result'=>1,'examine_time'=>time(),'examine_money'=>$data['examine_money'],'examine_reason'=>$data['examine_reason'],'refund_status'=>3]);
				Db::commit();
				return  1;
			} catch (\Exception $e) {
				Db::rollback();
				return  0;
			}
		}else{									//拒绝
			try{
				Order::where(['id'=>$data['id']])->update(['status'=>$oinfo['pristine_status'],'pristine_status'=>0]);
				Db::name('refund')->where(['id'=>$data['refund_id']])->update(['examine_result'=>2,'examine_time'=>time(),'examine_money'=>0,'examine_reason'=>$data['examine_reason'],'refund_status'=>2]);
				Db::commit();
				return  1;
			} catch (\Exception $e) {
				Db::rollback();
				return  0;
			}
		}
	}
	public function getpayinfo($oid){
		$info = Order::where(['id'=>$oid])->field('*,FROM_UNIXTIME(time) as date ,FROM_UNIXTIME(appoint_time) as appoint_date ')->find();
		$info['payinfo'] = Db::name('payment')->field('*,FROM_UNIXTIME(time) as pay_date')->where(['order_id'=>$oid,'type'=>1])->find();
		$info['refunds'] = Db::name('refund')->field('*,FROM_UNIXTIME(apply_time) as apply_date ,FROM_UNIXTIME(examine_time) as examine_date')->where(['order_id'=>$info['id']])->order('apply_time')->select();
		return $info;
	}
	//创建订单
	public function createorder($data){
		$order = Order::allowField(true)->create($data);
		return $order->id;
	}
	//单个订单信息
	public function apporderinfo($oid,$vid){
		$info = Order::where(['id'=>$oid,'vip_id'=>$vid])->field('*,FROM_UNIXTIME(time) as date ,FROM_UNIXTIME(appoint_time) as appoint_date ')->find();
		if(empty($info)){
			return array();
		}
		if($info['address_id'] > 0){
			$info['address_info'] = Db::name('address')->where(['id'=>$info['address_id']])->value('address');
			$info['phone'] = Db::name('address')->where(['id'=>$info['address_id']])->value('phone');
		}else{
			$unit = new Unitmodel();
			$belonginfo = $unit->getinfo($info['belong_id']);
			$info['address_info'] = $unit->apptransaddress($belonginfo);
			$info['phone'] = Db::name('vip')->where(['id'=>$info['vip_id']])->value('phone');
		}
		if($info['target'] == 3){				//买药订单
			$drugs = unserialize($info['info']);
			$i = 0;
			$drugslist = array();
			foreach($drugs as $key=>$value){
				$drugsinfo = Db::name('drugs')->where(['id'=>$value[0]])->find();
				$drugslist[$i]['drugname'] = $drugsinfo['name'];
				$drugslist[$i]['drugnum'] = $value[1];
				$drugslist[$i]['money'] = $value[2]*$value[1];
				$i++;
			}
			$info['drugs_length'] = count($drugslist);
			$info['drugs'] = $drugslist;
		}else{
			$info['seciton_name'] = Db::name('section')->where(['id'=>$info['target_id']])->value('name');
		}
		$info['vip_name'] = Db::name('vip')->where(['id'=>$info['vip_id']])->value('name');
		$info['belong_name'] = Db::name('unit')->where(['id'=>$info['belong_id']])->value('name');
		if($info['refund_id'] > 0){
			$refundinfo = Db::name('refund')->where(['order_id'=>$oid])->order('apply_time')->field('FROM_UNIXTIME(apply_time) as apply_date ,FROM_UNIXTIME(examine_time) as examine_date ,examine_reason,examine_result,reason ')->select();
			$info['refund_length'] = count($refundinfo);
			$info['refundinfo'] = $refundinfo;
		}
		$unset = array('payment_id','vip_id','target_id','info','belong_id','hospitalization','address_id','refund_id',);
		foreach($unset as $field){
			unset($info[$field]);
		}
		return $info;
	}
	//订单列表
	public function orderlist($map,$page,$pagesize){
		$listcount = Order::where($map)->field('belong_id,target_id,info,id,appoint_time,status,type,target,money')->count();
		$list = Order::where($map)->field('belong_id,target_id,info,id,appoint_time,status,type,target,money')->limit($pagesize)->page($page)->select();
		if(empty($list)){
			return array();
		}
		$pagecount = ceil($listcount / $pagesize);
		$unit = new Unitmodel();
		foreach($list as $key=>$value){
			$list[$key]['belong_name'] = $unit->getname($value['belong_id']);
			$info = array();
			if($value['target'] == 1 || $value['target'] == 2){			//看病
				$info['section_name'] = Db::name('section')->where(['id'=>$value['target_id']])->value('name');
				$info['appoint_data'] = date("Y.m.d",$value['appoint_time']);
				//$info['money'] = $value['money'];
			}else{
				$drugs = unserialize($value['info']);
				$i = 0;
				foreach($drugs as $k=>$v){
					$drugsinfo = Db::name('drugs')->where(['id'=>$v[0]])->find();
					$info[$i] = $drugsinfo['name'];
					$i++;
				}
				$info['count'] = $i;
			}
			$list[$key]['info'] = $info;

			switch($value['status']){
				case 5:$list[$key]['status_name'] = '待付款';break;
				case 10:$list[$key]['status_name'] = '已付款';break;
				case 15:$list[$key]['status_name'] = '待用户确认';break;
				case 20:$list[$key]['status_name'] = '待用户确认';break;
				case 25:$list[$key]['status_name'] = '退款中';break;
				case 30:$list[$key]['status_name'] = '已退款，待评价';break;
				case 35:$list[$key]['status_name'] = '用户确认完成，待评价';break;
				case 40:$list[$key]['status_name'] = '订单完成';break;
			}
			$unset = array('target_id','belong_id','type','appoint_time','hospitalization','address_id');
			foreach($unset as $field){
				unset($list[$key][$field]);
			}
		}
		$list['length'] = count($list);
		$list['page'] = $page;
		$list['pagecount'] = $pagecount;
		$list['pagesize'] = $pagesize;
		return $list;
	}
	//获取数据库订单信息
	public function getinfo($oid){
		return Order::get($oid);
	}
}
?>
