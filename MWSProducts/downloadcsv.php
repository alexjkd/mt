<?php 

require_once 'db.php';
$mysqli = mws_mysqlConnect();
$filename = "mws_csv.csv";
$fp = fopen('php://output', 'w');
$header = array('Id','Product Sku','Category Id','Rank','Price','Currency','Tier','Owner','Csv Id','Csv Group Product','Updated On','Created On');
header('Content-type: application/csv');
header('Content-Disposition: attachment; filename='.$filename);
fputcsv($fp, $header);
$select_sql = "SELECT * FROM mws";
$result = $mysqli->query($select_sql);
while($row = mysqli_fetch_row($result)) {
	fputcsv($fp, $row);
}
exit;
?>