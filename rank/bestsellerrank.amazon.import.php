<?php
include_once(__DIR__ . '/../lib/functions.php');
$tsvFileName = __DIR__. '/amazon.bestsellerrank.categories.tsv';
if (!is_file($tsvFileName)) echo "$tsvFileName Not found";
$categories = tsv2ToArray($tsvFileName);
$srcTableName = 'abs_13397631';
echo "<ol>";
foreach($categories as $urlPattern => $category) {
	echo "<br><li># $category :  $urlPattern";
	if (strlen($category) < 5 or strlen($urlPattern) < 12) continue ;
	preg_match('-/hi/(\d+)/ref-',$urlPattern,$matches);
	$idCategory = $matches[1];
	$tableName = 'abs_'. $idCategory;
	if (is_sqltableexist($tableName) == 0 ) {
		$sql = "create TABLE $tableName like $srcTableName";
		if (sqlquery($sql)) echo "<br> $sql </li><ol>" ;
	}
	echo '<ol>';
	for ($pageN=1;$pageN<=5;$pageN++) {
		$url = '';
		$url = str_ireplace('pageN',$pageN,$urlPattern);
		echo "<li> $url";
		htmlScraper($url);
		break; //test
	}
	echo "</ol>";
}
echo "</ol>";

function htmlScraper($url) {
	global $tableName;
	$html_patterns = array('rankNumber'=>'span.zg_rankNumber', 'href'=>'a[href*=/dp/]', 'reviewNumber'=>'a[href*=showViewpoints]', 'price'=>'strong.price');
	$html = $HtmlItems = $values = $sql = '';
	$html = get_html_curl($url);
	if (empty($html) or stripos($html,'zg_itemImmersion') < 10) {	echo "<br><font color=red>Empty $url </font>";  return false;	}
	$HtmlItems = html_find($html,'div.zg_itemImmersion');
	$date = date('Ymd');
	$hour = date('H');
	foreach($HtmlItems as $HtmlItem) {
		$rankNumber = html_find($HtmlItem,$html_patterns['rankNumber'],'innertext');
		preg_match('/(\d+)/',$rankNumber,$matches);
		$rankNumber = $matches[1];
		$href = html_find($HtmlItem,$html_patterns['href'],'href');
		preg_match('/(B00\w{7})/',$href,$matches);
		$asin = $matches[1];
		preg_match('-/(.+)/dp/-',$href,$matches);
		$product = $matches[1];
		$reviewNumber = html_find($HtmlItem,$html_patterns['reviewNumber'],'innertext');
		$reviewNumber = str_ireplace(',','',$reviewNumber);
		$price = html_find($HtmlItem,$html_patterns['price'],'innertext');
		$values = $date.', '. $hour. ", $rankNumber, '$asin', '$product', '$reviewNumber', '$price'";
		$sql = "insert ignore into $tableName (date,hour,rank,asin,product,reviews,price) values ( $values ) ";
		echo "<BR> $sql ";
		sqlquery($sql);
		// echo "<br> $href ";
		// break; //test
	}
}

?>