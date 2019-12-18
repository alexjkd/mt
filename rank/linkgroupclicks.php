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
//~ $lid_link_array = get_lid_link_array();

function get_lid_link_array() {
	$array = array();
	$headers = "gid,lid,link";
	$q = "select distinct $headers from seolinkgroups";
	$qr = sqlquery($q);
	foreach($qr as $row) {
		$lid = $row['lid'];
		$gid = $row['gid'];
		$link = $row['link'];
		$array[$lid] = array('gid'=>$gid,'link'=>$link);
	}
	//~ print_r($array); exit;
	return $array;
}


try {
handler();
} catch(Exception $e) {
echo $e->getMessage();
}
function handler() {
	if(empty($_GET['action']) and !empty($_GET['asin'])) {
		//~ throw new Exception("No action.");
		$_GET['action'] = 'details';
	}
	$action = $_GET['action'];
	//~ if (preg_match('/link\d+/',$_GET['id'])) $action = 'lidsummary';
	//~ if (preg_match('/FO\w+/',$_GET['id'])) $action = 'oidsummary';
	switch($action) {
		case 'lidsummary':
		$result = lidsummary();
		break;
	case 'oidsummary':
		$result = oidsummary();
		break;
	case 'details':
		$result = details($_GET['id']);
		break;
	default:
		throw new Exception("Undefind action.");
		break;
	}
	echo $result;
}


function lidsummary() {
	if (!empty($_GET['str'])) $str = " and link like '%" . $_GET['str'] . "%' ";
	$lid_link_array = get_lid_link_array();
	$header = "start,last,oid,gid,link,clicks";
	$header_array = explode(',',$header);
	//~ $q = "SELECT  from_unixtime(min(time)) as start,  from_unixtime(max(time)) as last, round(((max(time) - min(time)))/3600) as hours, oid,round(count(ip)/(max(time) - min(time))*3600) as hourly, count(ip) as clicks FROM `seoclicks_amz` group by oid order by clicks desc,hourly desc";
	//~ $q = "select from_unixtime(max(time) +3600 *3) as last, from_unixtime(min(time) +3600 *3) as start, oid, lid, count(ip) as clicks, ' ' as gid, ' ' as link from seoclicks_linkgroups c where lid <> '' and ip <> '' group by c.lid order by last desc";
	//~ $q = "SELECT from_unixtime(min(time+3600*3)) as start, from_unixtime(max(time+3600*3)) as last, oid, c.gid, c.lid, link, count(distinct ip) as clicks FROM `seoclicks_linkgroups` c left join seolinkgroups g on (c.lid = g.lid) group by link order by last desc, gid, link";
	$q = "SELECT from_unixtime(max(time) +3600 *3) as last, from_unixtime(min(time) +3600 *3) as start, c.oid, g.lid, count(ip) as clicks, g.gid, g.link FROM seoclicks_linkgroups c, seolinkgroups g WHERE c.lid = g.lid $str group by link order by last desc";
	echo $q;
	$qr = sqlquery($q);
	echo "<B>" . $_GET['str'] . " Traffic Summary</B> Total rows: ". count($qr);
	foreach ($qr as $r) {
		$lid = $r['lid'];
		$r['gid'] = $lid_link_array[$lid]['gid'];
		$r['link'] = $lid_link_array[$lid]['link'];
		$row = '';
		//~ preg_match('/(?P<market>ebay|homedepot).+(?:keyword|kw|qu)=(?P<kw>.+)/',$r['link'],$matches);
		$r['link'] = '<a target=_blank href="'. $r['link'] .'">'. $r['link'] .'</a>';
		$r['oid'] = '<a target=_blank href='. basename(__FILE__) .'?id='. $r['oid'] .'&action=details>'. substr(substr($r['oid'],0,12),-4) .'</a>&nbsp;<a target=_blank href=https://www.fiverr.com/users/chenzy73/orders/'. substr($r['oid'],0,12) .'>Fiverr</a>';
		$r['gid'] = "<a target=_blank href=". basename(__FILE__) ."?id=". $r['gid'] ."&action=details>". substr($r['gid'],-4) .'</a>';
		$r['clicks'] = "<a target=_blank href=". basename(__FILE__) ."?id=". $r['lid'] ."&action=details>". $r['clicks'] .'</a>';
		$row = '';
		foreach($header_array as $v) {
			$$v = $r[trim($v)];
			//~ if (preg_match('/start|last/',$v)) $$v = date('Y-m-d H:i',$r[$v]);
			$row .= "<td>". $$v ."</td>";
		}
		$rows .= "<tr>$row</tr>";
	}
	echo '<table id="table1" class="sortable filterable" width="auto"><tr><th>'. str_ireplace(',','</th><th>',$header) ."</tr>". $rows . "</table>";
}

function oidsummary() {
	global $asin_sku_array;
	$header = "start,end,hours,oid,hourly,clicks";
	$header_array = explode(',',$header);
	$q = "SELECT  from_unixtime(min(time)) as start,  from_unixtime(max(time)) as end, round(((max(time) - min(time)))/3600) as hours, oid,round(count(ip)/(max(time) - min(time))*3600) as hourly, count(ip) as clicks FROM seoclicks_linkgroups c group by oid having count(ip) > 500 order by hourly desc,clicks desc,end desc";
	$qr = sqlquery($q);
	foreach ($qr as $r) {
		$row = '';
		foreach($header_array as $v) {
			$$v = $r[trim($v)];
			if ($v=='hours') {$$v = $$v .' ('. round($$v/24,1) .' days)';  }
			if ($v=='oid') {
				$link = substr($$v,0,strpos($$v,'_'));
				$$v = '<a target=_blank href="https://www.fiverr.com/users/chenzy73/orders/'. $link .'">'. $$v. "</a>";
			}
			$row .= "<td>". $$v ."</td>";
		}
		$rows .= "<tr>$row</tr>";
	}
	echo '<B>New Channels Traffic OID Performance Summary</B><table id="table1" class="sortable filterable" width="auto"><tr><th>'. str_ireplace(',','</th><th>',$header) ."</tr>". $rows . "</table>";
}

function details($id) {
	switch(substr($id,0,1)) {
		case 'F': //oid=FO78A36F4C07_serg4554
			$q = "select from_unixtime(time+3600*3, '%m/%d/%y') as date, oid, count(ip) as clicks from seoclicks_linkgroups where oid like '%$id%' group by date order by date desc";
			$header='date,oid,clicks';
			break;
		case 'l': //lid=link904692800
			//~ $q = "select distinct from_unixtime(time+3600*3) as time, oid, c.gid, c.lid, link, ip, count(distinct ip) as clicks from seoclicks_linkgroups c left join seolinkgroups g on (c.lid = g.lid) where ip in (select ip from seoclicks_linkgroups where lid = '$id') group by c.lid,ip order by time desc,c.gid,link";
			//~ $q = "select distinct from_unixtime(time+3600*3) as time, oid, ' ' as gid, c.lid, ' ' as link, ip, count(ip) as clicks from seoclicks_linkgroups c where lid = '$id' group by ip order by time desc";
			$q = "select from_unixtime(time+3600*3, '%m/%d/%y') as date, c.oid, g.gid, g.lid, link, count(ip) as clicks from seoclicks_linkgroups c, seolinkgroups g where c.lid=g.lid and c.lid = '$id' group by date,oid order by date desc,clicks desc";
			$header='date,oid,clicks,gid,lid,link';
			break;
		default: //gid=searse565fa52
			$q = "select from_unixtime(time+3600*3) as time, oid, c.gid, link, c.lid, ip, count(distinct ip) as clicks from seoclicks_linkgroups c left join seolinkgroups g on (c.lid = g.lid) where c.gid in (select gid from seoclicks_linkgroups where gid = '$id') group by c.lid,ip order by time desc,c.gid,link";
			$header='time,oid,clicks,gid,lid,link,ip';
			break;

	}
	$lid_link_array = get_lid_link_array();
	$header_array = explode(',',$header);
	$kw = str_ireplace(' ','+',$kw);
	$qr = sqlquery($q);
	echo "<B>New Channels Traffic Details Of a Link - Total rows: </b>". count($qr) . "<br>". $q ."<br>";
	foreach ($qr as $r) {
		$n++;
		$row = "<td>$n</td>";
		$r['link'] = '<a target=_blank href='. $lid_link_array[$r['lid']]['link'] .'>'. $lid_link_array[$r['lid']]['link'] .'</a>';
		$r['gid'] = $lid_link_array[$r['lid']]['gid'];
		foreach($header_array as $v) {
			//~ $r['oid'] = substr($r['oid'],0,12);
			//~ $r['gid'] = substr($r['gid'],-4);
			//~ $r['lid'] = substr($r['lid'],-4);
			//~ $r['oid'] = preg_replace('/(FO\w+)_(\w+)/','https://www.fiverr.com/users/chenzy73/orders/$1'. basename(__FILE__) .'?oid='. $r['oid'], $r['oid']);
			$row .= "<td>". $r[$v] ."</td>";
		}
		$rows .= "<tr>$row</tr>";
	}
	$rows = preg_replace('/(FO\w{10})_*(\w+)*/','<a target=_blank href=https://www.fiverr.com/users/chenzy73/orders/$1>$1</a>&nbsp;<a target=_blank href='. basename(__FILE__). '?id=$1_$2&action=details>$2</a>', $rows);
	echo '<table id="table1" class="sortable filterable" width="100%"><tr><th>row#</th><th>'. str_ireplace('date</th>','M/D/Y</th>',str_ireplace(',','</th><th>',$header)) ."</tr>". $rows . "</table>";
}
?>
</body></html>