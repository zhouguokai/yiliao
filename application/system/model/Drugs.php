<?php
namespace app\system\model;

use think\Model;
use think\Db;
use think\Session;

/**
 * 单位模型  医院、门诊、药店
 */
class Drugs extends Model{
	protected $updateTime = false;
	protected $autoWriteTimestamp = false;
	public function getlist($map,$pagesize=10){
		$list = Drugs::where($map)->paginate($pagesize);
		foreach($list as $key=>$value){
			$list[$key]['class'] = Db::name('drugclass')->where(['id'=>$value['class_id']])->value('name');
		}
		return $list;
	}
	public function getdrugslist($pagesize=10){
		$list = Drugs::paginate($pagesize);
		foreach($list as $key=>$value){
			$list[$key]['class'] = Db::name('drugclass')->where(['id'=>$value['class_id']])->value('name');
			$list[$key]['pharmacy'] = Db::name('unit')->where(['id'=>$value['belong_id']])->value('name');
		}
		return $list;
	}
	public function adddrugs($data){
		$id = Db::name('drugs')->insertGetId($data);
		return $id;
	}
	public function transaddress($data){
		$province = Db::name('district')->where(['id'=>$data['province']])->value('name');
		$city = Db::name('district')->where(['id'=>$data['city']])->value('name');
		$district = Db::name('district')->where(['id'=>$data['district']])->value('name');
		return $province.' '.$city.' '.$district.' ';
	}
	public function getinfo($id){
		$info = Drugs::get($id);
		return $info;
	}
	public function getdrugsinfo($id){
		$info = Drugs::get($id);
		$info['class'] = Db::name('drugclass')->where(['id'=>$info['class_id']])->value('name');
		$info['pharmacy'] = Db::name('unit')->where(['id'=>$info['belong_id']])->value('name');
		return $info;
	}
	public function editdrugs($data){
		$drugs = new Drugs();
		return $drugs->where('id', $data['id'])->update($data);
	}
	public function deldrugs($id){
		$drugs = new Drugs();
		$del = $drugs->where('id', $id)->update(['status'=>0]);
		return $del;
	}
	public function regaindrugs($id){
		$drugs = new Drugs();
		$re = $drugs->where('id', $id)->update(['status'=>1]);
		return $re;
	}

	public function regainunit($id){
		$unit = new Unit;
		$re = $unit->where('id', $id)->update(['status'=>1]);
		if($re){
			$type = $unit->where('id', $id)->value('type');
			return $type;
		}else{
			return 0;
		}
	}
	public function getid($uid){
		return Unit::where(['uid'=>$uid])->value('id');
	}


	}
	?>
