<?php
namespace app\pc\controller;

use app\common\controller\Tplus;

use think\Session;

class Shopcar extends Tplus
{
    public function index(){
        return $this->fetch('shop_cart');
    }

    public function address(){
        return $this->fetch();
    }

    public function payment(){
    	return $this->fetch();
    }

}
