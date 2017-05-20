<?php
namespace app\system\model;

use think\Model;
use think\Db;
use app\system\model\Vip as Vipmodel;
use app\system\model\Unit as Unitmodel;
/**
 * 单位模型  医院、门诊、药店
 */
class Comment extends Model{
	protected $updateTime = false;
	protected $autoWriteTimestamp = false;
	public function getlist($map,$pagesize=10){
		$list = Comment::where($map)->field("*,FROM_UNIXTIME(time) as date")->paginate($pagesize);
		$vip = new Vipmodel();
		$unit = new Unitmodel();
		foreach($list as $key=>$value){
			$list[$key]['vipname'] = $vip->getname($value['vip_id']);
			$list[$key]['targetname'] = $unit->getname($value['target_id']);
		}
		return $list;
	}
	public function delcomment($id){
		$comment = new Comment();
		$del = $comment->where('id', $id)->update(['status'=>0]);
		return $del;
	}
	public function regaincomment($id){
		$comment = new Comment();
		$re = $comment->where('id', $id)->update(['status'=>1]);
		return $re;
	}

}
?>
