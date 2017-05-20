<?php
/*
* 项目控制器
* Author: 初心 [jialin507@foxmail.com]
*/
namespace app\system\controller;
use think\Db;
use app\system\model\Million;
use app\system\model\UserModel;
use think\Loader;
use app\system\model\Unit as Unitmodel;
use app\system\model\Drugclass as Drugclassmodel;
use think\Session;
use app\system\model\Comment as Commentmodel;
use app\system\model\Drugs as Drugsmodel;
use app\system\model\Order as Ordermodel;
use app\system\model\Section_list as Sectionlistmodel;

class Project extends Admin
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
	//医院管理
	public function hospital(){
		$unit = new Unitmodel();
		$type = 1;
		$list = $unit->getlist($type);
		$page = $list->render();
		$this->assign('list', $list);
		$this->assign('page', $page);
		$this->assign('type',$type);
		$this->assign('typename','医院');
		return $this->fetch('unit');
	}
	//添加单元	医院、门诊、药店
	public function addunit(){
		if($this->request->isAjax()){
			$data = $this->request->post();
			if(empty($data['district'])){
				return $this->ajaxError('请选择区域!');
			}
			if($data['password']!==$data['repassword'] || empty($data['password'])){
				return $this->ajaxError('两次密码输入不一致，请重新设置!');
			}
			$unit = new Unitmodel();
			if($unit->checkuser($data['username'])){
				return $this->ajaxError('请设置/修改登录名!');
			}
			$type = $data['type'];
			$location = $type == 1 ? 'hospital' : ($type == 2 ? 'clinic' : 'pharmacy');
			$add = $unit->addunit($data);
			if ($add) {
				return $this->ajaxSuccess('新增成功！', url('project/'.$location));
			} else {
				return $this->ajaxError('新增失败，请重试!');
			}
		}else{
			$type = $this->request->param('type');
			$typename = $type == 1 ? '医院' : ($type == 2 ? '门诊' : '药店');
			$this->assign('type',$type);
			$this->assign('typename',$typename);
			return $this->fetch();
		}
	}
	//编辑单元	医院、门诊、药店
	public function editunit(){
		$unit = new Unitmodel();
		if($this->request->isAjax()){
			$data = $this->request->post();
			if(empty($data['district'])){
				return $this->ajaxError('请选择区域!');
			}
			$type = $data['type'];
			$location = $type == 1 ? 'hospital' : ($type == 2 ? 'clinic' : 'pharmacy');
			$edit = $unit->editunit($data);
			if ($edit) {
				return $this->ajaxSuccess('修改成功！', url('project/'.$location));
			} else {
				return $this->ajaxError('修改失败，请重试!');
			}
		}else{
			$id = $this->request->param('id');
			$info = $unit->getinfo($id);
			$type = $info['type'];
			$typename = $typename = $type == 1 ? '医院' : ($type == 2 ? '门诊' : '药店');
			$this->assign('typename',$typename);
			$this->assign('info',$info);
			return $this->fetch();
		}
	}
	//删除单元	医院、门诊、药店
	public function delunit(){
		$id = $this->request->param('id');
		$unit = new Unitmodel();
		$del = $unit->delunit($id);
		if ($del) {
			$location = $del == 1 ? 'hospital' : ($del == 2 ? 'clinic' : 'pharmacy');
			return $this->ajaxSuccess('删除成功！', url('project/'.$location));
		} else {
			return $this->ajaxError('删除失败，请重试!');
		}
	}
	//恢复单元	医院、门诊、药店
	public function regainunit(){
		$id = $this->request->param('id');
		$unit = new Unitmodel();
		$re = $unit->regainunit($id);
		if ($re) {
			$location = $re == 1 ? 'hospital' : ($re == 2 ? 'clinic' : 'pharmacy');
			return $this->ajaxSuccess('恢复成功！', url('project/'.$location));
		} else {
			return $this->ajaxError('恢复失败，请重试!');
		}
	}
	//门诊管理
	public function clinic(){
		$unit = new Unitmodel();
		$type = 2;
		$list = $unit->getlist($type);
		$page = $list->render();
		$this->assign('list', $list);
		$this->assign('page', $page);
		$this->assign('type',$type);
		$this->assign('typename','门诊');
		return $this->fetch('unit');
	}
	//药店管理
	public function pharmacy(){
		$unit = new Unitmodel();
		$type = 3;
		$list = $unit->getlist($type);
		$page = $list->render();
		$this->assign('list', $list);
		$this->assign('page', $page);
		$this->assign('type',$type);
		$this->assign('typename','药店');
		return $this->fetch('unit');
	}
	//订单管理
	public function orderpro(){
		if(IS_ROOT){
			$map = array();
		}else{
			$map = array();
			$map['belong_id'] = $this->unit_id;
		}
		$order = new Ordermodel();
		$list = $order->getlist($map);
		$page = $list->render();
		$this->assign('list', $list);
		$this->assign('page', $page);
		return $this->fetch();
	}
	//订单详情
	public function orderinfo(){
		$oid = $this->request->param('id');
		$order = new Ordermodel();
		$info = $order->getorderinfo($oid);
		$this->assign('info', $info);
		return $this->fetch();
	}
	//药品分类
	public function drugclass(){
		$drugclass = new Drugclassmodel();
		$list = $drugclass->getlist();
		$this->assign('list',$list);
		return $this->fetch();
	}
	//药品分类详情
	public function classinfo(){
		$drugclass = new Drugclassmodel();
		$id = $this->request->param('id');
		$info = $drugclass->getinfo($id);
		$this->assign('info',$info);
		return $this->fetch();
	}
	//保存药品分类
	public function saveclass(){
		$data = $this->request->post();
		$drugclass = new Drugclassmodel();
		$save = $drugclass->saveclass($data);
		$this->redirect('project/drugclass');
	}
	//删除药品分类
	public function delclass(){
		$id = $this->request->param('id');
		$drugclass = new Drugclassmodel();
		$del = $drugclass->delclass($id);
		if($del == 0){
			return $this->ajaxSuccess('删除成功！', url('project/drugclass'));
		} else {
			return $this->ajaxError('该分类下有'.$del.'款药品，暂不支持删除!');
		}
	}
	//添加药品分类
	public function addclass(){
		$name = $this->request->param('name');
		if(empty($name)){
			return $this->ajaxError('请填写分类名!');
		}
		$drugclass = new Drugclassmodel();
		$add = $drugclass->addclass($name);
		if($add){
			return $this->ajaxSuccess('添加成功！', url('project/drugclass'));
		} else {
			return $this->ajaxError('已有分类名!');
		}
	}
	//评价管理
	public function comment(){
		if(IS_ROOT){
			$map = array();
		}else{
			$map = array();
			$map['target_id'] = $this->unit_id;
		}
		$comment = new Commentmodel();
		$list = $comment->getlist($map);
		$page = $list->render();
		$this->assign('list', $list);
		$this->assign('page', $page);
		return $this->fetch();
	}
	//删除评论
	public function delcomment(){
		$id = $this->request->param('id');
		$comment = new Commentmodel();
		$del = $comment->delcomment($id);
		if ($del) {
			return $this->ajaxSuccess('删除成功！', url('project/comment'));
		} else {
			return $this->ajaxError('删除失败，请重试!');
		}
	}
	//恢复评论
	public function regaincomment(){
		$id = $this->request->param('id');
		$comment = new Commentmodel();
		$regain = $comment->regaincomment($id);
		if ($regain) {
			return $this->ajaxSuccess('恢复成功！', url('project/comment'));
		} else {
			return $this->ajaxError('恢复失败，请重试!');
		}
	}
	//药品管理
	public function drugs(){
		$drugs = new Drugsmodel();
		$list = $drugs->getdrugslist();
		$page = $list->render();
		$this->assign('page', $page);
		$this->assign('list',$list);
		return $this->fetch();
	}
	//药品信息
	public function drugsinfo(){
		$drugs = new Drugsmodel();
		$id = $this->request->param('id');
		$info = $drugs->getdrugsinfo($id);
		$list = Db::name('drugclass')->select();
		$this->assign('list',$list);
		$this->assign('info',$info);
		return $this->fetch();
	}
	//结束订单
	public function endorder(){
		$id = $this->request->param('id');
		$order = new Ordermodel();
		$end = $order->endorder($id);
		if ($end) {
			return $this->ajaxSuccess('已结束订单！', url('project/orderpro'));
		} else {
			return $this->ajaxError('操作失败，请重试!');
		}
	}
	//退款订单管理
	public function refundmanger(){
		if(IS_ROOT){
			$map = 'refund_id > 0';
		}else{
			$map = "belong_id = $this->unit_id and refund_id > 0";
		}
		$orderlist = Db::name('order')->where($map)->order('refund_time')->paginate(2);
		//$refundlist = Db::name('refund')->where($map)->field('id')->order('refund_status , apply_time desc')->paginate(5);
		$orderlist = Db::name('order')->where($map)->order('refund_time')->paginate(2);
		//$refundlist = Db::name('refund')->where($map)->field('id')->order('refund_status , apply_time desc')->paginate(5);
		$orderlist = Db::name('order')->where($map)->order('refund_time')->paginate(10);
		//$refundlist = Db::name('refund')->where($map)->field('id')->order('refund_status , apply_time desc')->paginate(5);
		$order = new Ordermodel();
		$list = array();
		foreach($orderlist as $key=>$value){
			$list[$key] = $order->getrefundinfo($value['id']);
		}
		//return json($list);
		$page = $orderlist->render();
		$this->assign('list', $list);
		$this->assign('page', $page);
		return $this->fetch();
	}
	//订单退款详情
	public function refundorder(){
		$order = new Ordermodel();
		$id = $this->request->param('oid');
		$info = $order->getpayinfo($id);
		$this->assign('info',$info);
		return $this->fetch();
	}
	//订单退款
	public function orderrefund(){
		$order = new Ordermodel();
		$data = $this->request->post();
		if($data['examine_result'] == 1){				//同意
			if($data['examine_money'] > $data['money']){
				return $this->ajaxError('退款金额大于订单金额，请重新输入!');
			}
		}elseif($data['examine_result'] == 2) {		//拒绝
			if(empty($data['examine_reason'])){
				return $this->ajaxError('请填写拒绝理由!');
			}
		}else{
			return $this->ajaxError('请选择审核结果!');
		}
		$refund = $order->refundorder($data);
		if ($refund) {
			return $this->ajaxSuccess('审核成功！', url('project/refundmanger'));
		} else {
			return $this->ajaxError('操作失败，请重试!');
		}

	}
	//订单处理
	public function proorder(){
		$oid = $this->request->param('id');
		$updata = Db::name('order')->where(['id'=>$oid])->update(['status'=>15,'pro_time'=>time()]);
		return $this->ajaxSuccess('处理成功，待用户确定！', url('project/orderpro'));
	}

	//轮播管理
	public function carousel() {
		$list = Db::name('carousel')->order('id')->select();
		$this->assign('list',$list);
		return $this->fetch('carousel');
	}
	//删除轮播
	public function delcarousel(){
		$id = $this->request->param('id');
		Db::name('carousel')->delete($id);
		$this->redirect('Project/carousel');
	}
	//添加轮播
	public function addcarousel(){
		$img_id = $this->request->param('img_id');
		if($img_id == 0){
			$this->redirect('Project/carousel');
		}else{
			$img = Db::name('carousel')->where(['img_id'=>$img_id])->find();
			if(empty($img)){
				Db::name('carousel')->insert(['img_id'=>$img_id]);
			}
			$this->redirect('Project/carousel');
		}
	}

	public function section(){
		$listmodel = new Sectionlistmodel();
		$sectionlist = $listmodel->sectionlist();
		$this->assign('list',$sectionlist);
		return $this->fetch();
	}
	public function addsection(){
		if($this->request->isAjax()){
			$name = $this->request->param('name');
			$listmodel = new Sectionlistmodel();
			$add = $listmodel->addsection($name);
			if ($add) {
				return $this->ajaxSuccess('新增成功！', url('project/section'));
			} else {
				return $this->ajaxError('已有该病种，请重试!');
			}
		}else{
			return $this->fetch();
		}
	}
	public function addsectionchild(){
		$id = $this->request->param('id');
		$listmodel = new Sectionlistmodel();
		if($this->request->isAjax()){
			$name = $this->request->param('name');
			$add = $listmodel->addsectionchild($name,$id);
			if ($add) {
				return $this->ajaxSuccess('新增成功！', url('project/section'));
			} else {
				return $this->ajaxError('已有该病种，请重试!');
			}
		}else{
			$name = $listmodel->getsectionname($id);
			$this->assign('name',$name);
			$this->assign('id',$id);
			return $this->fetch();
		}
	}
	public function delsection(){
		$id = $this->request->param('id');
		$listmodel = new Sectionlistmodel();
		$del = $listmodel->delsection($id);
		if ($del) {
			return $this->ajaxSuccess('删除成功！', url('project/section'));
		} else {
			return $this->ajaxError('删除失败，请重试!');
		}
	}
	public function delsectionchild(){
		$id = $this->request->param('id');
		$listmodel = new Sectionlistmodel();
		$del = $listmodel->delsection($id);
		if ($del) {
			return $this->ajaxSuccess('删除成功！', url('project/section'));
		} else {
			return $this->ajaxError('删除失败，请重试!');
		}
	}
	public function checksection(){
		$id = $this->request->param('id');
		$listmodel = new Sectionlistmodel();
		$childlist = $listmodel->childlist($id);
		$name = $listmodel->getsectionname($id);
		$this->assign('name',$name);
		$this->assign('id',$id);
		$this->assign('list',$childlist);
		return $this->fetch();
	}
	public function editsection(){
		$id = $this->request->param('id');
		$listmodel = new Sectionlistmodel();
		if($this->request->isAjax()){
			$name = $this->request->param('name');
			$edit = $listmodel->editsection($name,$id);
			if ($edit) {
				return $this->ajaxSuccess('修改成功！', url('project/section'));
			} else {
				return $this->ajaxError('修改失败，请重试!');
			}
		}else{
			$name = $listmodel->getsectionname($id);
			$this->assign('name',$name);
			$this->assign('id',$id);
			return $this->fetch();
		}
	}

}
