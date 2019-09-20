<?php 

require_once '../db.php';

$mysqli = new mysqli($dbHost, $dbUser, $dbPwd, $dbName);
// Check connection
if (!$mysqli) {
    die("Connection failed: " . mysqli_connect_error());
}
if (mysqli_connect_errno()) {
printf("Connect failed: %s\n", mysqli_connect_error());
exit();
}
if($_GET['date_range'] == 604800000 && isset($_GET['date_range'])){
  $limit = 7;
}elseif($_GET['date_range'] == 2592000000 && isset($_GET['date_range'])){
  $limit = 31;
}elseif($_GET['date_range'] == 31536000000 && isset($_GET['date_range'])){
  $limit = 12;
}
if(isset($_GET['asin']) && isset($_GET['date_range']) && $_GET['date_range'] != 86400000){
  $region = $_GET['region'];
  $asin = $_GET['asin'];
  $query = $mysqli->query("SELECT id, product FROM csv WHERE group_asin = '".$asin."'");
  $res_array = array();
  $price_array = array();
  $date_array = array();
  $color = array('red', 'black', 'lime', 'olive', 'navy', 'saddle brown', 'purple');
  $i = 0;
  foreach($query as $que){
    $comp_prods = explode(',', $que['product']);
      // $count_length = count($comp_prod);
    foreach ($comp_prods as $comp_prod) {
      if($_GET['date_range'] != 31536000000){
        $month_result .= "(SELECT DATE(`updated`) AS update_date, mws_$region.rid,mws_$region.asin, mws_sku.Sku as sku, mws_$region.price, mws_$region.rank1, mws_$region.rank2 FROM mws_$region LEFT JOIN mws_sku ON mws_$region.sku=mws_sku.ID WHERE updated IN (SELECT MAX(updated) FROM mws_$region WHERE mws_$region.asin = ('".$comp_prod."') GROUP BY DATE(`updated`)) AND mws_$region.asin = ('".$comp_prod."') AND mws_$region.csv_id = ".$que['id']." ORDER BY updated DESC LIMIT ".$limit.") UNION ";
      }else{
        $year_result .= "(SELECT YEAR(updated), MONTH(updated),DATE(`updated`) AS update_date, mws_$region.rid,mws_$region.asin, mws_sku.Sku as sku, mws_$region.price, mws_$region.rank1, mws_$region.rank2 FROM mws_$region LEFT JOIN mws_sku ON mws_$region.sku=mws_sku.ID WHERE updated IN (SELECT MAX(updated) FROM mws_$region WHERE mws_$region.asin = ('".$comp_prod."') GROUP BY YEAR(updated), MONTH(updated)) AND mws_$region.asin = ('".$comp_prod."') AND mws_$region.csv_id = ".$que['id']." ORDER BY YEAR(updated),MONTH(updated) DESC LIMIT ".$limit.") UNION ";
      }
    }
    if($_GET['date_range'] == 31536000000){
      $yresult = preg_replace(strrev("/UNION/"),strrev(''),strrev($year_result),1);
      $y_res = strrev($yresult);
      $result = $mysqli->query($y_res);
    }else{
      $mresult = preg_replace(strrev("/UNION/"),strrev(''),strrev($month_result),1);
      $m_res = strrev($mresult);
      $result = $mysqli->query($m_res);
    }

      foreach($result as $res){
      $new_date = date('d-m-Y H:00:00', strtotime($res['update_date']));
      $date = strtotime($new_date)*1000;
        if(isset($res_array[$res['asin']])){
          if (empty($res['sku'])) {
            $res['sku'] = $res['asin'];
          }
          array_unshift($res_array[$res['asin']]['data'], array('x'=>$date,'date'=> $date,'y'=>intval(log($res['rank1'])*-1),'rank1' =>intval($res['rank1']),'rank2'=>intval($res['rank2']),'asin2'=>$res['sku'],'price' => floatval($res['price'])));
          array_unshift($price_array[$res['asin']]['data'], array('x'=>$date,'date'=> $date,'y'=>floatval($res['price'])));
        }else{
          if (empty($res['sku'])) {
            $res['sku'] = $res['asin'];
          }
          $res_array[$res['asin']] = array('name' => $res['sku'], 'data' => array(array('x'=>$date,'date'=> $date,'y'=>intval(log($res['rank1'])*-1),'rank1' =>intval($res['rank1']),'rank2'=>intval($res['rank2']),'asin2'=>$res['sku'],'price' => floatval($res['price']))), 'type' => 'line', 'color' => $color[$i]);
          $price_array[$res['asin']] = array('name' => $res['sku'], 'data' => array(array('x'=>$date,'date'=> $date,'y'=>floatval($res['price']))), 'yAxis' => 1, 'type' => 'column', 'showInLegend' => FALSE, 'color' => $color[$i]);
          $i++;
    }
  }
    $res_array = array_values($res_array);
    $price_array = array_values($price_array);
    $res_array = array_merge($res_array, $price_array);
    echo json_encode($res_array);die;
  }
}
if (isset($_GET['asin']) || $_GET['date_range'] == 86400000) {
  $region = $_GET['region'];
  $asin = $_GET['asin'];
  $group_asin = array();
  $query = $mysqli->query("SELECT id, product FROM csv WHERE group_asin = '".$asin."'");
  $res_array = array();
  $price_array = array();
  $date_array = array();
  $all_data_array = array();
  $color = array('red', 'black', 'lime', 'olive', 'navy', 'saddle brown', 'purple');
  $i = 0;
  foreach($query as $que){
    $concat_que = "'".str_replace(',', "','", $que['product'])."'";
    $result = $mysqli->query("SELECT mws_$region.updated AS update_date, mws_$region.rid,mws_$region.asin, mws_sku.Sku as sku, mws_$region.price, mws_$region.rank1, mws_$region.rank2 FROM mws_$region LEFT JOIN mws_sku ON mws_$region.sku = mws_sku.ID WHERE mws_$region.asin IN (".$concat_que.") AND mws_$region.csv_id = ".$que['id']." ORDER BY mws_$region.updated DESC LIMIT 100");
    foreach($result as $res){ 
      $new_date = date('d-m-Y H:00:00', strtotime($res['update_date']));
	  $date = strtotime($new_date)*1000;
	  if(isset($res_array[$res['asin']])){
      if (empty($res['sku'])) {
         $res['sku'] = $res['asin'];
      }
	    array_unshift($res_array[$res['asin']]['data'], array('x'=>$date,'date'=> $date,'y'=>intval(log($res['rank1'])*-1),'rank1' =>intval($res['rank1']),'rank2'=>intval($res['rank2']),'asin2'=>$res['sku'],'price' => floatval($res['price'])));
	    array_unshift($price_array[$res['asin']]['data'], array('x'=>$date,'date'=> $date,'y'=>floatval($res['price'])));
	  }else{
      if (empty($res['sku'])) {
        $res['sku'] = $res['asin'];
      }
	    $res_array[$res['asin']] = array('name' => $res['sku'], 'data' => array(array('x'=>$date,'date'=> $date,'y'=>intval(log($res['rank1'])*-1),'rank1' =>intval($res['rank1']),'rank2'=>intval($res['rank2']),'asin2'=>$res['sku'],'price' => floatval($res['price']))), 'type' => 'line', 'color' => $color[$i]);
	    $price_array[$res['asin']] = array('name' => $res['sku'], 'data' => array(array('x'=>$date,'date'=> $date,'y'=>floatval($res['price']))), 'yAxis' => 1, 'type' => 'column', 'showInLegend' => FALSE, 'color' => $color[$i]);
	    $i++;
	  }
	}
	$res_array = array_values($res_array);
	$price_array = array_values($price_array);
	$res_array = array_merge($res_array, $price_array);
	echo json_encode($res_array);die;
  }
}