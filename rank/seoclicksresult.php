<html><head>
<title>SEO Links Traffic</title>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<link rel="icon" href="../lib/images/Iconicon-Alpha-Magnets-Letter-ico" >
<link href="../lib/sortable/sortable_table.css" media="screen" rel="stylesheet" type="text/css" />
<link href="../lib/TableFilter/filtergrid.css" media="screen" rel="stylesheet" type="text/css" />
<script type="text/javascript" language="javascript" src="../lib/sortable/sorttable.js"></script>
<script type="text/javascript" language="javascript" src="../lib/TableFilter/tablefilter.js"></script>
</head><body>
<?php
ini_set("error_log", basename(__FILE__,'php') . 'error.log');
include_once('../lib/functions.php');
$asin_sku_array = get_asin_sku_array();

$site = $_GET['site'];
$kw = $_GET['kw'];
$asin = $_GET['asin'];

try {
handle();
} catch(Exception $e) {
echo $e->getMessage();
}
function handle() {
	if(empty($_GET['action'])) {
		throw new Exception("No action.");
	}
	$action = $_GET['action'];
	switch($action) {
		case 'lidsummary':
		$result = lidsummary();
		break;
	case 'oidsummary':
		$result = oidsummary();
		break;
	default:
		throw new Exception("Undefind action.");
		break;
	}
	echo $result;
}

function details($site,$kw,$asin) {
	global $asin_sku_array;
	$header = "time,site,kw,asin,sku,sr,lid,oid,ip";
	$header_array = explode(',',$header);
	$kw = str_ireplace(' ','+',$kw);
	//~ $q = "select distinct * from seoclicks_amz where site = '". $site ."' and kw = '". $kw ."' and asin = '". $asin ."' order by time desc";
	$q = "select distinct * from seoclicks_amz where site = '$site' and kw = '$kw' and asin = '$asin' order by time desc";
	//~ echo $q;
	$qr = sqlquery($q);
	foreach ($qr as $r) {
		$row = '';
		foreach($header_array as $v) {
			if (preg_match('/time/',$v)) $r[$v] = date('Y-m-d H:i',$r[$v]);
			if ($v == 'sku') $r[$v] = $asin_sku_array[$r['asin']];
			$row .= "<td>". $r[$v] ."</td>";
		}
		$rows .= "<tr>$row</tr>";
	}
	echo '<table id="table1" class="sortable filterable" width="100%"><tr><th>'. str_ireplace(',','</th><th>',$header) ."</tr>". $rows . "</table>";
}

function lidsummary() {
	global $asin_sku_array;
	$header = "start,end,site,kw,asin,sku,sr,oid,clicks";
	$header_array = explode(',',$header);
	$q = "select max(time) as end, min(time) as start, site, kw, asin, sr, oid, count(ip) as clicks from seoclicks_amz group by site,kw,asin,sr order by asin desc,kw,start desc";
	$qr = sqlquery($q);
	foreach ($qr as $r) {
		$row = '';
		//~ $kw = '<a target=_blank href="http://'. $r['site'] .'/s/ref=nb_sb_noss_2?url=search-alias%3Daps&field-keywords='. $r['kw'] .'">'. $r['kw'] .'</a>';
		//~ $clicks = "<a target=_blank href=". basename(__FILE__) ."?kw=". $r['clicks'] ."&asin=". $r['asin'] .">". $r['clicks'] ."</a>";
		$r['clicks'] = "<a target=_blank href=". basename(__FILE__) ."?site=". $r['site'] ."&kw=". $r['kw'] ."&asin=". $r['asin'] .">". $r['clicks'] .'</a>';
		$r['sku'] = $asin_sku_array[$r['asin']];
		$r['kw'] = '<a target=_blank href="http://'. $r['site'] .'/s/ref=nb_sb_noss_2?url=search-alias%3Daps&field-keywords='. $r['kw'] .'">'. $r['kw'] .'</a>';
		$r['asin'] = '<a target=_blank href="http://'. $r['site'] .'/dp/'. $r['asin'] .'">'. $r['asin'] .'</a>';
		$colorhash = 'FF'. hash("crc32b",$r['kw'] . $r['asin']);
		foreach($header_array as $v) {
			$$v = $r[trim($v)];
			if (preg_match('/start|end/',$v)) $$v = date('Y-m-d H:i',$r[$v]);
			//~ if ($v=='kw')
			//~ if ($v=='clicks')
			//~ if ($v == 'sku')
			$row .= "<td>". $$v ."</td>";
		}
		$rows .= "<tr bgcolor=$colorhash>$row</tr>";
	}
	echo '<table id="table1" class="sortable filterable" width="100%"><tr><th>'. str_ireplace(',','</th><th>',$header) ."</tr>". $rows . "</table>";
}
function oidsummary() {
	global $asin_sku_array;
	$header = "start,end,hours,oid,hourly,clicks";
	$header_array = explode(',',$header);
	$q = "SELECT  from_unixtime(min(time)) as start,  from_unixtime(max(time)) as end, round(((max(time) - min(time)))/3600) as hours, oid,round(count(ip)/(max(time) - min(time))*3600) as hourly, count(ip) as clicks FROM `seoclicks_amz` group by oid order by clicks desc,hourly desc";
	$qr = sqlquery($q);
	foreach ($qr as $r) {
		$row = '';
		foreach($header_array as $v) {
			$$v = $r[trim($v)];
			if ($v=='oid') $$v = '<a target=_blank href="https://www.fiverr.com/users/chenzy73/orders/'. $$v .'">'. $$v. "</a>";
			$row .= "<td>". $$v ."</td>";
		}
		$rows .= "<tr>$row</tr>";
	}
	echo '<table id="table1" class="sortable filterable" width="100%"><tr><th>'. str_ireplace(',','</th><th>',$header) ."</tr>". $rows . "</table>";
}
?>
</body></html>