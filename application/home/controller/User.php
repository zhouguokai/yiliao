<?php
namespace app\home\controller;

use app\common\controller\Tplus;
use app\system\model\Vip;
use think\Db;
use think\Session;
use app\system\model\Vip as Vipmodel;

class User extends Tplus
{
    protected function _initialize(){
        parent::_initialize();
        Session::set('vid',1);
        $vid = Session::get('vid');

        header('Access-Control-Allow-Origin:*');
        if(empty($vip_id)){
            //action('User/login');
        }
    }
    public function test(){
        echo "hello world!";
    }
    public function checklogin(){
        $vid = $this->request->param('id');
        $token = $this->request->param('token');
        //$vid = 1;
        if(!empty($vid)){
            $vinfo = Db::name('vip')->where(['id'=>$vid])->find();
            if(empty($vinfo)){
                echo json_encode([['code'=>1001,'msg'=>'错误的用户ID！','data'=>'']]);
                exit;
            }else{
                return $vid;
            }
        }
        if(!empty($token)){
            $vid =  Vip::where(['token'=>$token])->value('id');
            if(empty($vid)){
                echo json_encode([['code'=>1002,'msg'=>'错误的Token值！','data'=>'']]);
                exit;
            }else{
                return $vid;
            }
        }
        echo json_encode([['code'=>1000,'msg'=>'请先登录！','data'=>'']]);
        exit;

    }

    //APP登录接口
    public function APPlogin(){
        Session::clear();
        $token = $this->request->param('token');
        $vip = new Vipmodel();
        $check = $vip->checktoken($token);
        $re = array();
        if($check['check'] == 0){
            $re['code'] = 1000;
            $re['msg'] = '无效token值';
            $re['data'] = '';
        }elseif($check['check'] == -1){
            $re['code'] = 1001;
            $re['msg'] = 'token已过期，请重新登录';
            $re['data'] = '';
        }else{
            $re['code'] = 0;
            $re['msg'] = '登录成功';
            $re['data']['token'] = $check['info']['token'];
            $re['data']['id'] = $check['info']['id'];
            Session::set('vid',$check['info']['id']);
        }
        return json($re);
    }
    //用户登录
    public function login(){
        Session::clear();
        $data = $this->request->param();
        if(empty($data['phone']) || empty($data['password'])){
            return  json(['code'=>1001,'msg'=>'参数错误！','data'=>'']);
        }
        $vip = new Vipmodel();
        $login = $vip->login($data);
//        dump($login);exit;
//        echo $login['token'];exit;
        $re=array();
        if(empty($login)){
            $re['code'] = 1000;
            $re['msg'] = '手机号或密码不正确，请重新输入';
            $re['data'] = '';
        }else{
            $re['code'] = 0;
            $re['msg'] = '登录成功';
            $re['data']['token'] = $login['token'];
            $re['data']['id'] = $login['id'];
            Session::set('vid',$login['id']);
        }

        return json($re);
    }
    //退出登录
    public function logout(){
        Session::clear();
        $vid = $this->checklogin();
        Db::name('vip')->where(['id'=>$vid])->update(['token'=>'logout']);
        $re['code'] = 0;
        $re['msg'] = '退出成功';
        $re['data'] = '';
        return json($re);
    }
    //用户注册
    public function register(){
        $data = $this->request->param();
        $checksms = 1;
        Session::clear();
        if($checksms == 1){
            $vip = new Vipmodel();
            $userinfo = $vip->register($data);
            if(!$userinfo){
                return json(['code'=>1001,'msg'=>'该号码已注册','data'=>'']);
            }
            Session::set('vid',$userinfo['id']);
            $re = array(
                'code'=>0,
                'msg'=>'注册成功',
                'data'=>['token'=>$userinfo['token'],'id'=>$userinfo['id']],
            );
            return json($re);
        }else{
            return json(['code'=>1000,'msg'=>$checksms,'data'=>'']);
        }
    }
    //忘记密码
    public function setpassword(){
        $data = $this->request->param();
        $vip = new Vipmodel();
        $set = $vip->setpassword($data);
        $re = array();
        if($set){
            $re['code'] = 0;
            $re['msg']='新密码设置成功，请牢记';
            $re['data']='';
        }else{
            $re['code'] = 1000;
            $re['msg']='设置失败，请稍后重试！';
            $re['data']='';
        }
        return json($re);
    }
    //验证码验证     控制器内调用
    public function checksms($data){
        //$sms = Db::name('sms')->where(['phone'=>$data['phone'],'code'=>$data['code']])->order('time , desc')->find();
        $codeArr = Session::get('code');
        if(empty($codeArr)){
            $re = '请获取验证码！';
        }elseif((time()-$codeArr['time'])>300){
            $re = '验证码已过期！';
        }elseif($codeArr['code'] !== $data['code']){
            $re = '验证码不正确！';
        }elseif($codeArr['phone'] !== $data['phone']){
            $re = '手机号不正确！';
        }else{
            $re = 1;
        }
        return json($re);
    }
    //发送短信
    /*type  1:注册；2：绑定；3：忘记密码；4：修改资料     */
    public function sendsms(){
        $phone = $this->request->param('phone');
        $type = $this->request->param('type');
        $vip = new Vipmodel();
        $check = $vip->checkphone($phone);
        if($check == 1 && $type == 1){
            return json(['code'=>1000,'msg'=>'该手机号已注册！','data'=>'']);
        }elseif($check == 0 && $type == 2){
            return json(['code'=>1001,'msg'=>'该手机号尚未注册！','data'=>'']);
        }
        $re = $this->sendapi($phone,$type);
        if(empty($re['result'])){
            if(empty($re['reason'])){
                return json(['code'=>1003,'msg'=>'网络错误，请稍后再试！','data'=>'']);
            }else{
                return json(['code'=>1002,'msg'=>$re['reason'],'data'=>'']);
            }
        }else{
            return json(['code'=>0,'msg'=>'验证码已发送到您的手机上，请注意查收！','data'=>['code'=>$re['code'],'phone'=>$phone]]);
        }
    }
    //调用短信发送接口
    public function sendapi($phone,$type){
        $code = rand(100000,999999);
        $url = 'http://v.juhe.cn/sms/send';
        $param1 = [
            'mobile'    =>  $phone,
            'tpl_id'    =>  '28694',
            'tpl_value'    =>  urlencode('#code#='.$code),
            'key'    =>  '3c19febcfafb6b89a5c1bfa6add2838f',
        ];
        $param = http_build_query($param1);
        $result1 = https_request($url.'?'.$param);
        $result = json_decode($result1,true);
        $sms = array(
            'phone'=>$phone,
            'time'=>time(),
            'code'=>$code,
            'content'=>json_encode($param1),
            'type'=>$type,
            'result'=>$result1,
        );
        $codeArr = array(
            'phone'=>$phone,
            'code'=>$code,
            'time'=>time()
        );
        Session::set('code',$codeArr);
        $add = Db::name('sms')->insert($sms);
        $result['code'] = $code;
        return $result;
    }
    //密码验证
    public function checkpw(){
        $vid = $this->checklogin();
        $vip = new Vipmodel();
        $opw = $this->request->param('oldpw');
        $check = $vip->checkpw($opw,$vid);
        if($check){
            return json(['code'=>0,'msg'=>'密码验证通过！','data'=>'']);
        }else{
            return json(['code'=>1000,'msg'=>'原密码不正确！','data'=>'']);
        }
    }
    //修改密码
    public function modifypw(){
        $vid = $this->checklogin();
        $vip = new Vipmodel();
        $npw = $this->request->param('newpw');
        $modify = $vip->modifypw($npw,$vid);
        if($modify){
            return json(['code'=>0,'msg'=>'密码修改成功，请牢记！','data'=>'']);
        }else{
            return json(['code'=>1000,'msg'=>'密码修改失败！','data'=>'']);
        }
    }
    //我的个人信息
    public function personalinfo(){
        $vid = $this->checklogin();
        $info = Db::name('vip')->where(['id'=>$vid])->field('name,phone,img,sex')->find();
        if(empty($info)){
            return json(['code'=>1000,'msg'=>'参数错误！','data'=>'']);
        }else{
            return json(['code'=>0,'msg'=>'个人信息！','data'=>$info]);
        }
    }
    //修改个人资料
    public function changeinfo(){
        $vid = $this->checklogin();
        $data = $this->request->param();


//        $img = $this->request->file('img');
        if(!empty($data['img'])){

            header("Content-type: image/jpeg");
            $path = 'public/uploads/'.date("Ymd").'/';
            if (!file_exists($path)){
                mkdir($path);
            }
            $baseimg = $data['img'];
            $baseimg = substr($baseimg, 23);


            $img = base64_decode($baseimg);
            $all_path = $path.time().rand(1000,9999).'.'.$data['type'];
            $a = file_put_contents($all_path, $img);
            $th_path = substr($all_path,0,38).'_th.'.$data['type'];

            $this->image_png_size_add($all_path,$th_path,300);

            $upload = Db::name('vip')->where(['id'=>$vid])->update(['img_th'=>$th_path]);

            $upload = Db::name('vip')->where(['id'=>$vid])->update(['img'=>$all_path]);


//            $path = 'public/uploads/';
//            $info = $img->move($path);
//            $all_path = $path.date("Ymd").'/'.$info->getFilename();
//            $upload = Db::name('vip')->where(['id'=>$vid])->update(['img'=>$all_path]);


        }
        unset($data['img']);
        unset($data['type']);
        $vip = new Vipmodel();
        $data['update_time'] = time();
        $change = $vip->changeinfo($data,$vid);
        if($change){
            $info = Db::name('vip')->where(['id'=>$vid])->field('name,phone,img,sex,img_th')->find();
            return json(['code'=>0,'msg'=>'修改成功！','data'=>$info]);
        }else{
            return json(['code'=>1000,'msg'=>'修改失败，请重试！','data'=>'']);
        }
    }
    //我的地址列表
    public function myaddress(){
        $vid = $this->checklogin();
        $address = Db::name('address')->where(['vip_id'=>$vid,'status'=>1])->order('isdefault desc')->select();
        $length = count($address);
        if(empty($address)){
            return json(['code'=>0,'msg'=>'未找到相关信息！','data'=>'']);
        }else{
            return json(['code'=>0,'msg'=>'我的地址！','data'=>$address,'length'=>$length]);
        }
    }
    //添加收货、上门地址
    public function addaddress(){
        $data = $this->request->param();
        if(empty($data)){
            return ['code'=>1000,'msg'=>'无效参数！','data'=>''];
        }
        $data['vip_id'] = $this->checklogin();
        if($data['isdefault'] == 1){
            Db::name('address')->where(['vip_id'=>$data['vip_id'],'isdefault'=>1])->update(['isdefault'=>0]);
        }
        unset($data['id']);
        $add = Db::name('address')->insert($data);
        if($add){
            return  json(['code'=>0,'msg'=>'新增成功！','data'=>'']);
        }else{
            return  json(['code'=>1001,'msg'=>'添加失败，请稍后重试！','data'=>'']);
        }
    }
    //删除地址
    public function deladdress(){
        $id = $this->request->param('addressid');
        $info = Db::name('address')->where(['id'=>$id])->find();
        if(empty($info) || $info['vip_id'] != $this->checklogin()){
            return json(['code'=>1000,'msg'=>'无效参数！','data'=>'']);
        }
        $del = Db::name('address')->where(['id'=>$id])->update(['status'=>0]);
        if($del){
            return json(['code'=>0,'msg'=>'删除成功！','data'=>'']);
        }else{
            return json(['code'=>1001,'msg'=>'删除失败，请稍后重试！','data'=>'']);
        }
    }
    //设置默认地址
    public function setdefaultaddress(){
        $vid = $this->checklogin();
        $id = $this->request->param('addressid');
        $info = Db::name('address')->where(['id'=>$id])->find();
        if(empty($info) || $info['vip_id'] != $vid){
            return json(['code'=>1000,'msg'=>'无效参数！','data'=>'']);
        }
        $set1 = Db::name('address')->where(['vip_id'=>$vid])->update(['isdefault'=>0]);
        $set = Db::name('address')->where(['id'=>$id])->update(['isdefault'=>1]);
        if($set){
            return json(['code'=>0,'msg'=>'设置成功！','data'=>'']);
        }else{
            return json(['code'=>1001,'msg'=>'设置失败，请稍后重试！','data'=>'']);
        }
    }
    //地址详情
    public function addressinfo(){
        $vid = $this->checklogin();
        $aid = $this->request->param('aid');
        $info = Db::name('address')->where(['vip_id'=>$vid,'id'=>$aid,'status'=>1])->field('*')->find();
        if(empty($info)){
            return json(['code'=>1001,'msg'=>'参数错误！','data'=>'']);
        }else{
            return json(['code'=>0,'msg'=>'地址详情！','data'=>$info]);
        }
    }
    //修改地址
    public function modifyaddress(){
        $vid = $this->checklogin();
        $data = $this->request->param();
        $aid = $data['aid'];
        unset($data['aid']);
        unset($data['id']);
        if($data['isdefault'] == 1){
            Db::name('address')->where(['vip_id'=>$vid,'isdefault'=>1])->update(['isdefault'=>0]);
        }
        $modify = Db::name('address')->where(['vip_id'=>$vid,'id'=>$aid,'status'=>1])->update($data);
        if(empty($modify)){
            return json(['code'=>1001,'msg'=>'参数错误！','data'=>'']);
        }else{
            return json(['code'=>0,'msg'=>'修改成功！','data'=>'']);
        }
    }
    //修改头像
    public function changimg(){
        $vid = $this->checklogin();
        $img = $this->request->file('img');
        if(empty($img)){
            return json(['code'=>1000,'msg'=>'无效参数！','data'=>'']);
        }
        $path = 'public/uploads/';
        $info = $img->move($path);
        $all_path = $path.date("Ymd").'/'.$info->getFilename();
        //$all_path = $path.$info->getSaveName();
        $upload = Db::name('vip')->where(['id'=>$vid])->update(['img'=>$all_path]);
        if($upload){
            return json(['code'=>0,'msg'=>'设置成功！','data'=>$all_path]);
        }else{
            return json(['code'=>1001,'msg'=>'设置失败，请稍后重试！','data'=>'']);
        }
    }
    public function testimg(){
        $file = request()->file('image');
        // 移动到框架应用根目录/public/uploads/ 目录下
        $path = 'public/uploads/';
        $info = $file->validate(['size'=>30720,'ext'=>'jpg,png,gif'])->move($path);
        if($info){
            // 成功上传后 获取上传信息
            // 输出 jpg
            echo $info->getExtension();
            // 输出 20160820/42a79759f284b767dfcb2a0197904287.jpg
            echo $info->getSaveName();
            // 输出 42a79759f284b767dfcb2a0197904287.jpg
            echo $info->getFilename();
        }else{
            // 上传失败获取错误信息
            echo $file->getError();
        }
    }


    public function test1(){
        $str = '{"apps_id":"d349daadc9b14f44aab22eb4fa486f7f","out_trade_no":"14932178075376","trade_no":"5bde8ae8916945f6afb2630c97843fbd","mer_id":"17041516205572359","total_fee":"1","subject":"\u9ec4\u91d1\u4f1a\u5458","body":"","pay_name":"\u652f\u4ed8\u5b9d\u516c\u4';
        echo strlen($str);
    }
    public function tupianyasuo(){
        header("Content-type: image/jpeg");
        $path = 'public/uploads/';
        $file = "public/uploads/20170419/3.jpg";

        $this->image_png_size_add($file,$file);
    }

    /**
     * desription 压缩图片
     * @param sting $imgsrc 图片路径
     * @param string $imgdst 压缩后保存路径
     */
    public function image_png_size_add($imgsrc , $imgdst , $maxsize = 200){
        ini_set('gd.jpeg_ignore_warning', 1);
        list($width,$height,$type)=getimagesize($imgsrc);
        if($width < $maxsize && $height < $maxsize){
            return;
        }
        if($width >= $height){
            $new_width = $maxsize;
            $new_height = $height * $maxsize / $width;
        }else{
            $new_height = $maxsize;
            $new_width = $width * $maxsize / $height;
        }
//        $new_width = ($width>600?600:$width)*$percent;
//        $new_height =($height>600?600:$height)*$percent;
        switch($type){
            case 1:
                $giftype=$this->check_gifcartoon($imgsrc);
                if($giftype){
                    header('Content-Type:image/gif');
                    $image_wp=imagecreatetruecolor($new_width, $new_height);
                    $image = imagecreatefromgif($imgsrc);
                    imagecopyresampled($image_wp, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
                    imagejpeg($image_wp, $imgdst,75);
                    imagedestroy($image_wp);
                }
                break;
            case 2:
                header('Content-Type:image/jpeg');
                $image_wp=imagecreatetruecolor($new_width, $new_height);
                $image = imagecreatefromjpeg($imgsrc);
                imagecopyresampled($image_wp, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
                imagejpeg($image_wp, $imgdst,75);
                imagedestroy($image_wp);
                break;
            case 3:
                header('Content-Type:image/png');
                $image_wp=imagecreatetruecolor($new_width, $new_height);
                $image = imagecreatefrompng($imgsrc);
                imagecopyresampled($image_wp, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
                imagejpeg($image_wp, $imgdst,75);
                imagedestroy($image_wp);
                break;
        }
    }
    /**
     * desription 判断是否gif动画
     * @param sting $image_file图片路径
     * @return boolean t 是 f 否
     */
    public function check_gifcartoon($image_file){
        $fp = fopen($image_file,'rb');
        $image_head = fread($fp,1024);
        fclose($fp);
        return preg_match("/".chr(0x21).chr(0xff).chr(0x0b).'NETSCAPE2.0'."/",$image_head)?false:true;
    }

    public function minganci(){
        $filter_word = ['金币','专业','收','电话','手机','号码'];
        $str = '收金币';
        for ($i = 0; $i < count($filter_word); $i++) {//应用For循环语句对敏感词进行判断
            if (preg_match("/" . trim($filter_word[$i]) . "/i", $str)) {//应用正则表达式，判断传递的留言信息中是否含有敏感词
                echo "留言信息中包含敏感词:".$filter_word[$i];
            }
        }
    }


}
