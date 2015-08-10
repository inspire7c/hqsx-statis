<?php
    header("Content-type:text/html;charset=utf-8");
    /*$httpurl = 'http://'.$_SERVER['HTTP_HOST'].'/index.php';
    $data = file_get_contents($httpurl);
    if(empty($data)){
      echo json_encode(array('code' => 0, 'msg' => '获取数据失败'));
    }
    $data = json_decode($data, true);*/
    $connect = mysqli_connect('localhost','root','123','hqsx-statis') or die ('Unale to connect');
    mysqli_query($connect,'SET NAMES utf8');
    $date = date("Y-m-d");
    $keyword_nums_sql = "SELECT SUM(num) FROM keyword_search_nums WHERE DATE_FORMAT(created,'%Y-%m-%d') = '".$date."'"; 
    $keyword_nums_query = mysqli_query($connect, $keyword_nums_sql); 
    $keyword_nums_result = mysqli_fetch_row($keyword_nums_query);
    

 
    $keyword_top_sql = "SELECT keyword, SUM(num) AS num FROM keyword_top_10 WHERE DATE_FORMAT(created,'%Y-%m-%d') = '".$date."' GROUP BY keyword";
    $keyword_top_query = mysqli_query($connect, $keyword_top_sql); 
    while($row = mysqli_fetch_assoc($keyword_top_query)){
      $keyword_top_result[] = $row;
    }
    /*foreach($keyword_top_result as $k => $v){
      $result[$v['keyword']][] = $v['num'];
    }*/
    $keyword_top_result = arr_sort($keyword_top_result, 'num', 'desc');
    $keyword_top_result = array_slice($keyword_top_result, 0, 10);
    /*echo "<pre>";
    print_r($keyword_top_result);exit;*/


    $id_nums_sql = "SELECT SUM(num) FROM id_search_nums WHERE DATE_FORMAT(created,'%Y-%m-%d') = '".$date."'"; 
    $id_nums_query = mysqli_query($connect, $id_nums_sql);  
    $id_nums_result = mysqli_fetch_row($id_nums_query);
    //print_r($id_nums_result);exit;
    
    $id_top_sql = "SELECT guid, SUM(num) AS num FROM id_top_10 WHERE DATE_FORMAT(created,'%Y-%m-%d') = '".$date."' GROUP BY guid"; 
    $id_top_query = mysqli_query($connect, $id_top_sql); 
     while($row = mysqli_fetch_assoc($id_top_query)){
      $id_top_result[] = $row;
    }
    $id_top_result = arr_sort($id_top_result, 'num', 'desc');
    $id_top_result = array_slice($id_top_result, 0, 10);
    foreach($id_top_result as  $k => $v){
      $id_top_result[$k]['title'] = getTitleByguid($v['guid']);
    }
    /*echo "<pre>";
    print_r($id_top_result);exit;*/

function arr_sort($array, $key, $order="asc"){//asc是升序 desc是降序
  $arr_nums = $arr = array();
  foreach($array as $k => $v){
    $arr_nums[$k] = $v[$key];
  }
  if($order == 'asc'){
    asort($arr_nums);
  }else{
    arsort($arr_nums);
  }
  foreach($arr_nums as $k => $v){
    $arr[$k] = $array[$k];
  }
  return $arr;
}

function getTitleByguid($guid){
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
  
  /*echo $title;
  echo "<pre>";
  print_r($arr_data);exit;*/
  return $title;
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
      <div class="title"><?php echo date("Y-m-d");?>环球视讯数据统计</div>
      <table>
          <tr>
            <td class="text_title">今日执行关键词搜索查询的次数</td><td><?php if(isset($keyword_nums_result[0])){ echo $keyword_nums_result[0];}else{ echo "无";}?></td>
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
            <td class="text_title">相关搜索查询的次数</td><td><?php if(isset($id_nums_result[0])){ echo $id_nums_result[0];}else{ echo "无";}?></td>
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

