<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>iSpring Best Seller Rank Tracker - Amazon</title>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<link rel="icon" href="../lib/images/Iconicon-Alpha-Magnets-Letter-b.ico" >
<link href="../lib/sortable/sortable_table.css" media="screen" rel="stylesheet" type="text/css" />
<link href="../lib/TableFilter/filtergrid.css" media="screen" rel="stylesheet" type="text/css" />
<script type="text/javascript" language="javascript" src="../lib/sortable/sorttable.js"></script>
<script type="text/javascript" language="javascript" src="../lib/TableFilter/tablefilter.js"></script>
</head><body>
<?php
ini_set("error_log", basename(__FILE__,'php') . 'error.log');
include_once('../lib/functions.php');
// define(EMAIL_RECEPIENTS,'sales@ispringfilter.com,John@iSpringFilter.com');
define(EMAIL_RECEPIENTS,'John@iSpringFilter.com');
// define(BRANDS,'iSpring,APEC,Purenex');
$headers = 'asin,sku,date,hour,rank1,rank2,category';
$q = "select * from bestsellerrank_ispring tric left join ispring_asin_sku tias on (tric.asin = tias.asin) order by tric.date desc, tric.asin";	
$items = sqlquery($q);
$table_header = '<tr><th>'. str_ireplace(',','</th><th>',$headers).'</th></tr>';
echo '<table id="table1" class="sortable  filterable" width="100%">' . $table_header;
$headers_array = explode(',',$headers);
foreach($items as $item) {
	$bgcolor = '#fff'. stringToColorCode(substr($item['asin'],2,7));
	echo "<tr bgcolor=$bgcolor>";
	foreach($headers_array as $header) {
		if ($header == 'asin') {
			echo "<td><a target=_blank href=http://www.amazon.com/dp/$item[$header]>$item[$header]</a></td>"; 
		} else {
			echo "<td>$item[$header]</td>";
		}
	}
	echo "</tr>";
	continue;
	$asin = $item['asin'];
	$date = $item['date'];
	$sku = $item['sku'];
	$rank1 = $item['rank1'];
	$rank2 = $item['rank2'];
	$category = $item['category'];
	// $bgcolor = '#EFF8FB';
	// $bgcolor = '#fff'. stringToColorCode(substr($asin,2,7));
	if ($rank2 == 1) $bgcolor = 'lightblue';
	echo "<tr bgcolor=$bgcolor><td><a target=_blank href=http://www.amazon.com/dp/$asin>$asin</a></td><td>$sku</td><td>$date</td><td>$rank1</td><td>$rank2</td><td>$category</td></tr>";
}
echo "</table>";
include_once('../lib/TableFilter/TableFilterConfig_ReviewMonitor.js');

?>
</body></html>