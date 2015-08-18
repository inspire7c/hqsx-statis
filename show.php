<?php
    header("Content-type:text/html;charset=utf-8");
    $connect = mysqli_connect('10.10.51.14','root','tvmining@123','hqsx-statis') or die ('Link database failed');
    mysqli_query($connect,'SET NAMES utf8');
    $date = date("Y-m-d");
    $thatDate = @$_REQUEST['date'] ? $_REQUEST['date'] : $date;//2015-08-17
    //今日关键字搜索、guid搜索、独立ip搜索的次数
    $today_nums_sql = "SELECT keyword_nums, guid_nums, ip_nums FROM today_statis WHERE statis_date = '".$thatDate."'"; 
    $today_nums_query = mysqli_query($connect, $today_nums_sql); 
    $today_nums_result = mysqli_fetch_assoc($today_nums_query);
    mysqli_free_result($today_nums_query);
    if(empty($today_nums_result)){
      echo json_encode(array("code" => '0', "msg" => "没有查询到".$thatDate."的统计结果"));exit;
    }

    //搜索前10的关键字
    $keyword_top_sql = "SELECT keyword, num FROM keyword_top_10 WHERE statis_date = '".$thatDate."' ORDER BY num DESC";
    $keyword_top_query = mysqli_query($connect, $keyword_top_sql); 
    while($row = mysqli_fetch_assoc($keyword_top_query)){
      $keyword_top_result[] = $row;
    }
    mysqli_free_result($keyword_top_query);

    //搜索关键字前10的ip
    $k_ip_top_sql = "SELECT ip, num FROM keyword_top_10_ip WHERE statis_date = '".$thatDate."' ORDER BY num DESC";
    $k_ip_top_query = mysqli_query($connect, $k_ip_top_sql); 
    while($row = mysqli_fetch_assoc($k_ip_top_query)){
      $k_ip_top_result[] = $row;
    }
    mysqli_free_result($k_ip_top_query);

    //搜索前10的guid
    $id_top_sql = "SELECT title, guid, num FROM id_top_10 WHERE statis_date = '".$thatDate."' ORDER BY num DESC"; 
    $id_top_query = mysqli_query($connect, $id_top_sql); 
     while($row = mysqli_fetch_assoc($id_top_query)){
      $id_top_result[] = $row;
    }
    mysqli_free_result($id_top_query);

    //搜索guid前10的ip
    $id_ip_top_sql = "SELECT ip, num FROM id_top_10_ip WHERE statis_date = '".$thatDate."' ORDER BY num DESC"; 
    $id_ip_top_query = mysqli_query($connect, $id_ip_top_sql);  
     while($row = mysqli_fetch_assoc($id_ip_top_query)){
      $id_ip_top_result[] = $row;
    }
    mysqli_free_result($id_ip_top_query);
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
            <td class="text_title">今日独立ip搜索查询的次数</td><td><?php if(isset($today_nums_result['ip_nums'])){ echo $today_nums_result['ip_nums'];}else{ echo "无";}?></td>
          </tr>
          <tr>
            <td class="text_title">今日执行关键词搜索查询的次数</td><td><?php if(isset($today_nums_result['keyword_nums'])){ echo $today_nums_result['keyword_nums'];}else{ echo "无";}?></td>
          </tr>
          <tr>
            <td colspan="2" class="text_title">今日搜索次数排名前十的关键词及次数</td>
          </tr>
          <?php $i = 0;if(!empty($keyword_top_result)){ foreach($keyword_top_result as $k => $v){?>
          <tr class="keyword">
            <td class="wordleft"><?=$v['keyword']?></td><td><?=$v['num']?></td>
          </tr>
          <?php $i++;}}else{?>
          <tr>
            <td colspan="2"  class="keyword nosearch">没有相关搜索</td>
          </tr>
          <?php }?>
          <?php if(!empty($keyword_top_result) && $i<10){ for($j=0; $j<(10-$i); $j++){?>
          <tr>
            <td></td><td></td>
          </tr>
          <?php }}?>
          <tr>
            <td class="text_title">今日执行相关搜索查询的次数</td><td><?php if(isset($today_nums_result['guid_nums'])){ echo $today_nums_result['guid_nums'];}else{ echo "无";}?></td>
          </tr>
          <tr>
            <td colspan="2" class="text_title">今日相关搜索查询排名前十的GUID及次数</td>
          </tr>
          <?php $m = 0;if(!empty($id_top_result)){ foreach($id_top_result as $k => $v){?>
          <tr class="keyword">
            <td  class="wordleft"><a href="http://web.newsapp.cibntv.net/app/play/?id=<?=$v['guid']?>"><?=$v['title']?></a></td><td><?=$v['num']?></td>
          </tr>
          <?php $m++;}}else{?>
          <tr>
            <td colspan="2"  class="keyword nosearch">没有相关搜索</td>
          </tr>
          <?php }?>
          <?php if(!empty($data['id_top_10']) && $m<10){ for($n=0; $n<(10-$m); $n++){?>
          <tr>
            <td></td><td></td>
          </tr>
          <?php }}?>
      </table>
    </div>
    <div class="foot"><div class="foot_con">天脉聚源(北京)传媒科技有限公司&nbsp;&nbsp;版权所有</div></div>
  </div>
 </body>
</html>

