<?php
require_once 'db.php';	
if(isset($_POST)){
  $group_asin = $_POST['group_asin']; 
}
$mysqli = mws_mysqlConnect();
// $result = $mysqli->query("SELECT * from mws where csv_id in (select id from csv where `group_asin`='".$group_asin."') AND created_on BETWEEN CURDATE() - INTERVAL 1 DAY AND CURDATE()");
$result = $mysqli->query("SELECT * from mws where csv_id in (select id from csv where `group_asin`='".$group_asin."') AND created_on >= DATE_SUB(NOW(),INTERVAL 24 HOUR)");
// $result = $mysqli->query("SELECT * from mws where csv_id in (select id from csv where `group_asin`='".$group_asin."')");
// print_r($result);die;
$temp = array();
$rows = array();
foreach($result as $res) { 
  $rank_arr = [];
  $category_arr = [];
  $category_result = $mysqli->query("SELECT * from category where mws_id ='".$res['id']."'");
  foreach($category_result as $cat_res){
  	$rank_arr[] = $cat_res['rank'];
  	$category_arr[] = $cat_res['category'];
  }
  $rank_output = array_slice($rank_arr, 1);
  $update_rank = implode(",", $rank_output);
  $category_output = array_slice($category_arr, 1);
  $update_category = implode(",", $category_output);
   $temp[$res['product_sku']][] = array('date' => (strtotime($res['created_on']) * 1000), 'rank' => intval($rank_arr[0]), 'price' => intval($res['price']));
 //print_r(strtotime($res['created_on']) * 1000);die; 
}
foreach($temp as $key=>$value){
  $ranks = [];
  $length = count($temp[$key]);
  for ($i = 0; $i < $length; $i++) {
    $ranks[] =  ($temp[$key][$i]['rank']);
  }
  $maximum = max($ranks);
  $average = array_sum($ranks)/count($ranks);
  if(($average * 2) < $maximum){
    unset($maximum);
  }
}
$jsonTable = json_encode($temp);
echo $jsonTable;
?>