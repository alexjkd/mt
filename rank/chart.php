<?php
ini_set('max_execution_time', 0);
ini_set('memory_limit', '256M');
ini_set('default_charset', 'UTF-8');
ini_set("error_log", basename(__FILE__,'php') . 'error.log');
include_once('../lib/simple_html_dom.php');
include_once('../lib/db.php');
require_once('../lib/phplot-6.1.0/phplot.php');
$db = db::getInstance();
date_default_timezone_set('America/New_York');
// define(EMAIL_RECEPIENTS,'sales@ispringfilter.com,John@iSpringFilter.com');
define(EMAIL_RECEPIENTS,'John@iSpringFilter.com');
// define(BRANDS,'iSpring,APEC,Purenex');
if (isset($_GET['asin'])) {
	$asin = $_GET['asin'];
	$table = $_GET['table'];
} else {
	// header( 'Location: '. $PHP_SELF . '?asin=B0041HSOSI&table=keywordrank_com_sedimentfilter' ) ;
	$asin='B0041HSOSI';
	$table='keywordrank_com_sedimentfilter';
}
$sql = "SELECT date,rank,reviews,rating,text FROM $table WHERE asin= '$asin' order by date";
$query = $db->query($sql);
$items = $query->fetchAll(PDO::FETCH_ASSOC);
foreach($items as $item) {
	$asin = $item['asin'];
	$text = substr($item['text'],0,147);
	$date = $item['date'];
	$rank = $item['rank'];
	// $reviews = $item['reviews'];
	// if ($reviews > 10) $reviews = round(log($reviews,2),1);
	// $rating = $item['rating'];
	$bgcolor = '#fff'. stringToColorCode(substr($asin,2,7));
	// echo "<tr bgcolor=$bgcolor><td>$asin</td><td>$text</td><td>$date</td><td>$rank</td></tr>";
	// $array2d[] = array($date,$rank,$reviews,$rating);
	$array2d[] = array($date,$rank);
}
drawchart($array2d,$kw .' '. $asin .' '. substr($text,0,70),'date','rank');

function drawchart($array2d,$title_c='',$title_x='',$title_y=''){
	//Include the code
	
	//create a PHPlot object with 800x600 pixel image
	$plot = new PHPlot(1200,600);

	//Define some data
/*   	$example_data = array(
			 array('x1',3),
			 array('x2',5),
			 array('x3',7),
			 array('x4',8),
			 array('x5',2),
			 array('x6',6),
			 array('x7',7)
	);
 	$plot->SetDataValues($example_data);
	// print_r($example_data);
	// return;
 */	// 
 $plot->SetDataValues($array2d);

	//Set titles
	$plot->SetTitle($title_c);
	$plot->SetXTitle($title_x);
	$plot->SetYTitle($title_y);

	//Turn off X axis ticks and labels because they get in the way:
	$plot->SetXTickLabelPos('none');
	$plot->SetXTickPos('none');

	//Draw it
	$plot->DrawGraph();
}

function stringToColorCode($str) {
  $code = dechex(crc32($str));
  $code = substr($code, 0, 6);
  return $code;
}
function color_inverse($color){
    $color = str_replace('#', '', $color);
    if (strlen($color) != 6){ return '000000'; }
    $rgb = '';
    for ($x=0;$x<3;$x++){
        $c = 255 - hexdec(substr($color,(2*$x),2));
        $c = ($c < 0) ? 0 : dechex($c);
        $rgb .= (strlen($c) < 2) ? '0'.$c : $c;
    }
    return '#'.$rgb;
}
?>

