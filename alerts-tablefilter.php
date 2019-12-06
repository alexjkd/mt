<?php
require "vendor/autoload.php";
require_once "./lib/functions.php";
const RECORDS_PER_PAGE = 200;


function retriveTotal()
{
	$sql = "SELECT count(*) as total FROM `amazon_ispring_reviews`";
	$records = sqlquery($sql);
	return $records[0]['total'];
}
$endPage = ceil(retriveTotal()/RECORDS_PER_PAGE);
$pageNum = empty($_GET["pageNum"])?1:$_GET["pageNum"];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "http://localhost/mt/model/GraphData.php?alerts=true");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $output = curl_exec($ch);
    curl_close($ch);
    //var_dump($output);
    $data = json_decode($output, true);
    
    $html_table .= "<tr><th>ASIN</th>";
    $html_table .= "<th>SKU</th>";
    $html_table .= "<th>Dtime</th>";
    $html_table .= "<th>Price</th>";
    $html_table .= "<th>Percentage</th>";
    $html_table .= "<th>BSR</th>";
    $html_table .= "<th>Reviews</th>";
    $html_table .= "<th>Rating</th>";
    $html_table .= "</tr>";
    /*Dynamically generating rows & columns*/
    $sendmail = false;
    //$data = array_unique($data);
    for ($i = 0; $i < count($data); $i++) {
        $asin = $data[$i]["ASIN"]; 
        $percentage = $data[$i]["Percentage"];
        //$percentage = str_replace('+','↑',$percentage);
        //$percentage = str_replace('-','↓',$percentage);
        $html_table .= "<tr>";
        $html_table .= "<td align=\"center\">" ."<a target=_blank href=http://www.amazon.com/dp/$asin>" . $asin . "</a></td>";
        $html_table .= "<td align=\"center\">" . $data[$i]["SKU"] . "</td>";
        $html_table .= "<td align=\"center\">" . $data[$i]["Dtime"] . "</td>";
        $html_table .= "<td align=\"center\">" . $data[$i]["Price"] . "</td>";
        $html_table .= "<td align=\"center\">" . $data[$i]["Percentage"] . "</td>";
        $html_table .= "<td align=\"center\">" . $data[$i]["BSR"] . "</td>";
        $html_table .= "<td align=\"center\">" . $data[$i]["Reviews"] . "</td>";
        $html_table .= "<td align=\"center\">" . $data[$i]["Rating"] . "</td>";

 
        $html_table .= "</tr>";
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
	<div>
    <a href="?pageNum=1">Start</a>
	<a href="?pageNum=<?php echo $pageNum==$endPage?$endPage:($pageNum+1)?>">Next</a>
	<a href="?pageNum=<?php echo $pageNum==1?1:($pageNum-1)?>">Pre</a>
    <a href="?pageNum=<?php echo $endPage?>">End</a>
 
</div>
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
