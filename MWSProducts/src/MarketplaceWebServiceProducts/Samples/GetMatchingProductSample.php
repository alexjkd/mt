<?php
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
 * Get Matching Product Sample
 */

require_once('.config.inc.php');

/************************************************************************
 * Instantiate Implementation of MarketplaceWebServiceProducts
 *
 * AWS_ACCESS_KEY_ID and AWS_SECRET_ACCESS_KEY constants
 * are defined in the .config.inc.php located in the same
 * directory as this sample
 ***********************************************************************/
// More endpoints are listed in the MWS Developer Guide
// North America:
//$serviceUrl = "https://mws.amazonservices.com/Products/2011-10-01";
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
 $service = new MarketplaceWebServiceProducts_Mock();

/************************************************************************
 * Setup request parameters and uncomment invoke to try out
 * sample for Get Matching Product Action
 ***********************************************************************/
 // @TODO: set request. Action can be passed as MarketplaceWebServiceProducts_Model_GetMatchingProduct
 $request = new MarketplaceWebServiceProducts_Model_GetMatchingProductRequest();
 $request->setSellerId(MERCHANT_ID);
 // object or array of parameters
 invokeGetMatchingProduct($service, $request);

/**
  * Get Get Matching Product Action Sample
  * Gets competitive pricing and related information for a product identified by
  * the MarketplaceId and ASIN.
  *
  * @param MarketplaceWebServiceProducts_Interface $service instance of MarketplaceWebServiceProducts_Interface
  * @param mixed $request MarketplaceWebServiceProducts_Model_GetMatchingProduct or array of parameters
  */

  function invokeGetMatchingProduct(MarketplaceWebServiceProducts_Interface $service, $request)
  {
      try {
        // header("Content-type: text/xml");
        $response = $service->GetMatchingProduct($request);
        $dom = new DOMDocument();
        $dom->loadXML($response->toXML());
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        // echo $dom->saveXML(); die;
        echo '<pre>';
        $product_data = array();
        $products = $dom->getElementsByTagName("Product");
        foreach ($products as $product) {

          // Fetching Seller SKU for product
          $sku_info = $product->getElementsByTagName("SellerSKU");
          $sku = $sku_info[0]->nodeValue;
          $product_data[$sku] = array();

          // Fetching Category of product listing
          $categoryId = $product->getElementsByTagName("ProductCategoryId");
          $product_data[$sku]['category'] = $categoryId[0]->nodeValue;

          // Fetching Sales Ranking
          $salesRankings = $product->getElementsByTagName("Rank");
          $product_data[$sku]['rank'] = $salesRankings[0]->nodeValue;

          $Price = $product->getElementsByTagName("Price");
          foreach ($Price as $key => $value) {
            // Fetching List Price of product
          	$ListPrice = $value->getElementsByTagName("ListingPrice");
          	foreach ($ListPrice as $k => $val) {

              // Currency Code of list price of product
  				    $currencyCode = $val->getElementsByTagName("CurrencyCode");
              $product_data[$sku]['currency'] = $currencyCode[0]->nodeValue;

              // Fetching list price of product
        			$Amount = $val->getElementsByTagName("Amount");
              $product_data[$sku]['amount'] = $amount[0]->nodeValue;

          	}

          }
          
        }
        $conn = mysqli_connect("localhost", "think14d_demo", "demo@153", "think14d_mws");
        $sql = "INSERT INTO mws (product_sku, category_id, rank, price, currency) VALUES ('".$category."','".$ranking."','".$currency_code."','".$amount."')";
        if (mysqli_query($conn, $sql)) {
            echo "inserted successfully";
        } else {
            echo "Error:" . mysqli_error($conn);
        }
        mysqli_close($conn);
        die;
        // echo("ResponseHeaderMetadata: " . $response->getResponseHeaderMetadata() . "\n");

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

