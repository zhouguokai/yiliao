<?php
namespace app\pc\controller;

use app\common\controller\Tplus;

use think\Session;
use think\Db;
class Index extends Tplus
{
    public function index(){
        $list = Db::name('unit')->where('type',1)->select();
        $this->assign('list',$list);
        return $this->fetch();
    }
    public function menzhen(){
        $list = Db::name('section_list')->select();
        $this->assign('list',$list);

        $l_child = Db::view('section','id,father_id,name')
                ->view('section_list','id','section.father_id=section_list.id')
                ->select();
        $this->assign('l_child',$l_child);
        return $this->fetch();
    }
    public function pharmacy(){
        return $this->fetch();
    }
    public function medicine(){
    	return $this->fetch();
    }
    public function diseases_detail(){
    	return $this->fetch();
    }
}
