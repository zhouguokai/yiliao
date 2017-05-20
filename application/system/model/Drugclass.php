<?php
namespace app\system\model;

use think\Model;
use think\Db;
/**
 * 单位模型  医院、门诊、药店
 */
class Drugclass extends Model{
	protected $updateTime = false;
	protected $autoWriteTimestamp = false;
	public function getlist(){
		$list = Drugclass::all();
		return $list;
	}
	public function saveclass($data){
		return Drugclass::save(['name'=>$data['name']],['id'=>$data['id']]);
	}
	public function delclass($id){
		$drugs = Db::name('drugs')->where(['class_id'=>$id])->select();
		if(empty($drugs)){
			Drugclass::destroy($id);
			return 0;
		}else{
			return count($drugs);
		}
	}
	public function getinfo($id){
		return Drugclass::get($id);
	}
	public function addclass($name){
		$info = Drugclass::where(['name'=>$name])->find();
		if(empty($info)){
			$class = Drugclass::create(['name' => $name,]);
			return $class->id;
		}else{
			return 0;
		}
	}

	}
	?>
