<?php
set_time_limit(0);
date_default_timezone_set('Asia/shanghai');
header("Content-type:text/html;charset=utf-8");
$configPath = 'config.ini';
$configArray = parse_ini_file($configPath, true);
if ($configArray === false) {
    echo json_encode(array("code" => 0, 'msg' => "配置文件读取失败"));exit;
}
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
            		if(preg_match_all('/((2[0-4]\d|25[0-5]|[01]?\d\d?)\.){3}(2[0-4]\d|25[0-5]|[01]?\d\d?)/', $file, $matches)){
            			listDir($dir.$file."/", $file_date);
            		}
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
	//匹配访问时间
	if(preg_match('/\[(.*)[\]]/i', $log_data_cut[1], $accessTime)){
		$access_time = date("Y-m-d H:i:s", strtotime($accessTime[1]));
	}
	//筛选关键字的链接
	if(preg_match('/tvmfusion\/v4\/fusion\/v3-query?/',$log_data_cut[1])){
		if(preg_match('/q=([^&].+)&/iU', $log_data_cut[1], $matKeyword)){
			$keyword_arr['ip'] = $log_data_cut[0];
			$keyword_arr['keyword'] = urldecode($matKeyword[1]);
			//$keyword_arr['keyword'] = str_replace(" ", "", $keyword_arr['keyword']);
			//$keywordArr[] = urldecode($line);
			/*$tfp = fopen('test.log', "a");
		        fputs($tfp, urldecode($line));
		        fflush($tfp);
		        fclose($tfp);*/
			//判断来源1=ios,2=android,3=wechat,4=weibo,5=qq,6=other
			if(preg_match('/Mobile/i',$log_data_cut[1])){
				if(preg_match('/MicroMessenger/i',$log_data_cut[1])){
					$keyword_arr['source'] = 3;
				}else if(preg_match('/Weibo/i',$log_data_cut[1])){
					$keyword_arr['source'] = 4;
				}else if(preg_match('/QQ\/|MQQBrowser+[^MicroMessenger]/i',$log_data_cut[1])){
					$keyword_arr['source'] = 5;
				}else{
					$keyword_arr['source'] = 6;
				}
			}else{
				if(preg_match('/CFNetwork/i',$log_data_cut[1])){
					$keyword_arr['source'] = 1;
				}else if(preg_match('/"-" "-"/',$log_data_cut[1])){
					$keyword_arr['source'] = 2;
				}else{
					$keyword_arr['source'] = 6;
				}
			}
			//获取搜索关键字时的分页数
			//$log_data_cut[1] = '103.16.126.82 - - [13/Aug/2015:14:52:39  0800] "GET /tvmfusion/v4/fusion/v3-query?callback=jQuery21009672582282219082_1439448721257&q= 小女子&count=10&startPage=%d&alt=json&access_token=%@&fields=id,published,content,title,t:props,media:group,t:rtype,summary&sortby=published desc&_=1439448721258 HTTP/1.1" 200 4249 "http://192.168.28.157/wechat/?view=newsearch&tid=0" "Mozilla/5.0 (Linux; Android 5.0.2; HTC D816w Build/LRX22G) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/37.0.0.0 Mobile Safari/537.36 MicroMessenger/6.1.0.66_r1062275.542 NetType/WIFI"';
			if(preg_match('/startPage=([^&|%|#])/iU', $log_data_cut[1], $startPage)){
				$keyword_arr['startPage'] = $startPage[1];
			}else{
				$keyword_arr['startPage'] = 0;
			}
			$keyword_sql = "SELECT id, ip, start_page FROM `log_keyword_temp` WHERE ip = '".$keyword_arr['ip']."' AND keyword = '".$keyword_arr['keyword']."' LIMIT 1";
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
				$keyword_temp_sql = "INSERT log_keyword_temp(`ip`, `keyword`, `source`, `start_page`, `access_time`, `created`) VALUES ('".$keyword_arr['ip']."', '".$keyword_arr['keyword']."', '".$keyword_arr['source']."', '".$keyword_arr['startPage']."', '".$access_time."', '".$datetime."')";
				
				$keyword_count_sql = "SELECT id FROM `keyword_count` WHERE keyword = '".$keyword_arr['keyword']."' LIMIT 1";
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
			}else{
				if(!empty($keyword_arr['startPage']) && !empty($keyword_row[2]) && ($keyword_arr['startPage'] > $keyword_row[2])){
					$keyword_update_sql = "UPDATE `log_keyword_temp` SET `start_page` = {$keyword_arr['startPage']} WHERE id=".$keyword_row[0];
					mysqli_query($connect, $keyword_update_sql);
					if(mysqli_errno($connect)){
						mysqli_query($connect, "ROLLBACK");
					}else{
						mysqli_query($connect, "COMMIT");
						echo "第".$i."行执行成功\n";	
					}	
				}else{
					continue;
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
			
			//判断来源1=ios,2=android,3=wechat,4=weibo,5=qq,6=other
			if(preg_match('/Mobile/i',$log_data_cut[1])){
				if(preg_match('/MicroMessenger/i',$log_data_cut[1])){
					$id_arr['source'] = 3;
				}else if(preg_match('/Weibo/i',$log_data_cut[1])){
					$id_arr['source'] = 4;
				}else if(preg_match('/QQ\/|MQQBrowser+[^MicroMessenger]/i',$log_data_cut[1])){
					$id_arr['source'] = 5;
				}else{
					$id_arr['source'] = 6;
				}
			}else{
				if(preg_match('/CFNetwork/i',$log_data_cut[1])){
					$id_arr['source'] = 1;
				}else if(preg_match('/"-" "-"/',$log_data_cut[1])){
					$id_arr['source'] = 2;
				}else{
					$id_arr['source'] = 6;
				}
			}
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
			
				$id_temp_sql = "INSERT log_id_temp(`ip`, `guid`, `source`, `access_time`, `created`) VALUES ('".$id_arr['ip']."', '".$id_arr['id']."', '".$id_arr['source']."', '".$access_time."', '".$datetime."')";
				
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
//unlink($file_path);//删除文件
}
/*echo "<pre>";
print_r($keywordArr);exit;*/
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

	//搜索关键字的source次数统计
	$keyword_source_sql = "SELECT source, count(id) FROM log_keyword_temp GROUP BY source"; 
	$keyword_source_query = mysqli_query($connect, $keyword_source_sql); 
	$k_string = "";  
	while($row = mysqli_fetch_row($keyword_source_query)){
		$k_string .= $row[0].':'.$row[1].',';
	}
	mysqli_free_result($keyword_source_query);
	$k_source_count = substr($k_string, 0, -1);

	//统计搜索翻页次数
	$keyword_flip_sql = "SELECT count(id) FROM log_keyword_temp WHERE start_page != 0";  
	$keyword_flip_query = mysqli_query($connect, $keyword_flip_sql);  
	$keyword_flip_result = mysqli_fetch_row($keyword_flip_query);
	mysqli_free_result($keyword_flip_query);

	//统计搜索有效翻页次数
	$k_flip_sql = "SELECT count(id) FROM log_keyword_temp WHERE start_page not in(0,1)";  
	$k_flip_query = mysqli_query($connect, $k_flip_sql);  
	$k_flip_result = mysqli_fetch_row($k_flip_query);
	mysqli_free_result($k_flip_query);

	//搜索guid次数统计
	$id_nums_sql = "SELECT SUM(num) FROM id_count";  
	$id_nums_query = mysqli_query($connect, $id_nums_sql);  
	$id_nums_result = mysqli_fetch_row($id_nums_query);
	mysqli_free_result($id_nums_query);

	//搜索guid的source次数统计
	$id_source_sql = "SELECT source, count(id) FROM log_id_temp GROUP BY source"; 
	$id_source_query = mysqli_query($connect, $id_source_sql); 
	$id_string = "";  
	while($row = mysqli_fetch_row($id_source_query)){
		$id_string .= $row[0].':'.$row[1].',';
	}
	mysqli_free_result($id_source_query);
	$id_source_count = substr($id_string, 0, -1);

	//独立ip次数统计
	$ip_nums_sql = "SELECT count(id) FROM ip_count";  
	$ip_nums_query = mysqli_query($connect, $ip_nums_sql);  
	$ip_nums_result = mysqli_fetch_row($ip_nums_query);
	mysqli_free_result($ip_nums_query);
	
	//今日数据
	$today_sql = "INSERT today_statis(`keyword_nums`, `k_source_count`, `guid_nums`, `g_source_count`, `ip_nums`, `flip_count`, `flip_count_true`, `created`, `statis_date`) VALUES ('".$keyword_nums_result[0]."', '".$k_source_count."', '".$id_nums_result[0]."', '".$id_source_count."', '".$ip_nums_result[0]."', '".$keyword_flip_result[0]."', '".$k_flip_result[0]."', '".$datetime."', '".$thatDate."')";
	
	//排名前10的关键字
	$keyword_top_sql = "SELECT keyword, num FROM keyword_count ORDER BY num DESC LIMIT 0, {$configArray['keyword_top_n']}";
	$keyword_top_query = mysqli_query($connect, $keyword_top_sql);
	$keyword_insert_sql = "INSERT keyword_top_10(`keyword`, `num`, `source_count`, `created`, `statis_date`) VALUES";
	while($row = mysqli_fetch_row($keyword_top_query)){
		//查询关键字的来源并做统计
		$k_sql = "SELECT source, count(id) FROM log_keyword_temp WHERE keyword = '".$row[0]."' GROUP BY source";
		$k_query = mysqli_query($connect, $k_sql);
		$k_s_string = "";
		while($row3 = mysqli_fetch_row($k_query)){
			$k_s_string .= $row3[0].':'.$row3[1].',';
		}
		mysqli_free_result($k_query);
		$k_s_count = substr($k_s_string, 0, -1);

		$keyword_insert_sql .= " ('".$row[0]."', '".$row[1]."', '".$k_s_count."', '".$datetime."', '".$thatDate."'),";
		//关键字与ip一一对应
		$keyword_ip_sql = "SELECT ip, source FROM log_keyword_temp WHERE keyword = '".$row[0]."'";
		$keyword_ip_query = mysqli_query($connect, $keyword_ip_sql);
		while($row2 = mysqli_fetch_assoc($keyword_ip_query)){
			//$city = getLocation($row2['ip']) ? getLocation($row2['ip']) : "";//根据ip地址获取city
			$city = "";
			$keyword_to_ip_sql = "INSERT keyword_to_ip(`keyword`, `ip`, `city`, `source`, `created`, `statis_date`) VALUES ('".$row[0]."', '".$row2['ip']."', '".$city."', '".$row2['source']."', '".$datetime."', '".$thatDate."')";
			$keyword_to_ip_query = mysqli_query($connect, $keyword_to_ip_sql);
		}
		mysqli_free_result($keyword_ip_query);
	}
	mysqli_free_result($keyword_top_query);
	$keyword_insert_sql = substr($keyword_insert_sql, 0, -1);

	//搜索关键字次数排名前10的ip
	$keyword_topip_sql = "SELECT ip , count(id) as num FROM `log_keyword_temp` GROUP BY ip ORDER BY num DESC limit 0, {$configArray['keyword_top_n_ip']}";
	$keyword_topip_query = mysqli_query($connect, $keyword_topip_sql);
	$k_ip_insert_sql = "INSERT keyword_top_10_ip(`ip`, `keyword`, `num`, `created`, `statis_date`) VALUES";
	while($row = mysqli_fetch_row($keyword_topip_query)){
		$get_keyword_sql = "SELECT keyword FROM `log_keyword_temp` WHERE ip = '".$row[0]."'";
		$get_keyword_query = mysqli_query($connect, $get_keyword_sql);
		$keyword_string = "";
		while($row2 = mysqli_fetch_row($get_keyword_query)){
			$keyword_string .= $row2[0].',';
		}
		$keyword_string = substr($keyword_string, 0, -1);
		$k_ip_insert_sql .= " ('".$row[0]."', '".$keyword_string."', '".$row[1]."', '".$datetime."', '".$thatDate."'),";
	}
	mysqli_free_result($keyword_topip_query);
	$k_ip_insert_sql = substr($k_ip_insert_sql, 0, -1);

	//搜索关键字翻页次数排名前10的ip
	$keyword_tflip_sql = "SELECT ip , keyword, start_page FROM `log_keyword_temp` ORDER BY start_page DESC limit 0, {$configArray['keyword_flip_top_n']}";
	$keyword_tflip_query = mysqli_query($connect, $keyword_tflip_sql);
	$k_flip_insert_sql = "INSERT keyword_flip_top_10(`ip`, `keyword`, `start_page`,`created`, `statis_date`) VALUES";
	while($row = mysqli_fetch_row($keyword_tflip_query)){
		$k_flip_insert_sql .= " ('".$row[0]."', '".$row[1]."', '".$row[2]."', '".$datetime."', '".$thatDate."'),";
	}
	mysqli_free_result($keyword_tflip_query);
	$k_flip_insert_sql = substr($k_flip_insert_sql, 0, -1);

	//排名前10的guid
	$id_top_sql = "SELECT guid, num FROM id_count ORDER BY num  DESC LIMIT 0, {$configArray['guid_top_n']}"; 
	$id_top_query = mysqli_query($connect, $id_top_sql);
	$id_insert_sql = "INSERT id_top_10(`title`, `guid`, `num`, `source_count`, `created`, `statis_date`) VALUES"; 
	while($row = mysqli_fetch_row($id_top_query)){
		//查询guid的来源并做统计
		$i_sql = "SELECT source, count(id) FROM log_id_temp WHERE guid = '".$row[0]."' GROUP BY source";
		$i_query = mysqli_query($connect, $i_sql);
		$i_s_string = "";
		while($row2 = mysqli_fetch_row($i_query)){
			$i_s_string .= $row2[0].':'.$row2[1].',';
		}
		mysqli_free_result($i_query);
		$i_s_count = substr($i_s_string, 0, -1);

		$row[2] = getTitleByguid($row[0]);
		$id_insert_sql .= " ('".$row[2]."','".$row[0]."', '".$row[1]."', '".$i_s_count."', '".$datetime."', '".$thatDate."'),";
	}
	mysqli_free_result($id_top_query);
	$id_insert_sql = substr($id_insert_sql, 0, -1);

	//搜索guid次数排名前10的ip
	$id_topip_sql = "SELECT ip , count(id) as num FROM `log_id_temp` GROUP BY ip ORDER BY num DESC limit 0, {$configArray['guid_top_n_ip']}";
	$id_topip_query = mysqli_query($connect, $id_topip_sql);
	$i_ip_insert_sql = "INSERT id_top_10_ip(`ip`, `num`, `created`, `statis_date`) VALUES";
	while($row = mysqli_fetch_row($id_topip_query)){
		$i_ip_insert_sql .= " ('".$row[0]."', '".$row[1]."', '".$datetime."', '".$thatDate."'),";
	}
	mysqli_free_result($id_topip_query);
	$i_ip_insert_sql = substr($i_ip_insert_sql, 0, -1);

	//排名前30的ip
	$ip_top_sql = "SELECT ip, keyword, guid, num FROM ip_count ORDER BY num  DESC LIMIT 0, {$configArray['ip_top_n']}"; 
	$ip_top_query = mysqli_query($connect, $ip_top_sql);
	$ip_insert_sql = "INSERT ip_top_30(`ip`, `keyword`, `guid`, `num`, `created`, `statis_date`) VALUES"; 
	while($row = mysqli_fetch_row($ip_top_query)){
		$guidInfo = array();
		$guidArr = explode(",", $row[2]);
		foreach($guidArr as $k => $v){
			$guidInfo[$k]['title'] = getTitleByguid($v);
			$guidInfo[$k]['guid'] = $v;
		}
		$guid = serialize($guidInfo);
		$ip_insert_sql .= " ('".$row[0]."','".$row[1]."', '".$guid."', '".$row[3]."', '".$datetime."', '".$thatDate."'),";
		unset($guidInfo);
	}
	mysqli_free_result($ip_top_query);
	$ip_insert_sql = substr($ip_insert_sql, 0, -1);

	mysqli_query($connect, "START TRANSACTION");
	mysqli_query($connect, $today_sql);
	mysqli_query($connect, $keyword_insert_sql);
	mysqli_query($connect, $k_ip_insert_sql);
	mysqli_query($connect, $k_flip_insert_sql);
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
function getLocation($ip = ''){
    $res = @file_get_contents('http://int.dpool.sina.com.cn/iplookup/iplookup.php?format=json&ip=' . $ip);
    if(empty($res)){ return false; }
    /*$jsonMatches = array();
    preg_match('#\{.+?\}#', $res, $jsonMatches);
    if(!isset($jsonMatches[0])){ return false; }*/
    $json = json_decode($res, true);
    if(isset($json['ret']) && $json['ret'] == 1){
        /*$json['ip'] = $ip;
        unset($json['ret']);*/
        $_location = "";
        if(isset($json['country']) && $json['country'] != ""){
                $_location = $json['country'];
        }
        if($_location != "" && isset($json['province']) && $json['province'] != ""){
                $_location = $_location.'-'.$json['province'];
        }
        if($_location != "" && isset($json['city']) && $json['city'] != ""){
                $_location = $_location.'-'.$json['city'];
        }
        return $_location;
    }else{
        return false;
    }
}
?>
