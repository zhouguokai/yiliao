<?php
namespace app\system\model;

use think\Model;
use think\Db;
use think\Session;

/**
 * 单位模型  医院、门诊、药店
 */
class Section extends Model{
	protected $updateTime = false;
	protected $autoWriteTimestamp = false;
	public function getlist($map,$pagesize=5){
		$list = Section::where($map)->paginate($pagesize);
		foreach($list as $key=>$value){
			$list[$key]['belong_name'] = Db::name('unit')->where(['id'=>$value['belong_id']])->value('name');
			//$list[$key]['childname'] = Db::name('section_list')->where(['id'=>$value['child_id']])->value('name');
			$list[$key]['fathername'] = Db::name('section_list')->where(['id'=>$value['father_id']])->value('name');
		}
		return $list;
	}
	public function addsection($data){
		$sectionid = Db::name('section')->insertGetId($data);
		return $sectionid;
	}
	public function transaddress($data){
		$province = Db::name('district')->where(['id'=>$data['province']])->value('name');
		$city = Db::name('district')->where(['id'=>$data['city']])->value('name');
		$district = Db::name('district')->where(['id'=>$data['district']])->value('name');
		return $province.' '.$city.' '.$district.' ';
	}
	public function getinfo($id){
		$info = Section::get($id);
		//$info['childname'] = Db::name('section_list')->where(['id'=>$info['child_id']])->value('name');
		$info['fathername'] = Db::name('section_list')->where(['id'=>$info['father_id']])->value('name');
		return $info;
	}
	public function editsection($data){
		$section = new Section();
		return $section->where('id', $data['id'])->update($data);
	}
	public function delseciton($id){
		$section = new Section();
		$del = $section->where('id', $id)->update(['status'=>0]);
		return $del;
	}
	public function regainseciton($id){
		$section = new Section();
		$re = $section->where('id', $id)->update(['status'=>1]);
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
