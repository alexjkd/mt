<?php

require_once 'db.php';
require_once('mws_config.php');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$config = array (
  'ServiceURL' => $serviceUrl,
  'ProxyHost' => null,
  'ProxyPort' => -1,
  'ProxyUsername' => null,
  'ProxyPassword' => null,
  'MaxErrorRetry' => 3,
);
$service = new MarketplaceWebServiceProducts_Client(
  AWS_ACCESS_KEY_ID,
  AWS_SECRET_ACCESS_KEY,
  APPLICATION_NAME,
  APPLICATION_VERSION,
  $config
);


function getMwsReqObj($region,$serviceUrl,$marketplace_id,$reqType,$asin_array){
	global $config,$service;
  $asin_filter_array = array_filter($asin_array);
  $asin_filter_array = array_unique($asin_filter_array);
  $asin_chunk = array_chunk($asin_filter_array,10);
  $res = array_merge($csv_chunk, $asin_chunk);
  
  Switch ($reqType) {
  	case 'GetCompetitivePricingForASIN':
  		$request = new MarketplaceWebServiceProducts_Model_GetCompetitivePricingForASINRequest(); //testing		
  		break;
  	case 'GetMatchingProductRequest':
  		$request = new MarketplaceWebServiceProducts_Model_GetMatchingProductRequest(); 
  		break;
  }
  $request->setSellerId(MERCHANT_ID);
  $request->setMarketplaceId($marketplace_id);
	return $request;
}

?>