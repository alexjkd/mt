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
th.rotate {
  /* Something you can count on */
  height: 140px;
  white-space: nowrap;
}
th.rotate > div {
  transform:
    /* Magic Numbers */
    translate(25px, 51px)
    /* 45 is really 360 - 45 */
    rotate(315deg);
  width: 30px;
}
th.rotate > div > span {
  border-bottom: 1px solid #ccc;
  padding: 5px 10px;
}
</style>
</head><body>
<?php
ini_set("error_log", basename(__FILE__,'php') . 'error.log');
date_default_timezone_set('America/New_York');
include_once('../lib/functions.php');
$selfphp = basename(__FILE__);
//~ $lid_link_array = get_lid_link_array();
$actions = "OidSummary,LidSummary,details,realtime,listkws,special";
$periods = 'Monthly,Weekly,Daily,Hourly';
$period = $_GET['period'] ? $_GET['period'] : 'Weekly';
switch($period){
	case('Yearly'): $sDateFormat = '%Y'; $days = 730; break;
	case('Monthly'): $sDateFormat = '%Y-%m'; $days = 365; break;
	case('Weekly'): $sDateFormat = '%Y-%m Wk%u'; $days = 90; break;
	case('Daily'): $sDateFormat = '%Y-%m-%d'; $days = 30; break;
	case('Hourly'): $sDateFormat = '%Y-%m-%d H%H'; $days = 1; break;
	//~ default: $sDateFormat = '%Y-%m-%d'; break;
	default: $sDateFormat = '%Y-%m-%d'; $days = 7; break;
}
global $period, $action, $days, $sDateFormat;
$action = $_GET['action'];
switch($action) {
	case('special'):
		$q = "SELECT date_format(c.date,'%Y-%m-%d') as time, gid, str as link, count(ip) as clicks FROM `seolinkgroups_v2_clicks` c inner join seolinkgroups_v2_clicks_hash on (hash=lid) WHERE str LIKE '%$kw%' AND gid in ('overstock bathroom faucet','newegg reverse osmosis system','sears reverse osmosis water system under sink','amazon water softener') group by lid,gid ORDER BY date desc,clicks DESC";
		$headers = 'time,gid,link,clicks';
		summary($q,$headers);
		break;
	case('listkws'):
		$qr = sqlquery("SELECT DISTINCT * FROM seolinkgroups_v2 WHERE type='KW' ORDER BY gid");
		$headers="gid,kws";
		foreach($qr as $r) {
			$gid = $r['gid'];
			$sKws .= ','. $r['data'];
		}
		$aKws = explode(',',$sKws);
		$aKws = array_unique($aKws);
		array_multisort($aKws);
		$sKws = trim(implode(',',$aKws),',');
		$sKws = preg_replace('/[\+\-_\s+]/',' ',$sKws);
		echo str_replace(',','<br>',$sKws);
		break;
	case('realtime'):
		$q = "SELECT DATE_FORMAT( c.date,  '%Y-%m-%d %H:%i' ) AS time, oid, gid, lid, str AS link, ip
		FROM seolinkgroups_v2_clicks c INNER JOIN seolinkgroups_v2_clicks_hash h ON ( lid = hash )
		WHERE c.date >= DATE( NOW( ) - INTERVAL 1 HOUR ) ORDER BY TIME DESC, lid DESC LIMIT 1000;";
		$headers = 'time,gid,link,oid,ip';
		//~ foreach($qr as $r) {
			//~ $sHtmlRow = '';
			//~ foreach(explode(',',$sHeader) as $h) {$$h = $r[$h]; $sHtmlRow .= '<td>'. $$h .'</td>';}
			//~ $sHtmlRow = str_replace($link,'<a target=_blank title="'. $link .'" href="'. $link .'">'. substr($link,0,80) .'</a>',$sHtmlRow);
			//~ $sHtmlRow = str_replace($oid,"<a target=_blank title=\"OID details\" href=\"$selfphp?action=details&period=Weekly&id=$oid\">$oid</a>",$sHtmlRow);
			//~ $i++;
			//~ $sHtmlTableRows .= "<tr><td>$i</td>$sHtmlRow</tr>";
		//~ }
		//~ echo '<table id="table1" class="sortable filterable" width="100%"><tr><th>#</th><th>'. str_replace(',','</th><th>',str_replace('gid','Phase',$sHeader)) . '</th></tr>'. $sHtmlTableRows . '</table>';
		summary($q,$headers);
		break;
	case('details'):
		$id = $_GET['id'];
		$idFilter = preg_match('/FO/',$id) ? " AND oid = '$id' " : " AND lid = '$id' ";
		if ($period=='Daily' or $period=='Hourly') {
			$q = "SELECT date_format(c.date,'$sDateFormat') as time, oid, str as link, ip
						FROM `seolinkgroups_v2_clicks` c inner join seolinkgroups_v2_clicks_hash on (lid=hash)
						WHERE c.date >= DATE(NOW() - INTERVAL $days DAY) $idFilter
						AND str like '%$str%' ORDER BY time DESC;";
			$headers = 'time,gid,oid,link,ip';
			Summary($q, $headers);
			break;
		}
	case('OidSummary'):
		$qr = sqlquery("select oid, count(ip) as clicks from seolinkgroups_v2_clicks group by oid having clicks > 100 order by substring(oid,14)");
		$q = "select date_format(date,'$sDateFormat') as Time, sum(IF(oid LIKE 'FO%', 1, 0 ) ) AS 'ALL'";
		foreach($qr as $r) {
			$oid = $r['oid'];
			$aOids[] = $oid;
			preg_match('/(FO\w{10})_/',$oid,$mFo);
			$fo = $mFo[1];
			$htmlTableHeader .= ',<a target=_blank href="https://www.fiverr.com/users/chenzy73/orders/'. $fo .'">'. str_replace($fo .'_','',$oid) .'</a>';
			$q .= ",\nsum(IF(oid='$oid',1,0)) as '$oid'"; //key statement for
			//~ break; //for testing one oid
		}
		$q .= " FROM seolinkgroups_v2_clicks group by Time order by time desc;";
		$rows = sqlquery($q);
		if (empty($qr)) {	return "NO RESULT from query<br>$q"; }
		$aHeaders = explode(',',preg_replace('/\s','',$headers));
		foreach($rows as $row) {
			//~ print_r($row);
			//~ foreach($aHeaders as $v) { echo $v$$v = $r[$v]; print_r($r[$v]);}
			//~ preg_match('/_\$(\d+)/',$oid,$matches_dollars);
			//~ preg_match('/(FO.+?)_/',$oid,$matches_orderid);
			//~ preg_match('/_(\w+?)_/',$oid,$matches_gigid);
			$s = $row['Time'] .','. $row['ALL'];
			foreach($aOids as $oid) { $s .= ','. $row[$oid];}
			//~ echo $s .'<br>';
			//~ break;
			$aChart[] = explode(',',$s);
			$htmlTable .= '<tr><td>'. str_replace(',','</td> <td>',$s) .'</td></tr>';
		}
		//~ print_r($aChart);
		drawchart($aChart, '', 'Time', 'ALL,'. implode(',', $aOids), 1300, 250);
		$html = '<a href="linkgroups_v2_clicks.php?action=OidSummary&period=Monthly">Monthly</a>&nbsp;|&nbsp;<a href="linkgroups_v2_clicks.php?action=OidSummary&Da=Weekly">Weekly</a>&nbsp;|&nbsp;<a href="linkgroups_v2_clicks.php?action=OidSummary&period=daily">Daily</a>&nbsp;|&nbsp;<a href="linkgroups_v2_clicks.php?action=OidSummary&period=Hourly">Hourly</a>';
		$html .= '<table id="table1" class="sortable filterable" width="100%"><tr><th>'. str_replace(',','</th><th>', 'Time,ALL,'. trim($htmlTableHeader,',')) .'</th></tr>'. $htmlTable .'</table>';
		//~ echo rotate_table_header($html);
		echo $html;
		break;
	case('LidSummary'):
		/*$q = "select date_format(c.date,'$sDateFormat') as time, gid,lid,str as link,count(ip) as clicks
					from seolinkgroups_v2_clicks c inner join seolinkgroups_v2_clicks_hash h on (lid=hash)
					where c.date >= DATE(NOW() - INTERVAL $days DAY) AND gid NOT LIKE '%KEYWORDS%' $sLinkFilter
					group by time,lid having clicks > 1
					order by time desc;"; */
		$pid = $_GET['pid']? preg_replace('/(_|-|\s|\+)+/','%',$_GET['pid']):'http';
		$kw = $_GET['kw']? preg_replace('/(_|-|\s|\+)+/','+',$_GET['kw']):'';
		$str = $_GET['str'];
		$site = $_GET['site'];
		If (strlen($site) > 1) $linkFilter .= " AND str LIKE '%$site%'";
		If (strlen($pid) > 1) $linkFilter .= " AND str LIKE '%$pid%'";
		If (strlen($kw) > 1) $linkFilter .= " AND str LIKE '%=$kw'";
		If (strlen($str) > 1 ) $linkFilter .= " AND str LIKE '%$str%'";
		$q = "
		SELECT date_format(c.date,'$sDateFormat') AS time, oid, str AS link, count(ip) AS clicks
		FROM `seolinkgroups_v2_clicks` c INNER JOIN seolinkgroups_v2_clicks_hash ON (lid=hash)
		WHERE c.date >= DATE(NOW() - INTERVAL $days DAY)
		$idFilter
		$linkFilter
		GROUP BY time, lid
		ORDER BY time DESC,lid,clicks DESC;";
		/*if (!empty($_GET['str'])) {
			$sLinkFilter = str_replace(" ","+",$_GET['str']);
			$sLinkFilter = str_replace("_","%",$sLinkFilter);
			//~ $sLink = str_replace('_','',$sLinkFilter);
			$q = "SELECT date_format(c.date,'%Y-%m-%d') as time,gid,lid,str as link,count(ip) as clicks FROM `seolinkgroups_v2_clicks` c left join seolinkgroups_v2_clicks_hash h on (lid=hash)
					where lid in (select hash as lid from seolinkgroups_v2_clicks_hash where str like '%$sLinkFilter%')
					group by time, lid
					ORDER BY `time`  DESC";
		}*/
		$headers = 'time,gid,clicks,link';
		Summary($q, $headers);
		break;
	default:
		//~ throw new Exception("Undefind action.");
		$goto = basename(__FILE__) ."?action=realtime";
		header(sprintf('Location: %s', $goto));
		break;
}
function Summary($q,$headers){
	global $days, $period, $action, $actions;
	foreach(sqlquery($q) as $r) {
		$lineNumber++;
		$htmlTableRow = "<tr><td>$lineNumber</td>";
		foreach(explode(',',$headers) as $h) {$$h = $r[$h];}
		//~ preg_match('/=([\w\+\s]+)$/',$link,$mKw);
		if (!preg_match('/(B0\d{8}|_nkw|qu|keyword|keywords|cm_re|search)=([\w\+\s]+)/i',$link,$mKw)) continue;
		//~ if (strpos('-'. $lid,'LK') <> 1) continue;
		$kw = $mKw[2];
		$phase = str_replace('+',' ',$kw);
		preg_match('/:\/\/(.+?)\//',$link,$mSite);
		$site = $mSite[1];
		$link = "<a target=_blank href=\"$link\">$link</a>";
		$lid = $r['lid'];
		if (empty($idFilter)) $clicks = "<a target=_blank href=\"linkgroups_v2_clicks.php?action=details&period=$period&id=$lid\">$clicks</a>";
		switch($site) {
			case('www.amazon.ca'): $gid = '<a target=_blank href="'. str_replace('#KWS',$kw,'https://www.amazon.ca/s/ref=nb_sb_noss?url=search-alias%3Daps&field-keywords=#KWS') .'">'. $kw .'</a>'; break;
			case('www.amazon.com'): $gid = '<a target=_blank href="'. str_replace('#KWS',$kw,'https://www.amazon.com/s/ref=nb_sb_noss?url=search-alias%3Daps&field-keywords=#KWS') .'">'. $kw .'</a>'; break;
			case('www.ebay.com'): $gid = '<a target=_blank href="http://www.ebay.com/sch/i.html?_from=R40&_nkw='. $kw .'">'. $phase .'</a>'; break;
			case('www.overstock.com'): $gid = '<a target=_blank href="https://www.overstock.com/search?keywords='. $kw .'">'. $phase .'</a>'; break;
			case('www.homedepot.com'): $gid = '<a target=_blank href="http://www.homedepot.com/s/'. $kw .'?NCNI-5">'. $phase .'</a>'; break;
			case('www.rakuten.com'): $gid = '<a target=_blank href="http://www.rakuten.com/sr/searchresults#qu='. $kw .'">'. $phase .'</a>'; break;
			case('www.sears.com'): $gid = '<a target=_blank href="http://www.sears.com/search='. $kw .'">'. $phase .'</a>'; break;
			case('www.newegg.com'):
				preg_match('/cm_re=(\w+)-_-/',$link,$mKw);
				$kw = $mKw[1];
				$gid = '<a target=_blank href="http://www.newegg.com/Product/ProductList.aspx?Submit=ENE&DEPA=0&Order=BESTMATCH&Description='. $kw .'">'. $kw .'</a>';
				break;
			default:
				break;
		}
		foreach(explode(',',$headers) as $h) {$htmlTableRow .= "<td>". $$h . '</td>';}
		$htmlTableRows .=  $htmlTableRow . '</tr>';
	}
	ListActions();
	echo str_repeat('&nbsp;',6). "<a href=\"$selfphp\" title=\"$q\"><b>sql</b></a>";
	echo '<table id="table1" class="sortable filterable" width="100%"><tr><th>#</th><th>'. str_replace(',','</th><th>',str_replace('gid','Phase',$headers)) . '</th></tr>'. $htmlTableRows . '</table>';
}
function rotate_table_header($html){
	$html = str_replace('<th>','<th class="rotate"><div><span>',$html);
	$html = str_replace('</th>','</span></div></th>',$html);
	return $html;
}
function ListActions() {
	global $actions, $periods, $period;
	$currentURL = $_SERVER['REQUEST_URI'];
	echo "<b>Actions:</b> ";
	foreach(explode(',',$actions) as $action) {echo "<a href=$selfphp?action=$action&period=$period>$action</a>  |   ";}
	echo str_repeat('&nbsp;',6). "<b>Periods:</b> ";
	foreach(explode(',',$periods) as $p) {$nav .= $p==$period ? "$p | " : '<a href="'. preg_replace('/period=\w+/',"period=$p",$currentURL) .'">'. $p .'</a> | ' ;}
	echo $nav;
}
function chart($q, $ylable, $skuArray) {
	$rows = sqlquery($q);
	foreach($rows as $row) {
		$s = date('m/d_H',$row['time']);
		foreach($skuArray as $asin=>$sku) {
			$s .= ','. str_replace(',','',0-$row[$sku]);
		}
		$array[] = explode(',',$s);
		//~ print_r($s ."<br>");
		$i++; //if ($i>24*7) break;
	}
	//~ print_r($array);
	//~ $array = array_reverse($array);
	echo '<table id="table1" class="sortable filterable" width="100%"><tr><td style="text-align:center; ">';
	foreach($skuArray as $asin=>$sku) {
		$items .= $asin .',' . $sku .';';
		//~ $class = strpos($sku,'_',1) > 1 ? 'compe' : 'ispring';
		//~ echo "<a class=\"$class\" target=_blank href=\"http://www.amazon.com/dp/$asin\">$sku</a>&nbsp;";
		echo "<a target=_blank href=\"http://www.amazon.com/dp/$asin\">$sku</a>&nbsp;&nbsp;&nbsp;";
	}
	if ($_GET['items'] == '' && count($skuArray) > 1)	echo "&nbsp;&nbsp;&nbsp;<a target=_blank href=\"dashboard.php?items=$items\">History</a>";

	echo '</td></tr><tr><td>';
	drawchart($array,'','time',$ylable,1300,250);
	echo '</td></tr></table>';

	$html_table_data = '<table id="table1" class="sortable filterable" width="100%"><tr><td>time&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>';
	foreach($skuArray as $asin=>$sku) {
		$html_table_data .= '<td><a target=_blank href="rank/bestsellerrank.comp.php?asin='. $asin .'">'. $sku .'</td>';
	}
	$html_table_data .= '</tr>';
	foreach($array as $row) {
		$html_table_data .= '<tr>';
		for ($r = 0; $r<=count($skuArray); $r++) {
			$html_table_data .= '<td>'. trim($row[$r],'-') .'</td>';
		}
		$html_table_data .= '</tr>';
	}
	return $html_table_data .'</table>';
}
function get_lid_link_array() {
	$array = array();
	$q = "select distinct str as link, hash as lid from seolinkgroups_v2_clicks_hash";
	$qr = sqlquery($q);
	foreach($qr as $row) {
		$lid = $row['lid'];
		$link = $row['link'];
		$array[$lid] = $link;
	}
	//~ print_r($array); exit;
	return $array;
}


?>
</body></html>