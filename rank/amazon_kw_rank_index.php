<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "https://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="https://www.w3.org/1999/xhtml">
<head>
<meta https-equiv="Content-Type" content="text/html; charset=UTF-8" />
<link rel="shortcut icon" href="../lib/images/amazon.png" >
<link rel="icon" type="image/gif" href="../lib/images/amazon.png" >
<title><?php echo $tablename; ?></title>
<link href="../lib/sortable/sortable_table.css" media="screen" rel="stylesheet" type="text/css" />
<script type="text/javascript" language="javascript" src="../lib/sortable/sorttable.js"></script>
<link href="../lib/TableFilter/filtergrid.css" media="screen" rel="stylesheet" type="text/css" />
<script type="text/javascript" language="javascript" src="../lib/TableFilter/tablefilter.js"></script>
<style>td.class=hideshowe show:hover + div .hidden {
    display:block;
    visibility:visible;
}</style>
</head><body>
<?php
include_once('../lib/functions.php');
ini_set("error_log", basename(__FILE__,'php') . 'error.log');
define("EMAIL_RECEPIENTS","john@ispringfilter.com");
define("AMAZON_SEARCH_URL",'https://www.amazon.com/s/ref=sr_pg_1?fst=as%3Aon&rh=n%3A228013%2Cn%3A3754161%2Cn%3A13397631%2Cn%3A13397611%2Ck%3AKEYWORDS&page=1&keywords=KEYWORDS&ie=UTF8&lo=tools
');
$colspan = substr_count($valueNames,',');
if(isset($_GET['kw'])){
	$kw = str_ireplace(' ','_',$_GET['kw']);
	$region = 'com'; //for testing
	$tablename = 'akr_'. $kw;
} else {
	echoError("tablename $tablename EMPTY");
	exit;
}
if (is_sqltableexist($tablename) == false) {
	echo "tablename $tablename NOT exist";
	exit -2;
}
$pid_title_array = get_pid_title($tablename);
if (!empty($_GET['pid'])) {
	$pid = $_GET['pid'];
	if (strpos($pid,';')>-1) $pid .= ';';
	$aPid = explode(';',$pid);
	foreach($aPid as $pid) {
		$q = "select date_format(from_unixtime(time),'%Y.%m.%d') as dtime,round(avg(rank),1) as rank,pid,title,concat(max(price),' - ',min(price)) as price,rating,reviews from `$tablename` where pid = '$pid' group by dtime order by dtime desc";
		echo '<b><a target=_blank href='. str_replace('_','+',str_replace('KEYWORDS',$kw,AMAZON_SEARCH_URL)) .">$kw</a></b><br>"; //$q ";
		$rows = sqlquery($q);
		foreach($rows as $row) {
			$headers = 'dtime,rank,pid,title,price,rating,reviews';
			foreach(explode(',',$headers) as $vn) {$$vn = $row[$vn]; }
			if ($title == '') $title = $pid_title_array[$pid];
			//~ $time = date('m/d/Y H:i',$time);
			//~ $arrayChart[] = array(date('m/d_H',$row['time']),0 - $rank,$reviews);
			$htmlTableBody .= "<tr><td>$dtime</td><td>$rank</td><td><a target=_blank href=https://www.amazon.com/dp/$pid>$pid</a></td><td>$title</td><td>\$$price</td><td>$rating</td><td>$reviews</td></tr>";
			if ($reviews>100) {$reviews = $reviews/100; $y_title = "rank, reviews/100";}
			$arrayChart[] = array($dtime,0 - $rank,$reviews);
		}
		drawchart($arrayChart,substr($title,0,72),'time',$y_title,1200,350);
		echo '<table id="table1" border=1 width="100%" class="sortable filterable"><thead><tr><th>'. str_ireplace(',','</th><th>',$headers) .'</th></tr></thead><tbody>'. $htmlTableBody .'</tbody></table>';
		// include_once('../lib/TableFilter/TableFilterConfig.js');
	}
} else {
	$daysBack = strtotime('today - 5 days');
	$q = "select max(time) as time, round(avg(rank),1) as rank,pid,title,title,concat(max(price),' - ',min(price)) as price,rating,reviews from `$tablename` where time > $daysBack group by pid order by rank limit 1000";
	$rows = sqlquery($q);
	$headers = 'time,rank,pid,title,price,rating,reviews';
	foreach($rows as $row) {
		foreach(explode(',',$headers) as $h) {$$h = $row[$h];};
		$time = date('Y.m.d H:i',$row['time']);
		//~ $time = $row['time'];
		//~ $rank = $row['rank'];
		//~ $pid = $row['pid'];
		//~ $title = $row['title'];
		//~ $price = $row['price'];
		//~ $rating = $row['rating'];
		//~ $reviews = $row['reviews'];
		$htmlTableBody .= "<tr><td>$time</td><td><a target=_blank href=amazon_kw_rank_index.php?pid=$pid&kw=$kw>$rank</a></td><td><a target=_blank href=https://www.amazon.com/dp/$pid>$pid</a></td><td>$title</td><td>\$$price</td><td>$rating</td><td>$reviews</td></tr>";
		continue;
		foreach(explode(',',$headers) as $vn) {
			$$vn = $row[$vn];
			$csvrow .= $$vn .',';
		}
		if (substr_count($csvrow,',') == $colspan+1) $csv .= trim($csvrow,',') ."\n";
		$csvrow = '';
	}
	if (preg_match('/akr_/',$tablename) > 0) {
		$kw = str_ireplace('akr_','',$tablename);
		$url = '<b>Amazon Keyword Search: <a target=_blank href="'. str_replace('KEYWORDS',str_replace('_','+',$kw),AMAZON_SEARCH_URL) .'">'. $kw. '</a></b>';
	} else if (stripos($tablename,'cat_') >0) {
		$kw = str_replace('hdcat_','',$tablename);
		$url = '<b>Amazon Best Sellers in Category: <a target=_blank href="https://www.amazon.com/dp/'.  str_ireplace('_','-',$kw) .'/N-5yc1vZ'. $catid .'?&style=Grid&Nao=0">'. $kw. '</a></b>';
	}
	echo '<b>'. $url .'</b><br><table id="table1" border=1 width="100%" class="filterable"><thead><tr><th>'. str_ireplace(',','</th><th>',$valueNames) .'</th></tr></thead><tbody>'. $htmlTableBody .'</tbody></table>';
	include_once('../lib/TableFilter/TableFilterConfig.js');
}

function get_pid_title($tablename) {
	$q = "select distinct pid,title from `$tablename`";
	foreach (sqlquery($q) as $row) {
		$asin_title_array[$row['pid']] = $row['title'];
	}
	return $asin_title_array;
}

?>
 </body>
</html>
</html>