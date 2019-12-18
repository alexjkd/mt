<?php
require_once 'my_mws_functions.php';

$sPublishedGoogleSheetTsv = file('https://docs.google.com/spreadsheets/d/e/2PACX-1vR2gY22xgcaR4JUr3naK5nXbFzw3pL_Ogn4msFRDGfVA8nILfEs-BOdxDRt2Jvhx9Yz31eAF8IfpjBn/pub?gid=352479658&single=true&output=tsv'); //mwsTsv: IMPORTRANGE 4-LA "1-Tier!B1:F300"
$aWatchedListings = getWatchedListings($sPublishedGoogleSheetTsv);
// echo "<pre>"; print_r($aWatchedListings); echo "</pre>";
/*
Array
(
  [B003XELTTG] => Array
  (
    [model] => RCC7-AMUK
    [tier] => 1
    [owner] => Joy
    [comp] => B00I0ZGOZM,B01MXV9O3J,B00N9W3FTE
  )
)       
*/

// $serviceUrl = "https://mws.amazonservices.com/Products/2011-10-01";
// $serviceUrl = "https://mws.amazonservices.ca/Products/2011-10-01";
// $serviceUrl = "https://mws-eu.amazonservices.com//Products/2011-10-01";
// $serviceUrl = "https://mws.amazonservices.co.uk/Products/2011-10-01";
// Europe
$url_array = array(
	array("region" => "us","url" => "https://mws.amazonservices.com/Products/2011-10-01","id" => "ATVPDKIKX0DER"),
	array("region" => "ca","url" => "https://mws.amazonservices.ca/Products/2011-10-01","id" => "A2EUQ1WTGCTBG2"),
	array("region" => "uk","url" => "https://mws-eu.amazonservices.com/Products/2011-10-01","id" => "A1F83G8C2ARO7P"));
$array_length = count($url_array);
// for($i = 0; $i < $array_length; $i++){
for($i = 0; $i < 1; $i++){
	$region = $url_array[$i]['region'];
	$serviceUrl = $url_array[$i]['url'];
	$marketplace_id = $url_array[$i]['id'];
	// echo "<h3>$serviceUrl</h3>"; continue; //testing
	getMwsReqObj($region,$serviceUrl,$marketplace_id,$aAsin);
}
  $asin_chunk = array_chunk($aWatchedListings,10);
  $res = array_merge($csv_chunk, $asin_chunk);
  
  // $request = new MarketplaceWebServiceProducts_Model_GetCompetitivePricingForASINRequest(); //testing
  $request = new MarketplaceWebServiceProducts_Model_GetMatchingProductRequest(); 
  $request->setSellerId(MERCHANT_ID);
  $request->setMarketplaceId($marketplace_id);
  $asin_list= new MarketplaceWebServiceProducts_Model_ASINListType();
  foreach($res as $asin){
    $asin_list->setASIN($asin);
	  $request->setASINList($asin_list);
	  // invokeGetCompetitivePricingForASIN($service, $request,$region);
	  // invokeGetMatchingProduct2($service, $request, $region);
	  invokeGetMatchingProduct($service, $request,$region);
  }



function getWatchedListings($sPublishedGoogleSheetTsv) {
	$aWatchedListings = [];
	foreach($sPublishedGoogleSheetTsv as $sTsvRow)	{
		// echo $sTsvRow;
		$aTsvRow = explode("\t", $sTsvRow);
		// echo "<pre>"; print_r($aTsvRow); echo "</pre>";
		/*
		Array
		(
	    [0] => URL
	    [1] => Model
	    [2] => Tier
	    [3] => Owner
	    [4] => Top3Comp
		)
		*/
		if (preg_match('/(B0\w{8})/',$aTsvRow[0],$m) == FALSE) continue;
		$asin = $m[1];
		$model = $aTsvRow[1];
		$tier = $aTsvRow[2];
		$owner = $aTsvRow[3];
		$comp = $aTsvRow[4];
		// echo "<pre>"; print_r($asin .' top 3 comps: '. $comp ); echo "</pre>";
		$aWatchedListings[$asin] = array('model'=>$model,'tier'=>$tier,'owner'=>$owner,'comp'=>$comp);
	}
	$aWatchedListings = array_unique($aWatchedListings);
	return $aWatchedListings;
}
exit;

$csvFile = file('https://docs.google.com/spreadsheets/d/e/2PACX-1vR2gY22xgcaR4JUr3naK5nXbFzw3pL_Ogn4msFRDGfVA8nILfEs-BOdxDRt2Jvhx9Yz31eAF8IfpjBn/pub?gid=0&single=true&output=csv'); //MT_lists: IMPORTRANGE 4-LA "1-Tier!B1:F300"
$data = [];
$csv_data = [];
$i=0;
foreach ($csvFile as $line) {
  $csv_check_data = str_getcsv($line);
    if(isset($_GET['tier'])){
    	if($_GET['tier'] == $csv_check_data[2] ){
        $data[] = $csv_check_data;
    	}
    }else{
    	$data[] = $csv_check_data;
    }
    $i += 1;
    echo "<h3>$line</h3>";
   if ($i>3) break; //testing
   //continue; //testing
}

foreach($data as $csv_array_data){
  $csv_product_asin = substr($csv_array_data[0], strrpos($csv_array_data[0], '/') + 1);
 	$csv_product_len = strlen($csv_product_asin);
  if($csv_product_len == 10 && !preg_match('/[^A-Za-z0-9]/', $csv_product_asin)){
    $csv_array_data[5] = $csv_product_asin;
    $csv_data[] = $csv_product_asin;
  }   
  $csv_values = implode("\t", $csv_array_data);
}
echo "<pre>"; print_r($csv_values); echo "</pre>";
exit;
$csv_filter_array = array_filter($csv_data);
$csv_filter_array = array_unique($csv_filter_array);
$csv_chunk = array_chunk($csv_filter_array,10);
$GLOBALS['csv_array'] = $data;
$aWatchedListings = [];
foreach ($data as $entry) {
  if (!empty($entry[4])){
    $csv_data = explode(',', $entry[4]);
    if (!empty($csv_data)){  
      $asin_data = array_map('trim', $csv_data);
 	  foreach($asin_data as $asin_final_data){
 	    $asin_len = strlen($asin_final_data);
 	    if($asin_len == 10 && !preg_match('/[^A-Za-z0-9]/', $asin_final_data)){
          $aWatchedListings[] = $asin_final_data;
 	    }
     }
  }
  }
}
$aWatchedListings = array_filter($aWatchedListings);
$aWatchedListings = array_unique($aWatchedListings);
$asin_chunk = array_chunk($aWatchedListings,10);
$res = array_merge($csv_chunk, $asin_chunk);


?>