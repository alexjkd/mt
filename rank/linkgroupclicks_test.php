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
$text = "select time, gid, lid, oid, ip from seoclicks_linkgroups\n";
$text.= "select weight, gid, lid, link from linkgroups\n";
?>
<!-- HTML form -->
<form action="" method="post">
<textarea type="text" name="text" value="text" rows="2" cols="150"/><?php echo htmlspecialchars($text) ?></textarea>
<input type="submit" name="query" value="Query"/>
</form>

<?php
//~ $lid_link_array = get_lid_link_array();

// check if form has been submitted
if (isset($_POST['query']) && isset($_POST['text'])) {
	$q = $_POST['text'];
	//~ preg_match('/select\s+(?P<header>.+)\sfrom/',$q,$matches);
	preg_match_all('/\b(?P<headers>\w+),/',$q,$matches);
	$headers = $matches['headers'];
	print_r($headers);
	//~ $qr = sqlquery($q);
	//~ if (empty($qr)) exit;
	//~ $headers = ($header);
	exit;
}


function get_lid_link_array() {
	$headers = "gid,lid,link";
	$q = "select distinct * from seoclicks_linkgroups";
	$qr = sqlquery($q);
	foreach($qr as $r) {
		foreach(explode(',',$headers) as $v) {
			$$v = $r[$v];
		}
	}
}

$id = $_GET['id'];
if (!empty($id)) {
	details($id);
	//~ exit;
} else {
	sumarry();
}

function sumarry() {
	$header = "start,end,oid,gid,link,clicks";
	$header_array = explode(',',$header);
	//~ $q = "SELECT  from_unixtime(min(time)) as start,  from_unixtime(max(time)) as end, round(((max(time) - min(time)))/3600) as hours, oid,round(count(ip)/(max(time) - min(time))*3600) as hourly, count(ip) as clicks FROM `seoclicks_amz` group by oid order by clicks desc,hourly desc";
	//~ $q = "select max(time) as end, min(time) as start, count(ip) as clicks from seoclicks_linkgroups c left join seolinkgroups g on (c.lid=g.lid and c.gid=g.gid) where c.lid <> '' group by c.lid order by end desc";
	$q = "SELECT from_unixtime(min(time+3600*3)) as start, from_unixtime(max(time+3600*3)) as end, oid, c.gid, c.lid, link, count(distinct ip) as clicks FROM `seoclicks_linkgroups` c left join seolinkgroups g on (c.lid = g.lid) group by link order by end desc, gid, link";
	$qr = sqlquery($q);
	echo "Total rows: ". count($qr);
	foreach ($qr as $r) {
		$row = '';
		//~ preg_match('/(?P<market>ebay|homedepot).+(?:keyword|kw|qu)=(?P<kw>.+)/',$r['link'],$matches);
		$r['link'] = '<a target=_blank href="'. $r['link'] .'">'. $r['link'] .'</a>';
		$r['oid'] = '<a target=_blank href='. basename(__FILE__) .'?id='. $r['oid'] .'>'. substr(substr($r['oid'],0,12),-4) .'</a>&nbsp;<a target=_blank href=https://www.fiverr.com/users/chenzy73/orders/'. substr($r['oid'],0,12) .'>Fiverr</a>';
		$r['gid'] = "<a target=_blank href=". basename(__FILE__) ."?id=". $r['gid'] .">". substr($r['gid'],-4) .'</a>';
		$r['clicks'] = "<a target=_blank href=". basename(__FILE__) ."?id=". $r['lid'] .">". $r['clicks'] .'</a>';
		$row = '';
		foreach($header_array as $v) {
			$$v = $r[trim($v)];
			//~ if (preg_match('/start|end/',$v)) $$v = date('Y-m-d H:i',$r[$v]);
			$row .= "<td>". $$v ."</td>";
		}
		$rows .= "<tr>$row</tr>";
	}
	echo '<table id="table1" class="sortable filterable" width="100%"><tr><th>'. str_ireplace(',','</th><th>',$header) ."</tr>". $rows . "</table>";
}

function details($id) {
	switch(substr($id,0,1)) {
		case 'F': //oid=FO78A36F4C07_serg4554
			$q = "select distinct from_unixtime(time+3600*3) as time, oid, c.gid, link, ip from seoclicks_linkgroups c left join seolinkgroups g on (c.lid = g.lid) where oid = '$id' order by time desc,gid,ip";
			$header='time,oid,gid,link,ip';
			break;
		case 'l': //lid=link904692800
			$q = "select distinct from_unixtime(time+3600*3) as time, oid, c.gid, c.lid, link, ip, count(distinct ip) as clicks from seoclicks_linkgroups c left join seolinkgroups g on (c.lid = g.lid) where ip in (select ip from seoclicks_linkgroups where lid = '$id') group by c.lid,ip order by time desc,c.gid,link";
			$header='time,oid,gid,lid,link,ip,clicks';
			break;
		default: //gid=searse565fa52
			$q = "select from_unixtime(time+3600*3) as time, oid, c.gid, link, c.lid, ip, count(distinct ip) as clicks from seoclicks_linkgroups c left join seolinkgroups g on (c.lid = g.lid) where c.gid in (select gid from seoclicks_linkgroups where gid = '$id') group by c.lid,ip order by time desc,c.gid,link";
			$header='time,oid,gid,lid,link,ip,clicks';
			break;

	}
	$header_array = explode(',',$header);
	$kw = str_ireplace(' ','+',$kw);
	$qr = sqlquery($q);
	echo "$q &nbsp; Total rows: ". count($qr);
	foreach ($qr as $r) {
		$row = '';
		foreach($header_array as $v) {
			$r['oid'] = substr($r['oid'],0,12);
			$r['gid'] = substr($r['gid'],-4);
			$r['lid'] = substr($r['lid'],-4);
			$row .= "<td>". $r[$v] ."</td>";
		}
		$rows .= "<tr>$row</tr>";
	}
	echo '<table id="table1" class="sortable filterable" width="100%"><tr><th>'. str_ireplace(',','</th><th>',$header) ."</tr>". $rows . "</table>";
}
?>
</body></html>