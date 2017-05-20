<?php
namespace app\home\controller;

use app\common\controller\Tplus;
use think\Session;
use app\system\model\Unit as Unitmodel;

class Hospital extends Tplus
{
    protected function _initialize(){
        parent::_initialize();
        $vip_id = Session::get('vip_id');
        if(empty($vip_id)){
            //action('User/login');
        }
    }
    public function test(){
        echo "hello world!";
    }
    public function hlist(){
        $unit = new Unitmodel();
        $type = 1;
        $pagesize = 5;

        $list = $unit->getlist($type);
        $page = $list->render();
        $re = array();
        $re['list'] = $list;
        $re['page'] = $page;
        return $re;
    }


}
