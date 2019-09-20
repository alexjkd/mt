<html><head><meta http-equiv="Content-Type" content="text/html; charset=windows-1252">
<title>asin_ca_numbers_import</title>
<?php
set_time_limit(290);
chdir(dirname(__FILE__));
ini_set("error_log", basename(__FILE__,'php') . 'error.log');
include_once(__DIR__ .'/../lib/functions.php');
define('EMAIL_RECEPIENTS','John@iSpringFilter.com');
define('ASIN_TABLE_HEADERS','asin,buybox,fbt,product_details,purchase_sims,view_to_purchase');
define('DATA_FIELDS_SEARS',"time,rank,id,title,price,numreview,rating,soldby");
define('DATA_FIELDS_NEWEGG',"time,rank,id,model,title,price,numreview,rating");
define('LOGFILE','asin_ca_numbers_import.log.html');
if (filesize(LOGFILE) > 1234567) rename(LOGFILE,'old.'. LOGFILE);
$errorMsg = '';
$test = isset($_GET['test']) ? $_GET['test'] : 0;
$aFiles = glob("20*.asin_ca_numbers.csv");
if (empty($aFiles)) {
	$errorMsg = 'No 20*.asin_ca_numbers.csv in this hour';
} else {
	foreach($aFiles as $file) {
		$ft = filemtime($file);
		$fz = round(filesize($file)/1024,1);
		if ($fz < 2) {	$errorMsg .= "$file size = $fz KB\n";	}
		if ($ft < (time() - 3700)) {
			$errorMsg .= "\n$file older than 1 hour, ". date('H:i m/d/y',$ft) . ', file time: '. date('Ymd H:i',$ft) ."\r";
			file_put_contents(LOGFILE,"<li>$file is older than 1 hour. File time: ". date('Ymd H:i',$ft), FILE_APPEND );
		}
		$result = ImportNumbers($file);
		echo $result;
		file_put_contents(LOGFILE,$result ."\n",FILE_APPEND);
		if ($test <> 1 && $result <> 'sql error') rename($file,'asin_ca_numbers_last.csv');
	}
}
if (strlen($errorMsg) > 10) sendmail('asin_ca_numbers_import error',$errorMsg);

function ImportNumbers($file) {
	global $test;
	$q1=$q1=$q3=$q4=$q1h=$q2h=$q3h=$q4h=$q_1234=$r='';
	$af = explode(',','time,asin,Price,Lowest,Soldby,Reviews,Rating,FiveNeeded,ReviewsPerStar,qa,Rank1,Rank2,LatestReviews,Brand,Model,Title,featuresPoints,cps');
	$values1=$values2=$values3=$values4=$time=$asin=$Price=$Lowest=$Soldby=$Reviews=$Rating=$fiveneeded=$p5=$p4=$p3=$p2=$p1=$qa=$Rank1=$Rank2=$LatestReviews=$Brand=$Model=$Title=$featuresPoints=$cps='';
	$asin_ca_numbers = file_get_contents($file);
	//~ echo $asin_ca_numbers .'<br>';
	$lines = explode("\n",$asin_ca_numbers);
	//2016-06-01 19:01:06	B003XELTTG	190.11	1773	4.8	282	1	RCC7
	$q1h = 'INSERT IGNORE INTO asin_ca_numbers (time, asin, price, lowest, soldby, reviews, avgrating, fiveneeded, p5, p4, p3, p2, p1, qa, rank1, rank2) VALUES '; //. trim($values1,',');
	$q2h = 'INSERT IGNORE INTO asin_ca_strings (asin,brand,sku,title,featuresPoints) VALUES '; // . trim($values2,',');
	$q3h = 'INSERT IGNORE INTO asin_ca_cps (asin,cps) VALUES '; // . trim($values3,',');
	$q4h = 'INSERT IGNORE INTO asin_ca_lrs (asin,LatestReviews) VALUES '; // . trim($values4,',');

	foreach($lines as $line) {
		$a = explode("\t",$line);
		//~ print_r($a); break;
		//~ if (count($a) < 7 || !preg_match('/(\d+\|){4}/',$a[7])) {echo "NO ReviewsPerStar: $line<br>\n"; continue;}
		if (count($a) < 7 and !preg_match('/(\d+\|){4}/',$a[7])) {echo "NO ReviewsPerStar: $line<br>\n"; continue;}
		if (strlen($line) < 10 || $a[0] == '' || $a[1] == '' || $a[2] == '' || $a[5] == 0) continue;
		$time = strtotime(substr($a[0],0,14) . '00:00');
		for($i=0;$i<count($af);$i++) {
			$f = $af[$i];
			$$f = $a[$i];
		}
		$aRp = explode('|',$ReviewsPerStar);
		if (count($aRp)==5) {$p5=$aRp[0];		$p4=$aRp[1];		$p3=$aRp[2];		$p2=$aRp[3];		$p1=$aRp[4];}

		$time = strtotime($time);
		$values1 = "('$time', '$asin', '$Price', '$Lowest', '$Soldby', '$Reviews', '$Rating', '$FiveNeeded', '$p5', '$p4', '$p3', '$p2', '$p1', '$qa', '$Rank1', '$Rank2');\n";
		$q1 .= $q1h . $values1;
		if ($Brand<>'' && $Model<>'') {
			$values2 = "('$asin', '$Brand', '$Model', '$Title', '$featuresPoints');\n";
			$q2 = $q2h . $values2 ;
		}
		if (stripos($cps,'/dp/')>1) {
			$values3 = "('$asin', '$cps');\n";
			$q3 .= $q3h . $values3 ;
		}
		if (stripos($LatestReviews,'out of 5 stars')>1) {
			$values4 = "('$asin', '$LatestReviews');\n";
			$q4 .= $q4h . $values4 ;
		}

	}
	if (strlen($q1) > 60) {$r = sqlquery($q1); $q_1234.= $q1 ."\n"; echo $q1;}
	//~ if (strlen($q2) > 60) {$r = sqlquery($q2); $q_1234.= $q2 ."\n";}
	//~ if (strlen($q3) > 60) {$r = sqlquery($q3); $q_1234.= $q3 ."\n";}
	//~ if (strlen($q4) > 60) {$r = sqlquery($q4); $q_1234.= $q4 ."\n";}

	if ($test==1) return str_replace("\n","<br>\n",$q_1234);
	if ($r <> false) {
		$rows = count(explode('),',$q_1234));
		echo $rows ." rows imported into asin_ca_numbers from $file<br>\n". str_replace(')',")<br>",$q_1234);
		file_put_contents(LOGFILE,date('Y-m-d H:i'). ' - '. $rows ." rows imported into asin_ca_numbers from $file\n", FILE_APPEND );
		return $rows;
	} else {
		return "<h2>sql error</h2>" . str_replace("\n",'<br>',$q_1234);
	}
}


?>
</html>