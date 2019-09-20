<head><link rel="shortcut icon" href="mt.ico" type="image/x-icon" />
<link href="lib/sortable/sortable_table.css" media="screen" rel="stylesheet" type="text/css" />
<script type="text/javascript" language="javascript" src="lib/sortable/sorttable.js"></script>
<link href="lib/TableFilter/filtergrid.css" media="screen" rel="stylesheet" type="text/css" />
<script type="text/javascript" language="javascript" src="lib/TableFilter/tablefilter.js"></script>

<link href="lib/jquery-ui-1.10.4.css" media="screen" rel="stylesheet" type="text/css" />
<script type="text/javascript" language="javascript" src="lib/jquery.js"></script>
<script type="text/javascript" language="javascript" src="lib/jquery-ui-1.10.4.min.js"></script>
<script type="text/javascript" language="javascript" src="lib/jquery.dataTables.js"></script>
<style>
a.ispring:link, a.ispring:visited {
    background-color: blue;
		color: white;
    padding: 4px 25px;
    text-align: center;
    text-decoration: none;
    display: inline-block;
}
a.ispring:hover, a.ispring:active {
	color: blue;
	background-color: white;
}
a.compe:link, a.compe:visited {
    background-color: green;
    color: white;
    padding: 4px 25px;
    text-align: center;
    text-decoration: none;
    display: inline-block;
}
a.compe:hover, a.compe:active {
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

table th > div, table td > div {
    overflow: hidden;
    height: 15px;
		font-size: 0.875em; /* 14px/16=0.875em */
		padding: 0px;
		border-bottom: 1px solid #ddd;
		vertical-align: top;
		text-align: center;
}
tr.hover:hover {background-color:#f5f5f5;}

</style>
</head>
<?php
//~ if (stripos($_SERVER['HTTP_USER_AGENT'],'Mobile ')>1) { header('Location: good.dashboard.php'); exit; }
ini_set("error_log", basename(__FILE__,'php') . 'error.log');
include_once('lib/functions.php');
//~ include_once('lib/functions_dashboard.php');
define("EMAIL_RECEPIENTS",'John@iSpringFilter.com');
define("PERIODS",'2 years,1 years,6 months,3 months,30 days,7 days,120 hours,24 hours');
define('TooltipHeaders','rank1|rank2|reviews|avgrating|price');
$mapDnsCountries=array('us'=>'com','ca'=>'ca','uk'=>'co.uk');
$timeoffset = 3600 * 2; //before daylight saving time
if (time() > strtotime('First Sunday Of March') and time() < strtotime('First Sunday Of November ') ) $timeoffset = 3600 * 3;
$region = isset($_GET['region']) ? $_GET['region'] : 'us';
$dnsCountry=$mapDnsCountries[$region];
$table = 'asin_'. $region .'_numbers';
$image_width = 1380;

$period='24 hours';
if (isset($_GET['period'])) $period = str_replace('+',' ',$_GET['period']); //echo '<li>'. $period ;
switch(1){
	case (preg_match('/years/',$period)): $dateFormat = '%Y.%m'; $avgOrSum='avg'; break;
	case (preg_match('/months/',$period)): $dateFormat = '%y.%m.W%v';  $avgOrSum='avg'; break;
	case (preg_match('/days/',$period)): $dateFormat = '%y.%m.%d%a';  $avgOrSum='avg'; break;
	case (preg_match('/hours/',$period)): $dateFormat = '%m/%d.%H'; $avgOrSum='avg'; break;
	default: $dateFormat = '%m/%d.%H'; //11/03_09
}

$aAsinSku=get_asin_sku_array();
$aSkuAsin=array_flip($aAsinSku);
$sAsinsWithRank1NotToBeDevidedBy1000='/TTG|B002C0A7ZY|B00Q798N8E|B005LJ8EXU/';

if ( isset($_GET['q']) and $_GET['q']=='skuassign' ) {
	update_sku_assign();
	echo str_replace("\n",'<br>',list_sku_by_owner());
	exit;
}

//http://czyusa.com/mt/dashboard.php?region=us&period=30+days&items=B005LJ8EXU,RCC7AK_Eric;B006T3HYQ0,RCC7AK-UV_Eric;B06XD2KN2G,OLYMPIA%20OROS50;B00NWZ1RCK,APEC_PH75;B00HRHHFPW,APEC_RO90;
if (!empty($_GET) and !empty($_GET['items'])) {
	$days =  $period ? strtotime('now - '. $period . ' - 3 hours') : strtotime('now - 1 years');
	//~ $image_height = isset($_GET['imageheight']) ? $_GET['imageheight'] : 500;
	//~ $aGroups = explode(";",trim($_GET['items'],';') .';');
	$aGroups[] = str_replace(";","\n",trim($_GET['items'],';')) ."\n";
	echo '<button type="button" onclick="javascript:history.back()">Back</button>   ';
} else {
	$days = strtotime('now - 27 hours');
	if ($region <> 'us') $days = strtotime('now - 240 hours');
	// $days = strtotime('now - 24 days');
	if (isset($_GET['owner'])) {
		$file=list_sku_by_owner($_GET['owner']);
	} else {
		$file = file_get_contents('http://czyusa.com/amazon.'. $region .'_asin_sku_competitors.txt');
	}
	$aGroups = explode("\n\n",$file);
	//~ $image_width = 1400;
}
/*
if ($region=='us' and isset($_GET['list'])) {
	reviewGap('B003XELTTG','B00I0ZGOZM','RCC7-apecRO50');
	reviewGap('B005LJ8EXU','B00NWZ1RCK','RCC7AK-apecPH75'); //, ROUND((MAX(IF(asin='B005LJ8EXU',reviews,0)) - MAX(IF(asin='B00NWZ1RCK',reviews,0)))/10,1) AS 'RCC7AK-apecPH75'
}*/
if ($region=='us' and isset($_GET['list'])) {
	$group='B005LJ8EXU,RCC7AK_Eric vs B00NWZ1RCK,APEC_PH75;B003XELTTG,RCC7 vs B00I0ZGOZM,APEC RO50;';
	groupNumbers($group,'review gap');
}
foreach($aGroups as $group) {
	if (substr($group,0,1)<>'-' and strlen($group)>15) groupNumbers($group);
	//~ break;
}

function OLDreviewGap($asin1,$asin2,$title){
	global $table,$image_width,$aTooltipData;
	$sumif="ROUND(MAX(IF(asin='$asin1',reviews,0))/10,1) AS '$asin1 reviews',ROUND(MAX(IF(asin='$asin2',reviews,0))/10,1) AS '$asin2 reviews',";
	$sumif='';
	$q="SELECT date_format(from_unixtime(time+7200),'%y/%m/%d') as dtime, $sumif MAX(IF(asin='$asin1',reviews,0)) - MAX(IF(asin='$asin2',reviews,0)) AS gap FROM `". $table ."` WHERE asin IN ('$asin1','$asin2') AND time > ". strtotime('now - 28 days') ." GROUP BY dtime HAVING gap > -10 AND gap < 600 ORDER BY time DESC";
	$qReviews="SELECT date_format(from_unixtime(time+7200),'%y/%m/%d') as dtime, MAX(IF(asin='$asin1',reviews,0)) as '$asin1', MAX(IF(asin='$asin2',reviews,0)) AS '$asin2' FROM `". $table ."` WHERE asin IN ('$asin1','$asin2') AND time > ". strtotime('now - 28 days') ." GROUP BY dtime ORDER BY time DESC";
		//~ echo $q;
	echo '<li><a target=_blank href="reviewgap.php">History Data</a><br>';
	$aTooltipData=sqlquery($qReviews);
	$aTooltipData['header']="dtime ". $asin1 ." ". $asin2;
	$aMapData=sqlquery($q);
	mydrawchart($aMapData,'','time','Reviews gap: '.$title,$image_width,120);
}

function reviewGap($asin1,$asin2,$title){
	global $region,$dnsCountry,$timeoffset,$dateFormat,$table,$days,$aColors,$image_width,$aTooltipData;
	$sumif="ROUND(MAX(IF(asin='$asin1',reviews,0))/10,1) AS '$asin1 reviews',ROUND(MAX(IF(asin='$asin2',reviews,0))/10,1) AS '$asin2 reviews',";
	$sumif='';
	$q="SELECT date_format(from_unixtime(time+$timeoffset),'%y/%m/%d') as dtime, $sumif MAX(IF(asin='$asin1',reviews,0)) - MAX(IF(asin='$asin2',reviews,0)) AS gap FROM `". $table ."` WHERE asin IN ('$asin1','$asin2') AND time >= ". strtotime('now - 28 days') ." GROUP BY dtime HAVING gap > -10 AND gap < 600 ORDER BY time DESC";
	//~ $qReviews="SELECT date_format(from_unixtime(time+7200),'%y/%m/%d') as dtime, MAX(IF(asin='$asin1',reviews,0)) as '$asin1', MAX(IF(asin='$asin2',reviews,0)) AS '$asin2' FROM `". $table ."` WHERE asin IN ('$asin1','$asin2') AND time > ". strtotime('now - 28 days') ." GROUP BY dtime ORDER BY time DESC";
	$qNumbers="SELECT MAX(IF(asin='$asin1',a.rws,0)) - MAX(IF(asin='$asin2',a.rws,0)) AS gap, SUM(IF(a.asin = '$asin1', a.rk1, 0)) as '$asin1 rank1' , SUM(IF(a.asin = '$asin1', a.rk2, 0)) as '$asin1 rank2' , SUM(IF(a.asin = '$asin1', a.rws, 0)) as '$asin1 reviews' , SUM(IF(a.asin = '$asin1', a.ang, 0)) as '$asin1 avgrating' , SUM(IF(a.asin = '$asin1', a.pce, 0)) as '$asin1 price' , '$asin1 \n\t' as '$asin1', SUM(IF(a.asin = '$asin2', a.rk1, 0)) as '$asin2 rank1' , SUM(IF(a.asin = '$asin2', a.rk2, 0)) as '$asin2 rank2' , SUM(IF(a.asin = '$asin2', a.rws, 0)) as '$asin2 reviews' , SUM(IF(a.asin = '$asin2', a.ang, 0)) as '$asin2 avgrating' , SUM(IF(a.asin = '$asin2', a.pce, 0)) as '$asin2 price' , '$asin2 \n' as '$asin2' FROM (SELECT date_format(from_unixtime(time+$timeoffset),'%y/%m/%d') as dtime, asin,  ROUND(avg(rank1),0) as rk1 , ROUND(avg(rank2),0) as rk2 , ROUND(MAX(reviews),0) as rws , ROUND(avg(avgrating),1) as ang , ROUND(avg(price),2) as pce FROM asin_us_numbers WHERE asin IN ('$asin1','$asin2') AND rank1 > 0 AND time >= ". strtotime('now - 28 days') ." group by asin, dtime ) a GROUP BY dtime HAVING gap > -10 AND gap < 600 ORDER BY dtime DESC";
	//~ echo '<li>'. $q .'<li>'. $qNumbers .'<p>'; //for testing
	echo "<li><a target=_blank href=\"reviewgap.php\">Reviews gap:  $title</a><br>";
	$aTooltipData=sqlquery($qNumbers);
	//~ print_r($aTooltipData);
	//~ $aTooltipData['header']="DateTime  ". $asin1 ."  ". $asin2;
	$aTooltipData['header']=str_replace('|',"\t",'gap|'. TooltipHeaders .'|asin');
	$aMapData=sqlquery($q);
	mydrawchart($aMapData,'','time','',$image_width,120);
}

function groupNumbers($group,$alter=''){
	global $region,$dnsCountry,$timeoffset,$dateFormat,$table,$days,$aColors,$image_width,$aTooltipData,$sAsinsWithRank1NotToBeDevidedBy1000;
	$qBaseSub=$q1Sub=$qAllSub=$items=''; $legend='<div font-size: 0.875em; /* 14px/16=0.875em */>';
	//~ echo '<li>'. $group;
	$aLines=explode("\n",$group);
	foreach(explode('|',TooltipHeaders) as $h) {
		$qAllSub .= ",'\n$h:' AS '$h'"; //key statement that alians rows and columns in tooltip
		$sh=substr($h,0,1) . substr($h,-2);
		for($ln=0;$ln<count($aLines);$ln++) {
			$line=$aLines[$ln];
			if (strpos($line,'--')>-1) { continue;}
			if (preg_match(';https://www.amazon.com/(\w+-\w+-\w+).*/dp/(B\w{9});',$line,$m)) {
				$line=$m[2] .','. $m[1];
			}
			$aVs=explode(',',$line);
			if (strpos($line,',')==false) continue;
			$asin=$aVs[0];
			$sku=$aVs[1];
			if (stripos($legend,$asin)==false) {
				$legend .= "<span class=\"dot\" style=\"background-color:". $aColors[$ln] ."\"></span><a title=\"Open ". $asin ." at Amazon.". $dnsCountry ."\" target=_blank href=\"http://www.amazon.". $dnsCountry ."/dp/$asin\">$sku</a>&nbsp;&nbsp;|&nbsp;&nbsp;";
				$items .= $line .';';
				$aSkus[] = $sku;
				$aAsins[] = $asin;
			}
			$sAsins = implode("','",$aAsins);
			$sSkus = implode(',',$aSkus);
			if ($region <> 'us' or preg_match($sAsinsWithRank1NotToBeDevidedBy1000,$group)==FALSE) {
				$q1Sub .= ",SUM(IF(a.asin = '$asin', 0 - IF(a.rk1<1000,a.rk1,ROUND(a.rk1/1000,1)), 0)) as '$sku rank1' ";
			} else {
				$q1Sub .= ",SUM(IF(a.asin = '$asin', 0 - a.rk1, 0)) as '$sku rank1' ";
			}
			//~ if (stripos($group,'TTG')==FALSE) $qAllSub .= ", SUM(IF(a.asin = '$asin', IF(a.$sh<1000,a.$sh,ROUND(a.$sh/1000,1)), 0)) as '$sku $h' ";
			//~ if (stripos($group,'TTG')<>FALSE)
			if ($h=='price') {
				$qAllSub .= ", CONCAT('$',SUM(IF(a.asin = '$asin', a.$sh, 0))) as '$sku $h' ";
			} else {
				$qAllSub .= ", SUM(IF(a.asin = '$asin', a.$sh, 0)) as '$sku $h' ";
			}
			//~ $tooltipheader .= $h .'|';

		}
		//~ $tooltipheader .= $h ;
		//~ $qAllSub .= ", '$sku \n' as '$sku'";

		$decimal=0;
		$sh=substr($h,0,1) . substr($h,-2);
		if ($h=='avgrating') $decimal=1;
		if ($h=='price') {			$decimal=2; }
		$qBaseSub .= ", ROUND(avg($h),$decimal) as $sh ";
	}
	$q1="SELECT dtime $q1Sub FROM (SELECT date_format(from_unixtime(time+$timeoffset),'$dateFormat') as dtime, asin $qBaseSub FROM $table WHERE asin IN ('$sAsins') AND rank1 > 0 AND time >= $days GROUP BY asin, dtime HAVING rk1>0 ) a GROUP BY dtime ORDER BY dtime DESC";

	if ($alter=='review gap' ) {
		$sAsins=$q1Sub=''; $legend='Reviews gap: '; unset($aAsins); $ln=0;
		foreach(explode(';',$group) as $pair) {
			//~ echo $pair ."\n"; //TESTING
			if (preg_match_all('/(B\w{9}),([\w \_\-]+) vs (B\w{9}),([\w \_\-]+)/',$pair,$m)) {
				//~ print_r($m); //TESTING
				$asin1=$m[1][0]; $aAsins[]=$asin1;
				$sku1=$m[2][0];
				$asin2=$m[3][0]; $aAsins[]=$asin2;
				$sku2=$m[4][0];
				$q1Sub .= ", MAX(IF(a.asin='$asin1',a.rws,0)) - MAX(IF(a.asin='$asin2',a.rws,0)) AS '$sku1 vs $sku2'";
				$legend .= "<span class=\"dot\" style=\"background-color:". $aColors[$ln] ."\"></span>$sku1 vs $sku2 ";
				$ln++;
			}
		}
		$sAsins = implode("','", $aAsins);
		$q1="SELECT dtime $q1Sub FROM (SELECT date_format(from_unixtime(time+$timeoffset),'$dateFormat') as dtime, asin $qBaseSub FROM $table WHERE asin IN ('$sAsins') AND reviews > 0 AND time >= $days GROUP BY asin, dtime HAVING rk1>0) a GROUP BY dtime ORDER BY dtime DESC";
		$q1=str_replace($dateFormat,'%y/%m/%d',$q1);
		$q1=str_replace($days,strtotime('28 days ago'),$q1);
	}

	//~ foreach(explode('|',TooltipHeaders) as $h) {
		//~ $fields .= ", ROUND(avg($h),0) as $h";
	//~ }
	//~ $qAll=str_replace("dtime $q1Sub", substr($qAllSub,1),$q1);
	$qAll=str_replace("$q1Sub", substr($qAllSub,0),$q1);
	if (stripos(__FILE__,'beta')>1)	echo '<li>'. $q1 .'<li>'. $qAll .'<p>'; //for testing
	$aMapData=sqlquery($q1);
	$aTooltipData=sqlquery($qAll);
	//~ $aTooltipData['header']=str_replace('|',"    ",$tooltipheader);
	$aTooltipData['header']=implode("\t",$aSkus); //key statement that alians rows and columns in tooltip
	//~ $aTooltipData['aSku']=$aSku;
	//~ print_r($aMapData);
	//~ echo "<p>";
	//~ print_r($aTooltipData);

	foreach(explode(',',PERIODS) as $dateRange) {
		if ( (isset($_GET['period']) and $dateRange==$_GET['period']) or ( empty($_GET['items']) and $dateRange=='24 hours' )) {
			//~ echo $dateRange ."&nbsp;&nbsp;|&nbsp;&nbsp;";
			$legend .= '* ';
		}
		$legend .= '<a title="Chart of '. $dateRange .' averange" href="'. basename(__FILE__) .'?region='. $region .'&period='. str_replace(' ','+',$dateRange) . "&items=$items\">$dateRange</a>&nbsp;&nbsp;|&nbsp;&nbsp;";
	}
	$legend .='<br></div>';
	echo $legend;
	$image_height = 200;
	$image_height = count($aLines)*50;
	if ($image_height < 100) $image_height=100;
	//~ if ($_GET['items'] <>'') $image_height = 600;
	mydrawchart($aMapData,'','time','',$image_width,$image_height);
	echo '<br>';

	if (!empty($_GET['items'])) {
		$qAverage=str_replace($dateFormat,'%Y',$q1);
		$aAvgData=sqlquery($qAverage);
		echo 'Average of last <B>'. $_GET['period'] .'</B>';
		displayArrayToTable($aAvgData,"Average of last $dateRange");
		//~ echo '<table><tr><td>';
		//~ array_push($aMapData,' ');
		//~ displayArrayToTable($aMapData);
		//~ echo '</td><td>';
		displayArrayToTable($aTooltipData, "Detail of last $dateRange");
		//~ echo '</td></tr></table>';

	}
}

function displayArrayToTable($array, $tableId=1) {
	/*
	$array = array( array("title"=>"rose", "price"=>1.25 , "number"=>15),
               array("title"=>"daisy", "price"=>0.75 , "number"=>25),
               array("title"=>"orchid", "price"=>1.15 , "number"=>7)
             );
	*/
	if (count($array) > 0) {
		echo '<table id="'. $tableId .'" class="filterable sortable"><thead><tr class="hover"><th>'. implode('</th><th>', array_keys(current($array))) ."</th></tr></thead><tbody>";
		foreach ($array as $row) {
			if (!is_array($row) or empty($row)) continue;
			array_map('htmlentities', $row);
			echo '<tr class="hover"><td><div>'. implode('</div></td><td><div>', $row) .'</div></td></tr>';
		}
		echo "</tbody></table>";
	}
}

# Callback for 'data_points': Generate 1 <area> line in the image map:
//~ $iMap = "";
//~ function mystore_map($img, $passthru, $shape, $row, $column, $x, $y)
function store_map($img, $passthru, $shape='dot', $row, $column, $x, $y) {
	global $iMap,$aTooltipData;

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
	$tooltip=$aTooltipData[$row]['dtime'] ."\t". $aTooltipData['header'] . str_replace($aTooltipData[$row]['dtime'],'',implode("\t",$aTooltipData[$row])); //key statement that alians rows and columns in tooltip
	# Required alt-text:
	$alt = $aTooltipData['header'];
	$iMap .= "  <area shape=\"circle\" coords=\"$coords\" title=\"$tooltip\">\n";
}

function mydrawchart($aMapData,$title_c='',$title_x='',$yLegents='',$width=1600,$height=120){
	//~ print_r($aMapData);
	//~ for ($i=0;$i<29;$i++) { echo $aMapData[$i]['dtime'] .' '. $aMapData[$i]['gap'] .'<br>'; }
	//~ exit;
	global $aColors, $iMap;
	if (count($aMapData) > 48) {
		$arrays = array_chunk($aMapData,48);
		foreach($arrays as $array) {
			$width=count($array)*32;
			if ($width<640) $width=640;
			mydrawchart($array,$title_c,$title_x,$yLegents,$width,$height);
		}
		return;
	}
	# This global string accumulates the image map AREA tags.

	//create a PHPlot object with 800x600 pixel image
	$plot = new PHPlot($width,$height);
	# Disable error images, since this script produces HTML:
	$plot->SetFailureImage(False);
	// $plot->SetDefaultTTFont('/home/czyusa1973/public_html/mt/lib/Yagora.ttf');
	$plot->SetPrintImage(False);  // Do not output the image
	// $plot->SetFont('y_label', 2, 12);
	// $plot->SetFont('x_label', 2, 12);
	$plot->SetFontTTF('x_label', '/home/czyusa1973/public_html/mt/lib/Yagora.ttf', 9);
	$plot->SetFontTTF('y_label', '/home/czyusa1973/public_html/mt/lib/Yagora.ttf', 8);
	if (count($aMapData) >= 21) {$plot->SetXLabelAngle(45);}
	$plot->SetYDataLabelAngle(90);
	//Define some data
/*   	$example_data = array(
			 array('x1',3),
			 array('x2',5),
			 array('x3',7),
			 array('x4',8),
			 array('x5',2),
			 array('x6',6),
			 array('x7',7)
	);
 	$plot->SetDataValues($example_data);
	// print_r($example_data);
	// return;
 */	//
	//~ $plot->SetPlotType('linepoints');
	//~ $plot->SetDataType('text-data');
	$plot->SetDataValues($aMapData);

	//Set titles
	$plot->SetTitle($title_c);
	//~ $plot->SetXTitle($title_x);
	//~ $plot->SetYTitle($yLegents);

	//Turn off X axis ticks and labels because they get in the way:
	//~ $plot->SetXTickLabelPos('none');
	//~ $plot->SetXTickPos('none');
	$plot->SetXTickIncrement(1);
	$plot->SetDrawXGrid(True);
	$plot->SetDrawYGrid(False);
	$plot->SetXTickAnchor(0.5);
	$plot->SetXTickLabelPos('none');
	$plot->SetXDataLabelPos('plotdown');
	$plot->TuneYAutoRange(1, 'R', 0);
	$plot->SetYDataLabelPos('plotin');
	//~ $plot->SetXDataLabelAngle(90);
	$plot->SetYDataLabelAngle(0);
	$plot->SetPlotType('linepoints');
	$plot->SetYTickLabelPos('none');
	$plot->SetDataColors($aColors);
	$plot->SetLineStyles('solid');
	$plot->SetLineWidths(1);

	//Add legend for multi-line chart
	if (strpos($yLegents,',') > 2) {
		//~ $plot->SetMarginsPixels(80);
		$legend = explode(',',$yLegents);
		$plot->SetLegend($legend);
		//~ if ($width <= 1300) {
			//~ $plot->SetPlotAreaWorld(NULL,NULL,$height,$width+300);
			//~ $plot->SetLegendPixels($width, 1);
		//~ } else {
			$plot->SetLegendPixels(250, 1);
		//~ }
	} else {
		if (strlen($yLegents)>1) $plot->SetLegend($yLegents);
		$plot->SetLegendPosition(1, 0, 'plot', 0.5, 0);
	}
	//~ $plot->SetPlotAreaWorld(NULL, NULL, NULL, 5);
	//Draw it
	# Set the data_points callback which will generate the image map.
	//~ if (!empty($aTooltipData))
	$plot->SetCallback('data_points', 'store_map'); //imagemap
	$plot->DrawGraph();
	//~ echo "<img src=\"" . $plot->EncodeImage() . "\">\n";
	$mapId=rand();
	echo '<map name="map'. $mapId .'">' . $iMap .'</map><img src="'. $plot->EncodeImage() .'" alt="Plot Image" usemap="#map'. $mapId .'">' ."\n"; //imagemap
	$iMap='';
}

function list_sku_by_owner($owner=''){
	global $aAsinSku,$aSkuAsin;
	if ($owner<>'') $ownerfilter="WHERE owner='$owner'";
	$q="SELECT DISTINCT url,sku,tier,owner FROM `sku_assign` $ownerfilter ORDER BY lastupdated DESC, tier, sku ";
	foreach(sqlquery($q) as $r){
		$sku=$r['sku'];
		//~ $tier=$r['tier'];
		$url=$r['url'];
		$tier=$r['tier'];
		$owner=$r['owner'];
		//~ if (preg_match('-http-',$url)) $lines .= $url .','. $sku .','. $tier .','. $owner ."\n";
		if (preg_match('-/(B\w{9})-',$url,$m)) {
			$asin=$m[1];
		} else {
			$asin=$aSkuAsin[$sku];
		}
		//~ if (preg_match('-(B\w{9})-',$asin)) $lines .= $asin .','. $sku .','. $tier .','. $owner ."\n";
		if (preg_match('-(B\w{9})-',$asin) and stristr($lines,$sku)==FALSE) $lines .= $asin .','. $sku ."\n\n";
	}
	//~ print_r( $aSkuAsin );
	//~ print_r( $aLines );
	return $lines;
}

function update_sku_assign() {
	global $aAsinSku,$aSkuAsin;
	$list_url='https://docs.google.com/spreadsheets/d/e/2PACX-1vR2gY22xgcaR4JUr3naK5nXbFzw3pL_Ogn4msFRDGfVA8nILfEs-BOdxDRt2Jvhx9Yz31eAF8IfpjBn/pub?gid=0&single=true&output=tsv';
	$tsv=file_get_contents($list_url);
	$tsv=str_replace(' ','',$tsv);
	//~ echo $tsv;
	foreach (explode("\n",$tsv) as $line) {
		$vs = explode("\t",$line);
		if (count($vs)<3) continue;
		$line=trim($line);
		$values=date('Y-m-d H:i:s') ."','". str_replace("\t","','",$line);
		$q .= "INSERT IGNORE INTO sku_assign VALUES ('$values');\n";
	}
	//~ if (sqlquery($q)<>FALSE) echo str_replace("\n",'<br>',$q);
	sqlquery($q);
}


?>