<?php

	ini_set("error_log", basename(__FILE__,'php') . 'error.log');
	include_once('../lib/functions.php');


	$country = (isset($_GET['country']) && strlen($_GET['country'])>1) ? $_GET['country'] : 'us';
	$headers = "dt,price,asin,rank1,rank2";

	//~ $q = "SELECT FROM_UNIXTIME(time) as $headers FROM `asin_". $country ."_numbers` WHERE FROM_UNIXTIME(time) > DATE_SUB(NOW(),INTERVAL 30 MINUTE) ORDER BY time DESC";
	$q = "SELECT FROM_UNIXTIME(time) as $headers FROM `asin_". $country ."_numbers` WHERE time>". strtotime(date('Y-m-d H:0:0')) ." ORDER BY time DESC";
	//~ echo $q; exit;
	$csv = ''; $lastRank=array();
	foreach (sqlquery($q) as $r) {
		//~ foreach(explode($headers) as $h) {$$h = $r[$h]; }
		$csv .= $r['asin'] .','. $r['rank1'] ."\n";
		$lastRank[$r['asin']]=$r['rank1'];
	}
	$notdone='';
	$ListFileName = '../../amazon.'. $country .'_asin_sku_competitors.txt';
	foreach(explode("\n",file_get_contents($ListFileName)) as $line) {
		if (strlen($line) < 10 or strpos($line,'--')===0 or strpos($line,',')===false) continue;
		$a = explode(',',$line);
		$asin = $a[0];
		$sku = $a[1];
		if (!isset($lastRank[$asin]) or $lastRank[$asin]==0) $notdone .= $asin .','. $sku ."\n";
	}
	if (isset($_GET['csv'])) {
		unlink('asin_'. $country .'_numbers_notdone.csv');
		file_put_contents('asin_'. $country .'_numbers_notdone.csv', trim($notdone));
		download_processed('asin_'. $country .'_numbers_notdone.csv');
	} elseif (preg_match('/AutoIt/i',$_SERVER['HTTP_USER_AGENT']) ) {
		echo $notdone ."<p><hr><p>". $_SERVER['HTTP_USER_AGENT'];
	} else {
		echo $_SERVER['HTTP_USER_AGENT'] ."<H2>The browser is not AutoIt, show both Done and NotDone.</H2>";
		echo "<HR><H3>Done</H3>". str_replace("\n","\n<br>",$csv);
		echo "<HR><H3>Not Done</H3>". str_replace("\n","\n<br>",$notdone);
	}

?>