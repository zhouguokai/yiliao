<?php
namespace app\system\model;

use think\Model;
use think\Db;
/**
 * 单位模型  医院、门诊、药店
 */
class Vip extends Model{
	protected $updateTime = false;
	protected $autoWriteTimestamp = false;
	public function getinfo($vid){
		return Vip::get($vid);
	}
	public function getname($vid){
		return Vip::where(['id'=>$vid])->value('name');
	}
	public function checktoken($token){
		$info = Vip::where(['token'=>$token])->find();
		if(empty($info)){
			return ['check'=>0];
		}elseif(($info['token_time'] - time()) > 604800){
			return ['check'=>-1];
		}else{
			$token = $this->newtoken($info['id']);
			$info = Vip::where(['id'=>$info['id']])->find();
			return ['check'=>1,'info'=>$info];
		}
	}
	public function checkphone($phone){
		$info = Vip::where(['phone'=>$phone])->find();
		if(empty($info)){
			return 0;
		}else{
			return 1;
		}
	}
	public function login($data){
		$info = Db::name('vip')->where(['phone'=>$data['phone'],'password'=>md5($data['password'])])->find();
		$this->newtoken($info['id']);
		return Db::name('vip')->where(['id'=>$info['id']])->find();
	}
	public function register($data){
		$vip = new Vip;
		$check_register = Vip::where(['phone'=>$data['phone']])->find();
		if(!empty($check_register)){
			return false;
		}
		$vipinfo = array(
			'phone'=>$data['phone'],
			'password'=>md5($data['password']),
			'token'=>$this->newtoken($data['phone']),
			'token_time'=>time(),
		);
		$vip->data($vipinfo)->save();
		$vip_id = $vip->id;
		return Vip::get($vip_id);
	}
	public function setpassword($data){
		$set = Vip::where(['phone'=>$data['phone']])->update(['password'=>md5($data['password'])]);
		return $set;
	}
	//根据手机号生成随机字符串
	public function newtoken($vid){
		$uinfo = Vip::where(['id'=>$vid])->find();
		if(empty($uinfo['phone'])){
			$user = $uinfo['weixin'];
		}else{
			$user = $uinfo['phone'];
		}
		$str = '';
		$strPol = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz+-/*";
		$max = strlen($strPol)-1;
		for($i=0;$i<11;$i++){
			$str.=$strPol[rand(0,$max)].$user[$i].time();
		}
		$token = md5($str);
		$tokenarr = array(
			'token'=>$token,
			'token_time'=>time()
		);
		//
		Vip::where(['id'=>$vid])->update($tokenarr);
		//dump($tokenarr);exit;
		return $token;
	}

	public function checkpw($opw,$vid){
		return Vip::where(['id'=>$vid,'password'=>md5($opw)])->find();
	}
	public function modifypw($npw,$vid){
		return Vip::where(['id'=>$vid])->update(['password'=>md5($npw),'update_time'=>time()]);
	}
	public function changeinfo($data,$vid){
		$unset = array('phone','password','token_time','token','id');
		foreach($unset as $value){
			unset($data[$value]);//防止恶意篡改数据
		}
		return Vip::where(['id'=>$vid])->update($data);
	}

}
?>
