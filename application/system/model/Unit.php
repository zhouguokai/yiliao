<?php
namespace app\system\model;

use think\Model;
use think\Db;
/**
 * 单位模型  医院、门诊、药店
 */
class Unit extends Model{
	protected $updateTime = false;
	protected $autoWriteTimestamp = false;
	public function getlist($type,$pagesize=5){
		$list = Unit::where(['type'=>$type])->order('id desc')->paginate($pagesize);
		foreach($list as $key=>$value){
			$list[$key]['position'] = $this->transaddress($value).$value['address'];
		}
		return $list;
	}
	public function unitinfo($unitid){
		$info = Unit::where(['id'=>$unitid])->find();
		$info['position'] = $this->transaddress($info).$info['address'];
		return $info;
	}
	public function addunit($data){
		$user['username'] = $data['username'];
		$user['password'] = tplus_ucenter_md5($data['password'],config('auth_key'));
		$user['reg_time'] = time();
		$user['last_login_time'] = time();
		$user['update_time'] = time();
		$user['reg_ip'] = get_client_ip(1);
		$user['status'] = 1;
		$uid = Db::name('user')->insertGetId($user);
		$data['uid'] = $uid;
		$poweruser['uid'] = $uid;
		$type = $data['type'];
		$group_id = $type == 1 ? 2 : ($type == 2 ? 3 : 4);
		$poweruser['group_id'] = $group_id;
		Db::name('power_user')->insert($poweruser);
		unset($data['username']);
		unset($data['password']);
		unset($data['repassword']);
		$unitid = Db::name('unit')->insertGetId($data);
		return $unitid;
	}
	public function checkuser($username){
		$userinfo = Db::name('user')->where(['username'=>$username])->find();
		if(empty($userinfo)){
			return 0;
		}else{
			return 1;
		}
	}
	public function getname($uid){
		return Unit::where(['id'=>$uid])->value('name');
	}
	public function transaddress($data){
		$province = Db::name('district')->where(['id'=>$data['province']])->value('name');
		$city = Db::name('district')->where(['id'=>$data['city']])->value('name');
		$district = Db::name('district')->where(['id'=>$data['district']])->value('name');
		return $province.' '.$city.' '.$district.' '.' '.$data['address'];
	}
	public function apptransaddress($data){
		//$province = Db::name('district')->where(['id'=>$data['province']])->value('name');
		$city = Db::name('district')->where(['id'=>$data['city']])->value('name');
		$district = Db::name('district')->where(['id'=>$data['district']])->value('name');
		return $city.' '.$district.' '.$data['address'];
	}
	public function getinfo($id){
		$info = Unit::get($id);
		return $info;
	}
	public function getunitinfo($uid){
		$info = Unit::where(['uid'=>$uid])->find();
		return $info;
	}
	public function editunit($data){
		$unit = new Unit;
		return $unit->where('id', $data['id'])->update($data);
	}
	public function delunit($id){
		$unit = new Unit;
		$del = $unit->where('id', $id)->update(['status'=>0]);
		if($del){
			$type = $unit->where('id', $id)->value('type');
			return $type;
		}else{
			return 0;
		}
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
