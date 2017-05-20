<?php
namespace app\pc\controller;

use app\common\controller\Tplus;

use think\Session;

class join extends Tplus
{
    public function index(){
        return $this->fetch('index');
    }
    public function shops(){
 		return $this->fetch();
    }
    public function question(){
 		return $this->fetch();
    }
    public function brand(){
 		return $this->fetch();
    }
    public function join_in(){
 		return $this->fetch();
    }
}
