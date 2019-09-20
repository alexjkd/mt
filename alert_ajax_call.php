<?php
//database connection setting
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
if (isset($_GET['asin'])) {
$asin = $_GET['asin'];
$select_date_query = $mysqli->query("SELECT asin,price,rank1,rank2,updated FROM mws_us WHERE asin = '".$asin."' AND DATE_FORMAT(`updated`, '%Y-%m-%d %H:%i') = (SELECT MAX(DATE_FORMAT(`updated`, '%Y-%m-%d %H:%i')) as new FROM mws_us WHERE updated >= NOW() - INTERVAL 12 HOUR) UNION SELECT asin,price,rank1,rank2,updated FROM mws_us WHERE asin = '".$asin."' AND DATE_FORMAT(`updated`, '%Y-%m-%d %H:%i') = (SELECT MIN(DATE_FORMAT(`updated`, '%Y-%m-%d %H:%i')) as new FROM mws_us WHERE updated >= NOW() - INTERVAL 12 HOUR)");
	foreach ($select_date_query as $data_query) {
  	   $data_array[$data_query['asin']][] = array('asin' => $data_query['asin'],'rank1' => $data_query['rank1'],'rank2' => $data_query['rank2'],'price' => $data_query['price']);
	}
	$msg = array();
    $data_asin = $data_array[$asin][0]['asin'];    
    $first_rank1 = $data_array[$asin][0]['rank1'];
    $first_rank2 = $data_array[$asin][1]['rank1'];
    $first_rank_percentage = (20 / 100) * $first_rank2;
    $new_first_rank1 = $first_rank_percentage + $first_rank2;
    $new_first_rank2 = $first_rank2 - $first_rank_percentage;
    if($new_first_rank2 >= $first_rank1 || $new_first_rank1 <= $first_rank1) {
	    $more_less = $new_first_rank1 <= $first_rank1 ? "increased" : "decreased";
	    $msg[] = "The rank1 of ".$asin ." ". $more_less." by 20%";
    }

    $second_rank1 = $data_array[$asin][0]['rank2'];
    $second_rank2 = $data_array[$asin][1]['rank2'];
    $second_rank_percentage = (20 / 100) * $second_rank2;
    $new_second_rank1 = $second_rank_percentage + $second_rank2;
    $new_second_rank2 = $second_rank2 - $second_rank_percentage ;
    if($new_second_rank2 >= $second_rank1 || $new_second_rank1 <= $second_rank1) {
	    $rank2_more_less = $new_second_rank1 <= $second_rank1 ? "increased" : "decreased";
	    $msg[] = "The rank2 of ".$asin ." ". $rank2_more_less." by 20%";
	}

    $price1 = $data_array[$asin][0]['price'];
    $price2 = $data_array[$asin][1]['price'];
    $second_price_percentage = (20 / 100) * $price2;
    $new_price1 = $second_price_percentage + $price2;
    $new_price2 = $price2 - $second_price_percentage;
    if($new_price2 >= $price1 || $new_price1 <= $price1) {
        $price_more_less = $new_price1 <= $price1 ? "increased" : "decreased";
        $msg[] = "The price of ".$asin ." ". $price_more_less." by 20%";
    }
    if(empty($msg)){
    	echo json_encode(array('status' => false));
    	exit();
    }
    echo json_encode($msg);
}
?>