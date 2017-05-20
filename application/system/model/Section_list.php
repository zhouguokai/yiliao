<?php
namespace app\system\model;

use think\Model;
use think\Db;
use think\Session;

/**
 * 单位模型  医院、门诊、药店
 */
class Section_list extends Model{
	protected $updateTime = false;
	protected $autoWriteTimestamp = false;
	public function sectionlist(){
		return Section_list::where('')->select();
	}
	public function addsection($name){
		$section = Db::name('section_list')->where(['name'=>$name])->find();
		if(empty($section)){
			return Db::name('section_list')->insert(['name'=>$name]);
		}else{
			return 0;
		}
	}
//	public function addsectionchild($name,$id){
//		$section = Db::name('section_list')->where(['name'=>$name])->find();
//		if(empty($section)){
//			return Db::name('section_list')->insert(['name'=>$name]);
//		}else{
//			return 0;
//		}
//	}
	public function delsection($id){
		return Db::name('section_list')->where(['id'=>$id])->delete();
	}
//	public function childlist($id){
//		return Section_list::where('')->select();
//	}
	public function getsectionname($id){
		return Section_list::where(['id'=>$id])->value('name');
	}
	public function editsection($name,$id){
		return Section_list::where(['id'=>$id])->update(['name'=>$name]);
	}


	}
	?>
