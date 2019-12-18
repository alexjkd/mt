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

function getAmazonCatLink($tablename) {
	$tsvFileName ='amazon.bestsellerrank.categories.tsv';
	if (!is_file($tsvFileName)) echo "$tsvFileName Not found";
	$categories = tsv2ToArray($tsvFileName);
	// print_r($categories);
	foreach ($categories as $cat => $urlPattern) {
		if (strripos($urlPattern,str_ireplace('abs_','',$tablename))) {
			if ($tablename == 'abs_13397631') echo '<h3> Amazon department:  Home Improvement \ Kitchen & Bath Fixtures \ <a target=_blank href="http://www.amazon.com/Best-Sellers-Home-Improvement-Water-Filtration-Softeners/zgbs/hi/13397631/ref=zg_bs_unv_hi_3_680337011_1">Water Filtration & Softeners</a></h3>';
			if ($tablename <> 'abs_13397631') echo '<h3> Amazon department:  Home Improvement \ Kitchen & Bath Fixtures \ <a target=_blank href="http://www.amazon.com/Best-Sellers-Home-Improvement-Water-Filtration-Softeners/zgbs/hi/13397631/ref=zg_bs_unv_hi_3_680337011_1">Water Filtration & Softeners</a> \ <a target=_blank href="'. str_ireplace('pageN','1',$urlPattern) .'">'. $cat .'</a> </h3>';
			break;
		}
	}
}

$tablename = isset($_GET['tablename']) ? $_GET['tablename'] : '';
$category = isset($_GET['category']) ? $_GET['category'] : '';
$asin = isset($_GET['asin']) ? $_GET['asin'] : '';
getAmazonCatLink($tablename);
if (strpos($tablename,'abs_') === false) {
	echo "<br><font color=red> Tablename $tablename NOT valid</font><br>";
	exit -1;
}
if (strpos($asin,'B00') === false) {
	echo "<br><font color=red> ASIN $asin NOT valid</font><br>";
	exit -1;
} else {
	if (strlen($asin) == 10 ) {
		showProduct($asin,$category);
	} else {
		listProducts($category,$tablename);
	}
}

function listProducts($category,$tablename) {
	global $categories;
	// $category_id = str_ireplace('abs_','',$tablename);
	$q = "select distinct asin,replace(product,'/www.amazon.com/','') as title, round(avg(rank),2) as avgRank from $tablename group by asin order by avgRank limit 300";
	$items = sqlquery($q);
	echo '<table border=1 id="table1" class="sortable  filterable"><tr><th>avgRank</th><th>asin</th><th>product</th></tr>';
	foreach($items as $item) {
		$rows .= '<tr><td><a target=_blank href="'. basename(__FILE__) . '?tablename='. $tablename .'&asin='. $item['asin'] .'">'. $item['avgRank'] .'</a></td><td><a target=_blank href=http://www.amazon.com/dp/'. $item['asin'] .'>'. $item['asin'] .'</a></td><td>'. $item['title'] .'</td></tr>';
	}
	echo "$rows </table>";
	include_once('../lib/TableFilter/TableFilterConfig.js');
}

function showProduct($asin,$category) {
	global $tablename;
	$q = "select *,date,STR_TO_DATE(concat(date,'.',hour),'%Y%m%d.%H') as time from $tablename where asin = '$asin' order by time desc";
	$items = sqlquery($q);
	$headers = 'time,rank,asin,product,reviews,price';
	$table_header = '<tr><th>'. str_ireplace(',','</th><th>',$headers).'</th></tr>';
	$headers_array = explode(',',$headers);
	foreach($items as $item) {
		foreach($headers_array as $header) {
			$row .= "<td> $item[$header] </td>";
		}
		$title = $category .' '. substr($item['product'],0,70);
		$array2drank[] = array($item['date'], 0-$item['rank']);
		$array2dreviews[] = array($item['date'], $item['reviews']);
		$row = str_ireplace('/www.amazon.com/','',$row);
		$row = preg_replace('/(B00\w{7})/','<a target=_blank href=http://www.amazon.com/dp/\1>\1</a>',$row);
		$rows .= "<tr> $row </tr>";
		$row='';
	}
	drawchart(array_reverse($array2drank),$title,'DATE','RANK');
	drawchart(array_reverse($array2dreviews),$title,'DATE','REVIEWS');
	echo '<table border=1 id="table1" class="sortable filterable">' . $table_header . $rows ."</table>";
	include_once('../lib/TableFilter/TableFilterConfig.js');
	// function drawchart($array2d,$title_c='',$title_x='',$title_y=''){

}

?>
</body></html>