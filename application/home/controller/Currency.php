<?php
namespace app\home\controller;

use app\common\controller\Tplus;
use think\Db;
use think\Session;
//use app\system\model\Vip as Vipmodel;

class Currency extends Tplus
{
    #轮播图接口  catid：关联分类ID
    public function banner(){
        if($this->request->param('id')){
            $id = $this->request->param('id');
            $data = Db::name('focus_img')->where(['catid'=>$id,'status'=>1])->select();
        }else{
            $data = Db::name('focus_img')->select();
        }
        foreach($data as $key=>&$value){
            $dataPost = Db::name('picture')->where(['id'=>$value['pic_id']])->find();
            $dataGet = Db::name('focus_cat')->where(['id'=>$value['catid']])->find();
            $value['catid'] = $dataGet['title'];
            $value['pic_id'] = SERVER_NAME.$dataPost['path'];
        }
        $number = count($data);
        if($data){
            return json(['info'=>$data,'number'=>$number,'code'=>0,'status'=>1]);
        }else{
            return json(['info'=>'','code'=>1000,'msg'=>'未找到相关信息！','status'=>0]);
        }
    }


    #病种一级分类接口   id:所属医院ID type 1为医院 2为门诊
    public function index(){
        $id = $this->request->param();
        if(!empty($id)){
            $map = [
                'belong_id'=>$id['id'],
                'belong' =>$id['type'],
                'status'=>1
            ];
            $data = Db::name('section')->where($map)->select();
            $father = [];
            foreach($data as $key=>&$value){
                $father[$key] = Db::name('section_list')->where(['id'=>$value['father_id']])->find();
//                $value['father_id'] = $father[$key]['name'];
            }
//            print_r($father);exit;
            $number = count($father);
            if($father){
                $father = $this->array_unique_fb($father);
                return json(['info'=>$father,'number'=>$number,'code'=>0,'status'=>'1']);
            }else{
                return json(['info'=>'','code'=>1000,'msg'=>'未找到相关信息！','status'=>'0']);
            }
        }else{
            return json(['info'=>'','code'=>1000,'msg'=>'未传值！','status'=>'0']);
        }
    }

    #通过ID获取病种详情 oneId：为医院ID  status：1查询二级分类  2为查询二级分类详情 id：status为1时 id为医院ID status为2时 id为二级分类ID
    public function details(){
        $id = $this->request->param();
        if(!empty($id)){
            if($id['status'] == 1){
                $map = [
                    'father_id'=>$id["oneId"],
                    'belong_id'=>$id['id'],
                    'status'=>1
                ];
                $data = Db::name('section')->where($map)->select();
                foreach($data as $key=>&$value){
                    unset($value['child_id']);
                    unset($value['belong']);
                    unset($value['summary']);
                    unset($value['img_id']);
                    unset($value['price']);
                    unset($value['hospitalization']);
                    unset($value['price_hospitalization']);
                    unset($value['status']);
                }
//                print_r($data);exit;
            }else{
                $map = [
                    'id'=>$id["id"],
                    'status'=>1
                ];
                $data = Db::name('section')->where($map)->find();
                $db = Db::name('picture')->where('id',$data['img_id'])->find();
                $data['img_id'] = SERVER_NAME.$db['path'];
                if($data['belong'] == 1){
                    $data['belong'] = '医院';
                }else{
                    $data['belong'] = '门诊';
                }
                unset($data['child_id']);
//                print_r($data);exit;
            }
            $number = count($data);
            if($data){
                return json(['info'=>$data,'number'=>$number,'code'=>0,'status'=>'1']);
            }else{
                return json(['info'=>'','code'=>1000,'msg'=>'未找到相关信息！','status'=>'0']);
            }
        }else{
            return json(['info'=>'','code'=>1000,'msg'=>'未传值！','status'=>'0']);
        }
    }

    #药店侧边栏一级分类接口 id：药店ID
    public function drugs(){
        $id = $this->request->param();
        if($id){
            $data = [];
            $drugs = Db::name('drugs')->where(['belong_id'=>$id['id'],'status'=>1])->select();
            foreach($drugs as $key=>&$value){
                $data[$key] = Db::name('drugclass')->where('id',$value['class_id'])->find();
            }
//            print_r($data);exit;
            $number = count($data);
            if($data){
                $data = $this->array_unique_fb($data);
                return json(['info'=>$data,'number'=>$number,'code'=>0,'status'=>'1']);
            }else{
                return json(['info'=>'','code'=>1000,'msg'=>'未找到相关信息！','status'=>'0']);
            }
        }else{
            return json(['info'=>'','code'=>1000,'msg'=>'未传值！','status'=>'0']);
        }
    }

    //二维数组去掉重复值,并保留键值
    function array_unique_fb($array2D){
//        var_dump($array2D);exit;
        foreach($array2D as $k=>$v){
            $v=join(',',$v); //降维,也可以用implode,将一维数组转换为用逗号连接的字符串
            $temp[$k]=$v;
        }
        $temp=array_unique($temp); //去掉重复的字符串,也就是重复的一维数组
        foreach ($temp as $k => $v){
            $array=explode(',',$v); //再将拆开的数组重新组装
            //下面的索引根据自己的情况进行修改即可
            $temp2[$k]['id'] =$array[0];
            $temp2[$k]['name'] =$array[1];
        }
        return $temp2;
    }

    #药店侧边栏二级分类接口 type：1为查询二级分类 2为查询二级分类详情 id：当type为1时id为一级分类ID，type为2时id为二级分类ID
    public function top(){
        $id = $this->request->param();
        if($id){
            if($id['type'] == 1){
                $data = Db::name('drugs')->where(['class_id'=>$id['id'],'status'=>1])->select();
                foreach($data as &$value){
                    unset($value['summary']);
                    unset($value['price']);
                    unset($value['sales']);
                    unset($value['stock']);
                    unset($value['status']);
                    $db = Db::name('picture')->where('id',$value['img_id'])->find();
                    $value['img_id'] = SERVER_NAME.$db['path'];
                }
            }else{
                $data = Db::name('drugs')->where(['id'=>$id['id'],'status'=>1])->find();
                $db = Db::name('picture')->where('id',$data['img_id'])->find();
                $data['img_id'] = SERVER_NAME.$db['path'];
            }
            $number = count($data);
            if($data){
                return json(['info'=>$data,'number'=>$number,'code'=>0,'status'=>'1']);
            }else{
                return json(['info'=>'','code'=>1000,'msg'=>'未找到相关信息！','status'=>'0']);
            }
        }else{
            return json(['info'=>'','code'=>1000,'msg'=>'未传值！','status'=>'0']);
        }
    }

    #医院及门诊列表接口 type 1为医院 2为门诊 3为药店
    public function merge(){
        $type = $this->request->param();
        if($type){
            $ma = [
                'type'=>$type['type'],
                'status'=>1
            ];
            $db = Db::name('unit')->where($ma)->select();
            foreach($db as $key=>&$value){
                if($value['type'] == 1){
                    $value['type'] = '医院';
                }elseif($value['type'] == 2){
                    $value['type'] = '门诊';
                }else{
                    $value['type'] = '药店';
                }
                $img = Db::name('picture')->where('id',$value['img_id'])->find();
                $value['img_id'] = SERVER_NAME.$img['path'];
                $value['province'] = Db::name('district')->where('id',$value['province'])->find()['name'];
                $value['city'] = Db::name('district')->where('id',$value['city'])->find()['name'];
                $value['district'] = Db::name('district')->where('id',$value['district'])->find()['name'];
                $value['address'] = $value['province'].$value['city'].$value['district'].$value['address'];
                unset($value['province']);
                unset($value['city']);
                unset($value['district']);
                unset($value['uid']);
            }
            $number = count($db);
            if($db){
                return json(['info'=>$db,'number'=>$number,'code'=>0,'status'=>'1']);
            }else{
                return json(['info'=>'','code'=>1000,'msg'=>'未找到相关信息！','status'=>'0']);
            }
        }else{
            return json(['info'=>'','code'=>1000,'msg'=>'未传值！','status'=>'0']);
        }
    }

    #评价列表 type：1为医院 2为门诊 3为药店 id：药店、门诊、医院的ID
    public function comment(){
        $type = $this->request->param();
        if($type){
            $map = [
                'target_id'=>$type['id'],
                'status'=>1
            ];
            $data = Db::name('comment')->where($map)->select();
            foreach($data as $key=>&$value){
                $value['time'] = date('Y-m-d H:i',$value['time']);
                if($value['target'] == 1){
                    $value['target'] = '医院';
                }elseif($value['target'] == 2){
                    $value['target'] = '门诊';
                }else{
                    $value['target'] = '药店';
                }
                $value['target_id'] = Db::name('unit')->where('id',$value['target_id'])->find()['name'];
                $value['vip_id'] = Db::name('vip')->where('id',$value['vip_id'])->find()['name'];
            }
            $number = count($data);
            if($data){
                return json(['info'=>$data,'number'=>$number,'code'=>0,'status'=>'1']);
            }else{
                return json(['info'=>'','code'=>1000,'msg'=>'未找到相关信息！','status'=>'0']);
            }
        }else{
            return json(['info'=>'','code'=>1000,'msg'=>'未传值！','status'=>'0']);
        }
    }

    #医院门诊详情 id ：医院或门诊的id
    public function detailsadd(){
        $id = $this->request->param('id');
        if($id){
            $map = [
                'id'=>$id,
                'status'=>1
            ];
            $data = Db::name('unit')->where($map)->find();
            if($data['province']){
                $data['province'] = $this->district($data['province']);
                $data['city'] = $this->district($data['city']);
                $data['district'] = $this->district($data['district']);
                $data['address'] = $data['province'].$data['city'].$data['district'].$data['address'];
                unset($data['province']);
                unset($data['city']);
                unset($data['district']);
            }
            if($data['type'] == 1){
                $data['type'] = '医院';
            }elseif($data['type'] == 2){
                $data['type'] = '门诊';
            }else{
                $data['type'] = '药店';
            }
            unset($data['uid']);
            $db = Db::name('picture')->where('id',$data['img_id'])->find();
            $data['img_id'] = SERVER_NAME.$db['path'];
//            var_dump($data);exit;
            if($data){
                return json(['info'=>$data,'code'=>0,'status'=>'1']);
            }else{
                return json(['info'=>'','code'=>1000,'msg'=>'未找到相关信息！','status'=>'0']);
            }
        }else{
            return json(['info'=>'','code'=>1000,'msg'=>'未传值！','status'=>'0']);
        }
    }

    #通过ID查询地省市名
    public function district($id){
        $map = [
            'id'=>$id,
        ];
        $data = Db::name('district')->where($map)->find()['name'];
        return $data;
    }

}
