<?php

	ini_set("error_log", basename(__FILE__,'php') . 'error.log');

	include_once('../lib/functions.php');



	$headers = "dt,price,asin,rank1,rank2";

	$q = "SELECT FROM_UNIXTIME(time) as $headers FROM `asin_us_numbers` WHERE FROM_UNIXTIME(time) > DATE_SUB(NOW(),INTERVAL 30 MINUTE) ORDER BY time DESC";

	$csv = ''; $lastRank=array();

	foreach (sqlquery($q) as $r) {

		//~ foreach(explode($headers) as $h) {$$h = $r[$h]; }

		$csv .= $r['asin'] .','. $r['rank1'] ."\n";

		$lastRank[$r['asin']]=$r['rank1'];

	}



	$notdone='';

	$ListFileName = '../../amazon.us_asin_sku_competitors.txt';

	foreach(explode("\n",file_get_contents($ListFileName)) as $line) {

		if (strlen($line) < 10) continue;

		$a = explode(',',$line);

		$asin = $a[0];

		$sku = $a[1];

		if (!isset($lastRank[$asin]) or $lastRank[$asin]==0) $notdone .= $asin .','. $sku ."\n";

	}

	if (preg_match('/AutoIt/i',$_SERVER['HTTP_USER_AGENT']) ) {
		echo $notdone ."<p><hr><p>". $_SERVER['HTTP_USER_AGENT'];
	} else {
		echo str_replace("\n","\n<br>",$csv) ."\n\n<p><hr><p>". str_replace("\n","\n<br>",$notdone) ."\n\n<p><hr><p>". $_SERVER['HTTP_USER_AGENT'] ."\n";
	}

?>