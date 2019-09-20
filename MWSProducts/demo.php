<?php
ini_set('display_errors',1);
error_reporting(E_ALL);

require_once 'db.php';
/*******************************************************************************
 * Copyright 2009-2018 Amazon Services. All Rights Reserved.
 * Licensed under the Apache License, Version 2.0 (the "License"); 
 *
 * You may not use this file except in compliance with the License. 
 * You may obtain a copy of the License at: http://aws.amazon.com/apache2.0
 * This file is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR 
 * CONDITIONS OF ANY KIND, either express or implied. See the License for the 
 * specific language governing permissions and limitations under the License.
 *******************************************************************************
 * PHP Version 5
 * @category Amazon
 * @package  Marketplace Web Service Products
 * @version  2011-10-01
 * Library Version: 2017-03-22
 * Generated: Thu Oct 11 10:46:02 PDT 2018
 */

/**
 * Get Competitive Pricing For ASIN Sample
 */

require_once('mws_config.php');

/************************************************************************
 * Instantiate Implementation of MarketplaceWebServiceProducts
 *
 * AWS_ACCESS_KEY_ID and AWS_SECRET_ACCESS_KEY constants
 * are defined in the .config.inc.php located in the same
 * directory as this sample
 ***********************************************************************/
// More endpoints are listed in the MWS Developer Guide
// North America:
$serviceUrl = "https://mws.amazonservices.com/Products/2011-10-01";
// Europe
//$serviceUrl = "https://mws-eu.amazonservices.com/Products/2011-10-01";
// Japan
//$serviceUrl = "https://mws.amazonservices.jp/Products/2011-10-01";
// China
//$serviceUrl = "https://mws.amazonservices.com.cn/Products/2011-10-01";


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
        $config);

/************************************************************************
 * Uncomment to try out Mock Service that simulates MarketplaceWebServiceProducts
 * responses without calling MarketplaceWebServiceProducts service.
 *
 * Responses are loaded from local XML files. You can tweak XML files to
 * experiment with various outputs during development
 *
 * XML files available under MarketplaceWebServiceProducts/Mock tree
 *
 ***********************************************************************/
 // $service = new MarketplaceWebServiceProducts_Mock();

/************************************************************************
 * Setup request parameters and uncomment invoke to try out
 * sample for Get Competitive Pricing For ASIN Action
 ***********************************************************************/
 // @TODO: set request. Action can be passed as MarketplaceWebServiceProducts_Model_GetCompetitivePricingForASIN
 $csvFile = file('https://docs.google.com/spreadsheets/d/e/2PACX-1vR2gY22xgcaR4JUr3naK5nXbFzw3pL_Ogn4msFRDGfVA8nILfEs-BOdxDRt2Jvhx9Yz31eAF8IfpjBn/pub?gid=0&single=true&output=csv');
  $data = [];
  $csv_data = [];
  foreach ($csvFile as $line) {
    $csv_check_data = str_getcsv($line);
      if(isset($_GET['tier'])){
      	if($_GET['tier'] == $csv_check_data[2] ){
        $data[] = $csv_check_data;
      	}
      }else{
      	$data[] = $csv_check_data;
      }
  }
  foreach($data as $csv_array_data){
    $csv_product_asin = substr($csv_array_data[0], strrpos($csv_array_data[0], '/') + 1);
   	$csv_product_len = strlen($csv_product_asin);
	if($csv_product_len == 10 && !preg_match('/[^A-Za-z0-9]/', $csv_product_asin)){
      $csv_array_data[5] = $csv_product_asin;
      $csv_data[] = $csv_product_asin;
	}   
   $csv_values = implode("','", $csv_array_data);
   // $mysqli = mws_mysqlConnect();
   // $insert_sql = "INSERT INTO csv (product_group, model, tier, owner, product, group_asin) VALUES ('".$csv_values."')";
   // $mysqli->query($insert_sql);
   }
   $csv_filter_array = array_filter($csv_data);
   $csv_filter_array = array_unique($csv_filter_array);
   $csv_chunk = array_chunk($csv_filter_array,10);
   $GLOBALS['csv_array'] = $data;
   $asin_array = [];
   foreach ($data as $entry) {
     if (!empty($entry[4])){
	   $csv_data = explode(',', $entry[4]);
	   if (!empty($csv_data)){  
	     $asin_data = array_map('trim', $csv_data);
	 	 foreach($asin_data as $asin_final_data){
	 	   $asin_len = strlen($asin_final_data);
	 	   if($asin_len == 10 && !preg_match('/[^A-Za-z0-9]/', $asin_final_data)){
	         $asin_array[] = $asin_final_data;
	 	   }
	     }
	   }
     }
   }
   $asin_filter_array = array_filter($asin_array);
   $asin_filter_array = array_unique($asin_filter_array);
   $asin_chunk = array_chunk($asin_filter_array,10);
   $res = array_merge($csv_chunk, $asin_chunk);
   $request = new MarketplaceWebServiceProducts_Model_GetCompetitivePricingForASINRequest();
   $request->setSellerId(MERCHANT_ID);
   $request->setMarketplaceId(MARKETPLACE_ID);
   $asin_list= new MarketplaceWebServiceProducts_Model_ASINListType();
   foreach($res as $asin){
     $asin_list->setASIN($asin);
	 $request->setASINList($asin_list);
	 invokeGetCompetitivePricingForASIN($service, $request);
   }
   header('Location: index.php');
   exit();
 
 
 
 
 
 //$request = new MarketplaceWebServiceProducts_Model_GetCompetitivePricingForASINRequest();
 //$request->setSellerId(MERCHANT_ID);
 //// object or array of parameters
 //invokeGetCompetitivePricingForASIN($service, $request);

/**
  * Get Get Competitive Pricing For ASIN Action Sample
  * Gets competitive pricing and related information for a product identified by
  * the MarketplaceId and ASIN.
  *
  * @param MarketplaceWebServiceProducts_Interface $service instance of MarketplaceWebServiceProducts_Interface
  * @param mixed $request MarketplaceWebServiceProducts_Model_GetCompetitivePricingForASIN or array of parameters
  */

  function invokeGetCompetitivePricingForASIN(MarketplaceWebServiceProducts_Interface $service, $request)
  {
      try {
	    header("Content-type: text/xml");
        $response = $service->GetCompetitivePricingForASIN($request);
        $dom = new DOMDocument();
        $dom->loadXML($response->toXML());
        echo $dom->saveXML(); die;
        // print_r($response);die;
        echo ("Service Response\n");
        echo ("=============================================================================\n");

        $dom = new DOMDocument();
        $dom->loadXML($response->toXML());
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        echo $dom->saveXML();
        echo("ResponseHeaderMetadata: " . $response->getResponseHeaderMetadata() . "\n");

     } catch (MarketplaceWebServiceProducts_Exception $ex) {
        echo("Caught Exception: " . $ex->getMessage() . "\n");
        echo("Response Status Code: " . $ex->getStatusCode() . "\n");
        echo("Error Code: " . $ex->getErrorCode() . "\n");
        echo("Error Type: " . $ex->getErrorType() . "\n");
        echo("Request ID: " . $ex->getRequestId() . "\n");
        echo("XML: " . $ex->getXML() . "\n");
        echo("ResponseHeaderMetadata: " . $ex->getResponseHeaderMetadata() . "\n");
     }
 }

