<?php
namespace app\pc\controller;

use app\common\controller\Tplus;

use think\Session;

class Order extends Tplus
{
    public function index(){
        return $this->fetch('my-order');
    }


}
