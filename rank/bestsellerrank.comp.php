<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<?php
ini_set("error_log", basename(__FILE__,'php') . 'error.log');
include_once('../lib/functions.php');
// define(EMAIL_RECEPIENTS,'sales@ispringfilter.com,John@iSpringFilter.com');
define(EMAIL_RECEPIENTS,'John@iSpringFilter.com');
$asin_sku_array = get_asin_sku_array();
$asin = isset($_GET['asin']) ? $_GET['asin'] : '';
$sku = $asin_sku_array[$asin];
?>
<title><?php echo $sku; ?> Best Seller Rank Tracker - Amazon</title>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<link rel="icon" href="../lib/images/Iconicon-Alpha-Magnets-Letter-ico" >
<link href="../lib/sortable/sortable_table.css" media="screen" rel="stylesheet" type="text/css" />
<link href="../lib/TableFilter/filtergrid.css" media="screen" rel="stylesheet" type="text/css" />
<script type="text/javascript" language="javascript" src="../lib/sortable/sorttable.js"></script>
<script type="text/javascript" language="javascript" src="../lib/TableFilter/tablefilter.js"></script>
</head><body>
<?php
if (strlen($asin) == 10 ) {showProduct($asin); exit;}

function showProduct($asin) {
	global $asin_sku_array;
	// $days = date('Ymd', strtotime('today - 7 days'));;
	//~ $days = date('Ymd') - 30;
	$days = 0;
	$sku = $asin_sku_array[$asin];
	//~ $sku = asin_sku_conversion($asin);
	$q = "select date_format(from_unixtime(a.time),'%m.%d.%a_%H') as hour from asin_us_numbers where asin = '$asin' and time > $days order by hour desc limit 1000";
	$rows = sqlquery($q);
 	$headers = 'asin,sku,date,hour,rank1,rank2,category';
	$table_header = '<tr><th>'. str_ireplace(',','</th><th>',$headers).'</th></tr>';
	$headers_array = explode(',',$headers);
	$arrays=array();
	$n = 0;
	foreach($rows as $row) {
		$date = $row['date'];
		$date = preg_replace('/(\d{4})(\d{2})(\d{2})/','\2-\3',$date);
		$hour = $row['hour'];
		// $hour = $hour < 13 ? $hour : $hour - 12;
		$rank1 = $row['rank1'];
		$rank2 = $row['rank2'];
		if (empty($rank1) or empty($rank2)) continue;
		$category = $row['category'];
		$tablebody .= "<tr><td><a target=_blank href=http://www.amazon.com/dp/$asin>$asin</a></td><td>$sku</td><td>$date</td><td>$hour</td><td>$rank1</td><td>$rank2</td><td>$category</td></tr>";
		$array2drank1[] = array($date .' '. $hour,0 - $rank1);
		$array2drank2[] = array($date .' '. $hour,0 - $rank2);
		//~ if ($n % 128 < 10) array_push($arrays,$array2drank1,$array2drank2);
		//~ $n++;
	}
	//~ foreach ($arrays as $array) {
		//~ drawchart($array),$sku .'   '. $category,'date','rank',1600,250);
	//~ }
	drawchart($array2drank1,$sku .'   '. $category,'date','rank1',count($array2drank1)*16,250);
	drawchart($array2drank2,$sku .'   '. $category,'date','rank2',count($array2drank1)*16,250);
	echo '<table id="table1" class="sortable  filterable" width="100%">' . $table_header . $tablebody . "</table>";
	include_once('../lib/TableFilter/TableFilterConfig_ReviewMonitor.js');
}

$headers = 'asin,sku,date,rank1,rank2,records,category';
$table_header = '<tr><th>'. str_ireplace(',','</th><th>',$headers).'</th></tr>';
echo '<table id="table1" class="sortable  filterable" width="100%">' . $table_header;
$headers_array = explode(',',$headers);
$q = "select asin,date,round(avg(rank1),2) as rank1,round(avg(rank2),2) as rank2,count(asin) as records,category from bestsellerrank_comp group by asin order by rank1,rank2";
$rows = sqlquery($q);
foreach($rows as $row) {
	$bgcolor = '#fff'. stringToColorCode(substr($row['asin'],2,7));
	echo "<tr bgcolor=$bgcolor>";
/* 	foreach($headers_array as $header) {
		if ($header == 'asin') {
			echo "<td><a target=_blank href=http://www.amazon.com/dp/$row[$header]>$row[$header]</a></td>";
		} else {
			echo "<td>$row[$header]</td>";
		}
	}
	echo "</tr>";
	continue;
 */
	$asin = trim($row['asin']);
	$sku = $asin_sku_array[$asin];
	//~ $sku = asin_sku_conversion($asin);
	$date = $row['date'];
	$rank1 = $row['rank1'];
	$rank2 = $row['rank2'];
	$category = $row['category'];
	// $bgcolor = '#EFF8FB';
	// $bgcolor = '#fff'. stringToColorCode(substr($asin,2,7));
	if ($rank2 == 1) $bgcolor = 'white';
	$records = $row['records'];
	echo "<tr bgcolor=$bgcolor><td><a target=_blank href=http://www.amazon.com/dp/$asin>$asin</a></td><td><a href=bestsellerrank.comp.php?asin=$asin>$sku</a></td><td>$date</td><td>$rank1</td><td>$rank2</td><td>$records</td><td>$category</td></tr>";
}
echo "</table>";
include_once('../lib/TableFilter/TableFilterConfig_ReviewMonitor.js');

?>
</body></html>