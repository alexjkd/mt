<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<link rel="shortcut icon" href="../lib/images/target_animated.ico" >
<link rel="icon" type="image/gif" href="../lib/images/target_animated.gif" >
<title>iSpring Amazon Reviews Target Report</title>
<link href="../lib/css/sortable_table.css" media="screen" rel="stylesheet" type="text/css" />
<script type="text/javascript" language="javascript" src="../lib/js/sorttable.js"></script>
<link href="../lib/js/TableFilter/filtergrid.css" media="screen" rel="stylesheet" type="text/css" />
<script type="text/javascript" language="javascript" src="../lib/js/TableFilter/tablefilter.js"></script>
</head><body>
<?php
// include_once('../lib/simple_html_dom.php');
include_once('../lib/db.php');
include_once('../lib/functions.php');
$db = db::getInstance();
ini_set('max_execution_time', 130);
ini_set('memory_limit', '25M');
ini_set('default_charset', 'UTF-8');
ini_set("error_log", basename(__FILE__,'php') . 'error.log');
date_default_timezone_set('America/New_York');
// define("EMAIL_RECEPIENTS","sales@ispringfilter.com,john@ispringfilter.com");
define("EMAIL_RECEPIENTS","john@ispringfilter.com");
// $regions = ['com','co.uk','ca'];

if(isset($_GET['download'])){
	$region = 'com'; //for testing
	$report_file_name = basename(__FILE__,'php') .'.'. $region .'.'. date('Ymd-H') .'.TSV';
	exit;
}

$valueNames = 'date,asin,sku,total,five,four,three,two,one';
$valueNamesArray = explode(',',$valueNames);
// $q = 'select * from ispring_reviewstars_propcount where date < DATE_ADD(NOW(), INTERVAL + 1 DAY) ORDER BY asin, date desc;';
$q = 'select * from ispring_reviewstars_propcount LEFT JOIN ispring_asin_sku ON (ispring_reviewstars_propcount.asin = ispring_asin_sku.asin)';
try {$query = $db->query($q);} catch (Exception $e) {
	echo '<BR><font color=red>'. $q .'<br>Caught exception: ',  $e->getMessage(), "</font><br>\n";
	exit;
} 
$rows = $query->fetchAll(PDO::FETCH_ASSOC);
foreach($rows as $row) {
	foreach($valueNamesArray as $vn) {
		$$vn = $row[$vn];
		$csv .= $$vn .',';
	}
	$totalStars = $five * 5 + $four * 4 + $three * 3 + $two * 2 + $one;
	$avg = round($totalStars/$total,3);
	$FiveNeeded = (4.75-$avg) * $total * 4; //(4.75-$J2)*SUM($E2:$I2)*4
	$csv .= $avg .','. $FiveNeeded ."\n";
}
// file_put_contents('test.csv',$csv);
// $output = str_ireplace("\n",'</tr><tr>',$csv);
// $output = str_ireplace("\t",'</td><td>',$output);

echo '<table id="table1" border=1 width="100%" class="sortable filterable"><thead><tr><th>'. str_ireplace(',','</th><th>',$valueNames) . '</th><th>Average</th><th>FiveNeeded</th></tr></thead><tbody>'. preg_replace('-<td>(B0\w+)</td>-','<td><a target=_blank href="http://www.amazon.com/product-reviews/$1">$1</a></td>',csvToHtmlTable($csv)) .'</tbody></table>';
include_once('../lib/js/TableFilter/TableFilterConfig.txt');
?>
 </body>
</html>