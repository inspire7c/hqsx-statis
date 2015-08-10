<?php
set_time_limit(0);
header("Content-type:text/html;charset=utf-8");
$today = date("Ymd");

//遍历目录下的.log文件
$log_dir_path = 'log';

$log_list = listDir($log_dir_path);

function listDir($dir){
    static $result_array = array();
    if(!is_dir($dir)){
    	echo json_encode(array("code" => 0, 'msg' => $dir."目录不存在"));exit;
    }
    if ($dh = opendir($dir)){
        while (($file = readdir($dh)) !== false){
            if((is_dir($dir."/".$file)) && $file != "." && $file != ".."){
            	if(preg_match_all('/((2[0-4]\d|25[0-5]|[01]?\d\d?)\.){3}(2[0-4]\d|25[0-5]|[01]?\d\d?)/', $file, $matches)){
            		listDir($dir."/".$file."/");
            	}
            }else{
            	if($file!="." && $file!=".."){
            		$file_name = 'access'.date("Ymd").'.log';
            		if(is_file($dir.$file) && $file_name == $file){
            	    		$result_array[] = $dir.$file;
            		}
                }
            }
        }
        closedir($dh);
    }
    return $result_array;
}
/*echo "<pre>";
print_r($log_list);exit;*/
if(empty($log_list)){
	echo json_encode(array("code" => 0, 'msg' => $log_dir_path."下没有符合的日志文件"));exit;
}
/*echo "<pre>";
print_r($log_list);exit;*/
foreach($log_list as $key => $value){
//$file_name = 'access'.$today.'.log';//文件名称
//$file_path = ''.$file_name;//文件路径
$file_path = $value;
if (file_exists($file_path) == false) {
	echo json_encode(array("code" => 0, 'msg' => "文件不存在"));exit;
}
if (is_readable($file_path) == false) {
	echo json_encode(array("code" => 0, 'msg' => "文件不可读"));exit;
}

$fp = fopen($file_path, "r");
if($fp == false){
	echo json_encode(array("code" => 0, 'msg' => "文件打开失败"));exit;
 }
if(filesize($file_path) == false){
	echo json_encode(array("code" => 0, 'msg' => "文件内容为空"));exit;
}
//$log_data = fread($fp, filesize($file_path));
//$log_arr = explode("\n", $log_data);
//$log_data = file($file_path);
$top_keyword = $keyword_top_10 = array();
$keyword_search_nums = '';

$top_id = $id_top_10 = array();
$id_search_nums = '';
//foreach($log_data as $k => $v){
while(!feof($fp)){
  	$v = fgets($fp);
	if(empty($v)){
		//unset($log_data[$k]);
		continue;
	}
	$log_data_cut = explode(" - - ", $v);
	//验证ip是否合法，并排除内网访问地址
	if(!filter_var($log_data_cut[0], FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)){
		//unset($log_data[$k]);
		continue;
	}
	//$log_arr_3[$log_arr_2[0]] = $log_arr_2[1];
	//筛选关键字的链接
	if(preg_match('/tvmfusion\/v4\/fusion\/v3-query?/',$log_data_cut[1])){
		//$keyword_arr[] = urldecode($log_data_cut[1]);
		if(preg_match('/^(.+q=)?([^\&\|\s]+)/i', $log_data_cut[1], $matches)){
			$keyword_arr['ip'] = $log_data_cut[0];
			$keyword_arr['keyword'] = urldecode($matches[2]);
			//array_push($top_keyword, $keyword_arr);
			$top_keyword[] = $keyword_arr;
			
		}else{
			continue;
		}

	}else if(preg_match('/tvmfusion\/v4\/feed-rel\/feed?/',$log_data_cut[1])){
		//筛选id的链接
		if(preg_match('/(.+id=)/',$log_data_cut[1])){
			if(preg_match('/^(.+id=)?([^\&\|\s]+)/i', $log_data_cut[1], $matches)){
				$id_arr['ip'] = $log_data_cut[0];
				$id_arr['id'] = $matches[2];
				//array_push($top_id, $id_arr);
				$top_id[] = $id_arr;
				
			}else{
				continue;
			}
		}else{
			continue;
		}
	}else{
		continue;
	}
}
fclose($fp);
//过滤不合法的ip，内网数据
/*$log_data = array_values($log_data);
if(count($log_data) == 0){
	echo json_encode(array("code" => 0, 'msg' => "数据不合法"));exit;
}

foreach($log_data as $k => $v){
	//筛选关键字的链接
	if(preg_match('/tvmfusion\/v4\/fusion\/v3-query?/',$v)){
		$keyword_arr[] = urldecode($v);
	}
	//筛选id的链接
	if(preg_match('/tvmfusion\/v4\/feed-rel\/feed?/',$v)){
		if(preg_match('/(.+id=)/',$v)){
			$id_arr[] = $v;
		}
	}
}*/


//搜索次数排名前十的关键词及次数
if(!empty($top_keyword)){
	/*foreach($keyword_arr as $k => $v){
		$keyword_cut = explode(" - - ", $v);			
		if(preg_match('/^(.+q=)?([^\&\|\s]+)/i', $keyword_cut[1], $matches)){
			$top_keyword[$k]['ip'] = $keyword_cut[0];
			$top_keyword[$k]['keyword'] = $matches[2];
		}
	}*/
	//二维数组去掉重复ip
	$top_keyword = unique_arr($top_keyword, true, true);
	$keyword_search_nums = count($top_keyword);
	foreach($top_keyword as $k => $v){
		//把关键字放到新的一维数组
		$new_top_keyword[] = $v['keyword'];
	}
	//一维数组里面进行统计次数
	$new_top_keyword = array_count_values($new_top_keyword);
	arsort($new_top_keyword);
	$keyword_top_10 = array_slice($new_top_keyword, 0, 10);
}
//搜索查询排名前十的GUID及次数
if(!empty($top_id)){
	/*foreach($id_arr as $k => $v){
		$id_cut = explode(" - - ", $v);			
		if(preg_match('/^(.+id=)?([^\&\|\s]+)/i', $id_cut[1], $matches)){
			$top_id[$k]['ip'] = $id_cut[0];
			$top_id[$k]['id'] = $matches[2];
		}
	}*/
	//二维数组去掉重复ip
	$top_id = unique_arr($top_id, true, true);
	$id_search_nums = count($top_id);
	foreach($top_id as $k => $v){
		//把id放到新的一维数组
		$new_top_id[] = $v['id'];
	}
	//一维数组里面进行统计次数
	$new_top_id = array_count_values($new_top_id);
	arsort($new_top_id);
	$id_top_10 = array_slice($new_top_id, 0, 10);
}

if(preg_match('/((2[0-4]\d|25[0-5]|[01]?\d\d?)\.){3}(2[0-4]\d|25[0-5]|[01]?\d\d?)/', $value, $matches)){
	$server = $matches[0];
}
$date = date("Y-m-d");
$datetime = date("Y-m-d H:i:s");
$connect = mysqli_connect('localhost','root','123','hqsx-statis') or die ('Unale to connect');
mysqli_query($connect,'SET NAMES utf8');
$sql = "SELECT id FROM keyword_search_nums WHERE server='".$server."' AND  DATE_FORMAT(created,'%Y-%m-%d') = '".$date."'"; 
$query = mysqli_query($connect, $sql); 
$row = mysqli_fetch_row($query);
if(empty($row)){
	$keyword_sql = "INSERT keyword_search_nums(`server`, `num`, `created`) VALUES ('".$server."', '".$keyword_search_nums."', '".$datetime."')";
	$keyword_top_sql = "INSERT keyword_top_10(`server`, `keyword`, `num`, `created`) VALUES";
	foreach($keyword_top_10 as $k => $v){
		$keyword_top_sql .= " ('".$server."', '".$k."', '".$v."', '".$datetime."'),";
	}
	$keyword_top_sql = substr($keyword_top_sql, 0, -1);
	
	$id_sql = "INSERT id_search_nums(`server`, `num`, `created`) VALUES ('".$server."', '".$id_search_nums."', '".$datetime."')";
	$id_top_sql = "INSERT id_top_10(`server`, `guid`, `num`, `created`) VALUES";
	foreach($id_top_10 as $k => $v){
		$id_top_sql .= " ('".$server."', '".$k."', '".$v."', '".$datetime."'),";
	}
	$id_top_sql = substr($id_top_sql, 0, -1);

	mysqli_query($connect, "START TRANSACTION");
	$keyword_query = mysqli_query($connect, $keyword_sql);
	$keyword_top_query = mysqli_query($connect, $keyword_top_sql);
	$id_query = mysqli_query($connect, $id_sql);
	$id_top_query = mysqli_query($connect, $id_top_sql);
	if(mysqli_errno($connect)){
		mysqli_query($connect, "ROLLBACK");
	}else{
		mysqli_query($connect, "COMMIT");
		echo $datetime.'&nbsp;&nbsp;'.$server."&nbsp;&nbsp;数据入库成功"."<br />";
	}
}else{
	echo json_encode(array('code' => '1', 'msg' => "今日的数据已统计"));exit;
}
mysqli_close($connect);
/*$data['keyword_search_nums'] = $keyword_search_nums;
$data['keyword_top_10'] = $keyword_top_10;
$data['id_search_nums'] = $id_search_nums;
$data['id_top_10'] = $id_top_10;

echo json_encode($data);exit;*/
}

function unique_arr($array2D, $stkeep = false, $ndformat = true){
    // 判断是否保留一级数组键 (一级数组键可以为非数字)
    if($stkeep) $stArr = array_keys($array2D);
    // 判断是否保留二级数组键 (所有二级数组键必须相同)
    if($ndformat) $ndArr = array_keys(end($array2D));
    //降维,也可以用implode,将一维数组转换为用逗号连接的字符串
    foreach ($array2D as $v){
        $v = join(",",$v); 
        $temp[] = $v;
    }
    //去掉重复的字符串,也就是重复的一维数组
    $temp = array_unique($temp); 
    //再将拆开的数组重新组装
    foreach ($temp as $k => $v){
        if($stkeep) $k = $stArr[$k];
        if($ndformat){
            $tempArr = explode(",",$v); 
            foreach($tempArr as $ndkey => $ndval) $output[$k][$ndArr[$ndkey]] = $ndval;
        }
        else $output[$k] = explode(",",$v); 
    }
    return $output;
}
?>