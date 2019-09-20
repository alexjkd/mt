<?php
include_once(__DIR__ . '/../lib/functions.php');
// bestsellerrank_ispring
// $html_pattern = 'tr[id="SalesRank"],li[id="SalesRank"]';
$html_pattern = '#SalesRank';
$q = "select * from ispring_asin_sku where tier < 3";
// $q = "select * from ispring_asin_sku where sku <> ''";
$rows = sqlquery($q);
$checked_log_dir = str_ireplace('.php','_checkedlog',__FILE__);
/* if (!is_dir($checked_log_dir)) {
	echo "dir $checked_log_dir NOT found";
	// mkdir($checked_log_dir,0660);
}
 */
$hours = 2;
$checked = get_checked($checked_log_dir,$hours);
echo "<ol>";
importRank('B00I0ZGOZM','APEC RO-50');
importRank('B00NWZ1RCK','APEC PH-75');
importRank('B00NWZ1RCK','APEC RO-90');
importRank('B00N5D7ZFW','APEC ICEK');
importRank('B00J2DGTD8','ExpressWater RO5DX');
importRank('B005A3WM6C','HomeMaster TMAFC');
foreach($rows as $row) {
	importRank($row['asin'],$row['sku']);
}
echo "</ol>";
function importRank($asin,$sku) {
	global $html_pattern,$checked,$hours;
	if (strpos($checked,$asin) !== false) {
		echo "<li>$asin checked in $hours hours ago.";
		continue;
	}
	// $sku = $row['sku'];
	$url = "http://www.amazon.com/dp/$asin";
	echo "<li><b>$sku: $url</b></li>" ;
	// continue;
	// $html = get_html_curl($url);
	$html = file_get_html($url);
	// echo $html; exit;
	if (empty($html)) {
		echo '<li>Empty: file_get_html("http://www.amazon.$region/db/$asin")';
		continue;
	}
	$text = html_find($html,$html_pattern,'plaintext');
	if(empty($text)) $text = html_find($html,'div[class="wrapper USlocale"]','plaintext');
	if (empty($text)) $text = html_find($html,'div[class="section techD"]','plaintext');
	if (empty($text)) $text = html_find($html,'div[class="content pdClearfix"]','plaintext');
	if (empty($text)) $text = html_find($html,'div#prodDetails','plaintext');
	// $html->clear();
	// unset($html);
	// $text = preg_replace('/[^\w]/',' ',$text);
	echo "<br>html found: $text";
	//Best Sellers Rank                       #546,701 in Home Improvement (See top 100)                                                 #960       in&nbsp;Home Improvement &gt; Kitchen &amp; Bath Fixtures &gt; Water Filtration &amp; Softeners &gt; Under-Sink Filters         
	preg_match_all('/#([\d,]+)\s+in\s+([\w\s]*)\s+\(See/',$text,$matches);
	$rank1 = str_ireplace(',','',$matches[1][0]);
	// $category1 = $matches[2][0];
	preg_match_all('/100\)\s*#(\d+)\s+in.+?([A-Z][\w\s\-&;]+)/',$text,$matches);
	// print_r($matches);
	$rank2 = str_ireplace(',','',$matches[1][0]);
	$category = str_ireplace(' &gt; ',";",$matches[2][0]);
	$category = preg_replace('/\s+/',' ',$category);
	echo "<br>html found: $rank1, $rank2, $category";
	if (strlen($rank1) > 0 && strlen($rank2) > 0) {
		$q = "insert ignore into bestsellerrank_ispring (date,hour,asin,rank1,rank2,category) values (". date('Ymd') .','. date('H') .",'$asin','$rank1','$rank2','$category')";
		if (sqlquery($q)) echo "<br>$q";
		add_checked($checked_log_dir,$asin);
	}
	// break; //test only 1 asin
}

?>