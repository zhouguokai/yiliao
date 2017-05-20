<?php
namespace app\home\controller;

use app\common\controller\Tplus;

use think\Session;

class Index extends Tplus
{
    public function index(){
        $openid = Session::get('wechat_openid');
        $name = Session::get('wechat_nickname');
        $imgurl = Session::get('wechat_img_url');

        var_dump($openid);
        var_dump($name);
        var_dump($imgurl);
        exit;
    }

    public function test(){
        plugs('WechatLogin');
//        var_dump('qq');
    }



    public function get_ip_place(){
        $getIp=$_SERVER["REMOTE_ADDR"];
        echo 'IP:',$getIp;
        echo '<br/>';
        $content = file_get_contents("http://api.map.baidu.com/location/ip?ak=7IZ6fgGEGohCrRKUE9Rj4TSQ&ip={$getIp}&coor=bd09ll");
        $json = json_decode($content);

        echo 'log:',$json->{'content'}->{'point'}->{'x'};//按层级关系提取经度数据
        echo '<br/>';
        echo 'lat:',$json->{'content'}->{'point'}->{'y'};//按层级关系提取纬度数据
        echo '<br/>';
        return $json->{'content'}->{'address'};//按层级关系提取address数据
    }
    
    public function getCity($ip = '') {
        if($ip == ''){
            $url = "http://int.dpool.sina.com.cn/iplookup/iplookup.php?format=json";
            $ip=json_decode(file_get_contents($url),true);
            $data = $ip;
        }else{
            $url="http://ip.taobao.com/service/getIpInfo.php?ip=".$ip;
            $ip=json_decode(file_get_contents($url));
            if((string)$ip->code=='1'){
                return false;
            }
            $data = (array)$ip->data;
        }
        dump($data);
    }


}
