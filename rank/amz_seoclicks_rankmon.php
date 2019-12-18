<html><head>
<title>SEO Links Traffic</title>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<link rel="icon" href="../lib/images/Iconicon-Alpha-Magnets-Letter-ico" >
<link href="../lib/sortable/sortable_table.css" media="screen" rel="stylesheet" type="text/css" />
<link href="../lib/TableFilter/filtergrid.css" media="screen" rel="stylesheet" type="text/css" />
<script type="text/javascript" language="javascript" src="../lib/sortable/sorttable.js"></script>
<script type="text/javascript" language="javascript" src="../lib/TableFilter/tablefilter.js"></script>
<style>
td th {
	width: 1px;
	white-space: nowrap;
}
</style>
</head><body>
<?php
ini_set("error_log", basename(__FILE__,'php') . 'error.log');
include_once('../lib/functions.php');
include_once('../lib/geoipcity.inc');
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
	if(empty($_GET['action']) and !empty($_GET['asin'])) {
		//~ throw new Exception("No action.");
		$_GET['action'] = 'details';
	}
	$action = $_GET['action'];
	switch($action) {
		case 'lidsummary':
		$result = lidsummary();
		break;
	case 'oidsummary':
		$result = oidsummary();
		break;
	case 'details':
		$result = details();
		break;
	default:
		throw new Exception("Undefind action.");
		break;
	}
	echo $result;
}

function details() {
	global $asin_sku_array;
	$gi = geoip_open("../lib/GeoLiteCity.dat",GEOIP_STANDARD);
	$site = $_GET['site'];
	$kw   = $_GET['kw'];
	$asin = $_GET['asin'];

	$header = "time,site,kw,asin,sku,sr,oid,clicks";
	$header_array = explode(',',$header);
	$kw = str_ireplace(' ','+',$kw);
	//~ $q = "select distinct * from seoclicks_amz_rankmon where site = '". $site ."' and kw = '". $kw ."' and asin = '". $asin ."' order by time desc";
	$q = "select distinct max(time) as time, site, kw, asin, sr, oid, count(ip) as clicks  from seoclicks_amz_rankmon where site = '$site' and kw = '$kw' and asin = '$asin' group by sr order by time desc";
	//~ echo $q;
	$qr = sqlquery($q);
	foreach ($qr as $r) {
		$row = '';
		foreach($header_array as $v) {
			if (preg_match('/time/',$v)) $r[$v] = date('Y-m-d H:i',$r[$v]);
			if ($v == 'sku') $r[$v] = $asin_sku_array[$r['asin']];
			//~ if ($v == 'ip') {
				//~ $ip = $r['ip'];
				//~ $geoArray = unserialize(file_get_contents('http://www.geoplugin.net/php.gp?ip='.$r['ip']));
				//~ $geo = geoip_record_by_addr($gi,$ip);
				//~ print_r($geo); break;
				//~ $r['country'] = $geo->country_name;
				//~ $r['ip'] = $ip;
			//~ }
			//~ break;
			$row .= "<td>". $r[$v] ."</td>";
		}
		$rows .= "<tr>$row</tr>";
	}
	echo '<B>Amazon Traffic Details of a Link</B><table id="table1" class="sortable filterable" width="auto"><tr><th>'. str_ireplace(',','</th><th>',$header) ."</tr>". $rows . "</table>";
	geoip_close($gi);
}

function lidsummary() {
	global $asin_sku_array;
	$header = "start,end,site,kw,asin,sku,sr,clicks";
	$header_array = explode(',',$header);
	$q = "select max(time) as end, min(time) as start, site, kw, asin, sr, count(ip) as clicks from seoclicks_amz_rankmon group by site,kw,asin having clicks > 100 order by end desc,kw,asin desc";
	$qr = sqlquery($q);
	foreach ($qr as $r) {
		$row = '';
		//~ $kw = '<a target=_blank href="http://'. $r['site'] .'/s/ref=nb_sb_noss_2?url=search-alias%3Daps&field-keywords='. $r['kw'] .'">'. $r['kw'] .'</a>';
		//~ $clicks = "<a target=_blank href=". basename(__FILE__) ."?kw=". $r['clicks'] ."&asin=". $r['asin'] .">". $r['clicks'] ."</a>";
		$r['clicks'] = "<a target=_blank href=". basename(__FILE__) ."?site=". $r['site'] ."&kw=". $r['kw'] ."&asin=". $r['asin'] .">". $r['clicks'] .'</a>';
		$r['sku'] = $asin_sku_array[$r['asin']];
		$r['sr'] = str_ireplace('sr_1_','',$r['sr']);
		$r['kw'] = '<a target=_blank href="http://'. $r['site'] .'/s/ref=nb_sb_noss_2?url=search-alias%3Daps&field-keywords='. $r['kw'] .'">'. $r['kw'] .'</a>';
		$r['asin'] = '<a target=_blank href="http://'. $r['site'] .'/dp/'. $r['asin'] .'">'. $r['asin'] .'</a>';
		$colorhash = 'fff'. hash("crc32b",$r['kw'] . $r['asin']);
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
	echo '<B>Amazon Traffic Summary</B><table id="table1" class="sortable filterable" width="auto"><tr><th>'. str_ireplace(',','</th><th>',$header) ."</tr>". $rows . "</table>";
}
function oidsummary() {
	global $asin_sku_array;
	$header = "start,end,hours,oid,hourly,clicks";
	$header_array = explode(',',$header);
	$q = "SELECT  from_unixtime(min(time)) as start,  from_unixtime(max(time)) as end, round(((max(time) - min(time)))/3600) as hours, oid,round(count(ip)/(max(time) - min(time))*3600) as hourly, count(ip) as clicks FROM seoclicks_amz_rankmon group by oid having count(ip) > 500 order by hourly desc,clicks desc,end desc";
	$qr = sqlquery($q);
	foreach ($qr as $r) {
		$row = '';
		foreach($header_array as $v) {
			$$v = $r[trim($v)];
			if ($v=='hours') $$v = $$v .' ('. round($$v/24,1) .' days)';
			if ($v=='oid') {
				$link = substr($$v,0,strpos($$v,'_'));
				$$v = '<a target=_blank href="https://www.fiverr.com/users/chenzy73/orders/'. $link .'">'. $$v. "</a>";
			}
			$row .= "<td>". $$v ."</td>";
		}
		$rows .= "<tr>$row</tr>";
	}
	echo '<B>Amazon Traffic OID Performance Summary</B><BR><table id="table1" class="sortable filterable" width="auto"><tr><th>'. str_ireplace(',','</th><th>',$header) ."</tr>". $rows . "</table>";
}
?>
</body></html>