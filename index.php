<?php
set_time_limit(0);
date_default_timezone_set('Asia/shanghai');
header("Content-type:text/html;charset=utf-8");
$today = date("Ymd");
$date = date("Y-m-d");
$datetime = date("Y-m-d H:i:s");
$thatDate = @$_REQUEST['date'];//2015-08-17
$file_date = $thatDate ? date("Ymd", strtotime($thatDate)) : $today;
$connect = mysqli_connect('10.10.51.14','root','tvmining@123','hqsx-statis') or die ('Link database failed');
mysqli_query($connect,'SET NAMES utf8');
$sql = "SELECT id FROM today_statis WHERE statis_date = '".$thatDate."'"; 
$query = mysqli_query($connect, $sql); 
$row = mysqli_fetch_row($query);
if(!empty($row)){
	echo $thatDate."数据已统计";exit;
}
//遍历该目录下的.log文件
$log_dir_path = '/opt/host/hqsxlog/';
$log_list = listDir($log_dir_path, $file_date);
function listDir($dir, $file_date){
    static $result_array = array();
    if(!is_dir($dir)){
    	echo json_encode(array("code" => 0, 'msg' => $dir."目录不存在"));exit;
    }
    if ($dh = opendir($dir)){
    	while (($file = readdir($dh)) !== false){
    		if((is_dir($dir.$file)) && $file != "." && $file != ".."){
            		//if(preg_match_all('/((2[0-4]\d|25[0-5]|[01]?\d\d?)\.){3}(2[0-4]\d|25[0-5]|[01]?\d\d?)/', $file, $matches)){
            			listDir($dir.$file."/", $file_date);
            		//}
            	}else{
            		if($file!="." && $file!=".."){
            			//$file_name = 'access'.date("Ymd").'.log';
            			if(is_file($dir.$file) && strstr($file, $file_date)){
            			//if(strstr($file,"20150805") || strstr($file,"20150806")){
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
foreach($log_list as $key => $value){
$file_path = $value;
if (file_exists($file_path) == false) {
	echo json_encode(array("code" => 0, 'msg' => $file_path."文件不存在"));exit;
}
if (is_readable($file_path) == false) {
	echo json_encode(array("code" => 0, 'msg' => $file_path."文件不可读"));exit;
}
$fp = fopen($file_path, "r");
if($fp == false){
	echo json_encode(array("code" => 0, 'msg' => $file_path."文件打开失败"));exit;
 }
if(filesize($file_path) == false){
	echo json_encode(array("code" => 0, 'msg' => $file_path."文件内容为空"));exit;
}
$i = 1;
while(!feof($fp)){
  	$line = fgets($fp);
	if(empty($line)){
		continue;
	}
	$log_data_cut = explode(" - - ", $line);
	//验证ip是否合法，并排除内网访问地址
	if(!filter_var($log_data_cut[0], FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)){
		continue;
	}
	//筛选关键字的链接
	if(preg_match('/tvmfusion\/v4\/fusion\/v3-query?/',$log_data_cut[1])){
		if(preg_match('/q=([^&].+)&/iU', $log_data_cut[1], $matKeyword)){
			$keyword_arr['ip'] = $log_data_cut[0];
			$keyword_arr['keyword'] = urldecode($matKeyword[1]);
			
			$keyword_sql = "SELECT id, ip FROM `log_keyword_temp` WHERE ip = '".$keyword_arr['ip']."' AND keyword = '".$keyword_arr['keyword']."' LIMIT 1";
			$keyword_query = mysqli_query($connect, $keyword_sql); 
			$keyword_row = mysqli_fetch_row($keyword_query);
			mysqli_free_result($keyword_query);
			if(empty($keyword_row)){
				$ip_count_sql = "SELECT id, ip, keyword FROM `ip_count` WHERE ip = '".$keyword_arr['ip']."' LIMIT 1";
				$ip_count_query = mysqli_query($connect, $ip_count_sql); 
				$ip_count_row = mysqli_fetch_row($ip_count_query);
				mysqli_free_result($ip_count_query);
				if(empty($ip_count_row)){
					$ip_sql = "INSERT ip_count(`ip`, `keyword`, `num`) VALUES ('".$keyword_arr['ip']."', '".$keyword_arr['keyword']."', 1)";
				}else if($ip_count_row[2] == NULL){
					$ip_sql = "UPDATE ip_count SET `num` = num +1, `keyword` = '".$keyword_arr['keyword']."' WHERE id = '".$ip_count_row[0]."'";
				}else{
					$ip_sql = "UPDATE ip_count SET `num` = num +1, `keyword` = CONCAT(keyword, '".",".$keyword_arr['keyword']."') WHERE id = '".$ip_count_row[0]."'";
				}
				$keyword_temp_sql = "INSERT log_keyword_temp(`ip`, `keyword`, `created`) VALUES ('".$keyword_arr['ip']."', '".$keyword_arr['keyword']."', '".$datetime."')";
				
				$keyword_count_sql = "SELECT id, keyword FROM `keyword_count` WHERE keyword = '".$keyword_arr['keyword']."' LIMIT 1";
				$keyword_count_query = mysqli_query($connect, $keyword_count_sql);
				$keyword_count_row = mysqli_fetch_row($keyword_count_query);
				mysqli_free_result($keyword_count_query);
				if(!empty($keyword_count_row)){
					$keyword_exe_sql = "UPDATE `keyword_count` SET `num` = num + 1 WHERE id = ".$keyword_count_row[0];
				}else{
					$keyword_exe_sql = "INSERT keyword_count(`keyword`, `num`) VALUES ('".$keyword_arr['keyword']."', 1)";
				}
				mysqli_query($connect, "START TRANSACTION");
				mysqli_query($connect, $ip_sql);
				mysqli_query($connect, $keyword_temp_sql);
				mysqli_query($connect, $keyword_exe_sql);
				if(mysqli_errno($connect)){
					mysqli_query($connect, "ROLLBACK");
				}else{
					mysqli_query($connect, "COMMIT");
					echo "第".$i."行执行成功\n";	
				}	
			}
		}else{
			continue;
		}
	}else if(preg_match('/tvmfusion\/v4\/feed-rel\/feed?/',$log_data_cut[1])){
		//筛选id的链接
		if(preg_match('/[\?|&]id=([^\&|\s]+)/i', $log_data_cut[1], $matId)){
			$id_arr['ip'] = $log_data_cut[0];
			$id_arr['id'] = $matId[1];
			
			$id_sql = "SELECT id, ip FROM `log_id_temp` WHERE ip = '".$id_arr['ip']."' AND guid = '".$id_arr['id']."' LIMIT 1";
			$id_query = mysqli_query($connect, $id_sql); 
			$id_row = mysqli_fetch_row($id_query);
			mysqli_free_result($id_query);
			if(empty($id_row)){
				$ip_count_sql = "SELECT id, ip, guid FROM `ip_count` WHERE ip = '".$id_arr['ip']."' LIMIT 1";
				$ip_count_query = mysqli_query($connect, $ip_count_sql); 
				$ip_count_row = mysqli_fetch_row($ip_count_query);
				mysqli_free_result($ip_count_query);
				if(empty($ip_count_row)){
					$ip_sql = "INSERT ip_count(`ip`, `guid`, `num`) VALUES ('".$id_arr['ip']."', '".$id_arr['id']."', 1)";
				}else if($ip_count_row[2] == NULL){
					$ip_sql = "UPDATE ip_count SET `num` = num +1, `guid` = '".$id_arr['id']."' WHERE id = ".$ip_count_row[0];
				}else{
					$ip_sql = "UPDATE ip_count SET `num` = num +1, `guid` = CONCAT(guid, '".",".$id_arr['id']."') WHERE id = ".$ip_count_row[0];
				}
			
				$id_temp_sql = "INSERT log_id_temp(`ip`, `guid`, `created`) VALUES ('".$id_arr['ip']."', '".$id_arr['id']."', '".$datetime."')";
				
				$id_count_sql = "SELECT id, guid FROM `id_count` WHERE guid = '".$id_arr['id']."' LIMIT 1";
				$id_count_query = mysqli_query($connect, $id_count_sql); 
				$id_count_row = mysqli_fetch_row($id_count_query);
				mysqli_free_result($id_count_query);
				if(!empty($id_count_row)){
					$id_exe_sql = "UPDATE `id_count` SET `num` = num + 1 WHERE id='".$id_count_row[0]."'";
				}else{
					$id_exe_sql = "INSERT id_count(`guid`, `num`) VALUES ('".$id_arr['id']."', 1)";
					
				}
				mysqli_query($connect, "START TRANSACTION");
				mysqli_query($connect, $ip_sql);
				mysqli_query($connect, $id_temp_sql);
				mysqli_query($connect, $id_exe_sql);
				if(mysqli_errno($connect)){
					mysqli_query($connect, "ROLLBACK");
				}else{
					mysqli_query($connect, "COMMIT");
					echo "第".$i."行执行成功\n";
				}
			}
		}else{
			continue;
		}
	}else{
		continue;
	}
	$i++;
}
fclose($fp);
unlink($file_path);
}
//数据统计入库
$sql = "SELECT id FROM today_statis WHERE statis_date = '".$thatDate."'"; 
$query = mysqli_query($connect, $sql); 
$row = mysqli_fetch_row($query);
mysqli_free_result($query);
if(empty($row)){
	//搜索关键字次数统计
	$keyword_nums_sql = "SELECT SUM(num) FROM keyword_count"; 
	$keyword_nums_query = mysqli_query($connect, $keyword_nums_sql); 
	$keyword_nums_result = mysqli_fetch_row($keyword_nums_query);
	mysqli_free_result($keyword_nums_query);
	//搜索guid次数统计
	$id_nums_sql = "SELECT SUM(num) FROM id_count";  
	$id_nums_query = mysqli_query($connect, $id_nums_sql);  
	$id_nums_result = mysqli_fetch_row($id_nums_query);
	mysqli_free_result($id_nums_query);
	//独立ip次数统计
	$ip_nums_sql = "SELECT count(id) FROM ip_count";  
	$ip_nums_query = mysqli_query($connect, $ip_nums_sql);  
	$ip_nums_result = mysqli_fetch_row($ip_nums_query);
	mysqli_free_result($ip_nums_query);
	
	//今日数据
	$today_sql = "INSERT today_statis(`keyword_nums`, `guid_nums`, `ip_nums`, `created`, `statis_date`) VALUES ('".$keyword_nums_result[0]."', '".$id_nums_result[0]."', '".$ip_nums_result[0]."', '".$datetime."', '".$thatDate."')";
	
	//排名前10的关键字
	$keyword_top_sql = "SELECT keyword, num FROM keyword_count ORDER BY num DESC LIMIT 0, 10";
	$keyword_top_query = mysqli_query($connect, $keyword_top_sql);
	$keyword_insert_sql = "INSERT keyword_top_10(`keyword`, `num`, `created`, `statis_date`) VALUES";
	while($row = mysqli_fetch_row($keyword_top_query)){
		$keyword_insert_sql .= " ('".$row[0]."', '".$row[1]."', '".$datetime."', '".$thatDate."'),";
	}
	mysqli_free_result($keyword_top_query);
	$keyword_insert_sql = substr($keyword_insert_sql, 0, -1);

	//搜索关键字次数排名前10的ip
	$keyword_topip_sql = "SELECT ip , count(id) as num FROM `log_keyword_temp` GROUP BY ip ORDER BY num DESC limit 0,10";
	$keyword_topip_query = mysqli_query($connect, $keyword_topip_sql);
	$k_ip_insert_sql = "INSERT keyword_top_10_ip(`ip`, `num`, `created`, `statis_date`) VALUES";
	while($row = mysqli_fetch_row($keyword_topip_query)){
		$k_ip_insert_sql .= " ('".$row[0]."', '".$row[1]."', '".$datetime."', '".$thatDate."'),";
	}
	mysqli_free_result($keyword_topip_query);
	$k_ip_insert_sql = substr($k_ip_insert_sql, 0, -1);

	//排名前10的guid
	$id_top_sql = "SELECT guid, num FROM id_count ORDER BY num  DESC LIMIT 0, 10"; 
	$id_top_query = mysqli_query($connect, $id_top_sql);
	$id_insert_sql = "INSERT id_top_10(`title`, `guid`, `num`, `created`, `statis_date`) VALUES"; 
	while($row = mysqli_fetch_row($id_top_query)){
		$row[2] = getTitleByguid($row[0]);
		$id_insert_sql .= " ('".$row[2]."','".$row[0]."', '".$row[1]."', '".$datetime."', '".$thatDate."'),";
	}
	mysqli_free_result($id_top_query);
	$id_insert_sql = substr($id_insert_sql, 0, -1);

	//搜索guid次数排名前10的ip
	$id_topip_sql = "SELECT ip , count(id) as num FROM `log_id_temp` GROUP BY ip ORDER BY num DESC limit 0,10";
	$id_topip_query = mysqli_query($connect, $id_topip_sql);
	$i_ip_insert_sql = "INSERT id_top_10_ip(`ip`, `num`, `created`, `statis_date`) VALUES";
	while($row = mysqli_fetch_row($id_topip_query)){
		$i_ip_insert_sql .= " ('".$row[0]."', '".$row[1]."', '".$datetime."', '".$thatDate."'),";
	}
	mysqli_free_result($id_topip_query);
	$i_ip_insert_sql = substr($i_ip_insert_sql, 0, -1);

	//排名前30的ip
	$ip_top_sql = "SELECT ip, keyword, guid, num FROM ip_count ORDER BY num  DESC LIMIT 0, 30"; 
	$ip_top_query = mysqli_query($connect, $ip_top_sql);
	$ip_insert_sql = "INSERT ip_top_30(`ip`, `keyword`, `guid`, `num`, `created`, `statis_date`) VALUES"; 
	while($row = mysqli_fetch_row($ip_top_query)){
		$ip_insert_sql .= " ('".$row[0]."','".$row[1]."', '".$row[2]."', '".$row[3]."', '".$datetime."', '".$thatDate."'),";
	}
	mysqli_free_result($ip_top_query);
	$ip_insert_sql = substr($ip_insert_sql, 0, -1);

	mysqli_query($connect, "START TRANSACTION");
	mysqli_query($connect, $today_sql);
	mysqli_query($connect, $keyword_insert_sql);
	mysqli_query($connect, $k_ip_insert_sql);
	mysqli_query($connect, $id_insert_sql);
	mysqli_query($connect, $i_ip_insert_sql);
	mysqli_query($connect, $ip_insert_sql);
	//清空以下数据表
	mysqli_query($connect, "TRUNCATE log_id_temp");
	mysqli_query($connect, "TRUNCATE log_keyword_temp");
	mysqli_query($connect, "TRUNCATE ip_count");
	mysqli_query($connect, "TRUNCATE id_count");
	mysqli_query($connect, "TRUNCATE keyword_count");
	if(mysqli_errno($connect)){
		mysqli_query($connect, "ROLLBACK");
	}else{
		mysqli_query($connect, "COMMIT");
		mysqli_close($connect);
		echo $datetime." 数据入库成功";exit;
	}
}else{
	echo $thatDate."数据已统计";exit;
}
//根据guid获取title
function getTitleByguid($guid = ''){
  $httpurl = "http://www.tvm.cn/share/search.php?guid=".$guid."&action=guid";
  $json_data = file_get_contents($httpurl);
  if(empty($json_data)){
    echo json_encode(array("code" => '0', 'msg' => $guid.'请求获取title失败'));
  }
  $arr_data = json_decode($json_data, true);
  if(isset($arr_data['feed']['entry'])){
    $title = $arr_data['feed']['entry']['title']['$t'];
  }else{
    $title = "";
  }
  return $title;
}
?>
