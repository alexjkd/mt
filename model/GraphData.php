<?php
include_once(dirname(__FILE__) . "/../lib/functions.php");
//include_once(dirname(__FILE__) . "/../lib/phplot.6.1.0.php");
//SR,URL,Model,Tier,Assignee,Top3Comp,Top3Keywords,3MonthBSRGoal,RecentNegativeReviews
define("CSV_URL",1);
define("CSV_MODE",2);
define("CSV_TIER",3);
define("CSV_ASSIGNEE",4);
define("CSV_TOP3COM",5);
define("CSV_BSR",7);
    
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
$BSR =array();
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

//$aAsinSku = get_asin_sku_array();
//$aSkuAsin = array_flip($aAsinSku);
$sAsinsWithRank1NotToBeDevidedBy1000 = '/TTG|B002C0A7ZY|B00Q798N8E|B005LJ8EXU/';

if (isset($_GET['q']) and $_GET['q'] == 'skuassign') {
    update_sku_assign();
    echo str_replace("\n", '<br>', list_sku_by_owner_new());
    exit;
}

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
$google_doc = 'https://docs.google.com/spreadsheets/d/e/2PACX-1vR2gY22xgcaR4JUr3naK5nXbFzw3pL_Ogn4msFRDGfVA8nILfEs-BOdxDRt2Jvhx9Yz31eAF8IfpjBn/pub?gid=0&single=true&output=csv';
$local_csv = dirname(__FILE__) . '/../MT_lists - mws.csv';
$group = '';
$iMap = '';

function googleGroupToAsinAlais( $googleGroup)
{
    $ret = [];
    foreach($googleGroup as $group){
        $group = str_replace(array("\""), "", $group);
        $asin_alias = explode("\n", $group);
        $asin_alias = array_filter($asin_alias);
        //var_dump($asin_alias);
        $ret = array_merge($ret, $asin_alias);
    }
    return $ret;
}


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

    if (!empty($_GET['show'])) {
        $group = $_GET['show'];
        $group = str_replace("\\n", "\n", $group);
    }
    if (!empty($_GET['alerts'])) {
        //$data = array(
        //array("#" => 0, "asin" => "empty", "sku" => "alias", "time1" => "112233", "time2" => "112233", "result" => "empty"),
        //array("asin" => "abc", "result" => "yes")
        //);
        $data = retriveAlertInfo();
        header('Content-Type: application/json');
        echo json_encode($data);
        exit();
    }
    if (!empty($_GET['groups'])) {
        $groups = $_GET['groups'];
        $aAlias = retriveCmpetitorAlias();
        if (isset($_SERVER['SERVER_NAME']) && $_SERVER['SERVER_NAME'] <> 'localhost') {
            $googleGroup = getGroupsFromGoogleDoc($aAlias, $google_doc);
        } else {
            $googleGroup = getGroupsFromGoogleDoc($aAlias, $local_csv);
        }
        header('Content-Type: application/json');
        echo json_encode($googleGroup);
        exit();
    }
    if (!empty($_GET['simple-table'])) {
        $datas = array();
        $items = array();
        if (isset($_SERVER['SERVER_NAME']) && $_SERVER['SERVER_NAME'] <> 'localhost') {
            $googleGroup = getGroupsFromGoogleDoc($aAlias, $google_doc);
        } else {
            $googleGroup = getGroupsFromGoogleDoc($aAlias, $local_csv);
        }
        foreach ($csvFile as $line) {
            $csv_check_data = str_getcsv($line);
            $datas[] = $csv_check_data;
        }
        $pick_title = array();
        $new_titles = array();
        //var_dump($datas);
        for ($i = 0; $i < count($datas); $i++) {
            if ($i == 0) {
                $titles = $datas[0];
                $titles[7] = '3MonthBSRGoal';
                $pick_title = array_slice($titles, 0, 8);
                continue;
            }
            $pick_data = $datas[$i];
            $pick_data = array_slice($pick_data, 0, 8);

            echo "<pre> count = " . count($datas) . ", pick_data = " . var_dump($pick_data) . "</pre>";
            $product_asin = '';
            $csv_product_asin = substr($pick_data[1], strrpos($pick_data[0], '/') + 1);
            $csv_product_len = strlen($csv_product_asin);
            if ($csv_product_len == 10 && !preg_match('/[^A-Za-z0-9]/', $csv_product_asin)) {
                $product_asin = $csv_product_asin;
            }
            $matches = array();
            $rank1 = 0;
            $rank1_sql = '';
            $records = array();
            $DaysReached = 0;
            $Percentage = 0;
            if (preg_match('/[0-9.,]{1,}/', $pick_data[5], $matches)) {
                $rank1 = $matches[0] * 1000;
                $rank1_sql = "select asin, rank1,updated, datediff(updated,'2019-10-01 00:00:00') as days from mws_us where asin = '$product_asin' and rank1 > $rank1 order by days desc limit 1";
                $records = sqlquery($rank1_sql);
            }
            if (isset($records)) {
                $DaysReached = $records[0]['days'];
                $rank1 = $records[0]['rank1'];
                $datetime1 = new DateTime(date('Y-m-d'));
                $datetime2 = new DateTime('2019-10-01');
                $interval = $datetime1->diff($datetime2);
                $Percentage = round(($DaysReached / $interval->days) * 100, 2);
            }
            $new_col_val = array($DaysReached, $Percentage);
            $new_col_name = array('DaysReached', 'Percentage');

            $new_titles = array_merge($pick_title, $new_col_name);
            $vals = array_merge($pick_data, $new_col_val);
            $item = array_combine($new_titles, $vals);
            $items[] = $item;
        }
        header('Content-Type: application/json');
        echo json_encode($items);
        exit();
    }
    if (!empty($_GET['mws-check'])){
        $aAlias = retriveCmpetitorAlias();
        $googleGroup = getGroupsFromGoogleDoc($aAlias, $google_doc);
        $asin_alias = googleGroupToAsinAlais($googleGroup);
        $mws_records = array();
        $i =0;
        foreach($asin_alias as $asin_alia) {
            $items =  explode(",",$asin_alia);
            $num = check_mws($items[0], $items[1]);
            if($num < 0 or $num >= 24) {
                continue;
            }
            $tmp['asin'] = $items[0];
            $tmp['sku_names'] = $items[1];
            $tmp['24hours-records'] =$num; 
            //var_dump($mws_records);
            $mws_records[] = $tmp;
        }

        header('Content-Type: application/json');
        echo json_encode($mws_records);
        exit();
    }
}

if (empty($aGroups) && empty($group)) {
    $aAlias = retriveCmpetitorAlias();
    $googleGroup = getGroupsFromGoogleDoc($aAlias, $local_csv);
    $aGroups = $googleGroup;
    echo "<head>";
    echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"../css/tip.css\">";
    echo "<script src=\"../js/tip.js\"></script>";
    echo "</head><body>";
    echo "<div id=\"mjs:tip\" class=\"tip\" style=\"position:absolute;left:0;top:0;display:none;\"></div>";
    for ($i = 0; $i < 7; $i++) {
        echo "<div id=\"mjs:tip$i\" class=\"tip$i\" style=\"position:absolute;left:0;top:0;display:none;\"></div>";
    }
    echo "</body>";
} else if (empty($aGroups) && !empty($group)) {
    $aGroups = array($group);
}
if (!empty($_GET['items'])) {
    echo "<head>";
    echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"../css/tip.css\">";
    echo "<script src=\"../js/tip.js\"></script>";
    echo "</head><body>";
    echo "<div id=\"mjs:tip\" class=\"tip\" style=\"position:absolute;left:0;top:0;display:none;\"></div>";
    for ($i = 0; $i < 7; $i++) {
        echo "<div id=\"mjs:tip$i\" class=\"tip$i\" style=\"position:absolute;left:0;top:0;display:none;\"></div>";
    }
    echo "</body>";
}

foreach ($aGroups as $group) {
    if (substr($group, 0, 1) <> '-' and strlen($group) > 15) {
        GroupPriceAvgratingReivew($group, array('rank1'));
        GroupPriceAvgratingReivew($group, array('price'));
        GroupPriceAvgratingReivew($group, array('avgrating'));
        GroupPriceAvgratingReivew($group, array('reviews'));
    }
}



function retriveAlertInfo()
{
    global $local_csv, $google_doc;
    $ret = array();

    $aAlias = retriveCmpetitorAlias();
    //if (isset($_SERVER['SERVER_NAME']) && $_SERVER['SERVER_NAME'] <> 'localhost') {
    $googleGroup = getGroupsFromGoogleDoc($aAlias, $google_doc);
    //} else {
    //    $googleGroup = getGroupsFromGoogleDoc($aAlias, $local_csv);
    //}

    $aGroups = $googleGroup;
    foreach ($aGroups as $group) {
        if (substr($group, 0, 1) <> '-' and strlen($group) > 15) {
            //retriveAlertFromDB($group, array('rank1'));
            $tmp = retriveAlertFromDB($group, array('price'));
            if (isset($tmp) && count($tmp) > 0) {
                $ret = array_merge($tmp, $ret);
            }
        }
    }
    return $ret;
}

function check_mws($asin, $sku_names)
{
    if(strstr($sku_names, 'ispr') == null) {
        return -1;
    }

    $date = new DateTime();
    //$date->setTimezone(new DateTimeZone('EST'));
    //$str_time_start = $date->format('Y-m-d H:i:s');
    $time_start = strtotime('now  - 3 hours');
    $time_24hours_before = strtotime('now  - 27 hours');
    $date->setTimestamp($time_start);
    $str_time_start = $date->format('Y-m-d H:i:s');
    $date->setTimestamp($time_24hours_before);
    $str_time_24hours_before = $date->format('Y-m-d H:i:s');
    //$str_time_24hours_before = date_format($time_24hours_before, 'Y-m-d H:i:s');

    $sql = sprintf("select * from mws_us where asin='$asin' and updated between '%s' and '%s'", $str_time_24hours_before, $str_time_start) ;
    //var_dump($sql);
    $records = mwsQuery($sql);
    return count($records);
}

function find_range($key, $asin, $data)
{
    $i = 0;
    $key_start ='';
    $key_end ='';

    foreach($data as $entry) {
        if($entry['asin'] == $asin ) {
            if($i == 0) {
                $key_start = $entry[$key];
            }
            $key_end = $entry[$key];
        }
    }
    return $key_start . " → " . $key_end;
}

function analyzeAlertInfo($data)
{
    global $BSR;

    $prices = $data[0];
    $ret = array();
    $aAsins = array();
    $aSkus = array();
    $review_rating = $data[3];

    if (isset($data[1])) {
        $aSkus = explode(',', $data[1]);
    }
    if (isset($data[2])) {
        $aAsins = explode(',', $data[2]);
    }
    //var_dump($prices);
    for ($i = 0; $i < count($aSkus); $i++) {
        $sku_str = $aSkus[$i];
        $sku_index = $sku_str = str_replace("'", "", $sku_str);
        $sku_index = $sku_index . " price";
        $asin_str = str_replace("'", "", $aAsins[$i]);
        $last_index = count($prices) - 1;

        $increase_price = $prices[0][$sku_index];
        $base_price = $prices[$last_index][$sku_index];
        $perstange = (($increase_price - $base_price) / $base_price) * 100;
        $reviews = find_range('reviews',$asin_str,$data[3]);
        $rating = find_range('avgrating',$asin_str,$data[3]);
        $bsr = $BSR[$asin_str];
        //var_dump($reviews);
        $dtime1 = $prices[0]['dtime2'];
        $dtime2 = $prices[$last_index]['dtime2'];
        //var_dump($perstange);
        if (is_float($perstange) && !is_infinite($perstange) && !is_nan($perstange) && $perstange <> 0 && abs($perstange) < 100) {
            if ($perstange > 0) {
                $percentage_str = sprintf("$%-01.2f - $%.2f (<span style=\"color:blue;\"><strong>↑</strong></span>)%.2f%%", $base_price, $increase_price, $perstange);
            } else {
                $percentage_str = sprintf("$%-01.2f - $%.2f (<span style=\"color:red;\"><strong>↓</strong></span>)%.2f%%", $base_price, $increase_price, abs($perstange));
            }
            $interval = abs(round((strtotime($dtime1) - strtotime($dtime2))/3600));
            $ret[] = array(
                "ASIN" => $asin_str,
                "SKU" => "<a target=_blank  href=\"http://wh1.czyusa.com/mt/dashboard.php?items=$asin_str\">". $sku_str . "</a>",
                "Dtime" => "$dtime1 → $dtime2 ($interval hours)",
                "Percentage" => $percentage_str,
                "BSR"=>$bsr,
                "Reviews"=>"$reviews",
                "Rating"=>"$rating",
            );
        }
    }
    return $ret;
}
function retriveAlertFromDB($group, $aH, $alter = '')
{
    global $interval, $rank1_date_sql, $models, $assignee, $region, $sellers, $dnsCountry, $timeoffset, $dateFormat, $table, $date_sql, $aColors, $image_width, $aTooltipData, $sAsinsWithRank1NotToBeDevidedBy1000;
    $qBaseSub = $q1Sub = $qAllSub = $items = '';
    $legend = '<div font-size: 0.875em; /* 14px/16=0.875em */>';
    $group = str_replace(array("\""), "", $group);
    $aLines = explode("\n", $group);
    $graph_tip = array();
    $sSkus = '';
    //~ echo '<pre>'; 	print_r($aLines); 	echo '</pre>'; //testing

    foreach ($aH as $h) {
        $sh = substr($h, 0, 1) . substr($h, -2);
        $q1 = $q1Sub = '';
        for ($ln = 0; $ln < count($aLines); $ln++) {
            $line = $aLines[$ln];
            if (empty($line)) {
                continue;
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

            if (stripos($legend, $asin) == false) {
                $modelName = empty($sku_names[1]) ? '' : $sku_names[1];
                $sellerName = $sku_names[0];
                if ($ln == 0 && $sellerName == 'ispr') {
                    $assigneeName = empty($sku_names[2]) ? '' : $sku_names[2];
                    $sku = $sellerName . "." . $modelName . "." . $assigneeName;
                } else {
                    $sku = $sellerName . "." . $modelName;
                }
                $legend .= "<span class=\"dot\" style=\"background-color:" . $aColors[$ln] . "\"></span><a title=\"Open " . $asin . " at Amazon." . $dnsCountry . "\" target=_blank href=\"http://www.amazon." . $dnsCountry . "/dp/$asin\">$sku</a>&nbsp;&nbsp;|&nbsp;&nbsp;";
                $items .= $line . ';';
                $aSkus[] = $sku;
                $aAsins[] = $asin;
            }
            $sAsins = implode("','", $aAsins);
            $sSkus = implode("','", $aSkus);
            //var_dump($sSkus);
            //var_dump($sAsins);
            $op = '+';
            if ($h == 'rank1') $op = '-';
            if ($region <> 'ca' and $h == 'rank1') {
                $q1Sub .= ",SUM(IF(a.asin = '$asin', 0 $op IF(a.$sh<1000,a.$sh,ROUND(a.$sh/1000,1)), 0)) as '$sku $h' ";
            } else {
                $q1Sub .= ",SUM(IF(a.asin = '$asin', 0 $op a.$sh, 0)) as '$sku $h' ";
            }
            if ($h == 'price') {
                $qAllSub .= ", CONCAT('$',SUM(IF(a.asin = '$asin', a.$sh, 0))) as '$sku $h' ";
            } else {
                $qAllSub .= ", SUM(IF(a.asin = '$asin', a.$sh, 0)) as '$sku $h' ";
            }
        }
        if (empty($sAsins)) {
            goto GroupPriceAvgratingEnd;
        }

        $decimal = 0;
        $sh = substr($h, 0, 1) . substr($h, -2);
        if ($h == 'reviews') {
            $decimal = 0;
        } else if ($h == 'avgrating') {
            $decimal = 1;
        } else if ($h == 'price') {
            $decimal = 2;
        } else if ($h == 'rank1') {
            $decimal = 2;
        }
        $qBaseSub .= ", ROUND(avg($h),$decimal) as $sh ";
        if ($h == 'rank1') $q1 = "SELECT dtime $q1Sub FROM (SELECT date_format(updated - INTERVAL HOUR(updated)%$interval HOUR,'$dateFormat') as dtime,asin $qBaseSub FROM mws_us WHERE asin IN ('$sAsins') AND $h > 0 AND $rank1_date_sql  GROUP BY asin, dtime HAVING $sh>0 ) a GROUP BY dtime ORDER BY dtime DESC"; //limit 2
        else $q1 = "SELECT dtime $q1Sub FROM (SELECT date_format(from_unixtime(time+$timeoffset),'$dateFormat') as dtime, asin $qBaseSub FROM $table WHERE asin IN ('$sAsins') AND $h > 0 AND $date_sql GROUP BY asin, dtime HAVING $sh>0 ) a GROUP BY dtime ORDER BY dtime DESC"; //limit 2
        //echo "<pre>$q1</pre>"; //testing
        if ($h == 'rank1') {
            $aMapData = mwsQuery($q1);
            $sql1 = "SELECT dtime,dtime2 $q1Sub FROM (SELECT date_format(updated - INTERVAL HOUR(updated)%$interval HOUR,'$dateFormat') as dtime, date_format(updated - INTERVAL HOUR(updated)%$interval HOUR,'%Y-%b-%d %l:00%p') as dtime2,asin $qBaseSub FROM mws_us WHERE asin IN ('$sAsins') AND $h > 0 AND $rank1_date_sql  GROUP BY asin, dtime,dtime2 HAVING $sh>0 ) a GROUP BY dtime,dtime2 ORDER BY dtime DESC"; //limit 2
            $sql2 = "SELECT date_format(from_unixtime(time+$timeoffset),'$dateFormat') as dtime, asin,price,reviews,avgrating FROM $table WHERE asin IN ('$sAsins') AND price > 0 AND $date_sql GROUP BY asin, dtime,price,reviews,avgrating HAVING price>0  order by dtime desc";
            //echo "<pre>$sql2</pre>"; //testing
            $priceData = array();
            $priceData = sqlquery($sql2);
            $aRank1Data = sqlquery($sql1);
            $graph_tip[0] = $aRank1Data;
            $graph_tip[1] = $priceData;
            $graph_tip[2] = $sSkus;
            $graph_tip[3] = $sAsins;
        }
        if ($h == 'price') {
            $sql1 = "SELECT dtime,dtime2 $q1Sub FROM (SELECT date_format(from_unixtime(time+$timeoffset),'$dateFormat') as dtime,date_format(from_unixtime(time+$timeoffset),'%b-%d %l:00%p') as dtime2, asin,ROUND(avg(price),2) as pce FROM $table WHERE asin IN ('$sAsins') AND $h > 0 AND $date_sql GROUP BY asin, dtime,dtime2 HAVING pce>0 ) a GROUP BY dtime,dtime2 ORDER BY dtime DESC"; //limit 2
            $sql2 = "SELECT date_format(from_unixtime(time+$timeoffset),'$dateFormat') as dtime, asin,price,reviews,avgrating FROM $table WHERE asin IN ('$sAsins') AND price > 0 AND $date_sql GROUP BY asin, dtime,price,reviews,avgrating HAVING price>0  order by dtime desc";
            $ReviewRating = sqlquery($sql2);
            $PriceData = sqlquery($sql1);
            $alert_data[0] = $PriceData;
            $alert_data[1] = $sSkus;
            $alert_data[2] = $sAsins;
            $alert_data[3] = $ReviewRating;
            return analyzeAlertInfo($alert_data);
        }
        if($h == 'reviews') {
        }
        GroupPriceAvgratingEnd:
    }
}

function mwsQuery($sql)
{
    $ret = array();
    $link = mysqli_connect(
        'mysql',
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
//printf("<pre>%s</pre>\n",var_dump($aAlias));
function retriveCmpetitorAlias()
{
    $region = isset($_GET['region']) ? $_GET['region'] : 'us';
    //$file = file_get_contents('http://czyusa.com/amazon.'. $region .'_asin_sku_competitors.txt');
    $file = file_get_contents('/var/www/html/mt/MWSProducts/asin_alias.txt');
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

function getGroupsFromGoogleDoc($aAlias, $dataSrc)
{
    global $BSR;
    $aGroup = array();
    $csvFile = file($dataSrc);
    foreach ($csvFile as $line) {
        $csv_check_data = str_getcsv($line);
        if (isset($_GET['tier'])) {
            if ($_GET['tier'] == $csv_check_data[CSV_TIER]) {
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
        //var_dump($line);
        $csv_product_asin = substr($line[CSV_URL], strrpos($line[CSV_URL], '/') + 1);
        $csv_product_len = strlen($csv_product_asin);
        if ($csv_product_len == 10 && !preg_match('/[^A-Za-z0-9]/', $csv_product_asin)) {
            $product_asin = $csv_product_asin;
        }
        $BSR[$product_asin]=$line[CSV_BSR];
        $line = $line[CSV_TOP3COM];
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
                $alia = "NO_ALIA";
            } else {
                $alia = $aAlias[$asin];
            }

            $new_str = sprintf("%s,%s\n", $asin, $alia);
            $line_str = $new_str . $line_str;
        }

        if (!empty($product_asin)) {
            if (empty($aAlias[$product_asin])) {
                $alia = "NO_ALIA";
            } else {
                $alia = $aAlias[$product_asin];
            }
            $line_str = sprintf("%s,%s\n", $product_asin, $alia) .  $line_str;;
        }
        $aGroup[] = $line_str;
    }
    //var_dump($BSR);
    return $aGroup;
}

function find_price($aPrices, $asin, $dtime)
{
    foreach ($aPrices as $price) {
        if ($price['asin'] == $asin) {
            if ($price['dtime'] >= $dtime) {
                continue;
            }

            return $price;
        }
    }
    return null;
}

function store_map($img, $data, $shape = 'dot', $col, $row, $x, $y)
{
    global $iMap;
    # Title, also tool-tip text:
    //var_dump($data);
    //$day_data = array_chunk($data, 4);
    $tip_str = '';
    $rank1Data = $data[0];
    $priceData = $data[1];
    $aAsins = array();
    $aSkus = array();
    if (isset($data[2])) {
        $aSkus = explode(',', $data[2]);
    }
    if (isset($data[3])) {
        $aAsins = explode(',', $data[3]);
    }

    $tip_str = sprintf("dtime:%s<br>", $rank1Data[$col]['dtime2']);

    for ($i = 0; $i < count($aSkus); $i++) {
        $sku_str = $aSkus[$i];
        $sku_index = $sku_str = str_replace("'", "", $sku_str);
        $asin_str = str_replace("'", "", $aAsins[$i]);
        $block = explode('.', $sku_str);
        $sku_str = $block[1];
        $rank1 = empty($rank1Data[$col][$sku_index . " rank1"]) ? '' : $rank1Data[$col][$sku_index . " rank1"];
        $price_details = find_price($priceData, $asin_str, $rank1Data[$col]['dtime']);
        $price = empty($price_details['price']) ? 'N/A' : $price_details['price'];
        $reviews = empty($price_details['reviews']) ? 'N/A' : $price_details['reviews'];
        $avgrating = empty($price_details['avgrating']) ? 'N/A' : $price_details['avgrating'];
        $lr = ($i == count($aSkus) - 1) ? "" : "<br>";
        $tip_str = $tip_str . sprintf("%s,RK1=%s,PR=%s,RV=%s,RT=%s" . "$lr", $sku_str, $rank1, $price, $reviews, $avgrating);
    }

    //$title = "$asin,\nrank1:$rank1,\nrank2:$rank2,price:$price,reviews:$reviews,avgrating:$avgrating";
    # Required alt-text:
    $title = '';
    $alt = "GRAPH";
    # Link URL, for demonstration only:
    $href = "#$row";
    # Convert coordinates to integers:
    $coords = sprintf("%d,%d,3", $x, $y);
    # Append the record for this data point shape to the image map string:
    $iMap .= "<area shape=\"circle\" coords=\"$coords\""
        .  " title=\"\" alt=\"$alt\" href=\"$href\" onmouseover=\"tip.start(this)\" tips=\"$tip_str\"/>\n";
}

function doDrawChart($aMapData, $plotType, $graph_tip, $aTooltipData, $h = '', $title_x = '', $yLegents = '', $width = 1600, $height = 120, $yAxisType = 'linear')
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
    $plot->SetFontTTF('x_label', dirname(__FILE__) . '/../lib/Yagora.ttf', 9);
    $plot->SetFontTTF('y_label', dirname(__FILE__) . '/../lib/Yagora.ttf', 8);
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
    if ($plotType == 'linepoints') {
        $plot->SetCallback('data_points', 'store_map', $graph_tip); //tooltip
    }

    $plot->DrawGraph();
    $mapId = rand();
    echo '<map name="map' . $mapId . '">' . $iMap . '</map><img src="' . $plot->EncodeImage() . '" alt="Plot Image" usemap="#map' . $mapId . '">' . "\n";
    $iMap = '';
}
function DrawChartLinearBar($points, $aMapData, $aTooltipData, $h = '', $title_x = '', $yLegents = '', $width = 1600, $height = 120, $graph_tip)
{
    $plotType = 'linepoints';
    if (stristr('rank1,reviewGap', $h) == FALSE) {
        $plotType = 'bars';
        $height = $height * 0.7;
    }
    $aTooltipData = array('this is a test');
    if (count($aMapData) > $points) {
        $arrays = array_chunk($aMapData, $points);
        foreach ($arrays as $array) {
            if (count($array) <> $points) {
                continue;
            }
            $width = count($array) * 40;
            if ($width < 1400) $width = 1400;
            doDrawChart($array, $plotType, $graph_tip, $aTooltipData, $h, $title_x, $yLegents, $width, $height);
        }
        return;
    }
    doDrawChart($aMapData, $plotType, $graph_tip, $aTooltipData, $h, $title_x, $yLegents, $width, $height);
}

function GroupPriceAvgratingReivew($group, $aH, $alter = '')
{
    global $interval, $rank1_date_sql, $models, $assignee, $region, $sellers, $dnsCountry, $timeoffset, $dateFormat, $table, $date_sql, $aColors, $image_width, $aTooltipData, $sAsinsWithRank1NotToBeDevidedBy1000;
    $qBaseSub = $q1Sub = $qAllSub = $items = '';
    $legend = '<div font-size: 0.875em; /* 14px/16=0.875em */>';
    $group = str_replace(array("\""), "", $group);
    $aLines = explode("\n", $group);
    $graph_tip = array();
    $sSkus = '';
    //~ echo '<pre>'; 	print_r($aLines); 	echo '</pre>'; //testing

    foreach ($aH as $h) {
        $sh = substr($h, 0, 1) . substr($h, -2);
        $q1 = $q1Sub = '';
        for ($ln = 0; $ln < count($aLines); $ln++) {
            $line = $aLines[$ln];
            if (empty($line)) {
                continue;
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

            if (stripos($legend, $asin) == false) {
                $modelName = empty($sku_names[1]) ? '' : $sku_names[1];
                $sellerName = $sku_names[0];
                if ($ln == 0 && $sellerName == 'ispr') {
                    $assigneeName = empty($sku_names[2]) ? '' : $sku_names[2];
                    $sku = $sellerName . "." . $modelName . "." . $assigneeName;
                } else {
                    $sku = $sellerName . "." . $modelName;
                }
                $legend .= "<span class=\"dot\" style=\"background-color:" . $aColors[$ln] . "\"></span><a title=\"Open " . $asin . " at Amazon." . $dnsCountry . "\" target=_blank href=\"http://www.amazon." . $dnsCountry . "/dp/$asin\">$sku</a>&nbsp;&nbsp;|&nbsp;&nbsp;";
                $items .= $line . ';';
                $aSkus[] = $sku;
                $aAsins[] = $asin;
            }
            $sAsins = implode("','", $aAsins);
            $sSkus = implode("','", $aSkus);
            //var_dump($sSkus);
            //var_dump($sAsins);
            $op = '+';
            if ($h == 'rank1') $op = '-';
            if ($region <> 'ca' and $h == 'rank1') {
                $q1Sub .= ",SUM(IF(a.asin = '$asin', 0 $op IF(a.$sh<1000,a.$sh,ROUND(a.$sh/1000,1)), 0)) as '$sku $h' ";
            } else {
                $q1Sub .= ",SUM(IF(a.asin = '$asin', 0 $op a.$sh, 0)) as '$sku $h' ";
            }
            if ($h == 'price') {
                $qAllSub .= ", CONCAT('$',SUM(IF(a.asin = '$asin', a.$sh, 0))) as '$sku $h' ";
            } else {
                $qAllSub .= ", SUM(IF(a.asin = '$asin', a.$sh, 0)) as '$sku $h' ";
            }
        }
        if (empty($sAsins)) {
            goto GroupPriceAvgratingEnd;
        }

        $decimal = 0;
        $sh = substr($h, 0, 1) . substr($h, -2);
        if ($h == 'reviews') {
            $decimal = 0;
        } else if ($h == 'avgrating') {
            $decimal = 1;
        } else if ($h == 'price') {
            $decimal = 2;
        } else if ($h == 'rank1') {
            $decimal = 2;
        }
        $qBaseSub .= ", ROUND(avg($h),$decimal) as $sh ";
        if ($h == 'rank1') $q1 = "SELECT dtime $q1Sub FROM (SELECT date_format(updated - INTERVAL HOUR(updated)%$interval HOUR,'$dateFormat') as dtime,asin $qBaseSub FROM mws_us WHERE asin IN ('$sAsins') AND $h > 0 AND $rank1_date_sql  GROUP BY asin, dtime HAVING $sh>0 ) a GROUP BY dtime ORDER BY dtime DESC"; //limit 2
        else $q1 = "SELECT dtime $q1Sub FROM (SELECT date_format(from_unixtime(time+$timeoffset),'$dateFormat') as dtime, asin $qBaseSub FROM $table WHERE asin IN ('$sAsins') AND $h > 0 AND $date_sql GROUP BY asin, dtime HAVING $sh>0 ) a GROUP BY dtime ORDER BY dtime DESC"; //limit 2
        echo "<pre>$q1</pre>"; //testing
        $aMapData = mwsQuery($q1);
        if ($h == 'rank1') {
            $sql1 = "SELECT dtime,dtime2 $q1Sub FROM (SELECT date_format(updated - INTERVAL HOUR(updated)%$interval HOUR,'$dateFormat') as dtime, date_format(updated - INTERVAL HOUR(updated)%$interval HOUR,'%Y-%b-%d %l:00%p') as dtime2,asin $qBaseSub FROM mws_us WHERE asin IN ('$sAsins') AND $h > 0 AND $rank1_date_sql  GROUP BY asin, dtime,dtime2 HAVING $sh>0 ) a GROUP BY dtime,dtime2 ORDER BY dtime DESC"; //limit 2

            $sql2 = "SELECT date_format(from_unixtime(time+$timeoffset),'$dateFormat') as dtime, asin,price,reviews,avgrating FROM $table WHERE asin IN ('$sAsins') AND price > 0 AND $date_sql GROUP BY asin, dtime,price,reviews,avgrating HAVING price>0  order by dtime desc";
            //echo "<pre>$sql2</pre>"; //testing
            $priceData = array();
            $priceData = sqlquery($sql2);
            $aRank1Data = sqlquery($sql1);
            $graph_tip[0] = $aRank1Data;
            $graph_tip[1] = $priceData;
            $graph_tip[2] = $sSkus;
            $graph_tip[3] = $sAsins;
        }
        if ($h == 'price') {
            $alert_data[0] = $aMapData;
            $alert_data[1] = $sSkus;
            $alert_data[2] = $sAsins;
            //getAlerts($alert_data);
        }
        //echo "<pre>". var_dump($aMapData). "</pre>";
        $image_height = 200;
        $image_height = count($aLines) * 35;
        if ($image_height < 150) $image_height = 150;
        //~ if ($_GET['items'] <>'') $image_height = 600;
        if (!empty($aMapData)) {
            $image_width = count($aMapData) * 40;
        }

        if ($image_width < 1400) $image_width = 1400;
        foreach (explode(',', PERIODS) as $dateRange) {
            if ((isset($_GET['period']) and $dateRange == $_GET['period']) or (empty($_GET['items']) and $dateRange == '24 hours')) {
                //~ echo $dateRange ."&nbsp;&nbsp;|&nbsp;&nbsp;";
                $legend .= '* ';
            }
            $legend .= '<a target=_blank title="Chart of ' . $dateRange . ' averange" href="' . '/mt/model/' . basename(__FILE__) . '?region=' . $region . '&period=' . str_replace(' ', '+', $dateRange) . "&items=$items\">$dateRange</a>&nbsp;&nbsp;|&nbsp;&nbsp;";
        }
        $points = (24 / $interval) + 2;
        $legend .= '<br></div>';
        if ($h == 'rank1') echo '<hr>' . $legend;
        if (is_array($aMapData) and !empty($aMapData)) DrawChartLinearBar($points, $aMapData, $aTooltipData, $h, 'time', '', $image_width, $image_height, $graph_tip);

        GroupPriceAvgratingEnd: if (!empty($_GET['items'])) {
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
    if (isset($array) && count($array) > 0) {
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
