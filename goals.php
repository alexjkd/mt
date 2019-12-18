<?php
//include_once(dirname(__FILE__) . "/lib/functions.php");
include_once(dirname(__FILE__) . "/lib/functions.php");

define("EMAIL_RECEPIENTS", 'John@iSpringFilter.com');
define("PERIODS", '2 years,1 years,6 months,3 months,30 days,7 days,120 hours,24 hours');
define('TooltipHeaders', 'rank1|rank2|reviews|avgrating|price');
$mapDnsCountries = array('us' => 'com', 'ca' => 'ca', 'uk' => 'co.uk');
$timeoffset = 3600 * 3; //before daylight saving time
if (time() > strtotime('First Sunday Of March') and time() < strtotime('First Sunday Of November ')) $timeoffset = 3600 * 2;
$region = isset($_GET['region']) ? $_GET['region'] : 'us';
$dnsCountry = $mapDnsCountries[$region];
$table = 'asin_' . $region . '_numbers';
$table_rank1 = 'mws_' . $region;
$aColors = array('red', 'blue', 'DarkGreen', 'orange', 'cyan', 'SkyBlue', 'green', 'SlateBlue', 'DimGrey', 'gold', 'grey', 'ivory', 'PeachPuff');
$image_width = 1380;
$aAssignee = isset($_GET['assignee']) ? $_GET['assignee'] : '';
$daysInPeriod = isset($_GET['period']) ? $_GET['period']:15 ;
$google_doc = 'https://docs.google.com/spreadsheets/d/e/2PACX-1vR2gY22xgcaR4JUr3naK5nXbFzw3pL_Ogn4msFRDGfVA8nILfEs-BOdxDRt2Jvhx9Yz31eAF8IfpjBn/pub?gid=0&single=true&output=tsv';
$local_csv = dirname(__FILE__) . '/MT_lists - mws.csv';
$sGoogleTsv = file_get_contents($google_doc);
//~ echo $sGoogleTsv;
$q=$qSub=$asin=$asinList=$sAssignees='';
$top3coms = array();
foreach (explode("\n",$sGoogleTsv) as $line) {
	$aTsv = explode("\t",trim($line));
	//~ print_r($aTsv);
	$sr = $aTsv[0];
	$url = $aTsv[1];
	$quarterBsrGoal = $aTsv[7];
	if (preg_match('/B0\w{8}/',$url,$mAsin) and preg_match('/(\d+)/',$quarterBsrGoal,$mGoal)) {
		$asin = $mAsin[0];
		$goal = $mGoal[1] * 1000;
		//echo "<li>$url -- $asin";
		$model = $aTsv[2];
		$tier = $aTsv[3];
		$assignee = $aTsv[4];
		$top3comp = $aTsv[5];
		$top3keywords = $aTsv[6];
		$recentNegativeReviews = $aTsv[8];
		$qSub .= ",SUM(IF(asin = '$asin' and dailyAvg < $goal ,1,0)) AS '$assignee,$model,$tier,$asin,$quarterBsrGoal'";
		$asinList .= ",'". $asin ."'";
		if (stristr($sAssignees,$assignee)==FALSE) $sAssignees .= $assignee .',';
    if (strlen($aAssignee) == 0 || strcasecmp($aAssignee, $assignee) == 0){
      $top3coms[$asin] = $top3comp ;
    }
	}
	//~ break;
}
//print_r(var_dump($top3coms)."===============\\n");
$qSub = trim($qSub,",");
$asinList = trim($asinList,",");
$q = "SELECT $qSub FROM ( SELECT DATE_FORMAT(updated,'%Y-%m-%d') aS dt, asin, AVG(rank1) AS dailyAvg FROM `mws_us` WHERE asin IN ($asinList) AND updated>=DATE_SUB(NOW(), INTERVAL $daysInPeriod DAY) GROUP BY dt, asin ORDER BY dt DESC) s ";
//echo "<h3> $q </h3>";
$records = sqlquery($q);
$table_goal = [];

foreach($records[0] as $key=>$val)
{  
    $items=explode(',',$key);
  if (strlen($aAssignee) == 0 || strcasecmp($aAssignee, $items[0]) == 0){
    $row['Asin']=$items[3];
    $row['Model']=$items[1];
    $row['Tier']=$items[2];
    $row['Assignee']=$items[0];
    $row['Goal']=$items[4];
    $row['DaysReached']=$val;
    $table_goal[]=$row;
  }
}

    $data = $table_goal;
    $html_table .= "<tr><th>ASIN</th>";
    $html_table .= "<th>MODEL</th>";
    $html_table .= "<th>TIER</th>";
    $html_table .= "<th>ASSIGNEE</th>";
    $html_table .= "<th>GOAL</th>";
    $html_table .= "<th>DAYS</th>";
    $html_table .= "<th>PERIOD</th>";
    $html_table .= "<th>%DaysByTier</th>";
    $html_table .= "</tr>";
    /*Dynamically generating rows & columns*/
    //$data = array_unique($data);
    $total_days = 0;
    $total_percentange = 0;
    for ($i = 0; $i < count($data); $i++) {
        $asin = $data[$i]["Asin"];
        $aItems = getItemsByAsin($asin, $top3coms);
        $params = "region=us&period=15+days&items=" . $aItems;
        $html_table .= "<tr>";
        $html_table .= "<td align=\"center\">" ."<a target=_blank href=http://www.amazon.com/dp/$asin>" . $asin . "</a></td>";
        $html_table .= "<td align=\"center\">" . "<a href=dashboard.php?$params>" . $data[$i]["Model"] . "</a></td>";
        $html_table .= "<td align=\"center\">" . $data[$i]["Tier"] . "</td>";
        $html_table .= "<td align=\"center\">" . $data[$i]["Assignee"] . "</td>";
        $html_table .= "<td align=\"center\">" . $data[$i]["Goal"] . "</td>";
        $html_table .= "<td align=\"center\">" . $data[$i]["DaysReached"] . "</td>";
        $html_table .= "<td align=\"center\">" . $daysInPeriod . "</td>";
        $percentage = 100 * $data[$i]["DaysReached"] / $daysInPeriod / $data[$i]["Tier"];
        $total_days += $data[$i]["DaysReached"]; 
        $total_percentange += $percentage;
        $html_table .= "<td align=\"center\">" . round($percentage, 2) . "</td>";
        $html_table .= "</tr>";

    }
        $html_table .= "<tr>";
        $html_table .= "<td align=\"center\"></td>";
        $html_table .= "<td align=\"center\"></td>";
        $html_table .= "<td align=\"center\"></td>";
        $html_table .= "<td align=\"center\"></td>";
        $html_table .= "<td align=\"center\"></td>";
        $html_table .= "<td align=\"center\">" . $total_days . "</td>";
        $html_table .= "<td align=\"center\"></td>";
        $html_table .= "<td align=\"center\">" . round($total_percentange / count($data),2) . "</td>";
        $html_table .= "</tr>";

function getGroupByAsins($sAsins) {
    $file = file_get_contents('/var/www/html/mt/MWSProducts/asin_alias.txt');
    $aAliaGroups = explode("\n", $file);
    $groups = array();
    foreach($aAliaGroups as $group) {
        $asin2Group = explode(",", $group);
        foreach($sAsins as $sAsin) {
          if (strcasecmp($sAsin, trim($asin2Group[0])) == 0) {
            $groups[$sAsin] = $group;
          }
        }
    }
    return $groups;
}

function getItemsByAsin($aAsin, $aTop3Coms) {
    $sItems = '';
    $asins = [];
    $asins[] = trim($aAsin);
           
    if(!empty($aTop3Coms[$aAsin])){  
      $sTop3Coms = explode(",", $aTop3Coms[$aAsin]);      
      foreach($sTop3Coms as $sTop3Com) {            
        $sTop3Com = trim($sTop3Com);
        $asins[] = $sTop3Com;
      }
    }    
    $sGroups = getGroupByAsins($asins);
    foreach($asins as $sAsin) {
        $sItems = $sItems . $sGroups[$sAsin]. ";";
    }    
    return $sItems;
}

?>


<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
	<title>iSpring Review Monitor</title>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />

	<link href="./lib/sortable/sortable_table.css" media="screen" rel="stylesheet" type="text/css" />
	<script type="text/javascript" language="javascript" src="./lib/sortable/sorttable.js"></script>
	<link href="./lib/TableFilter/filtergrid.css" media="screen" rel="stylesheet" type="text/css" />
	<script type="text/javascript" language="javascript" src="./lib/TableFilter/tablefilter.js"></script>

	<link href="./lib/jquery-ui-1.10.4.css" media="screen" rel="stylesheet" type="text/css" />
	<script type="text/javascript" language="javascript" src="./lib/jquery.js"></script>
	<script type="text/javascript" language="javascript" src="./lib/jquery-ui-1.10.4.min.js"></script>
	<script type="text/javascript" language="javascript" src="./lib/jquery.dataTables.js"></script>

	<link rel="shortcut icon" href="./lib/img/favicon_rmispri.ico" />

	<script type="text/javascript" charset="utf-8">
		document.write("<center>Enter text or number in columns to filter the rows. Optional Operators: <   >   =   >=   <=   *   !   {   }   ||   &&   [empty]   [nonempty]   rgx:   </center>");
$(document).ready(function() {
      });
	</script>

<body>
	</head>
	<body>
		<?php
		foreach(explode(',',trim($sAssignees,',')) as $assignee) {
			echo "<a href=goals.php?assignee=$assignee>$assignee</a> | ";
		}
   echo "<a href=goals.php>ALL</a> ";
		?>
		<table border=1 id="table1" class="filterable sortable" cellpadding="0" cellspacing="0" width="100%">
        <?php echo $html_table;?>
        </table>
		<script data-config>
			var filtersConfig = {
				col_1: "select",
				col_3: "select",
				btn: false
			}
			var tf = new TableFilter('table1', filtersConfig);
			tf.init();
		</script>
		<div id="review-modal" title="Review Detail" style="display:none;">
			<p align="center">Loading...Please wait...</p>
		</div>
		<div id="remedy-modal" title="Viewing Remedy" style="display:none;">
			<p align="center">Loading...Please wait...</p>
		</div>
	</body>

</html>
