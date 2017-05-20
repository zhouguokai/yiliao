<?php
/*
* 项目控制器
* Author: 初心 [jialin507@foxmail.com]
*/
namespace app\system\controller;
use app\system\model\Drugs;
use think\Db;
use app\system\model\Million;
use app\system\model\UserModel;
use think\Loader;
use app\system\model\Unit as Unitmodel;
use app\system\model\Drugclass as Drugclassmodel;
use think\Session;
use app\system\model\Drugs as Drugsmodel;

class Pharmacy extends Admin
{
	protected $unit_id = 0;
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
				Session::set('unit_id',$info['id']);
			}
		}
	}
	public function index(){
		if (IS_ROOT) {
			$this->redirect('project/pharmacy');
		} else {
			$unitid = $this->unit_id;
			$unitmodel = new Unitmodel();
			$info = $unitmodel->unitinfo($unitid);
			$this->assign('info', $info);
			$this->assign('typename', '药店');
			$this->assign('list', array(0 => $info));
			return $this->fetch();
		}
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
				return $this->ajaxSuccess('修改成功！', url('pharmacy/index'));
			} else {
				return $this->ajaxError('修改失败，请重试!');
			}
		}else{
			$unitid = $this->unit_id;
			$unitmodel = new Unitmodel();
			$info = $unitmodel->unitinfo($unitid);
			$this->assign('info',$info);
			return $this->fetch();
		}
	}
	public function drugs(){
		if(IS_ROOT){
			$map = array();
		}else{
			$map = array();
			$map['belong_id'] = $this->unit_id;
		}
		//$uid = $this->unit_id;
		$drugs = new Drugs();
		$list = $drugs->getlist($map);
		$page = $list->render();
		$this->assign('page', $page);
		$this->assign('list',$list);
		return $this->fetch();
	}
	public function adddrugs(){
		if($this->request->isAjax()){
			$data = $this->request->post();
			if($data['name'] == ''){
				return $this->ajaxError('请选择病种名称!');
			}
			if($data['img_id'] == 0){
				return $this->ajaxError('请设置简介图!');
			}
			if($data['price'] == 0){
				return $this->ajaxError('请设置价格!');
			}
			if($data['stock'] == 0){
				return $this->ajaxError('请设置可售库存!');
			}
			$drugs = new Drugsmodel();
			$data['belong_id'] = $this->unit_id;
			$add = $drugs->adddrugs($data);
			if ($add) {
				return $this->ajaxSuccess('新增成功！', url('pharmacy/drugs'));
			} else {
				return $this->ajaxError('新增失败，请重试!');
			}
		}else{
			$list = Db::name('drugclass')->select();
			$this->assign('list',$list);
			return $this->fetch();
		}
	}
	public function editdrugs(){
		$drugs = new Drugsmodel();
		if($this->request->isAjax()){
			$data = $this->request->post();
			if($data['img_id'] == 0){
				return $this->ajaxError('请设置简介图!');
			}
			$edit = $drugs->editdrugs($data);
			if ($edit) {
				return $this->ajaxSuccess('修改成功！', url('pharmacy/drugs'));
			} else {
				return $this->ajaxError('修改失败，请重试!');
			}
		}else{
			$id = $this->request->param('id');
			$info = $drugs->getinfo($id);
			$list = Db::name('drugclass')->select();
			$this->assign('list',$list);
			$this->assign('info',$info);
			return $this->fetch();
		}
	}
	public function deldrugs(){
		$id = $this->request->param('id');
		$drugs = new Drugsmodel();
		$del = $drugs->deldrugs($id);
		if ($del) {
			return $this->ajaxSuccess('删除成功！', url('pharmacy/drugs'));
		} else {
			return $this->ajaxError('删除失败，请重试!');
		}
	}
	public function regaindrugs(){
		$id = $this->request->param('id');
		$drugs = new Drugsmodel();
		$regain = $drugs->regaindrugs($id);
		if ($regain) {
			return $this->ajaxSuccess('恢复成功！', url('pharmacy/drugs'));
		} else {
			return $this->ajaxError('恢复失败，请重试!');
		}
	}
	public function comment(){
		$this->redirect('project/comment');
	}
	public function orderpro(){
		$this->redirect('project/orderpro');
	}



}
