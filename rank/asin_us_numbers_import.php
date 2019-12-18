<html><head><meta http-equiv="Content-Type" content="text/html; charset=windows-1252">
<title>asin_us_numbers_import</title>
<?php
set_time_limit(290);
chdir(dirname(__FILE__));
ini_set("error_log", basename(__FILE__,'php') . 'error.log');
include_once(__DIR__ .'/../lib/functions.php');
define('EMAIL_RECEPIENTS','John@iSpringFilter.com');
define('ASIN_TABLE_HEADERS','asin,buybox,fbt,product_details,purchase_sims,view_to_purchase');
define('DATA_FIELDS_SEARS',"time,rank,id,title,price,numreview,rating,soldby");
define('DATA_FIELDS_NEWEGG',"time,rank,id,model,title,price,numreview,rating");
define('LOGFILE','asin_us_numbers_import.log.html');
if (filesize(LOGFILE) > 1234567) rename(LOGFILE,'old.'. LOGFILE);
$errorMsg = '';
$test = isset($_GET['test']) ? $_GET['test'] : 0;
$aFiles = glob("*.asin_us_numbers.csv");
if (empty($aFiles)) {
	$errorMsg = "\n<li>No *.asin_us_numbers.csv in this hour\r";
} else {
	foreach($aFiles as $file) {
		$errorMsg = "\n$file";
		$ft = file_exists($file) ? filemtime($file) : time()-999999;
		$fz = file_exists($file) ? round(filesize($file)/1024,1) : 0;
		if ($fz < 2) {	$errorMsg = "\nsize = $fz KB\r";	}
		if ($ft < (time() - 3700)) {
			$errorMsg .= "\n$file older than 1 hour, ". date('H:i m/d/y',$ft) . ', file time: '. date('Ymd H:i',$ft) ."\r";
			file_put_contents(LOGFILE,"\n<li>$file is older than 1 hour. File time: ". date('Ymd H:i',$ft), FILE_APPEND );
		}
		$result = ImportNumbers($file);
		echo $file ."<BR>". $result;
		//~ file_put_contents(LOGFILE,$result ."\n",FILE_APPEND);
		if ($test <> 1 && strpos($result,'sql error') === false) {
			rename($file,'asin_us_numbers_last.csv');
		} else {
			if (strlen($errorMsg) > 10) sendmail('asin_us_numbers.csv error',$errorMsg . "\n<p><a href=http://czysua.com/mt/uploads/". LOGFILE .">". LOGFILE ."</a>" );
		}
	}
}

function ImportNumbers($file) {
	global $test,$errorMsg;
	$af = explode(',','time,asin,Price,Lowest,Soldby,Reviews,Rating,FiveNeeded,ReviewsPerStar,qa,Rank1,Rank2,LatestReviews,Brand,Model,Title,featuresPoints,cps');
	$qnumbers=$qstring=$qcps=$qlrs=$vnumbers=$vstring=$vcps=$vlrs=$time=$asin=$Price=$Lowest=$Soldby=$Reviews=$Rating=$fiveneeded=$p5=$p4=$p3=$p2=$p1=$qa=$Rank1=$Rank2=$LatestReviews=$Brand=$Model=$Title=$featuresPoints=$cps='';
	$asin_us_numbers = file_get_contents($file);
	//~ echo $asin_us_numbers .'<br>';
	$lines = explode("\n",$asin_us_numbers);
	//2016-06-01 19:01:06	B003XELTTG	190.11	1773	4.8	282	1	RCC7
	$q=$r='';
	foreach($lines as $line) {
		$a = explode("\t",$line);
		//~ print_r($a); break;
		if (count($a) < 12) {$errorMsg .= "\nNO 12 TABs: $line\n<br>"; continue;}
		if ($a[10] < 1) {$errorMsg .= "\nRank1 is empty: $line\n<br>"; continue;}
		//~ if (count($a) < 7 || !preg_match('/(\d+\|){4}/',$a[8])) {echo "NO ReviewsPerStar: $line<br>\n"; continue;}
		//~ if (strlen($line) < 10 || $a[0] == '' || $a[1] == '' || $a[2] == '' || $a[5] == 0) continue;
		//~ $time = strtotime(substr($a[0],0,14) . '00:00');
		for($i=0;$i<count($a);$i++) {
			$f = $af[$i];
			$$f = str_replace("'",'',$a[$i]);
		}
		$aRp = explode('|',$ReviewsPerStar);
		$p5=$aRp[0];		$p4=$aRp[1];		$p3=$aRp[2];		$p2=$aRp[3];		$p1=$aRp[4];

		$time = strtotime($time);
		$vnumbers .= "('$time', '$asin', '$Price', '$Lowest', '$Soldby', '$Reviews', '$Rating', '$FiveNeeded', '$p5', '$p4', '$p3', '$p2', '$p1', '$qa', '$Rank1', '$Rank2'),";
		if ($Brand<>'' && $Model<>'') $vstring .= "('$asin', '$Brand', '$Model', '$Title', '$featuresPoints'),";
		if (stripos($cps,'/dp/')>1) $vcps .= "('$asin', '$cps'),";
		if (stripos($LatestReviews,'out of 5 stars') > 1) $vlrs .= "('$asin', '$LatestReviews'),";
	}
	if (strlen($vnumbers) > 60 and count(explode("'),",$vnumbers)) > 0) {
		$qnumbers = 'INSERT IGNORE INTO asin_us_numbers (time, asin, price, lowest, soldby, reviews, avgrating, fiveneeded, p5, p4, p3, p2, p1, qa, rank1, rank2) VALUES ' . trim($vnumbers,',');
		$r = sqlquery($qnumbers);
	}
	if (strlen($vstring) > 60 and count(explode("'),",$vstring)) > 0) {
		$qstring = 'INSERT IGNORE INTO asin_us_strings (asin,brand,sku,title,featuresPoints) VALUES ' . trim($vstring,',');
		sqlquery($qstring);
	}
	if (strlen($vcps) > 60 and count(explode("'),",$vcps)) > 0) {
		$qcps = 'INSERT IGNORE INTO asin_us_cps (asin,cps) VALUES ' . trim($vcps,',');
		sqlquery($qcps);
	}
	if (strlen($vlrs) > 60 and count(explode("'),",$vlrs)) > 0) {
		$qlrs = 'INSERT IGNORE INTO asin_us_lrs (asin,LatestReviews) VALUES ' . trim($vlrs,',');
		sqlquery($qlrs);
	}
	if ($test==1) return str_replace("'),","<br>\n",$q);
	if ($r <> false) {
		$rows = count(explode('),',$q));
		echo $rows ." rows imported into asin_us_numbers from $file\n<br>". str_replace(')',")<br>",$q);
		file_put_contents(LOGFILE,date('Y-m-d H:i'). ' - '. $rows ." rows imported into asin_us_numbers from $file\n<br>", FILE_APPEND );
		return $rows;
	} else {
		return "\n<li>$r sql error\n";
	}
}


?>
</html>
