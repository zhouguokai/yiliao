<?php
/* 后台应用私有函数文件
 * 2016-08-04 10:23:00 [Onion]
 */

function up_title($pid){
	if(!$pid){
		return '无父分类';
	}
	$result=db("Menu")->where(['id'=>$pid])->find();
	if($result){
		return $result['title'];
	}else{
		return '父分类丢失';
	}
}


// 分析枚举类型配置值 格式 a:名称1,b:名称2
function parse_config_attr($string) {
	$array = preg_split('/[,;\r\n]+/', trim($string, ",;\r\n"));
	if(strpos($string,':')){
		$value  =   array();
		foreach ($array as $val) {
			list($k, $v) = explode(':', $val);
			$value[$k]   = $v;
		}
	}else{
		$value  =   $array;
	}
	return $value;
}

/**
 * 获取数据库中的配置列表
 * @return array 配置数组
 */
function config_lists(){
	$map    = array('status' => 1);
	$data   = db('Config')->where($map)->field('type,name,value')->select();

	$config = array();
	if($data && is_array($data)){
		foreach ($data as $value) {
			$config[$value['name']] = parse($value['type'], $value['value']);
		}
	}
	return $config;
}

/**
 * 根据配置类型解析配置
 * @param  integer $type  配置类型
 * @param  string  $value 配置值
 */
function parse($type, $value){
	switch ($type) {
		case 3: //解析数组
			$array = preg_split('/[,;\r\n]+/', trim($value, ",;\r\n"));
			if(strpos($value,':')){
				$value  = array();
				foreach ($array as $val) {
					list($k, $v) = explode(':', $val);
					$value[$k]   = $v;
				}
			}else{
				$value =    $array;
			}
			break;
	}
	return $value;
}