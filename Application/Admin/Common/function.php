<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 麦当苗儿 <zuojiazi@vip.qq.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------

/**
 * 后台公共文件
 * 主要定义后台公共函数库
 */

function urldecode_to_array($url) {
	$ret_ar = array();

	if (($pos = strpos($url, '?')) !== false)		// parse only what is after the ?
		$url = substr($url, $pos + 1);
	if (substr($url, 0, 1) == '&')					// if leading with an amp, skip it
		$url = substr($url, 1);

	$elems_ar = explode('&', $url);					// get all variables
	for ($i = 0; $i < count($elems_ar); $i++) {
		list($key, $val) = explode('=', $elems_ar[$i]); // split variable name from value
		$ret_ar[urldecode($key)] = urldecode($val);		// store to indexed array
	}

	return $ret_ar;
}

 function curlHttpPost($url, $pstr) {
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
	curl_setopt($ch, CURLOPT_TIMEOUT, 5);
	//curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $pstr);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	//curl_setopt($ch, CURLOPT_USERAGENT, 'TEST PHP5 Client 1.1 (curl) ' . phpversion());
	$result = curl_exec($ch);
	curl_close($ch);
	return $result;
}

function get_by_curl($url, $data, $debug = false, $decode_to_array = true) {
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_URL, $url . '?' . http_build_query($data));
	$ret = curl_exec($ch);

	if (true == $debug) {
		if($ret === false){
			echo 'ERROR<br/>' . curl_error($ch) . '<br/>---------------------<br/>';
		}

		$info = curl_getinfo($ch);  //能够在cURL执行后获取这一请求的有关信息
		//var_dump($info);
	}

	curl_close($ch);
	if (!$ret) {
		return false;
	}
	if ($decode_to_array)
		return urldecode_to_array($ret);
	else
		return $ret;
}

function simpleget_by_curl($url, $debug = false, $decode_to_array = true) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_URL, $url);
    $ret = curl_exec($ch);

    if (true == $debug) {
        if($ret === false){
            echo 'ERROR<br/>' . curl_error($ch) . '<br/>---------------------<br/>';
        }

        $info = curl_getinfo($ch);  //能够在cURL执行后获取这一请求的有关信息
        //var_dump($info);
    }

    curl_close($ch);
    if (!$ret) {
        return false;
    }
    if ($decode_to_array)
        return urldecode_to_array($ret);
    else
        return $ret;
}

/* 解析列表定义规则*/

function get_list_field($data, $grid,$model){

	// 获取当前字段数据
    foreach($grid['field'] as $field){
        $array  =   explode('|',$field);
        $temp  =	$data[$array[0]];
        // 函数支持
        if(isset($array[1])){
            $temp = call_user_func($array[1], $temp);
        }
        $data2[$array[0]]    =   $temp;
    }
    if(!empty($grid['format'])){
        $value  =   preg_replace_callback('/\[([a-z_]+)\]/', function($match) use($data2){return $data2[$match[1]];}, $grid['format']);
    }else{
        $value  =   implode(' ',$data2);
    }

	// 链接支持
	if(!empty($grid['href'])){
		$links  =   explode(',',$grid['href']);
        foreach($links as $link){
            $array  =   explode('|',$link);
            $href   =   $array[0];
            if(preg_match('/^\[([a-z_]+)\]$/',$href,$matches)){
                $val[]  =   $data2[$matches[1]];
            }else{
                $show   =   isset($array[1])?$array[1]:$value;
                // 替换系统特殊字符串
                $href	=	str_replace(
                    array('[DELETE]','[EDIT]','[MODEL]'),
                    array('del?ids=[id]&model=[MODEL]','edit?id=[id]&model=[MODEL]',$model['id']),
                    $href);

                // 替换数据变量
                $href	=	preg_replace_callback('/\[([a-z_]+)\]/', function($match) use($data){return $data[$match[1]];}, $href);

                $val[]	=	'<a href="'.U($href).'">'.$show.'</a>';
            }
        }
        $value  =   implode(' ',$val);
	}
    return $value;
}

// 获取模型名称
function get_model_by_id($id){
    return $model = M('Model')->getFieldById($id,'title');
}

// 获取属性类型信息
function get_attribute_type($type=''){
    // TODO 可以加入系统配置
    static $_type = array(
        'num'       =>  array('数字','int(10) UNSIGNED NOT NULL'),
        'string'    =>  array('字符串','varchar(255) NOT NULL'),
        'textarea'  =>  array('文本框','text NOT NULL'),
        'datetime'  =>  array('时间','int(10) NOT NULL'),
        'bool'      =>  array('布尔','tinyint(2) NOT NULL'),
        'select'    =>  array('枚举','char(50) NOT NULL'),
    	'radio'		=>	array('单选','char(10) NOT NULL'),
    	'checkbox'	=>	array('多选','varchar(100) NOT NULL'),
    	'editor'    =>  array('编辑器','text NOT NULL'),
    	'picture'   =>  array('上传图片','int(10) UNSIGNED NOT NULL'),
    	'file'    	=>  array('上传附件','int(10) UNSIGNED NOT NULL'),
    );
    return $type?$_type[$type][0]:$_type;
}

/**
 * 获取对应状态的文字信息
 * @param int $status
 * @return string 状态文字 ，false 未获取到
 * @author huajie <banhuajie@163.com>
 */
function get_status_title($status = null){
    if(!isset($status)){
        return false;
    }
    switch ($status){
        case -1 : return    '已删除';   break;
        case 0  : return    '禁用';     break;
        case 1  : return    '正常';     break;
        case 2  : return    '待审核';   break;
        default : return    false;      break;
    }
}

// 获取数据的状态操作
function show_status_op($status) {
    switch ($status){
        case 0  : return    '启用';     break;
        case 1  : return    '禁用';     break;
        case 2  : return    '审核';		break;
        default : return    false;      break;
    }
}

/**
 * 获取文档的类型文字
 * @param string $type
 * @return string 状态文字 ，false 未获取到
 * @author huajie <banhuajie@163.com>
 */
function get_document_type($type = null){
    if(!isset($type)){
        return false;
    }
    switch ($type){
        case 1  : return    '目录'; break;
        case 2  : return    '主题'; break;
        case 3  : return    '段落'; break;
        default : return    false;  break;
    }
}

/**
 * 获取配置的类型
 * @param string $type 配置类型
 * @return string
 */
function get_config_type($type=0){
    $list = C('CONFIG_TYPE_LIST');
    return $list[$type];
}

/**
 * 获取配置的分组
 * @param string $group 配置分组
 * @return string
 */
function get_config_group($group=0){
    $list = C('CONFIG_GROUP_LIST');
    return $group?$list[$group]:'';
}

/**
 * select返回的数组进行整数映射转换
 *
 * @param array $map  映射关系二维数组  array(
 *                                          '字段名1'=>array(映射关系数组),
 *                                          '字段名2'=>array(映射关系数组),
 *                                           ......
 *                                       )
 * @author 朱亚杰 <zhuyajie@topthink.net>
 * @return array
 *
 *  array(
 *      array('id'=>1,'title'=>'标题','status'=>'1','status_text'=>'正常')
 *      ....
 *  )
 *
 */
function int_to_string(&$data,$map=array('status'=>array(1=>'正常',-1=>'删除',0=>'禁用',2=>'未审核',3=>'草稿'))) {
    if($data === false || $data === null ){
        return $data;
    }
    $data = (array)$data;
    foreach ($data as $key => $row){
        foreach ($map as $col=>$pair){
            if(isset($row[$col]) && isset($pair[$row[$col]])){
                $data[$key][$col.'_text'] = $pair[$row[$col]];
            }
        }
    }
    return $data;
}

/**
 * 动态扩展左侧菜单,base.html里用到
 * @author 朱亚杰 <zhuyajie@topthink.net>
 */
function extra_menu($extra_menu,&$base_menu){
    foreach ($extra_menu as $key=>$group){
        if( isset($base_menu['child'][$key]) ){
            $base_menu['child'][$key] = array_merge( $base_menu['child'][$key], $group);
        }else{
            $base_menu['child'][$key] = $group;
        }
    }
}

/**
 * 获取参数的所有父级分类
 * @param int $cid 分类id
 * @return array 参数分类和父类的信息集合
 * @author huajie <banhuajie@163.com>
 */
function get_parent_category($cid){
    if(empty($cid)){
        return false;
    }
    $cates  =   M('Category')->where(array('status'=>1))->field('id,title,pid')->order('sort')->select();
    $child  =   get_category($cid);	//获取参数分类的信息
    $pid    =   $child['pid'];
    $temp   =   array();
    $res[]  =   $child;
    while(true){
        foreach ($cates as $key=>$cate){
            if($cate['id'] == $pid){
                $pid = $cate['pid'];
                array_unshift($res, $cate);	//将父分类插入到数组第一个元素前
            }
        }
        if($pid == 0){
            break;
        }
    }
    return $res;
}

/**
 * 检测验证码
 * @param  integer $id 验证码ID
 * @return boolean     检测结果
 * @author 麦当苗儿 <zuojiazi@vip.qq.com>
 */
function check_verify($code, $id = 1){
    $verify = new \Think\Verify();
    return $verify->check($code, $id);
}

/**
 * 获取当前分类的文档类型
 * @param int $id
 * @return array 文档类型数组
 * @author huajie <banhuajie@163.com>
 */
function get_type_bycate($id = null){
    if(empty($id)){
        return false;
    }
    $type_list  =   C('DOCUMENT_MODEL_TYPE');
    $model_type =   M('Category')->getFieldById($id, 'type');
    $model_type =   explode(',', $model_type);
    foreach ($type_list as $key=>$value){
        if(!in_array($key, $model_type)){
            unset($type_list[$key]);
        }
    }
    return $type_list;
}

/**
 * 获取当前文档的分类
 * @param int $id
 * @return array 文档类型数组
 * @author huajie <banhuajie@163.com>
 */
function get_cate($cate_id = null){
    if(empty($cate_id)){
        return false;
    }
    $cate   =   M('Category')->where('id='.$cate_id)->getField('title');
    return $cate;
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

// 获取子文档数目
function get_subdocument_count($id=0){
    return  M('Document')->where('pid='.$id)->count();
}



 // 分析枚举类型字段值 格式 a:名称1,b:名称2
 // 暂时和 parse_config_attr功能相同
 // 但请不要互相使用，后期会调整
function parse_field_attr($string) {
    if(0 === strpos($string,':')){
        // 采用函数定义
        return   eval(substr($string,1).';');
    }
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
 * 获取行为数据
 * @param string $id 行为id
 * @param string $field 需要获取的字段
 * @author huajie <banhuajie@163.com>
 */
function get_action($id = null, $field = null){
	if(empty($id) && !is_numeric($id)){
		return false;
	}
	$list = S('action_list');
	if(empty($list[$id])){
		$map = array('status'=>array('gt', -1), 'id'=>$id);
		$list[$id] = M('Action')->where($map)->field(true)->find();
	}
	return empty($field) ? $list[$id] : $list[$id][$field];
}

/**
 * 根据条件字段获取数据
 * @param mixed $value 条件，可用常量或者数组
 * @param string $condition 条件字段
 * @param string $field 需要返回的字段，不传则返回整个数据
 * @author huajie <banhuajie@163.com>
 */
function get_document_field($value = null, $condition = 'id', $field = null){
	if(empty($value)){
		return false;
	}

	//拼接参数
	$map[$condition] = $value;
	$info = M('Model')->where($map);
	if(empty($field)){
		$info = $info->field(true)->find();
	}else{
		$info = $info->getField($field);
	}
	return $info;
}

/**
 * 获取行为类型
 * @param intger $type 类型
 * @param bool $all 是否返回全部类型
 * @author huajie <banhuajie@163.com>
 */
function get_action_type($type, $all = false){
	$list = array(
		1=>'系统',
		2=>'用户',
	);
	if($all){
		return $list;
	}
	return $list[$type];
}
function dhtmlspecialchars($string) {
	if (is_array($string)) {
		foreach ($string as $key => $val) {
			$string[$key] = dhtmlspecialchars($val);
		}
	} else {
		$string = preg_replace('/&((#(\d{3,5}|x[a-fA-F0-9]{4})|[a-zA-Z][a-z0-9]{2,5});)/', '&\\1', str_replace(array('&', '"', '<', '>', "'"), array('&', '&quot;', '&lt;', '&gt;', '&#039;'), $string));
	}
	return $string;
}

function dhtmlspecialchars_decode($string) {
	if (is_array($string)) {
		foreach ($string as $key => $val) {
			$string[$key] = dhtmlspecialchars($val);
		}
	} else {
		$string = preg_replace('/&((#(\d{3,5}|x[a-fA-F0-9]{4})|[a-zA-Z][a-z0-9]{2,5});)/', '&\\1', str_replace(array('&', '&quot;', '&lt;', '&gt;', '&#039;', '&quot', '&lt', '&gt', '&#039'), array('&', '"', '<', '>', "'", '"', '<', '>', "'"), $string));
	}
	return $string;
}


/**
 * @param $message 日志内容 array or string
 * @param bool $username 账号名称
 * @param int $level 日志等级
 * @param string $filename 文件名
 * @return bool
 */
function log_message($message, $location, $username = false, $level = 0, $filename = 'entry') {
    if ('phperror' == $filename) return false;
    $logPath = C("LOG_PATH");
    $filename = $username ? ($logPath . $username . '_' . date('Y-m-d') . '.log') : ($logPath . $filename . '_' . date('Y-m-d') . '.log');
    $file = fopen($filename, 'ab');
    switch($level) {
        case 0: $lv_str = 'Common';break;
        case 1: $lv_str = 'Notice';break;
        case 2: $lv_str = 'Warning';break;
        case 3: $lv_str = 'Error';break;
        case 4: $lv_str = 'Fatal!';break;
        default: $lv_str = 'Common';
    }

    if (is_array($message)) {
        fwrite($file, $lv_str . ' -> ' . date('Y-m-d H:i:s') . ' -> IP: ' . getIp() .', 账号：' . $username . ', 位置：' . $location . ', 内容：' . print_r($message, true) . " \r\n");
    } else {
        fwrite($file, $lv_str . ' -> ' . date('Y-m-d H:i:s') . ' -> IP: ' . getIp() .', 账号：' . $username . ', 位置：' . $location . ', 内容：' . " {$message} \r\n");
    }
    fclose($file);
    return true;
}

/*
 * 获取Ip
 */
function getIp() {
    $realip = '';

    if (isset($_SERVER)) {
        if (isset($_SERVER["HTTP_X_FORWARDED_FOR"])) {
            $realip = $_SERVER["HTTP_X_FORWARDED_FOR"];
        } elseif (isset($_SERVER["HTTP_CLIENT_IP"])) {
            $realip = $_SERVER["HTTP_CLIENT_IP"];
        } else {
            $realip = $_SERVER['REMOTE_ADDR'];
        }
    } else {
        if (getenv("HTTP_X_FORWARDED_FOR")) {
            $realip = getenv("HTTP_X_FORWARDED_FOR");
        } else if (getenv("HTTP_CLIENT_IP")) {
            $realip = getenv("HTTP_CLIENT_IP");
        } else {
            $realip = getenv("REMOTE_ADDR");
        }
    }
    return $realip;
}

function getTableLength() {
    return 50;
}

function getTableName($oldtable,$str) {
    $str=md5($str);
    $num=crc32($str)%50;
    //var_dump(crc32($str),$num);
    //php取余数bug,不是返回正数
    if($num<0)
    {
        $num=$num+50;
    }
    $num+1;
    return $oldtable.'_'.$num;
}

function OperationLog($operator,$optype,$opcontent,$state=0) {
    $data['oper']=$operator;
    $data['optype']=$optype;
    $data['opcontent']=$opcontent;
    $data['state']=$state;
    $data['optime']=date('Y-m-d H:i:s',time());
    D("oplog")->add($data);
}

function exportExcel($fileName,$expTitle,$expCellName,$expTableData){
    $xlsTitle = iconv('utf-8', 'gb2312', $expTitle);//文件名称
    $cellNum = count($expCellName);
    //$dataNum = count($expTableData);
    vendor("PHPExcel");

    $objPHPExcel = new PHPExcel();
    $cellName = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','AA','AB','AC','AD','AE','AF','AG','AH','AI','AJ','AK','AL','AM','AN','AO','AP','AQ','AR','AS','AT','AU','AV','AW','AX','AY','AZ');

    $objPHPExcel->getActiveSheet(0)->mergeCells('A1:'.$cellName[$cellNum-1].'1');//合并单元格
    $objPHPExcel->setActiveSheetIndex(0)->getStyle('A1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);// 水平居中
    $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A1', $expTitle);
    $styleArray = array(
        'font' => array(
            'bold' => true,
            'color' => array('rgb' => 'FF0000'),
            'name' => 'Verdana'
        ));
    $objPHPExcel->getActiveSheet(0)->getStyle('A1')->applyFromArray($styleArray);
    for($i=0;$i<$cellNum;$i++){
        if (!empty($expCellName[$i][2])) {
            $objPHPExcel->setActiveSheetIndex(0)->getColumnDimension($cellName[$i])->setWidth($expCellName[$i][2]);
        } else {
            $objPHPExcel->setActiveSheetIndex(0)->getColumnDimension($cellName[$i])->setAutoSize(true);
        }
        $objPHPExcel->getActiveSheet(0)->getStyle($cellName[$i].'2')->applyFromArray($styleArray);
        $objPHPExcel->setActiveSheetIndex(0)->getStyle($cellName[$i])->getAlignment()->setWrapText(true);
        $objPHPExcel->setActiveSheetIndex(0)->getStyle($cellName[$i].'2')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);// 垂直居中
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellName[$i].'2', $expCellName[$i][1]);
    }
    // Miscellaneous glyphs, UTF-8
    //var_dump($expTableData);exit;
    $count=0;
    foreach($expTableData as $k=>$value ){
        for($j=0;$j<$cellNum;$j++){
            $objPHPExcel->getActiveSheet(0)->getStyle($cellName[$j].($count+3))->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);// 垂直居中
            if (!empty($expCellName[$j][3]) && $expCellName[$j][3] == "img" && !empty($value[$expCellName[$j][0]])){
//                //写入图片
//                $img=new PHPExcel_Worksheet_Drawing();
//                $img->setPath($value[$expCellName[$j][0]],false);
//                //$img->setPath("./images/00100.jpg");
//                $img->setHeight(400);//写入图片高度
//                $img->setWidth(123);//写入图片宽度
//                $img->setName('Logo');//写入图片在指定格中的X坐标值
//                $img->setDescription('Logo');//写入图片在指定格中的Y坐标值
//                $img->setCoordinates('A2');//设置图片所在表格位置
//                $img->setWorksheet($objPHPExcel->getActiveSheet());//把图片写到当前的表格中

                $objPHPExcel->getActiveSheet(0)->setCellValue($cellName[$j].($count+3), $value[$expCellName[$j][0]]);
                $objPHPExcel->getActiveSheet(0)->getCell($cellName[$j].($count+3))->getHyperlink()->setUrl($value[$expCellName[$j][0]]);
            }else {
                $objPHPExcel->getActiveSheet(0)->setCellValue($cellName[$j].($count+3), $value[$expCellName[$j][0]]);
            }
        }
        $count++;
    }
    //var_dump($expTableData);exit;
    header('pragma:public');
    header('Content-type:application/vnd.ms-excel;charset=utf-8;name="'.$xlsTitle.'.xls"');
    header("Content-Disposition:attachment;filename=$fileName.xls");//attachment新窗口打印inline本窗口打印
    $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
    $objWriter->save('php://output');
    exit;
}