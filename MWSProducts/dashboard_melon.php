<head>
	<link rel="shortcut icon" href="mt.ico" type="image/x-icon" />
	<link href="lib/sortable/sortable_table.css" media="screen" rel="stylesheet" type="text/css" />
	<script type="text/javascript" language="javascript" src="lib/sortable/sorttable.js"></script>
	<link href="lib/TableFilter/filtergrid.css" media="screen" rel="stylesheet" type="text/css" />
	<script type="text/javascript" language="javascript" src="lib/TableFilter/tablefilter.js"></script>

	<link href="lib/jquery-ui-1.10.4.css" media="screen" rel="stylesheet" type="text/css" />
	<script type="text/javascript" language="javascript" src="lib/jquery.js"></script>
	<script type="text/javascript" language="javascript" src="lib/jquery-ui-1.10.4.min.js"></script>
	<script type="text/javascript" language="javascript" src="lib/jquery.dataTables.js"></script>
	<style>
		a.ispring:link,
		a.ispring:visited {
			background-color: blue;
			color: white;
			padding: 4px 25px;
			text-align: center;
			text-decoration: none;
			display: inline-block;
		}

		a.ispring:hover,
		a.ispring:active {
			color: blue;
			background-color: white;
		}

		a.compe:link,
		a.compe:visited {
			background-color: green;
			color: white;
			padding: 4px 25px;
			text-align: center;
			text-decoration: none;
			display: inline-block;
		}

		a.compe:hover,
		a.compe:active {
			color: green;
			background-color: white;
		}

		.dot {
			height: 12px;
			width: 12px;
			background-color: #bbb;
			border-radius: 50%;
			display: inline-block;
		}

		table {
			width: 10px;
			border: 1px solid black;
			empty-cells: hide;
		}

		table th>div,
		table td>div {
			overflow: hidden;
			height: 15px;
			font-size: 0.875em;
			/* 14px/16=0.875em */
			padding: 0px;
			border-bottom: 1px solid #ddd;
			vertical-align: top;
			text-align: center;
		}

		tr.hover:hover {
			background-color: #f5f5f5;
		}
	</style>
</head>
<?php
//~ if (stripos($_SERVER['HTTP_USER_AGENT'],'Mobile ')>1) { header('Location: good.dashboard.php'); exit; }
ini_set("error_log", basename(__FILE__, 'php') . 'error.log');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
include_once('lib/functions.php');
//~ include_once('lib/functions_dashboard.php');
define("EMAIL_RECEPIENTS", 'John@iSpringFilter.com');
define("PERIODS", '2 years,1 years,6 months,3 months,30 days,7 days,120 hours,24 hours');
define('TooltipHeaders', 'rank1|rank2|reviews|avgrating|price');
$mapDnsCountries = array('us' => 'com', 'ca' => 'ca', 'uk' => 'co.uk');
$timeoffset = 3600 * 3; //before daylight saving time
if (time() > strtotime('First Sunday Of March') and time() < strtotime('First Sunday Of November ')) $timeoffset = 3600 * 2;
$region = isset($_GET['region']) ? $_GET['region'] : 'us';
$dnsCountry = $mapDnsCountries[$region];
$table = 'asin_' . $region . '_numbers';
$image_width = 1380;

$period = '24 hours';
if (isset($_GET['period'])) $period = str_replace('+', ' ', $_GET['period']); //echo '<li>'. $period ;
switch (1) {
	case (preg_match('/years/', $period)):
		$dateFormat = '%Y.%m';
		$avgOrSum = 'avg';
		break;
	case (preg_match('/months/', $period)):
		$dateFormat = '%y.%m.W%v';
		$avgOrSum = 'avg';
		break;
	case (preg_match('/days/', $period)):
		$dateFormat = '%y.%m.%d%a';
		$avgOrSum = 'avg';
		break;
	case (preg_match('/hours/', $period)):
		$dateFormat = '%m/%d.%H';
		$avgOrSum = 'avg';
		break;
	default:
		$dateFormat = '%m/%d.%H'; //11/03_09
}

$aAsinSku = get_asin_sku_array();
$aSkuAsin = array_flip($aAsinSku);
$sAsinsWithRank1NotToBeDevidedBy1000 = '/TTG|B002C0A7ZY|B00Q798N8E|B005LJ8EXU/';

if (isset($_GET['q']) and $_GET['q'] == 'skuassign') {
	update_sku_assign();
	echo str_replace("\n", '<br>', list_sku_by_owner_new());
	exit;
}

//http://czyusa.com/mt/dashboard.php?region=us&period=30+days&items=B005LJ8EXU,RCC7AK_Eric;B006T3HYQ0,RCC7AK-UV_Eric;B06XD2KN2G,OLYMPIA%20OROS50;B00NWZ1RCK,APEC_PH75;B00HRHHFPW,APEC_RO90;
if (!empty($_GET) and !empty($_GET['items'])) {
	$days =  $period ? strtotime('now - ' . $period . ' - 3 hours') : strtotime('now - 1 years');
	//~ $image_height = isset($_GET['imageheight']) ? $_GET['imageheight'] : 500;
	//~ $aGroups = explode(";",trim($_GET['items'],';') .';');
	$aGroups[] = str_replace(";", "\n", trim($_GET['items'], ';')) . "\n";
	//echo "<pre> aGroups = ";var_dump($aGroups);echo "</pre>";exit;
	echo '<button type="button" onclick="javascript:history.back()">Back</button>   ';
} else {
	$days = strtotime('now - 27 hours');
	if ($region <> 'us') $days = strtotime('now - 120 hours');
	// $days = strtotime('now - 24 days');
	if (isset($_GET['owner'])) {
		$file = list_sku_by_owner_new($_GET['owner']);
	} else {
		$file = file_get_contents('http://czyusa.com/amazon.' . $region . '_asin_sku_competitors.txt');
	}
	//$aGroups = explode("\n\n",$file);
	//~ $image_width = 1400;
}
$assignee = array();
$date_from = 0;
$date_to = $days;
$date_sql = "time >= $days";
$rank1_date_sql = "updated >= FROM_UNIXTIME($days)";
$sellers = array();
$models = array();
$interval = 1;

if (!empty($_GET)) {
	if (!empty($_GET['assignee'])) {
		$getData = $_GET['assignee'];
		$assignee = explode(',', $getData);
	}
	if (!empty($_GET['dates'])) {
		$getData = $_GET['dates'];
		list($date_from, $date_to) = explode('to', $getData);
		$pattern = '/^(20[0-9]\d{1})(0?[1-9]|1[012])(0?[1-9]|[12][0-9]|3[01])$/';
		//$pattern = '/^\d{4}\d{1,2}\1\d{1,2}$/';
		$validDateFrom = preg_match($pattern, $date_from);
		$validDateTo = preg_match($pattern, $date_to);
		if ($validDateFrom && $validDateTo) {
			$date_from = strtotime($date_from);
			$date_to = strtotime($date_to);
			if ($date_from <= $date_to) {
				$date_sql = "(time >= $date_from and time < $date_to)";
				$rank1_date_sql = "(updated >= FROM_UNIXTIME($date_from) and updated < FROM_UNIXTIME($date_to))";
			}
		}
	}

	if (!empty($_GET['sellers'])) {
		$getData = $_GET['sellers'];
		$sellers = explode(',', $getData);
	}

	if (!empty($_GET['models'])) {
		$getData = $_GET['models'];
		$models = explode(',', $getData);
	}

	if (!empty($_GET['interval'])) {
		$interval = $_GET['interval'];
	}
}

$aAlias = retriveCmpetitorAlias();
$aGroupGoogle = getGroupsFromGoogleDoc($aAlias);
//var_dump($aAlias);
//printf("===================\n");
//var_dump($aGroupGoogle);



if (empty($aGroups)) {
	$aGroups = $aGroupGoogle;
}
foreach ($aGroups as $group) {
	if (substr($group, 0, 1) <> '-' and strlen($group) > 15) {
		//echo "<pre>";var_dump($group);echo "</pre>";
		MwsGroupNumbers($group, array('rank1'));
		GroupPriceAvgrating($group, array('price'));
		GroupPriceAvgrating($group, array('avgrating'));
	}
}

//printf("<pre>%s</pre>\n",var_dump($aAlias));
/////////////////////////////////////////////////////
function retriveCmpetitorAlias()
{
	$region = isset($_GET['region']) ? $_GET['region'] : 'us';
	//$file = file_get_contents('http://czyusa.com/amazon.'. $region .'_asin_sku_competitors.txt');
	$file = file_get_contents('/var/www/html/uat/mt/MWSProducts/asin_alias.txt');
	$aAliaGroups = explode("\n\n", $file);

	foreach ($aAliaGroups as $aAlianGroup) {
		if (substr($aAlianGroup, 0, 1) == '-' or strlen($aAlianGroup) <= 15) {
			continue;
		}
		$aAliaLines = explode("\n", $aAlianGroup);
		foreach ($aAliaLines as $line) {
			if (strpos($line, '--') > -1) {
				continue;
			}
			if (strpos($line, ',') == false) continue;
			$aVs = explode(',', $line);
			$aAlias[$aVs[0]] = $aVs[1];
		}
	}
	/*
	$aAlias = array();
	$select_amazon_items = "SELECT `asin`,`sku` FROM asin_us_strings";
	foreach(sqlquery($select_amazon_items) as $r){
		$aAlias[$r['asin']]=$r['sku'];
	}*/
	return $aAlias;
}

function getGroupsFromGoogleDoc($aAlias)
{
	$aGroup = array();
	$csvFile = file('https://docs.google.com/spreadsheets/d/e/2PACX-1vR2gY22xgcaR4JUr3naK5nXbFzw3pL_Ogn4msFRDGfVA8nILfEs-BOdxDRt2Jvhx9Yz31eAF8IfpjBn/pub?gid=0&single=true&output=csv');
	//$csvFile = file('./melon-dash-test.csv');
	foreach ($csvFile as $line) {
		$csv_check_data = str_getcsv($line);
		if (isset($_GET['tier'])) {
			if ($_GET['tier'] == $csv_check_data[2]) {
				$data[] = $csv_check_data;
			}
		} else {
			$data[] = $csv_check_data;
		}
		//$i += 1;
		//echo "<h3>$line</h3>";
		//if ($i>2) break; //testing
		//continue; //testing
	}
	foreach ($data as $line) {
		$csv_product_asin = substr($line[0], strrpos($line[0], '/') + 1);
		$csv_product_len = strlen($csv_product_asin);
		if ($csv_product_len == 10 && !preg_match('/[^A-Za-z0-9]/', $csv_product_asin)) {
			$product_asin = $csv_product_asin;
		}
		$line = $line[4];
		if (empty($line)) {
			continue;
		}
		if (strpos($line, '--') > -1) {
			continue;
		}
		if (strpos($line, ',') == false) {
			continue;
		}
		$aAsins = explode(',', $line);
		$line_str = '';

		foreach ($aAsins as $asin) {
			$asin = trim($asin);
			if (empty($aAlias[$asin])) {
				$alia = "NEW_ASIN";
			} else {
				$alia = $aAlias[$asin];
			}

			$new_str = sprintf("%s,%s\n", $asin, $alia);
			$line_str = $new_str . $line_str;
		}

		if (!empty($product_asin)) {
			if (empty($aAlias[$product_asin])) {
				$alia = "NEW_ASIN";
			} else {
				$alia = $aAlias[$product_asin];
			}
			$line_str = sprintf("%s,%s\n", $product_asin, $alia) .  $line_str;;
		}
		$aGroup[] = $line_str;
	}
	return $aGroup;
}

function melonQuery($sql)
{
	$ret = array();
	$link = mysqli_connect(
		'localhost',
		'mws',
		'mws9lBl88G2uvVtcHw$',
		'mws'
	);

	if (!$link) {
		printf("Can't connect to MySQL Server. Errorcode: %s ", mysqli_connect_error());
		exit;
	} else

	if ($result = mysqli_query($link, $sql)) {
		while ($row = mysqli_fetch_assoc($result)) {
			$ret[] = $row;
		}
		mysqli_free_result($result);
	}
	mysqli_close($link);
	return $ret;
}
function doDrawChart($aMapData, $plotType, $aTooltipData, $h = '', $title_x = '', $yLegents = '', $width = 1600, $height = 120, $yAxisType = 'linear')
{
	global $aColors, $iMap;

	$plot = new PHPlot($width, $height);
	# Disable error images, since this script produces HTML:
	//~ $plot->SetFailureImage(False);
	$plot->SetPlotType($plotType);
	$plot->SetYScaleType($yAxisType);  //Y axis in log or linear type
	//~ $plot->SetTitle($h);
	//$plot->SetPlotType('bars');
	$plot->SetYTitle($h);
	if ($h <> 'rank1') {
		$plot->SetYDataLabelAngle(90);
		$plot->SetMarginsPixels(NULL, NULL, 50);
	}
	if ($plotType == 'bars') {
		$plot->bar_width_adjust = 0.2;
		$plot->group_frac_width = 0.6;
	}
	$plot->SetPrintImage(False);  // Do not output the image
	$plot->SetFontTTF('x_label', '/var/www/html/uat/mt/lib/Yagora.ttf', 9);
	$plot->SetFontTTF('y_label', '/var/www/html/uat/mt/lib/Yagora.ttf', 8);
	if (count($aMapData) > 21) {
		$plot->SetXLabelAngle(45);
	}
	$plot->SetDataValues($aMapData);
	$plot->SetXTickIncrement(1);
	$plot->SetDrawXGrid(True);
	$plot->SetDrawYGrid(False);
	$plot->SetXTickAnchor(0.5);
	$plot->SetXTickLabelPos('none');
	$plot->SetXDataLabelPos('plotdown');
	$plot->TuneYAutoRange(1, 'R', 0);
	$plot->SetYDataLabelPos('plotin');
	$plot->data_value_label_distance = 0;
	$plot->SetYTickLabelPos('none');
	$plot->SetDataColors($aColors);
	$plot->SetLineStyles('solid');
	$plot->SetLineWidths(1);
	if (is_array($aTooltipData)) $plot->SetCallback('data_points', 'store_map'); //tooltip
	$plot->DrawGraph();
	$mapId = rand();
	echo '<map name="map' . $mapId . '">' . $iMap . '</map><img src="' . $plot->EncodeImage() . '" alt="Plot Image" usemap="#map' . $mapId . '">' . "\n";
	$iMap = '';
}
function DrawChartLinearBar($chunk, $aMapData, $aTooltipData, $h = '', $title_x = '', $yLegents = '', $width = 1600, $height = 120)
{
	$plotType = 'linepoints';
	if (stristr('rank1,reviewGap', $h) == FALSE) {
		$plotType = 'bars';
		$height = $height * 0.7;
	}

	if (count($aMapData) > $chunk) {
		$arrays = array_chunk($aMapData, $chunk);
		foreach ($arrays as $array) {
			$width = count($array) * 40;
			if ($width < 1400) $width = 1400;
			doDrawChart($array, $plotType, $aTooltipData, $h, $title_x, $yLegents, $width, $height);
		}
		return;
	}
	doDrawChart($aMapData, $plotType, $aTooltipData, $h, $title_x, $yLegents, $width, $height);
}

function GroupPriceAvgrating($group, $aH, $alter = '')
{
	global $models, $assignee, $region, $sellers, $dnsCountry, $timeoffset, $dateFormat, $table, $date_sql, $aColors, $image_width, $aTooltipData, $aSkus, $sAsinsWithRank1NotToBeDevidedBy1000;
	$qBaseSub = $q1Sub = $qAllSub = $items = '';
	$legend = '<div font-size: 0.875em; /* 14px/16=0.875em */>';
	$aLines = explode("\n", $group);
	//~ echo '<pre>'; 	print_r($aLines); 	echo '</pre>'; //testing
	foreach ($aH as $h) {
		$sh = substr($h, 0, 1) . substr($h, -2);
		$q1 = $q1Sub = '';
		for ($ln = 0; $ln < count($aLines); $ln++) {
			$line = $aLines[$ln];
			if (strpos($line, '--') > -1) {
				continue;
			}
			if (preg_match(';https://www.amazon.com/(\w+-\w+-\w+).*/dp/(B\w{9});', $line, $m)) {
				$line = $m[2] . ',' . $m[1];
			}
			$aVs = explode(',', $line);
			if (strpos($line, ',') == false) continue;
			$asin = $aVs[0];
			$sku = $aVs[1];
			$sku_names = explode(".", $sku);
			//logic to get asin name
			if (!empty($assignee) && isset($assignee)) {
				if (empty($sku_names[2]) or !(in_array("$sku_names[2]", $assignee) <> 0)) {
					goto GroupPriceAvgratingEnd;
				}
			}

			if (!empty($sellers) && isset($sellers)) {
				if (empty($sku_names[0]) or !(in_array("$sku_names[0]", $sellers)) <> 0) {
					continue;
				}
			}

			if (!empty($models) && isset($models)) {
				$matched_module = false;
				foreach ($models as $model) {
					if (stristr("$sku_names[1]", $model)) {
						$matched_module = true;
						break;
					}
				}
				if (!$matched_module) {
					continue;
				}
			}

			$modelName = empty($sku_names[1]) ? '' : $sku_names[1];
			$sellerName = $sku_names[0];
			if ($ln == 0) {
				$assigneeName = empty($sku_names[2]) ? '' : $sku_names[2];
				$sku = $sellerName . "." . $modelName . "." . $assigneeName;
			} else {
				$sku = $sellerName . "." . $modelName;
			}

			if (stripos($legend, $asin) == false) {
				$legend .= "<span class=\"dot\" style=\"background-color:" . $aColors[$ln] . "\"></span><a title=\"Open " . $asin . " at Amazon." . $dnsCountry . "\" target=_blank href=\"http://www.amazon." . $dnsCountry . "/dp/$asin\">$sku</a>&nbsp;&nbsp;|&nbsp;&nbsp;";
				$items .= $line . ';';
				$aSkus[] = $sku;
				$aAsins[] = $asin;
			}
			$sAsins = implode("','", $aAsins);
			$sSkus = implode(',', $aSkus);
			//~ if ($region <> 'us' or preg_match($sAsinsWith$hNotToBeDevidedBy1000,$group)==FALSE) {
			$op = '+';
			if ($h == 'rank1') $op = '-';
			if ($region <> 'ca' and $h == 'rank1') {
				$q1Sub .= ",SUM(IF(a.asin = '$asin', 0 $op IF(a.$sh<1000,a.$sh,ROUND(a.$sh/1000,1)), 0)) as '$sku $h' ";
			} else {
				$q1Sub .= ",SUM(IF(a.asin = '$asin', 0 $op a.$sh, 0)) as '$sku $h' ";
			}
			//~ if (stripos($group,'TTG')==FALSE) $qAllSub .= ", SUM(IF(a.asin = '$asin', IF(a.$sh<1000,a.$sh,ROUND(a.$sh/1000,1)), 0)) as '$sku $h' ";
			//~ if (stripos($group,'TTG')<>FALSE)
			if ($h == 'price') {
				$qAllSub .= ", CONCAT('$',SUM(IF(a.asin = '$asin', a.$sh, 0))) as '$sku $h' ";
			} else {
				$qAllSub .= ", SUM(IF(a.asin = '$asin', a.$sh, 0)) as '$sku $h' ";
			}
			//~ $tooltipheader .= $h .'|';

		} //end aLines loop
		//~ $tooltipheader .= $h ;
		//~ $qAllSub .= ", '$sku \n' as '$sku'";
		if (empty($sAsins)) {
			goto GroupPriceAvgratingEnd;
		}

		$decimal = 0;
		$sh = substr($h, 0, 1) . substr($h, -2);
		if ($h == 'avgrating') $decimal = 1;
		if ($h == 'price') {
			$decimal = 2;
		}
		$qBaseSub .= ", ROUND(avg($h),$decimal) as $sh ";
		$q1 = "SELECT dtime $q1Sub FROM (SELECT date_format(from_unixtime(time+$timeoffset),'$dateFormat') as dtime, asin $qBaseSub FROM $table WHERE asin IN ('$sAsins') AND $h > 0 AND $date_sql GROUP BY asin, dtime HAVING $sh>0 ) a GROUP BY dtime ORDER BY dtime DESC"; //limit 2
		echo "<pre>$q1</pre>"; //testing
		$aMapData = melonQuery($q1);
		//echo "<pre>". var_dump($aMapData). "</pre>";
		$image_height = 200;
		$image_height = count($aLines) * 35;
		if ($image_height < 150) $image_height = 150;
		//~ if ($_GET['items'] <>'') $image_height = 600;
		$image_width = count($aMapData) * 40;
		if ($image_width < 1400) $image_width = 1400;
		foreach (explode(',', PERIODS) as $dateRange) {
			if ((isset($_GET['period']) and $dateRange == $_GET['period']) or (empty($_GET['items']) and $dateRange == '24 hours')) {
				//~ echo $dateRange ."&nbsp;&nbsp;|&nbsp;&nbsp;";
				$legend .= '* ';
			}
			$legend .= '<a target=_blank title="Chart of ' . $dateRange . ' averange" href="' . basename(__FILE__) . '?region=' . $region . '&period=' . str_replace(' ', '+', $dateRange) . "&items=$items\">$dateRange</a>&nbsp;&nbsp;|&nbsp;&nbsp;";
		}
		$legend .= '<br></div>';
		if ($h == 'rank1') echo '<hr>' . $legend;
		if (is_array($aMapData) and !empty($aMapData)) DrawChartLinearBar(13, $aMapData, $aTooltipData, $h, 'time', '', $image_width, $image_height);

		GroupPriceAvgratingEnd: if (!empty($_GET['items'])) {
			$qAverage = str_replace($dateFormat, '%Y', $q1);
			$aAvgData = sqlquery($qAverage);
			echo '<br>Average of last <B>' . $_GET['period'] . '</B>';
			displayArrayToTable($aAvgData, "Average of last $dateRange");
			displayArrayToTable($aMapData, "Detail of last $dateRange");
		}
	}
}

function MwsGroupNumbers($group, $aH, $alter = '')
{
	global $interval, $models, $rank1_date_sql, $assignee, $region, $sellers, $dnsCountry, $timeoffset, $dateFormat, $table, $date_sql, $date_to, $aColors, $image_width, $aTooltipData, $aSkus, $sAsinsWithRank1NotToBeDevidedBy1000;
	$qBaseSub = $q1Sub = $qAllSub = $items = '';
	$legend = '<div font-size: 0.875em; /* 14px/16=0.875em */>';
	$aLines = explode("\n", $group);
	//echo '<pre> aLines='; 	var_dump($aLines); 	echo '</pre>'; //testing
	foreach ($aH as $h) {
		$sh = substr($h, 0, 1) . substr($h, -2);
		$q1 = $q1Sub = '';
		for ($ln = 0; $ln < count($aLines); $ln++) {
			$line = $aLines[$ln];
			if (strpos($line, '--') > -1) {
				continue;
			}
			if (preg_match(';https://www.amazon.com/(\w+-\w+-\w+).*/dp/(B\w{9});', $line, $m)) {
				$line = $m[2] . ',' . $m[1];
			}
			$aVs = explode(',', $line);
			if (strpos($line, ',') == false) continue;
			$asin = $aVs[0];
			$sku = $aVs[1];
			$sku_names = explode(".", $sku);
			//logic to get asin name
			if (!empty($assignee) && isset($assignee)) {
				if (empty($sku_names[2]) or !(in_array("$sku_names[2]", $assignee) <> 0)) {
					goto MwsGroupNumbersEnd;
				}
			}

			if (!empty($sellers) && isset($sellers)) {
				if (empty($sku_names[0]) or !(in_array("$sku_names[0]", $sellers)) <> 0) {
					continue;
				}
			}

			if (!empty($models) && isset($models)) {
				$matched_module = false;
				foreach ($models as $model) {
					if (stristr("$sku_names[1]", $model)) {
						$matched_module = true;
						break;
					}
				}
				if (!$matched_module) {
					continue;
				}
			}

			$modelName = empty($sku_names[1]) ? '' : $sku_names[1];
			$sellerName = $sku_names[0];
			if ($ln == 0) {
				$assigneeName = empty($sku_names[2]) ? '' : $sku_names[2];
				$sku = $sellerName . "." . $modelName . "." . $assigneeName;
			} else {
				$sku = $sellerName . "." . $modelName;
			}

			if (stripos($legend, $asin) == false) {
				$legend .= "<span class=\"dot\" style=\"background-color:" . $aColors[$ln] . "\"></span><a title=\"Open " . $asin . " at Amazon." . $dnsCountry . "\" target=_blank href=\"http://www.amazon." . $dnsCountry . "/dp/$asin\">$sku</a>&nbsp;&nbsp;|&nbsp;&nbsp;";
				$items .= $line . ';';
				$aSkus[] = $sku;
				$aAsins[] = $asin;
			}
			$sAsins = implode("','", $aAsins);
			//echo "<pre> sAsins =";var_dump($sAsins);echo "</pre>";
			$sSkus = implode(',', $aSkus);
			//~ if ($region <> 'us' or preg_match($sAsinsWith$hNotToBeDevidedBy1000,$group)==FALSE) {
			$op = '+';
			if ($h == 'rank1') $op = '-';
			if ($region <> 'ca' and $h == 'rank1') {
				$q1Sub .= ",SUM(IF(a.asin = '$asin', 0 $op IF(a.$sh<1000,a.$sh,ROUND(a.$sh/1000,1)), 0)) as '$sku $h' ";
			} else {
				$q1Sub .= ",SUM(IF(a.asin = '$asin', 0 $op a.$sh, 0)) as '$sku $h' ";
			}
			//~ if (stripos($group,'TTG')==FALSE) $qAllSub .= ", SUM(IF(a.asin = '$asin', IF(a.$sh<1000,a.$sh,ROUND(a.$sh/1000,1)), 0)) as '$sku $h' ";
			//~ if (stripos($group,'TTG')<>FALSE)
			if ($h == 'price') {
				$qAllSub .= ", CONCAT('$',SUM(IF(a.asin = '$asin', a.$sh, 0))) as '$sku $h' ";
			} else {
				$qAllSub .= ", SUM(IF(a.asin = '$asin', a.$sh, 0)) as '$sku $h' ";
			}
			//~ $tooltipheader .= $h .'|';

		} //end aLines loop
		//~ $tooltipheader .= $h ;
		//~ $qAllSub .= ", '$sku \n' as '$sku'";
		if (empty($sAsins)) {
			goto MwsGroupNumbersEnd;
		}
		$decimal = 0;
		$sh = substr($h, 0, 1) . substr($h, -2);
		if ($h == 'avgrating') $decimal = 1;
		if ($h == 'price') {
			$decimal = 2;
		}
		$qBaseSub .= ", ROUND(avg($h),$decimal) as $sh ";

		$limit = (24 / $interval) + 1;
		$q1 = "SELECT dtime $q1Sub FROM (SELECT date_format(from_unixtime(time+$timeoffset),'$dateFormat') as dtime, asin $qBaseSub FROM $table WHERE asin IN ('$sAsins') AND $h > 0 AND $date_sql GROUP BY asin, dtime HAVING $sh>0 ) a GROUP BY dtime ORDER BY dtime DESC"; //limit 2
		if ($h == 'rank1') $q1 = "SELECT dtime $q1Sub FROM (SELECT date_format(updated - INTERVAL HOUR(updated)%$interval HOUR,'$dateFormat') as dtime, asin $qBaseSub FROM mws_us WHERE asin IN ('$sAsins') AND $h > 0 AND $rank1_date_sql  GROUP BY asin, dtime HAVING $sh>0 ) a GROUP BY dtime ORDER BY dtime DESC LIMIT $limit"; //limit 2
		echo "<pre>$q1</pre>"; //			
		echo "  ";
		$aMapData = $aTooltipData = sqlquery($q1);
		$image_height = 200;
		$image_height = count($aLines) * 40;
		if ($image_height < 100) $image_height = 100;
		//~ if ($_GET['items'] <>'') $image_height = 600;
		$image_width = count($aMapData) * 40;
		if ($image_width < 1400) $image_width = 1400;
		foreach (explode(',', PERIODS) as $dateRange) {
			if ((isset($_GET['period']) and $dateRange == $_GET['period']) or (empty($_GET['items']) and $dateRange == '24 hours')) {
				//~ echo $dateRange ."&nbsp;&nbsp;|&nbsp;&nbsp;";
				$legend .= '* ';
			}
			$legend .= '<a target=_blank title="Chart of ' . $dateRange . ' averange" href="' . basename(__FILE__) . '?region=' . $region . '&period=' . str_replace(' ', '+', $dateRange) . "&items=$items\">$dateRange</a>&nbsp;&nbsp;|&nbsp;&nbsp;";
		}
		$legend .= '<br></div>';
		if ($h == 'rank1') echo '<hr>' . $legend;
		if (is_array($aMapData) and !empty($aMapData)) DrawChartLinearBar(24 / $interval + 1, $aMapData, $aTooltipData, $h, 'time', '', $image_width, $image_height);

		MwsGroupNumbersEnd: if (!empty($_GET['items'])) {
			$qAverage = str_replace($dateFormat, '%Y', $q1);
			$aAvgData = sqlquery($qAverage);
			echo '<br>Average of last <B>' . $_GET['period'] . '</B>';
			displayArrayToTable($aAvgData, "Average of last $dateRange");
			displayArrayToTable($aMapData, "Detail of last $dateRange");
		}
	}
}

function displayArrayToTable($array, $tableId = 1)
{
	/*
	$array = array( array("title"=>"rose", "price"=>1.25 , "number"=>15),
               array("title"=>"daisy", "price"=>0.75 , "number"=>25),
               array("title"=>"orchid", "price"=>1.15 , "number"=>7)
             );
	*/
	if (count($array) > 0) {
		echo '<table id="' . $tableId . '" class="sortable"><thead><tr class="hover"><th>' . implode('</th><th>', array_keys(current($array))) . "</th></tr></thead><tbody>";
		foreach ($array as $row) {
			if (!is_array($row) or empty($row)) continue;
			//~ echo '<pre>'; print_r($row); echo '</pre>';
			array_map('htmlentities', $row);
			echo '<tr class="hover"><td><div>' . implode('</div></td><td><div>', $row) . '</div></td></tr>';
		}
		echo "</tbody></table>";
	}
}

function store_map($img, $passthru, $shape = 'dot', $row, $column, $x, $y)
{
	global $iMap, $aTooltipData, $aSkus;
	///////////////////////////////////
	return;
	//////////////////////////////////
	# Link URL, for demonstration only:
	//~ $href = "javascript:alert('($row, $col)')";
	# Convert coordinates to integers:
	//~ $coords = sprintf("%d,%d,%d,%d", $x1, $y1, $x2, $y2);
	# Append the record for this data point shape to the image map string:
	//~ $iMap .= "  <area shape=\"rect\" coords=\"$coords\""
	//~ .  " title=\"$title\" alt=\"$alt\" href=\"$href\">\n";
	//~ define('MAP_RADIUS', 5); // Capture area circle radii
	$coords = sprintf("%d,%d,%d", $x, $y, 5);
	//~ $coords = sprintf("%d,%d,%d,%d", $x1, $y1, $x2, $y2);
	//~ $tooltip='$row='. $row .', $column='. $column .', $x='. $x .', $y='. $y; //$chartdata[1][$column];
	//~ $tooltip='$row='. $row .', $column='. $column; //$chartdata[1][$column];
	//~ $tooltip=$aTooltipData['header'] ."\n". preg_replace("/\n\t/","\n",implode("\t",$aTooltipData[$row])); //key statement that alians rows and columns in tooltip
	//~ $tooltip="Fields\t". $aTooltipData['header'] . implode("\t",$aTooltipData[$row]); //key statement that alians rows and columns in tooltip
	//~ $tooltip=$aTooltipData[$row]['dtime'] ."\t". implode("\t",$aSkus) . str_replace($aTooltipData[$row]['dtime'],"\n",implode("\t",$aTooltipData[$row])); //key statement that alians rows and columns in tooltip
	$tooltip = '';
	foreach ($aTooltipData[$row] as $k => $v) {
		if ($k <> 'dtime') $v = abs($v);
		$tooltip .= $k . '=' . $v . "\n";
	}
	# Required alt-text:
	$alt = implode("\t", $aSkus);
	$iMap .= "  <area shape=\"circle\" coords=\"$coords\" title=\"$tooltip\">\n";
}

function list_sku_by_owner_new($owner = '')
{
	// global $aAsinSku,$aSkuAsin;
	$lines = $sCompAsins = $ownerfilter = '';
	if ($owner <> '') $ownerfilter = "WHERE owner='$owner'";
	$q = "SELECT DISTINCT tier,asin,sku,owner,comp,lastupdated FROM `GoogleSheet4LA1Tier` $ownerfilter ORDER BY lastupdated DESC, tier, sku, asin, owner;";
	$aSqlQueryResults = sqlquery($q);
	array_multisort($aSqlQueryResults);
	foreach ($aSqlQueryResults as $r) {
		$sku = trim($r['sku']);
		//~ $tier=$r['tier'];
		$asin = trim($r['asin']);
		$tier = trim($r['tier']);
		$owner = trim($r['owner']);
		//~ if (preg_match('-(B\w{9})-',$asin)) $lines .= $asin .','. $sku .','. $tier .','. $owner ."\n";
		if (count($r) > 3 and preg_match_all('/B0\w{8}/', ' ' . $r['comp'], $m)) {
			// echo "<pre>"; print_r($m[0]); echo "</pre>";
			foreach ($m[0] as $v) {
				// echo "<pre>"; print_r($v); echo "</pre>";
				$sCompAsins .= trim($v) . ',' . trim($v) . "\n";
			}
		}
		// echo str_replace("\n",'<br>',$asin .','. $sku ."\n". $sCompAsins ."\n\n");
		$lines .= $asin . ',' . $sku . "\n" . $sCompAsins . "\n\n";
		$sCompAsins = '';
		// $lines .= $asin .','. $sku ."\n\n"; //. $sCompAsins ."\n\n";
	}
	return $lines;
}

function list_sku_by_owner($owner = '')
{
	global $aAsinSku, $aSkuAsin;
	if ($owner <> '') $ownerfilter = "WHERE owner='$owner'";
	$q = "SELECT DISTINCT url,sku,tier,owner FROM `sku_assign` $ownerfilter ORDER BY lastupdated DESC, tier, sku ";
	foreach (sqlquery($q) as $r) {
		$sku = $r['sku'];
		//~ $tier=$r['tier'];
		$url = $r['url'];
		$tier = $r['tier'];
		$owner = $r['owner'];
		//~ if (preg_match('-http-',$url)) $lines .= $url .','. $sku .','. $tier .','. $owner ."\n";
		if (preg_match('-/(B\w{9})-', $url, $m)) {
			$asin = $m[1];
		} else {
			$asin = $aSkuAsin[$sku];
		}
		//~ if (preg_match('-(B\w{9})-',$asin)) $lines .= $asin .','. $sku .','. $tier .','. $owner ."\n";
		if (preg_match('-(B\w{9})-', $asin) and stristr($lines, $sku) == FALSE) $lines .= $asin . ',' . $sku . "\n\n";
	}
	//~ print_r( $aSkuAsin );
	//~ print_r( $aLines );
	return $lines;
}

function update_sku_assign()
{
	global $aAsinSku, $aSkuAsin;
	$list_url = 'https://docs.google.com/spreadsheets/d/e/2PACX-1vR2gY22xgcaR4JUr3naK5nXbFzw3pL_Ogn4msFRDGfVA8nILfEs-BOdxDRt2Jvhx9Yz31eAF8IfpjBn/pub?gid=0&single=true&output=tsv';
	$tsv = file_get_contents($list_url);
	$tsv = str_replace(' ', '', $tsv);
	$q = '';
	//~ echo $tsv;
	foreach (explode("\n", $tsv) as $line) {
		$vs = explode("\t", $line);
		if (count($vs) < 3) continue;
		$line = trim($line);
		$values = date('Y-m-d H:i:s') . "','" . str_replace("\t", "','", $line);
		$q .= "INSERT IGNORE INTO sku_assign VALUES ('$values');\n";
	}
	//~ if (sqlquery($q)<>FALSE) echo str_replace("\n",'<br>',$q);
	// sqlquery($q);
}


?>