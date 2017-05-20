<?php
/*
* 项目控制器
* Author: 初心 [jialin507@foxmail.com]
*/
namespace app\system\controller;
use app\system\model\Section_list;
use think\Db;
use app\system\model\Million;
use app\system\model\UserModel;
use think\Loader;
use app\system\model\Unit as Unitmodel;
use app\system\model\Drugclass as Drugclassmodel;
use think\Session;
use app\system\model\Section as Sectionmodel;

class Hospital extends Admin
{
	protected $unit_id = 0;
	protected $hospital = 0;
	public function __construct()
	{
		parent::__construct();
		if(!IS_ROOT){
			$data=Session::get();
			$uid=$data['user_auth']['uid'];
			$unitmodel = new Unitmodel();
			$info = $unitmodel->getunitinfo($uid);
			if(empty($info)){
				$this->redirect('Base/login');
			}else{
				$this->unit_id = $info['id'];
				$this->hospital = $info['type'];
				$this->typename = $this->hospital == 1 ? '医院' : '门诊';
				Session::set('unit_id',$info['id']);
				Session::set('type',$info['type']);
			}
		}
	}
	//医院信息
	public function index(){
		if(IS_ROOT){
			$this->redirect('project/hospital');
		}else{
			$unitid = $this->unit_id;
			$unitmodel = new Unitmodel();
			$info = $unitmodel->unitinfo($unitid);
			$this->assign('type',$this->hospital);
			$this->assign('typename',$this->typename);
			$this->assign('info',$info);
			$this->assign('list',array(0=>$info));
			return $this->fetch();
		}
//		if($this->request->isAjax()){
//			$data = $this->request->post();
//			if(empty($data['district'])){
//				return $this->ajaxError('请选择区域!');
//			}
//			$unit = new Unitmodel();
//			$edit = $unit->editunit($data);
//			if ($edit) {
//				return $this->ajaxSuccess('修改成功！', url('hospital/index'));
//			} else {
//				return $this->ajaxError('修改失败，请重试!');
//			}
//		}else{
//			$unitid = $this->unit_id;
//			$unitmodel = new Unitmodel();
//			$info = $unitmodel->getinfo($unitid);
//			$this->assign('type',$this->hospital);
//			$this->assign('info',$info);
//			return $this->fetch();
//		}
	}
	public function editunit(){
		if($this->request->isAjax()){
			$data = $this->request->post();
			if(empty($data['district'])){
				return $this->ajaxError('请选择区域!');
			}
			$unit = new Unitmodel();
			$edit = $unit->editunit($data);
			if ($edit) {
				return $this->ajaxSuccess('修改成功！', url('hospital/index'));
			} else {
				return $this->ajaxError('修改失败，请重试!');
			}
		}else{
			$unitid = $this->unit_id;
			$unitmodel = new Unitmodel();
			$info = $unitmodel->getinfo($unitid);
			$this->assign('type',$this->hospital);
			$this->assign('info',$info);
			return $this->fetch();
		}
	}
	//病种列表
	public function section(){
		if(IS_ROOT){
			$map = array();
		}else{
			$map = array();
			$map['belong_id'] = $this->unit_id;
		}
		$section = new Sectionmodel();
		$list = $section->getlist($map);
		$page = $list->render();
		$this->assign('page', $page);
		$this->assign('type',$this->hospital);
		$this->assign('list',$list);
		return $this->fetch();
	}
	//添加病种
	public function addsection(){
		if($this->request->isAjax()){
			$data = $this->request->post();
			if($data['img_id'] == ''){
				return $this->ajaxError('请设置病种图片!');
			}
			if($data['name'] == ''){
				return $this->ajaxError('请选择病种名称!');
			}
			if($data['price'] == 0){
				return $this->ajaxError('请设置价格!');
			}
			$section = new Sectionmodel();
			$data['belong_id'] = $this->unit_id;
			$data['belong'] = $this->hospital;
			$add = $section->addsection($data);
			if ($add) {
				return $this->ajaxSuccess('新增成功！', url('Hospital/section'));
			} else {
				return $this->ajaxError('新增失败，请重试!');
			}
		}else{
			$this->assign('type',$this->hospital);
			$list = Db::name('section_list')->select();
			$this->assign('list',$list);
			return $this->fetch();
		}
	}
	//获取二级分类列表（不用）
	public function getsectionchild(){
		$id = $this->request->param('id');
		$section = new Section_list();
		$list = $section->childlist($id);
		$re['code'] = 1;
		$re['data'] = $list;
		return $re;
	}
	//编辑病种
	public function editsectionadmin(){
		if($this->request->isAjax()){
			$data = $this->request->post();
			if($data['img_id'] == ''){
				return $this->ajaxError('请设置病种图片!');
			}
			if($data['name'] == ''){
				return $this->ajaxError('请选择病种名称!');
			}
			if($data['price'] == 0){
				return $this->ajaxError('请设置价格!');
			}
			$section = new Sectionmodel();
			$edit = $section->editsection($data);
			if ($edit) {
				return $this->ajaxSuccess('修改成功！', url('Hospital/section'));
			} else {
				return $this->ajaxError('修改失败，请重试!');
			}
		}else{
			$this->assign('type',$this->hospital);
			$section = new Sectionmodel();
			$id = $this->request->param('id');
			$info = $section->getinfo($id);
			$list = Db::name('section_list')->select();
			//$childlist = Db::name('section_list')->where(['pid'=>$info['father_id']])->select();
			//$this->assign('childlist',$childlist);
			$this->assign('list',$list);
			$this->assign('info',$info);
			return $this->fetch();
		}
	}
	//删除病种
	public function delsection(){
		$id = $this->request->param('id');
		$section = new Sectionmodel();
		$del = $section->delseciton($id);
		if ($del) {
			return $this->ajaxSuccess('删除成功！', url('Hospital/section'));
		} else {
			return $this->ajaxError('删除失败，请重试!');
		}
	}
	//恢复病种
	public function regainsection(){
		$id = $this->request->param('id');
		$section = new Sectionmodel();
		$regain = $section->regainseciton($id);
		if ($regain) {
			return $this->ajaxSuccess('恢复成功！', url('Hospital/section'));
		} else {
			return $this->ajaxError('恢复失败，请重试!');
		}
	}
	//评论管理-->跳转至管理后台的评论管理
	public function comment(){
		$this->redirect('project/comment');
	}
	//订单管理-->跳转至管理后台的订单管理
	public function orderpro(){
		$this->redirect('project/orderpro');
	}
	//
	public function sectionlist(){
		$belongid=5;
		$father = Db::name('section')->where(['belong_id'=>$belongid])->field('id,father_id')->group('father_id')->select();
		foreach($father as $key=>$value){
			$father[$key]['name'] = Db::name('section_list')->where(['id'=>$value['father_id']])->value('name');
			$child = Db::name('section')->where(['father_id'=>$value['father_id']])->select();
			$father[$key]['child'] = $child;
		}
		return json(['code'=>0,'msg'=>'病种列表','data'=>$father]);
	}

}
