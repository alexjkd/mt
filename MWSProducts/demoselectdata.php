<?php
require_once 'db.php';
$mysqli = mws_mysqlConnect();
if (mysqli_connect_errno()) {
printf("Connect failed: %s\n", mysqli_connect_error());
exit();
}
if(empty($_GET) || $_GET['date_range'] == 86400000){
	$query = $mysqli->query("SELECT id, product FROM csv WHERE group_asin = 'B009AEJWZG'");
	$res_array = array();
	$price_array = array();
	$date_array = array();
	$color = array('red', 'black', 'lime', 'olive', 'navy', 'saddle brown', 'purple');
	$i = 0;
	foreach($query as $que){
	    $concat_que = "'".str_replace(',', "','", $que['product'])."'";
	    $result = $mysqli->query("SELECT mws_us.updated AS update_date, mws_us.rid,mws_us.asin, mws_sku.Sku as sku, mws_us.price, mws_us.rank1, mws_us.rank2 FROM mws_us LEFT JOIN mws_sku ON mws_us.sku = mws_sku.ID WHERE mws_us.asin IN (".$concat_que.") AND mws_us.csv_id = ".$que['id']." ORDER BY mws_us.updated DESC LIMIT 100");
	    foreach($result as $res){ 
			$new_date = date('d-m-Y H:00:00', strtotime($res['update_date']));
			$date = strtotime($new_date)*1000;
		  	if(isset($res_array[$res['asin']])){
		  	 array_unshift($res_array[$res['asin']]['data'], array('x'=>$date,'date'=> $date,'y'=>intval(log($res['rank1'])*-1),'rank1' =>intval($res['rank1']),'rank2'=>intval($res['rank2']),'asin2'=>$res['sku'],'price' => floatval($res['price'])));
		  	  array_unshift($price_array[$res['asin']]['data'], array('x'=>$date,'date'=> $date,'y'=>floatval($res['price'])));
		  	}else{
		  	  $res_array[$res['asin']] = array('name' => $res['sku'], 'data' => array(array('x'=>$date,'date'=> $date,'y'=>intval(log($res['rank1'])*-1),'rank1' =>intval($res['rank1']),'rank2'=>intval($res['rank2']),'asin2'=>$res['sku'],'price' => floatval($res['price']))), 'type' => 'line', 'color' => $color[$i]);
		  	  $price_array[$res['asin']] = array('name' => $res['sku'], 'data' => array(array('x'=>$date,'date'=> $date,'y'=>floatval($res['price']))), 'yAxis' => 1, 'type' => 'column', 'showInLegend' => FALSE, 'color' => $color[$i]);
		  	  $i++;
		}
	}
		$res_array = array_values($res_array);
		$price_array = array_values($price_array);
		$res_array = array_merge($res_array, $price_array);
		echo json_encode($res_array);
	}
}
if($_GET['date_range'] == 604800000 && isset($_GET['date_range'])){
	$limit = 7;
}elseif($_GET['date_range'] == 2592000000 && isset($_GET['date_range'])){
	$limit = 31;
}elseif($_GET['date_range'] == 31536000000 && isset($_GET['date_range'])){
	$limit = 12;
}
if(!empty($_GET) && isset($_GET) && $_GET['date_range'] != 86400000 ){
	$query = $mysqli->query("SELECT id, product FROM csv WHERE group_asin = 'B009AEJWZG'");
	$res_array = array();
	$price_array = array();
	$date_array = array();
	$color = array('red', 'black', 'lime', 'olive', 'navy', 'saddle brown', 'purple');
	$i = 0;
	foreach($query as $que){
	    $concat_que = "'".str_replace(',', "','", $que['product'])."'";
	    // $result = $mysqli->query("(SELECT DATE(`updated`) AS update_date, mws_us.rid,mws_us.asin, mws_us.sku, mws_us.price, mws_us.rank1, mws_us.rank2 FROM mws_us WHERE updated IN (SELECT MAX(updated) FROM mws_us WHERE mws_us.asin = ('B00TT9I2PS') GROUP BY DATE(`updated`)) AND mws_us.asin = ('B00TT9I2PS') AND mws_us.csv_id = 4  ORDER BY updated DESC  LIMIT ".$limit.") UNION (SELECT DATE(`updated`) AS update_date, mws_us.rid,mws_us.asin, mws_us.sku, mws_us.price, mws_us.rank1, mws_us.rank2 FROM mws_us WHERE updated IN (SELECT MAX(updated) FROM mws_us WHERE mws_us.asin = ('B00CHYLXLW') GROUP BY DATE(`updated`) ) AND mws_us.asin = ('B00CHYLXLW') AND mws_us.csv_id = 4  ORDER BY updated DESC  LIMIT ".$limit.") UNION (SELECT DATE(`updated`) AS update_date, mws_us.rid,mws_us.asin, mws_us.sku, mws_us.price, mws_us.rank1, mws_us.rank2 FROM mws_us WHERE updated IN (SELECT MAX(updated) FROM mws_us WHERE mws_us.asin = ('B002XISS2Y') GROUP BY DATE(`updated`) ) AND mws_us.asin = ('B002XISS2Y') AND mws_us.csv_id = 4  ORDER BY updated DESC  LIMIT ".$limit.") UNION (SELECT DATE(`updated`) AS update_date, mws_us.rid,mws_us.asin, mws_us.sku, mws_us.price, mws_us.rank1, mws_us.rank2 FROM mws_us WHERE updated IN (SELECT MAX(updated) FROM mws_us WHERE mws_us.asin = ('B009AEJWZG') GROUP BY DATE(`updated`) ) AND mws_us.asin = ('B009AEJWZG') AND mws_us.csv_id = 4  ORDER BY updated DESC  LIMIT ".$limit.")");
	   if($_GET['date_range'] != 31536000000){
	    $result = $mysqli->query("(SELECT DATE(`updated`) AS update_date, mws_us.rid,mws_us.asin, mws_sku.Sku as sku, mws_us.price, mws_us.rank1, mws_us.rank2 FROM mws_us LEFT JOIN mws_sku ON mws_us.sku=mws_sku.ID WHERE updated IN (SELECT MAX(updated) FROM mws_us WHERE mws_us.asin = ('B00TT9I2PS') GROUP BY DATE(`updated`)) AND mws_us.asin = ('B00TT9I2PS') AND mws_us.csv_id = 4 ORDER BY updated DESC LIMIT ".$limit.") UNION (SELECT DATE(`updated`) AS update_date, mws_us.rid,mws_us.asin, mws_sku.Sku as sku, mws_us.price, mws_us.rank1, mws_us.rank2 FROM mws_us LEFT JOIN mws_sku ON mws_us.sku=mws_sku.ID WHERE updated IN (SELECT MAX(updated) FROM mws_us WHERE mws_us.asin = ('B00CHYLXLW') GROUP BY DATE(`updated`)) AND mws_us.asin = ('B00CHYLXLW') AND mws_us.csv_id = 4 ORDER BY updated DESC LIMIT ".$limit.") UNION (SELECT DATE(`updated`) AS update_date, mws_us.rid,mws_us.asin, mws_sku.Sku as sku, mws_us.price, mws_us.rank1, mws_us.rank2 FROM mws_us LEFT JOIN mws_sku ON mws_us.sku=mws_sku.ID WHERE updated IN (SELECT MAX(updated) FROM mws_us WHERE mws_us.asin = ('B002XISS2Y') GROUP BY DATE(`updated`)) AND mws_us.asin = ('B002XISS2Y') AND mws_us.csv_id = 4 ORDER BY updated DESC LIMIT ".$limit.") UNION (SELECT DATE(`updated`) AS update_date, mws_us.rid,mws_us.asin, mws_sku.Sku as sku, mws_us.price, mws_us.rank1, mws_us.rank2 FROM mws_us LEFT JOIN mws_sku ON mws_us.sku=mws_sku.ID WHERE updated IN (SELECT MAX(updated) FROM mws_us WHERE mws_us.asin = ('B009AEJWZG') GROUP BY DATE(`updated`)) AND mws_us.asin = ('B009AEJWZG') AND mws_us.csv_id = 4 ORDER BY updated DESC LIMIT ".$limit.")");
	   }else{
	   	$result = $mysqli->query("(SELECT YEAR(updated), MONTH(updated),DATE(`updated`) AS update_date, mws_us.rid,mws_us.asin, mws_sku.Sku as sku, mws_us.price, mws_us.rank1, mws_us.rank2 FROM mws_us LEFT JOIN mws_sku ON mws_us.sku=mws_sku.ID WHERE updated IN (SELECT MAX(updated) FROM mws_us WHERE mws_us.asin = ('B00TT9I2PS') GROUP BY YEAR(updated), MONTH(updated)) AND mws_us.asin = ('B00TT9I2PS') AND mws_us.csv_id = 4 ORDER BY YEAR(updated),MONTH(updated) DESC LIMIT ".$limit.") UNION (SELECT YEAR(updated), MONTH(updated),DATE(`updated`) AS update_date, mws_us.rid,mws_us.asin, mws_sku.Sku as sku, mws_us.price, mws_us.rank1, mws_us.rank2 FROM mws_us LEFT JOIN mws_sku ON mws_us.sku=mws_sku.ID WHERE updated IN (SELECT MAX(updated) FROM mws_us WHERE mws_us.asin = ('B00CHYLXLW') GROUP BY YEAR(updated), MONTH(updated)) AND mws_us.asin = ('B00CHYLXLW') AND mws_us.csv_id = 4 ORDER BY YEAR(updated),MONTH(updated) DESC LIMIT ".$limit.") UNION (SELECT YEAR(updated), MONTH(updated),DATE(`updated`) AS update_date, mws_us.rid,mws_us.asin, mws_sku.Sku as sku, mws_us.price, mws_us.rank1, mws_us.rank2 FROM mws_us LEFT JOIN mws_sku ON mws_us.sku=mws_sku.ID WHERE updated IN (SELECT MAX(updated) FROM mws_us WHERE mws_us.asin = ('B002XISS2Y') GROUP BY YEAR(updated), MONTH(updated)) AND mws_us.asin = ('B002XISS2Y') AND mws_us.csv_id = 4 ORDER BY YEAR(updated),MONTH(updated) DESC LIMIT ".$limit.") UNION (SELECT YEAR(updated), MONTH(updated),DATE(`updated`) AS update_date, mws_us.rid,mws_us.asin, mws_sku.Sku as sku, mws_us.price, mws_us.rank1, mws_us.rank2 FROM mws_us LEFT JOIN mws_sku ON mws_us.sku=mws_sku.ID WHERE updated IN (SELECT MAX(updated) FROM mws_us WHERE mws_us.asin = ('B009AEJWZG') GROUP BY YEAR(updated), MONTH(updated)) AND mws_us.asin = ('B009AEJWZG') AND mws_us.csv_id = 4 ORDER BY YEAR(updated),MONTH(updated) DESC LIMIT ".$limit.")");
	   }
	    foreach($result as $res){
			$new_date = date('d-m-Y H:00:00', strtotime($res['update_date']));
			$date = strtotime($new_date)*1000;
		  	if(isset($res_array[$res['asin']])){
		  	  array_unshift($res_array[$res['asin']]['data'], array('x'=>$date,'date'=> $date,'y'=>intval(log($res['rank1'])*-1),'rank1' =>intval($res['rank1']),'rank2'=>intval($res['rank2']),'asin2'=>$res['sku'],'price' => floatval($res['price'])));
		  	  array_unshift($price_array[$res['asin']]['data'], array('x'=>$date,'date'=> $date,'y'=>floatval($res['price'])));
		  	}else{
		  	  $res_array[$res['asin']] = array('name' => $res['sku'], 'data' => array(array('x'=>$date,'date'=> $date,'y'=>intval(log($res['rank1'])*-1),'rank1' =>intval($res['rank1']),'rank2'=>intval($res['rank2']),'asin2'=>$res['sku'],'price' => floatval($res['price']))), 'type' => 'line', 'color' => $color[$i]);
		  	  $price_array[$res['asin']] = array('name' => $res['sku'], 'data' => array(array('x'=>$date,'date'=> $date,'y'=>floatval($res['price']))), 'yAxis' => 1, 'type' => 'column', 'showInLegend' => FALSE, 'color' => $color[$i]);
		  	  $i++;
		}
	}
		$res_array = array_values($res_array);
		$price_array = array_values($price_array);
		$res_array = array_merge($res_array, $price_array);
		echo json_encode($res_array);
	}
}
?>
