<?php
    header("Content-type:text/html;charset=utf-8");
    $connect = mysqli_connect('10.10.51.14','root','tvmining@123','hqsx-statis') or die ('Link database failed');
    mysqli_query($connect,'SET NAMES utf8');
    $date = date("Y-m-d");
    $thatDate = @$_REQUEST['date'] ? $_REQUEST['date'] : $date;//2015-08-17
    //今日关键字搜索、guid搜索、独立ip搜索的次数
    $today_nums_sql = "SELECT keyword_nums, k_source_count, guid_nums, g_source_count, ip_nums, flip_count, flip_count_true FROM today_statis WHERE statis_date = '".$thatDate."'"; 
    $today_nums_query = mysqli_query($connect, $today_nums_sql); 
    $today_nums_result = mysqli_fetch_assoc($today_nums_query);
    mysqli_free_result($today_nums_query);
    if(empty($today_nums_result)){
      echo json_encode(array("code" => '0', "msg" => "没有查询到".$thatDate."的统计结果"));exit;
    }
    $today_nums_result['k_source_count'] = getSource($today_nums_result['k_source_count']);
    $today_nums_result['g_source_count'] = getSource($today_nums_result['g_source_count']);
    
    //搜索前10的关键字
    $keyword_top_sql = "SELECT keyword, num, source_count FROM keyword_top_10 WHERE statis_date = '".$thatDate."' ORDER BY num DESC";
    $keyword_top_query = mysqli_query($connect, $keyword_top_sql); 
    while($row = mysqli_fetch_assoc($keyword_top_query)){
      $row['source_count'] = getSource($row['source_count']);
      $keyword_top_result[] = $row;
    }
    mysqli_free_result($keyword_top_query);

    //搜索关键字次数排名前10的ip
    $k_ip_top_sql = "SELECT ip, keyword, num FROM keyword_top_10_ip WHERE statis_date = '".$thatDate."' ORDER BY num DESC";
    $k_ip_top_query = mysqli_query($connect, $k_ip_top_sql); 
    while($row = mysqli_fetch_assoc($k_ip_top_query)){
      $k_ip_top_result[] = $row;
    }
    mysqli_free_result($k_ip_top_query);

    //搜索关键字翻页次数排名前10的ip
    $k_flip_top_sql = "SELECT ip, keyword, start_page FROM keyword_flip_top_10 WHERE statis_date = '".$thatDate."' ORDER BY start_page DESC";
    $k_flip_top_query = mysqli_query($connect, $k_flip_top_sql); 
    while($row = mysqli_fetch_assoc($k_flip_top_query)){
      $k_flip_top_result[] = $row;
    }
    mysqli_free_result($k_flip_top_query);

    //搜索前10的guid
    $id_top_sql = "SELECT title, guid, num, source_count FROM id_top_10 WHERE statis_date = '".$thatDate."' ORDER BY num DESC"; 
    $id_top_query = mysqli_query($connect, $id_top_sql); 
     while($row = mysqli_fetch_assoc($id_top_query)){
      $row['source_count'] = getSource($row['source_count']);
      $id_top_result[] = $row;
    }
    mysqli_free_result($id_top_query);

    //查看guid次数排名前10的ip
    $id_ip_top_sql = "SELECT ip, num FROM id_top_10_ip WHERE statis_date = '".$thatDate."' ORDER BY num DESC"; 
    $id_ip_top_query = mysqli_query($connect, $id_ip_top_sql);  
     while($row = mysqli_fetch_assoc($id_ip_top_query)){
      $id_ip_top_result[] = $row;
    }
    mysqli_free_result($id_ip_top_query);

    //搜过关键字+查看guid次数排名前30的ip
    $ip_top_sql = "SELECT ip, keyword, guid, num FROM ip_top_30 WHERE statis_date = '".$thatDate."' ORDER BY num DESC"; 
    $ip_top_query = mysqli_query($connect, $ip_top_sql);
    while($row = mysqli_fetch_assoc($ip_top_query)){
        $row['guid'] = unserialize($row['guid']);
        $ip_top_result[] = $row;
    }
    mysqli_free_result($ip_top_query);

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
function getSource($source = ''){
  if(!is_string($source)){
    return false;
  }
  $string = "";
  $sourceArr = explode(",", $source);
  foreach($sourceArr as $k => $v){
    $source = explode(":", $v);
    switch ($source[0]) {
      case '1':
        $string .= 'ios:'.$source[1].',';
        break;
      case '2':
        $string .= 'android:'.$source[1].',';
        break;
      case '3':
        $string .= 'wechat:'.$source[1].',';
        break;
      case '4':
        $string .= 'weibo:'.$source[1].',';
        break;
      case '5':
        $string .= 'qq:'.$source[1].',';
        break;
      case '6':
        $string .= 'other:'.$source[1].',';
        break;
      default:
        break;
    } 
  }
  return substr($string, 0, -1);
}
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8">
    <meta name="Keywords" content="">
    <meta name="Description" content="">
    <title></title>
    <link rel="stylesheet" type="text/css" href="css.css" />
    <style>
    body{ background:#fff; font-size:16px; font-family:"Microsoft YaHei"}
    .main{ width:900px; height:auto; margin: 0 auto; border:2px solid #ccc;}
    .main .title{ text-align: center; font-size: 24px; margin:30px auto;}
    .main table{ width:800px; background: #f9eee8; margin: 0 auto; border-collapse:collapse; border-spacing: 0; text-align: center}
    table tr{ height:30px; line-height: 30px;}
    table td{ width:300px; border: 1px solid #fff;}
    table .text_title{ text-align: left; padding:5px 0 5px 20px;}
    table .keyword{ font-size: 14px;}
    table .wordleft{ text-align: left; padding-left:20px;}
    table .nosearch{ height:150px;}
    .foot{ text-align: center; margin:20px 0;}
    </style>
 </head>
 <body>
  <div class="main">
    <div>
      <div class="title"><?php echo $thatDate;?>环球视讯数据统计</div>
      <table>
          <tr>
            <td colspan="2" class="text_title">今日独立ip搜索查询的次数</td><td  colspan="2"><?php if(isset($today_nums_result['ip_nums'])){ echo $today_nums_result['ip_nums'];}else{ echo "无";}?></td>
          </tr>
          <tr bgcolor="#fff">
            <td colspan="4"  class=""></td>
          </tr>
          <tr>
            <td  colspan="2" class="text_title">今日执行关键词搜索查询的次数 <span style="color:#999"><?php if(isset($today_nums_result['k_source_count'])){ echo $today_nums_result['k_source_count'];}?></span></td><td  colspan="2"><?php if(isset($today_nums_result['keyword_nums'])){ echo $today_nums_result['keyword_nums'];}else{ echo "无";}?></td>
          </tr>
          <tr bgcolor="#fff">
            <td colspan="4"  class=""></td>
          </tr>
          <tr>
            <td colspan="4" class="text_title">今日搜索次数排名前十的关键词及次数</td>
          </tr>
          <?php $i = 0;if(!empty($keyword_top_result)){ foreach($keyword_top_result as $k => $v){?>
          <tr class="keyword">
            <td  colspan="2" class="wordleft"><?=$v['keyword']?> <span style="color:#999"><?=$v['source_count']?></span></td><td  colspan="2"><?=$v['num']?></td>
          </tr>
          <?php $i++;}}else{?>
          <tr>
            <td colspan="4"  class="keyword nosearch">没有相关搜索</td>
          </tr>
          <?php }?>
          <?php if(!empty($keyword_top_result) && $i<10){ for($j=0; $j<(10-$i); $j++){?>
          <tr>
            <td></td><td></td><td></td><td></td>
          </tr>
          <?php }}?>
          <tr bgcolor="#fff">
            <td colspan="4"  class=""></td>
          </tr>
          <tr>
            <td  colspan="2" class="text_title">今日搜索关键词总翻页次数及有效次数</td><td><?php if(isset($today_nums_result['flip_count'])){ echo $today_nums_result['flip_count'];}else{ echo "无";}?></td><td><?php if(isset($today_nums_result['flip_count_true'])){ echo $today_nums_result['flip_count_true'];}else{ echo "无";}?></td>
          </tr>
          <tr bgcolor="#fff">
            <td colspan="4"  class=""></td>
          </tr>
          <tr>
            <td colspan="4" class="text_title">今日搜索关键字翻页次数排名前10的ip</td>
          </tr>
          <?php $i = 0;if(!empty($k_flip_top_result)){ foreach($k_flip_top_result as $k => $v){?>
          <tr class="keyword">
            <td class="wordleft"><?=$v['ip']?></td><td colspan="2"><?=$v['keyword']?></td><td><?=$v['start_page']?></td>
          </tr>
          <?php $i++;}}else{?>
          <tr>
            <td colspan="4"  class="keyword nosearch">没有相关搜索</td>
          </tr>
          <?php }?>
          <?php if(!empty($k_flip_top_result) && $i<10){ for($j=0; $j<(10-$i); $j++){?>
          <tr>
            <td></td><td></td><td></td><td></td>
          </tr>
          <?php }}?>
          <tr bgcolor="#fff">
            <td colspan="4"  class=""></td>
          </tr>
          <tr>
            <td colspan="4" class="text_title">今日搜索关键字次数排名前10的ip</td>
          </tr>
          <?php $i = 0;if(!empty($k_ip_top_result)){ foreach($k_ip_top_result as $k => $v){?>
          <tr class="keyword">
            <td class="wordleft"><?=$v['ip']?></td><td colspan="2"><?=$v['keyword']?></td><td><?=$v['num']?></td>
          </tr>
          <?php $i++;}}else{?>
          <tr>
            <td colspan="4"  class="keyword nosearch">没有相关搜索</td>
          </tr>
          <?php }?>
          <?php if(!empty($k_ip_top_result) && $i<10){ for($j=0; $j<(10-$i); $j++){?>
          <tr>
            <td></td><td></td><td></td><td></td>
          </tr>
          <?php }}?>
          <tr bgcolor="#fff">
            <td colspan="4"  class=""></td>
          </tr>
          <tr>
            <td  colspan="2" class="text_title">今日执行相关搜索查询的次数 <span style="color:#999"><?php if(isset($today_nums_result['g_source_count'])){ echo $today_nums_result['g_source_count'];}?></span></td><td  colspan="2"><?php if(isset($today_nums_result['guid_nums'])){ echo $today_nums_result['guid_nums'];}else{ echo "无";}?></td>
          </tr>
          <tr bgcolor="#fff">
            <td colspan="4"  class=""></td>
          </tr>
          <tr>
            <td colspan="4" class="text_title">今日相关搜索查询排名前十的GUID及次数</td>
          </tr>
          <?php $i = 0;if(!empty($id_top_result)){ foreach($id_top_result as $k => $v){?>
          <tr class="keyword">
            <td  colspan="2"  class="wordleft"><a href="http://web.newsapp.cibntv.net/app/play/?id=<?=$v['guid']?>"><?=$v['title']?></a> <span style="color:#999"><?=$v['source_count']?></td><td  colspan="2"><?=$v['num']?></td>
          </tr>
          <?php $i++;}}else{?>
          <tr>
            <td colspan="4"  class="keyword nosearch">没有相关搜索</td>
          </tr>
          <?php }?>
          <?php if(!empty($data['id_top_10']) && $i<10){ for($j=0; $j<(10-$i); $j++){?>
          <tr>
            <td></td><td></td><td></td><td></td>
          </tr>
          <?php }}?>
          <tr bgcolor="#fff">
            <td colspan="4"  class=""></td>
          </tr>
          <tr>
            <td colspan="4" class="text_title">今日查看guid次数排名前10的ip</td>
          </tr>
          <?php $i = 0;if(!empty($id_ip_top_result)){ foreach($id_ip_top_result as $k => $v){?>
          <tr class="keyword">
            <td colspan="2" class="wordleft"><?=$v['ip']?></td><td colspan="2"><?=$v['num']?></td>
          </tr>
          <?php $i++;}}else{?>
          <tr>
            <td colspan="4"  class="keyword nosearch">没有相关搜索</td>
          </tr>
          <?php }?>
          <?php if(!empty($id_ip_top_result) && $i<10){ for($j=0; $j<(10-$i); $j++){?>
          <tr>
            <td></td><td></td>
          </tr>
          <?php }}?>
          <tr bgcolor="#fff">
            <td colspan="4"  class=""></td>
          </tr>
          <tr>
            <td colspan="4" class="text_title">今日搜索关键字+查看guid次数排名前10的ip</td>
          </tr>
          <?php $i = 0;if(!empty($ip_top_result)){ foreach($ip_top_result as $k => $v){?>
          <tr class="keyword">
            <td class="wordleft"><?=$v['ip']?></td>
            <td><?=$v['keyword']?></td>
            <td><ul>
                    <?php foreach($v['guid'] as $key => $value){ ?>
                    <li style='list-style:none;'><?=$key+1;?>. <a href="http://web.newsapp.cibntv.net/app/play/?id=<?=$value['guid']?>"><?=$value['title']?></a></li>
                    <?php }?>
                    </ul>
            </td>
            <td><?=$v['num']?></td>
          </tr>
          <?php $i++;}}else{?>
          <tr>
            <td colspan="4"  class="keyword nosearch">没有相关搜索</td>
          </tr>
          <?php }?>
          <?php if(!empty($ip_top_result) && $i<10){ for($j=0; $j<(10-$i); $j++){?>
          <tr>
            <td></td><td></td><td></td><td></td>
          </tr>
          <?php }}?>
      </table>
    </div>
    <div class="foot"><div class="foot_con">天脉聚源(北京)传媒科技有限公司&nbsp;&nbsp;版权所有</div></div>
  </div>
 </body>
</html>

