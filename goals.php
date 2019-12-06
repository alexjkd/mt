<?php
//include_once(dirname(__FILE__) . "/lib/functions.php");
include_once(dirname(__FILE__) . "/lib/functions.php");

define("EMAIL_RECEPIENTS", 'John@iSpringFilter.com');
define("PERIODS", '2 years,1 years,6 months,3 months,30 days,7 days,120 hours,24 hours');
define('TooltipHeaders', 'rank1|rank2|reviews|avgrating|price');
$mapDnsCountries = array('us' => 'com', 'ca' => 'ca', 'uk' => 'co.uk');
$timeoffset = 3600 * 3; //before daylight saving time
if (time() > strtotime('First Sunday Of March') and time() < strtotime('First Sunday Of November ')) $timeoffset = 3600 * 2;
$region = isset($_GET['region']) ? $_GET['region'] : 'us';
$dnsCountry = $mapDnsCountries[$region];
$table = 'asin_' . $region . '_numbers';
$table_rank1 = 'mws_' . $region;
$aColors = array('red', 'blue', 'DarkGreen', 'orange', 'cyan', 'SkyBlue', 'green', 'SlateBlue', 'DimGrey', 'gold', 'grey', 'ivory', 'PeachPuff');
$image_width = 1380;

$days = 65;
$google_doc = 'https://docs.google.com/spreadsheets/d/e/2PACX-1vR2gY22xgcaR4JUr3naK5nXbFzw3pL_Ogn4msFRDGfVA8nILfEs-BOdxDRt2Jvhx9Yz31eAF8IfpjBn/pub?gid=0&single=true&output=tsv';
$local_csv = dirname(__FILE__) . '/MT_lists - mws.csv';
$sGoogleTsv = file_get_contents($google_doc);
//~ echo $sGoogleTsv;
$q=$qSub=$asin=$asinList='';
foreach (explode("\n",$sGoogleTsv) as $line) {
	$aTsv = explode("\t",trim($line));
	//~ print_r($aTsv);
	$sr = $aTsv[0];
	$url = $aTsv[1];
	$quarterBsrGoal = $aTsv[7];
	if (preg_match('/B0\w{8}/',$url,$mAsin) and preg_match('/(\d+)/',$quarterBsrGoal,$mGoal)) {
		$asin = $mAsin[0];
		$goal = $mGoal[1] * 1000;
		echo "<li>$url -- $asin";
		$model = $aTsv[2];
		$tier = $aTsv[3];
		$assignee = $aTsv[4];
		$top3comp = $aTsv[5];
		$top3keywords = $aTsv[6];
		$recentNegativeReviews = $aTsv[8];
		$qSub .= ",SUM(IF(asin = '$asin' and dailyAvg < $goal ,1,0)) AS '$assignee,$model,$asin'";
		$asinList .= ",'". $asin ."'";
	}
	//~ break;
}
$qSub = trim($qSub,",");
$asinList = trim($asinList,",");
$q = "SELECT $qSub FROM ( SELECT DATE_FORMAT(updated,'%Y-%m-%d') aS dt, asin, AVG(rank1) AS dailyAvg FROM `mws_us` WHERE asin IN ($asinList) AND updated>DATE_SUB(NOW(), INTERVAL $days DAY) GROUP BY dt, asin ORDER BY dt DESC) s ";
//~ echo "<h3> $q </h3>";
$r = sqlquery($q);
echo '<pre>';
print_r($r);
echo '</pre>';
?>