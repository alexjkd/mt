<?php
include_once(__DIR__ . '/../lib/functions.php');
$date = date('Ymd'); $hour=date('H');
$html_pattern = '#SalesRank';
// $q = "select * from comp_asin_sku";
$q = "SELECT asin,avg(rank) as rank FROM `keywordrank_com_reverseosmosiswaterfiltrationsystem` where rank < 12 group by asin order by rank";
$rows = sqlquery($q);
$checked_log_dir = str_ireplace('.php','_checkedlog/',__FILE__);
if (!is_dir($checked_log_dir)) {
	echo "dir $checked_log_dir NOT found";
	// mkdir($checked_log_dir,0660);
}
$hours = 2;
$checked = get_checked($checked_log_dir,$hours);
echo "<ol>";
foreach($rows as $row) {
	importCategoryRank($row['asin']);
}
echo "</ol>";
function importCategoryRank($asin) {
	global $html_pattern,$checked,$hours,$date,$hour;
	if (strpos($checked,$asin) !== false) {
		echo "<li>$asin checked in $hours hours ago.";
		continue;
	}
	// $sku = $row['sku'];
	$url = "https://www.amazon.com/dp/$asin";
	echo "<li><b>$sku: $url</b></li>" ;
	// continue;
	// $html = get_html_curl($url);
	$html = file_get_html($url);
	// echo $html; exit;
	if (empty($html)) {
		echo '<li>Empty: file_get_html("https://www.amazon.$region/db/$asin")';
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
	// echo "<br>html found: $text";
	//Best Sellers Rank                       #546,701 in Home Improvement (See top 100)                                                 #960       in&nbsp;Home Improvement &gt; Kitchen &amp; Bath Fixtures &gt; Water Filtration &amp; Softeners &gt; Under-Sink Filters
	preg_match_all('/#([\d,]+)\s+in\s+([\w\s]*)\s+\(See/',$text,$matches);
	$rank1 = str_ireplace(',','',$matches[1][0]);
	// $category1 = $matches[2][0];
	preg_match_all('/100\)\s*#(\d+)\s+in.+?([A-Z][\w\s\-&;]+)/',$text,$matches);
	// print_r($matches);
	$rank2 = str_ireplace(',','',$matches[1][0]);
	$category = str_ireplace(' &gt; ',";",$matches[2][0]);
	$category = preg_replace('/\s+/',' ',$category);
	// echo "<br>html found: $rank1, $rank2, $category";
	if (strlen($rank1) > 0 && strlen($rank2) > 0) {
		$q = "insert ignore into bestsellerrank_comp (date,hour,asin,rank1,rank2,category) values ( $date, $hour,'$asin',$rank1,$rank2,'$category')";
		echo "<br>$q";
		if (sqlquery($q) <> false) {
			add_checked($checked_log_dir,$asin);
			echo "&nbsp;&nbsp;--<b>INSERTED</b>--";
		}
	}
	// break; //test only 1 asin
}

?>