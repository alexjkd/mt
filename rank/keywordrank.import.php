<html><head><meta http-equiv="Content-Type" content="text/html; charset=windows-1252">
<title>Market Tracker HTML scraber - Rank Monitor</title>
<?php
include_once('../lib/functions.php');
// define(EMAIL_RECEPIENTS,'sales@ispringfilter.com,John@iSpringFilter.com');
define(EMAIL_RECEPIENTS,'John@iSpringFilter.com');
// define(BRANDS,'iSpring,APEC,Purenex');
$array_url_patterns = array(
'com'  => 'http://www.amazon.com/s/ref=sr_pg_pg#?rh=n%3A228013%2Ck%3A#kw#&page=pg#&keywords=#kw#&ie=UTF8',
'co.uk'=> 'http://www.amazon.co.uk/s/ref=sr_pg_pg#?rh=i%3Aaps%2Ck%3A#kw#&page=pg#&keywords=#kw#&ie=UTF8',
'ca'   => 'http://www.amazon.ca/s/ref=sr_pg_pg#?rh=i%3Aaps%2Ck%3A#kw#&page=pg#&keywords=#kw#&ie=UTF8');

// include_once('bestsellerrank.import.php');

if (isset($_GET['keyword'])) {
	// echo $_GET['keyword'] ."<br><br><br>\n";
	$keyword = $_GET['keyword'];
	$pages = isset($_GET['pages']) ? $_GET['pages'] : 1;
	$region = isset($_GET['region']) ? $_GET['region'] : 'com';
	$table_name = 'keywordrank_'. trim(str_replace('.','',$region)) .'_'. trim(str_replace(' ','',$keyword));
	keywordrankmon($table_name,$array_url_patterns[$region],$region,$keyword,$pages); 
} else {
	$lines = file('keywords.csv',FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
	foreach ($lines as $line ) {
		$words = explode(':',$line);
		$region = trim($words[0]);
		$keywords = explode(',',$words[1]);
		echo '<ul>';
		foreach($keywords as $keyword) {
			$db->query("insert ignore into keywordrank_kws (region,keyword) value ('$region','$keyword') ");
			$table_name = 'keywordrank_'. trim(str_replace('.','',$region)) .'_'. trim(str_replace(' ','',$keyword));
			$check = $db->query("SHOW TABLES LIKE '$table_name'");
			$sql = "CREATE TABLE $table_name LIKE keywordrankmon_com_reverseosmosis;";
			if ($check->rowCount() == 0 && $db->query($sql) === false) {
				echo "<br><font color=red>Failed to create table: $sql </font><br>\n";
				error_log("Failed to create table: $sql ");
				return -1;
			}
			// echo $sql .','. $array_url_patterns[$region] .','. $region .','. $keyword .'<br>';
			// echo $array_url_patterns[$region] .'<br>'. $region .'<br>'. $keyword .'<br>';
			keywordrankmon($table_name,$array_url_patterns[$region],$region,$keyword,6); 
		}
		echo '</ul>';
		break; //testing
	}
}

function keywordrankmon($table_name,$url_pattern,$region,$keyword,$pages=2) {
	global $db, $array_asin_sku;
	$keyword = preg_replace('/\W+/','+',$keyword);
	// echo $keyword ."<br>";
	// exit;
	for ($n = 1; $n <=$pages; $n++) {
		$url_n = str_replace("#kw#", $keyword, $url_pattern);
		$url_n = str_replace("pg#", $n, $url_n);
		echo '<li><h3>'. $url_n .'</h3></li>';
		// continue; //testing
		$html = file_get_html($url_n);
		if (empty($html)) {get_html_curl($url_n);}
		if (empty($html)) {echoError('$html = file_get_html($url_n)  IS EMPTY'); return -1;}
		// ul id="s-results-list-atf" li id="result_24" data-asin="B00B8XDA46" class="s-result-item  celwidget"
		// <a class="a-link-normal s-access-detail-page  a-text-normal" title="Watts Premier 105311 RO-4 RO-Pure UF3 Sediment Filter" href="http://www.amazon.com/Watts-Premier-105311-RO-Pure-Sediment/dp/B00DU5WBN6/ref=sr_1_27?s=hi&amp;ie=UTF8&amp;qid=1439671877&amp;sr=1-27&amp;keywords=sediment+filter"><h2 class="a-size-base a-color-null s-inline s-access-title a-text-normal">Watts Premier 105311 RO-4 RO-Pure UF3 Sediment Filter</h2></a>
		// $results = $html->find('ul#s-results-list-atf li[id^=result_"] a."a-link-normal s-access-detail-page  a-text-normal"');
		// $html_ul = html_find($html,'ul#s-results-list-atf');
		$results = html_find($html,'li[id^=result_]');
		if (empty($results)) {$results = html_find($html,'ul#s-results-list-atf li');}
		if (empty($results)) {$results = html_find($html,'div#atfResults ul li');}
		if (empty($results)) {$results = html_find($html,'li[class*=s-result-item]');}
		if (empty($results)) {$results = html_find($html,'div ul li');}
		if (empty($results)) {$results = html_find($html,'li[data-asin]');}
		if (empty($results)) {$results = html_find($html,'ul li');}
		if (empty($results)) {$results = html_find($html,'li');}
		if (empty($results)) {echoError("html->find('li[id*=result_]') IS EMPTY");  return -2;	}
		$i = 0;
		foreach($results as $result) {
			$i++; if ($i > 24) break;
			$asin = $result->{'data-asin'};
			$rank = $result->{'id'};
			echo "\n<br><b>". $rank .' '. $asin .'</b>';
			$rank = str_ireplace('result_','',$rank);
			$rank = intval($rank);
			// $n_reviews = html_find($result,'a[href*=#customerReviews]','innertext');
			// $title = html_find($result,'h2',innertext);
			// $title = str_replace(',',' ',$title);
			// $brand = html_find($result,'span[class="a-size-small a-color-secondary"]',innertext);
			// echo '<li>'. $n_reviews .', '. $brand .', '. $asin .', '. $title .', '. $rank .', '. $title .'<br>'. $result->plaintext;
			// continue; 
			$text = $result->plaintext;
			$text = str_replace(',',' ',$text);
			$text = preg_replace('/See |more |choices |Options |Save more with monthly Subscribe & Save deliveries |Get it by[ |\w]+|Only \d+ left in stock - order soon\. |Buying /i','',$text);
			preg_match('/\s(\d\.\d) out of 5 stars\s+(\d+)/',$text,$match);
			if ($match[1]) {
				$rating = trim($match[1]);
				$reviews = trim($match[2]);
			}
			preg_match('/\s(\S*[\$£] *\d+\.\d+) /',$text,$match);
			$price = trim($match[1]);
			$text = mysql_escape_string($text);
			// if ( $n > 1 && stripos($text,'ispring ') === false) continue;  //Not to import non ispring listings or low rank listings
			// $sql = "insert ignore into $table_name (date,brand,asin,title,rank,text) value ('". date('Y-m-d') ."','$asin',$rank,'$text')";	
			$sql = "insert ignore into $table_name (date,asin,rank,price,rating,reviews,text) value ('". date('Y-m-d') ."','$asin',$rank,'$price','$rating','$reviews','$text')";	
			try {
				if (!empty($asin) && !empty($result->{'id'}) && $db->query($sql)) echo '<br>'. $sql ;
			} catch (Exception $e) {
					echo 'Caught exception: ',  $e->getMessage(), "\n";
					continue;
			}
		
			// continue; // for testing only "insert ignore into ispring_rank_reviews_history"
		}
		$html->clear();
		unset($results);
		unset($html);
		break; //for testing
	}
	// file_put_contents(basename(__FILE__,'php') . '_OUTPUT.CSV',$csv)
}
?>
</html>