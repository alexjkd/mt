<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<link rel="shortcut icon" href="../lib/images/R.ico" >
<link rel="icon" type="image/gif" href="../lib/images/target_animated.gif" >
<title>iSpring Listing Rank Monitor - Amazon</title>
<link href="../lib/sortable/sortable_table.css" media="screen" rel="stylesheet" type="text/css" />
<link href="../lib/js/TableFilter/filtergrid.css" media="screen" rel="stylesheet" type="text/css" />
<script type="text/javascript" language="javascript" src="../lib/sortable/sorttable.js"></script>
<script type="text/javascript" language="javascript" src="../lib/TableFilter/tablefilter.js"></script>
</head><body>
<?php
ini_set('max_execution_time', 0);
ini_set('memory_limit', '256M');
ini_set('default_charset', 'UTF-8');
ini_set("error_log", basename(__FILE__,'php') . 'error.log');
include_once('/lib/simple_html_dom.php');
include_once('/lib/db.php');
include_once('../lib/functions.php');
$db = db::getInstance();
date_default_timezone_set('America/New_York');
// define(EMAIL_RECEPIENTS,'sales@ispringfilter.com,John@iSpringFilter.com');
define(EMAIL_RECEPIENTS,'John@iSpringFilter.com');
// define(BRANDS,'iSpring,APEC,Purenex');
$array_url_patterns = array(
'com'  => 'http://www.amazon.com/s/ref=sr_pg_pg#?rh=n%3A228013%2Ck%3A#kw#&page=pg#&keywords=#kw#&ie=UTF8',
'co.uk'=> 'http://www.amazon.co.uk/s/ref=sr_pg_pg#?rh=i%3Aaps%2Ck%3A#kw#&page=pg#&keywords=#kw#&ie=UTF8',
'ca'   => 'http://www.amazon.ca/s/ref=sr_pg_pg#?rh=i%3Aaps%2Ck%3A#kw#&page=pg#&keywords=#kw#&ie=UTF8');
$brand = isset($_GET['brand']) ? $_GET['brand'] : 'iSpring';
$lines = file('topbrands.txt',FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
echo '<form method="GET"><table><tr><th>BRANDS</th><th>REGION_KEYWORDS</th><th>TOP_RANKS</th></tr><tr><td><select name="brand"><option>ALL</option>';
foreach($lines as $line) {
	$words = explode('.',$line);
	$brand = $words[1];
	echo "<option>$brand</option>";
}
echo '</select></td><td><select name="region_keyword"><option>ALL</option>';
$lines = file('keywords.csv',FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$q = "select distinct region,keyword from keywordrank_kws order by region,keyword";	
$query = $db->query($q);
$rows = $query->fetchAll(PDO::FETCH_ASSOC);
foreach($rows as $row) {		
	echo "<option>". $row['region'] ."_". $row['keyword'] ."</option>";
}
echo '</select></td><td><select name="top_ranks"><option>ALL</option>';
for ($i=1; $i < 13; $i++) {		
	echo "<option>". $i ."</option>";
}
echo '</select><input type="submit"></td></tr></table></form>';

$where = " where text like 'iSpring %' OR text like '% iSpring %'";
if (isset($_GET['brand']) && $_GET['brand'] <> 'ALL') {
	$where1 = " text like '". $_GET['brand'] ."%' OR text like '% ". $_GET['brand'] ." %'" ;
}
if (isset($_GET['top_ranks']) && $_GET['top_ranks'] <> 'ALL') {
		$where2 = " rank < ". $_GET['top_ranks'] ;
}
if (!empty($where1) &&  empty($where2)) $where = 'WHERE '. $where1;
if ( empty($where1) && !empty($where2)) $where = 'WHERE '. $where2;
if (!empty($where1) && !empty($where2)) $where = 'WHERE ('. $where1 .') AND ('. $where2 .')';

$lines = file('keywords.csv',FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
if (isset($_GET['region_keyword']) && $_GET['region_keyword'] <> 'ALL') $lines = array(str_replace('_',':',$_GET['region_keyword']));
foreach ($lines as $line ) {
	$words = explode(':',$line);
	$region = trim($words[0]);
	$kws = explode(',',$words[1]);
	foreach($kws as $kw) {
		$tablename = preg_replace('/\.| /','','keywordrank_'. $region .'_'. $kw);
		// if (strpos($done_tablename,$tablename)) continue;
		// $done_tablename .= $tablename .'-';
		if (strpos($kw,'_')) $tablename = preg_replace('/\.| /','','keywordrank_'. $kw);
		$sql = "SELECT * FROM $tablename $where order by asin, date desc, rank";
		// $sql = "SELECT date,asin,text,rank FROM $tablename $where order by rank, asin, date desc ";
		// echo $sql. "<br>"; 
		// continue;
		try {
			$query = $db->query($sql);
			$items = $query->fetchAll(PDO::FETCH_ASSOC);
		} catch (Exception $e) {
			echo 'Caught exception: ',  $e->getMessage(), "\n";
			continue;
		}
		$tableid++;
		echo '<script language="javascript" type="text/javascript">  
			var table'. $tableid .'Filters = {  
					col_0: "select",  
					col_4: "none",  
					btn: true  
			}  
			
			var tf0'. $tableid .'= new TF("table'. $tableid .'",2,table'. $tableid .'Filters);  
			tf0'. $tableid .'.AddGrid();  
		</script>';

		echo "<h2><a target=_blank href=http://www.amazon.". $region .'/s/ref=nb_sb_ss_c_0_10?url=search-alias%3Daps&field-keywords='. str_replace($region.'_','',str_replace(' ','+',$kw)) . '>amazon'. $region .' '. $kw .'</a></h2><table border=1 width=100% class="sortable  filterable"  id="table'. $tableid .'"><thead><tr><th>asin</th><th>text</th><th>date</th><th>price</th><th>review#</th><th>rating</th><th>rank</th></tr></thead>';
		foreach($items as $item) {
			$asin = $item['asin'];
			$text = preg_replace('/See |more |choices |Options |Size |Sizes |Save more with monthly |Subscribe & Save |deliveries |Get it by[ |\w]+|Only \d+ left in stock - order soon\. |Buying /i','',$item['text']);
			$text = substr($text,0,200);
			$date = $item['date'];
			$price = $item['price'];
			$reviews = $item['reviews'];
			$rating = $item['rating'];
			$rank = $item['rank']+1;
			// $bgcolor = '#EFF8FB';
			$bgcolor = '#fff'. stringToColorCode(substr($asin,2,7));
			if ($rank == 1) $bgcolor = 'lightblue';
			echo "<tr bgcolor=$bgcolor><td><a target=_blank href=http://www.amazon.$region/dp/$asin>$asin</a></td><td>$text</td><td>$date</td><td>$price</td><td>$reviews</td><td>$rating</td><td><a target=_blank href=chart.php?asin=$asin&table=$tablename>$rank</a></td></tr>";
		}
		echo "</table>";
	}
}
?>