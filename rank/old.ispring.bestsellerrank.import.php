<?php
include_once('../lib/functions.php');

$asin = 'B007VZ2O38';
$html = file_get_html('http://www.amazon.com/dp/'. $asin);
BestSellerRank($html);
exit;

function BestSellerRank($html) {
	$html_patterns = array('sections'=>'tr#SalesRank', 'value'=>'td.value', 'ranks'=>'a[href*=ref=pd_zg_hrsr_hi_1]');
	$sections = html_find($html,$html_patterns['sections']);
	if (empty($sections)) return -1;
	foreach($sections as $section) {
		$ranks = html_find($section,$html_patterns['ranks']);
		if (empty($ranks)) return -2;
		foreach($ranks as $rank) {
			echo "<br>". $rank->href;
			echo "<br>". $rank->innertext;
			echo "<br>". $rank->outtertext;
		}
	}
}
$html_pattern = '#SalesRank';
$q = "select * from ispring_asin_sku where tier < 3";
// $q = "select * from ispring_asin_sku where sku <> ''";
$rows = sqlquery($q);
$checked_log_dir = basename(__FILE__,'php') .'checkedlog';
if (!is_dir($checked_log_dir)) {
	echo "dir $checked_log_dir NOT found";
	// mkdir($checked_log_dir,0660);
}
$hours = 2;
$checked = get_checked($checked_log_dir,$hours);
echo "<ol>";
foreach($rows as $row) {
	$asin = $row['asin'];
	if (strpos($checked,$asin) !== false) {
		echo "<li>$asin checked in $hours hours ago.";
		continue;
	} else {
		add_checked($checked_log_dir,$asin);
	}
	$sku = $row['sku'];
	$url = "http://www.amazon.com/dp/$asin";
	echo "<li><b>$url</b>" ;
	// continue;
	$html = file_get_html($url);
	if (empty($html)) {
		echo '<li>Empty: file_get_html("http://www.amazon.$region/db/$asin")';
		continue;
	}
	$text = html_find($html,$html_pattern,'plaintext');
	$html->clear();
	unset($html);
	// $text = preg_replace('/[^\w]/',' ',$text);
	echo "<br>$text";
	//Best Sellers Rank                       #546,701 in Home Improvement (See top 100)                                                 #960       in&nbsp;Home Improvement &gt; Kitchen &amp; Bath Fixtures &gt; Water Filtration &amp; Softeners &gt; Under-Sink Filters         
	preg_match_all('/#([\d,]+)\s+in\s+([\w\s]*)\s+\(See/',$text,$matches);
	$rank1 = str_ireplace(',','',$matches[1][0]);
	// $category1 = $matches[2][0];
	preg_match_all('/100\)\s*#(\d+)\s+in.+?([A-Z][\w\s\-&;]+)/',$text,$matches);
	// print_r($matches);
	$rank2 = str_ireplace(',','',$matches[1][0]);
	$category = str_ireplace(' &gt; ',";",$matches[2][0]);
	$category = preg_replace('/\s+/',' ',$category);
	echo "<br> $rank1, $rank2, $category";
	if (strlen($rank1) > 0 && strlen($rank2) > 0) {
		$q = "insert ignore into bestsellerrank (date,asin,rank1,rank2,category) values ('". date('Y-m-d') ."','$asin','$rank1','$rank2','$category')";
		if (sqlquery($q)) echo "<br>$q";
	}
	// break; //test only 1 asin
}
echo "</ol>";
?>