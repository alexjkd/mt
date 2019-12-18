<html><head><meta http-equiv="Content-Type" content="text/html; charset=windows-1252">
<title>Market Tracker Importer - html scraber</title>
<?php
set_time_limit(290);
chdir(dirname(__FILE__));
ini_set("error_log", basename(__FILE__,'php') . 'error.log');
include_once(__DIR__ .'/../lib/functions.php');
define('EMAIL_RECEPIENTS','John@iSpringFilter.com');
define('ASIN_TABLE_HEADERS','asin,buybox,fbt,product_details,purchase_sims,view_to_purchase');
define('DATA_FIELDS_SEARS',"time,rank,id,title,price,numreview,rating,soldby");
define('DATA_FIELDS_NEWEGG',"time,rank,id,model,title,price,numreview,rating");
define('LOGFILE','import.log.html');
if (filesize(LOGFILE) > 1234567) rename(LOGFILE,'old.'. LOGFILE);
$errorMsg = '';
$test = isset($_GET['test']) ? $_GET['test'] : 0;
$aFiles = glob("20*.BSR.csv");
if (empty($aFiles)) {
	$errorMsg = 'No 20*.BSR.csv in this hour';
} else {
	foreach($aFiles as $file) {
		$ft = filemtime($file);
		$fz = round(filesize($file)/1024,1);
		if ($fz < 2) {	$errorMsg .= "$file size = $fz KB\n";	}
		if ($ft < (time() - 3700)) {
			$errorMsg .= "\n$file older than 1 hour, ". date('H:i m/d/y',$ft) . ', file time: '. date('Ymd H:i',$ft) ."\r";
			file_put_contents(LOGFILE,"<li>$file is older than 1 hour. File time: ". date('Ymd H:i',$ft), FILE_APPEND );
		}
		$result = ImportBSR($file);
		echo $result;
		if ( $result <> 'sql error') rename($file,'BSR_last.csv');
	}
}
if (strlen($errorMsg) > 10) sendmail('bsr_import error',$errorMsg);

function ImportBSR($file) {
	global $test;
	$values=$time=$avgrating=$reviews=$rank1=$rank2=$asin=$rank1cat=$rank2cat=$buybox=$fbt=$purchase_sims=$view_to_purchase=$rows='';
	$bsr = file_get_contents($file);
	//~ echo $bsr .'<br>';
	$lines = explode("\n",$bsr);
	//2016-06-01 19:01:06	B003XELTTG	190.11	1773	4.8	282	1	RCC7
	foreach($lines as $line) {
		$a = explode("\t",$line);
		if (strlen($line) < 10 || $a[0] == '' || $a[1] == '' || $a[2] == '' || $a[5] == 0) continue;
		$time = strtotime(substr($a[0],0,14) . '00:00');
		//~ $time = $a[0] - ($a[0]%3600) + (5*3600);
		//~ $time = $a[0];
		$asin = $a[1];
		$price = $a[2];
		$reviews = $a[3];
		$avgrating = $a[4];
		$rank1 = $a[5];
		$rank2 = $a[6];
		$values .= "('$time','$avgrating','$reviews','$price','$rank1','$rank2','$asin'),";
		//~ echo $q .'<br>';
		//~ echo sqlquery($q);
	}
	$q = 'insert ignore asin_us_hash (time,avgrating,reviews,price,rank1,rank2,asin) values ' . trim($values,',');
	if (sqlquery($q) <> false) {
		$rows = count(explode('),',$q));
		echo '<li>'. $rows ." rows imported into asin_us_numbers from $file<br>\n". str_replace(')',")<br>",$q);
		file_put_contents(LOGFILE,date('Y-m-d H:i'). ' - '. $rows ." rows imported from $file\n", FILE_APPEND );
		return $rows;
	} else {
		return "sql error";
	}
}

function OldImportBSR($file) {
	$values=$time=$avgrating=$reviews=$rank1=$rank2=$asin=$rank1cat=$rank2cat=$buybox=$fbt=$purchase_sims=$view_to_purchase=$rows='';
	$bsr = file_get_contents($file);
	//~ echo $bsr .'<br>';
	$lines = explode("\n",$bsr);
	//2016-06-01 19:01:06	B003XELTTG	190.11	1773	4.8	282	1	RCC7
	foreach($lines as $line) {
		$a = explode("\t",$line);
		if (strlen($line) < 10 || $a[0] == '' || $a[1] == '' || $a[2] == '' || $a[5] == 0) continue;
		$time = strtotime(substr($a[0],0,14) . '00:00');
		//~ $time = $a[0] - ($a[0]%3600) + (5*3600);
		//~ $time = $a[0];
		$asin = $a[1];
		$price = $a[2];
		$reviews = $a[3];
		$avgrating = $a[4];
		$rank1 = $a[5];
		$rank2 = $a[6];
		$values .= "('$time','$avgrating','$reviews','$rank1','$rank2','$asin','$rank1cat','$rank2cat','$buybox','$fbt','$purchase_sims','$view_to_purchase'),";
		//~ echo $q .'<br>';
		//~ echo sqlquery($q);
	}
	$q = 'insert ignore asin_us_hash (time,avgrating,reviews,rank1,rank2,asin,rank1cat,rank2cat,buybox,fbt,purchase_sims,view_to_purchase) values ' . trim($values,',');
	if (sqlquery($q) <> false) {
		$rows = count(explode('),',$q));
		echo str_replace(')',")<br>",$q);
		file_put_contents(LOGFILE,date('Y-m-d H:i'). ' - '. $rows ." rows imported from $file\n", FILE_APPEND );
		return $rows;
	} else {
		return "sql error";
	}
}
?>
</html>