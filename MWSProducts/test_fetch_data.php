<?php

require_once 'db.php';
require_once('mws_config.php');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
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
	config_service_url($region,$serviceUrl,$marketplace_id);
}
function config_service_url($region,$serviceUrl,$marketplace_id){
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

  $csvFile = file('https://docs.google.com/spreadsheets/d/e/2PACX-1vR2gY22xgcaR4JUr3naK5nXbFzw3pL_Ogn4msFRDGfVA8nILfEs-BOdxDRt2Jvhx9Yz31eAF8IfpjBn/pub?gid=0&single=true&output=csv');
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
     if ($i>2) break; //testing
     //continue; //testing
  }
  
  foreach($data as $csv_array_data){
    $csv_product_asin = substr($csv_array_data[0], strrpos($csv_array_data[0], '/') + 1);
   	$csv_product_len = strlen($csv_product_asin);
	  if($csv_product_len == 10 && !preg_match('/[^A-Za-z0-9]/', $csv_product_asin)){
      $csv_array_data[5] = $csv_product_asin;
      $csv_data[] = $csv_product_asin;
	  }   
    $csv_values = implode("','", $csv_array_data);
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
}

// header('Location: index.php');
 exit();

function test(){

    $response = $service->getLowestOfferListingsForASIN($request);
    
    $dom = new DOMDocument();
    $dom->loadXML($response->toXML());
    $dom->preserveWhiteSpace = false;
    $dom->formatOutput = true;
    $xml_data = $dom->saveXML();
    $dom->loadXML($xml_data);
    
    $otherOfferXml = simplexml_load_string($xml_data);
    
    foreach($otherOfferXml as $offers)
    {
    	// Skipping last RequestID section
    	if(!isset($offers["status"]))
    		continue;
    	
    	// Checking if the API returned any error then continue to next SKU
    	if($offers["status"] != "Success")
    		continue;
    	
    	$asin = (String) $offers->Product->Identifiers->MarketplaceASIN->ASIN;
    	
    	// Going through all ASIN's offers to get price
    	$seller_counter = 0;
    	$others_response_data[$asin] = "";
    	foreach($offers->Product->LowestOfferListings->LowestOfferListing as $offers_list)
    	{
    		$others_response_data[$asin][$seller_counter]["LandedPrice"] = (String) $offers_list->Price->LandedPrice->Amount;
    		$others_response_data[$asin][$seller_counter]["ListingPrice"] = (String) $offers_list->Price->ListingPrice->Amount;
    		$others_response_data[$asin][$seller_counter]["Shipping"] = (String) $offers_list->Price->Shipping->Amount;
    		$others_response_data[$asin][$seller_counter]["Fulfillment"] = $fulfillment_channel;
    		$others_response_data[$asin][$seller_counter]["SKU"] = $asin_array[$asin]["sku"];
    		$others_response_data[$asin][$seller_counter]["AZN_ASIN"] = $asin;
    		$seller_counter++;	
    	}
    }
	}


/**
 * Get Matching Product Action Sample
 * GetMatchingProduct will return the details (attributes) for the
 * given ASIN.
 *   
 * @param MarketplaceWebServiceProducts_Interface $service instance of MarketplaceWebServiceProducts_Interface
 * @param mixed $request MarketplaceWebServiceProducts_Model_GetMatchingProduct or array of parameters
 */
function invokeGetMatchingProduct2(MarketplaceWebServiceProducts_Interface $service, $request, $region) {
    try {
        $response = $service->getMatchingProduct($request);
        echo "Service Response\n";
        echo "=============================================================================\n";
        echo "        GetMatchingProductResponse\n";
        
        $getMatchingProductResultList = $response->getGetMatchingProductResult();
        foreach ($getMatchingProductResultList as $getMatchingProductResult) {
            echo "            GetMatchingProductResult\n";
            if ($getMatchingProductResult->isSetASIN()) {
                echo "        ASIN";
                echo "\n";
                echo "                " . $getMatchingProductResult->getASIN() . "\n";
            }
            if ($getMatchingProductResult->isSetStatus()) {
                echo "        status";
                echo "\n";
                echo "                " . $getMatchingProductResult->getStatus() . "\n";
            }
            if ($getMatchingProductResult->isSetProduct()) {
                echo "                Product\n";
                $product = $getMatchingProductResult->getProduct();
                if ($product->isSetIdentifiers()) {
                    echo "                    Identifiers\n";
                    $identifiers = $product->getIdentifiers();
                    if ($identifiers->isSetMarketplaceASIN()) {
                        echo "                        MarketplaceASIN\n";
                        $marketplaceASIN = $identifiers->getMarketplaceASIN();
                        if ($marketplaceASIN->isSetMarketplaceId()) {
                            echo "                            MarketplaceId\n";
                            echo "                                " . $marketplaceASIN->getMarketplaceId() . "\n";
                        }
                        if ($marketplaceASIN->isSetASIN()) {
                            echo "                            ASIN\n";
                            echo "                                " . $marketplaceASIN->getASIN() . "\n";
                        }
                    }
                    if ($identifiers->isSetSKUIdentifier()) {
                        echo "                        SKUIdentifier\n";
                        $SKUIdentifier = $identifiers->getSKUIdentifier();
                        if ($SKUIdentifier->isSetMarketplaceId()) {
                            echo "                            MarketplaceId\n";
                            echo "                                " . $SKUIdentifier->getMarketplaceId() . "\n";
                        }
                        if ($SKUIdentifier->isSetSellerId()) {
                            echo "                            SellerId\n";
                            echo "                                " . $SKUIdentifier->getSellerId() . "\n";
                        }
                        if ($SKUIdentifier->isSetSellerSKU()) {
                            echo "                            SellerSKU\n";
                            echo "                                " . $SKUIdentifier->getSellerSKU() . "\n";
                        }
                    }
                }
                if ($product->isSetAttributeSets()) {
                    echo "  AttributeSets\n";
                    $attributeSets = $product->getAttributeSets();
                    if ($attributeSets->isSetAny()) {
                        $nodeList = $attributeSets->getAny();
                        echo "<pre>"; print_r($nodeList); echo "</pre>"; ;
                    }
                }
                if ($product->isSetRelationships()) {
                    echo "  Relationships\n";
                    $relationships = $product->getRelationships();
                    if ($relationships->isSetAny()) {
                        $nodeList = $relationships->getAny();
                        echo "<pre>"; print_r($nodeList); echo "</pre>"; ;
                    }
                }
                if ($product->isSetCompetitivePricing()) {
                    echo "                    CompetitivePricing\n";
                    $competitivePricing = $product->getCompetitivePricing();
                    if ($competitivePricing->isSetCompetitivePrices()) {
                        echo "                        CompetitivePrices\n";
                        $competitivePrices = $competitivePricing->getCompetitivePrices();
                        $competitivePriceList = $competitivePrices->getCompetitivePrice();
                        foreach ($competitivePriceList as $competitivePrice) {
                            echo "                            CompetitivePrice\n";
                            if ($competitivePrice->isSetCondition()) {
                                echo "                        condition";
                                echo "\n";
                                echo "                                " . $competitivePrice->getCondition() . "\n";
                            }
                            if ($competitivePrice->isSetSubcondition()) {
                                echo "                        subcondition";
                                echo "\n";
                                echo "                                " . $competitivePrice->getSubcondition() . "\n";
                            }
                            if ($competitivePrice->isSetBelongsToRequester()) {
                                echo "                        belongsToRequester";
                                echo "\n";
                                echo "                                " . $competitivePrice->getBelongsToRequester() . "\n";
                            }
                            if ($competitivePrice->isSetCompetitivePriceId()) {
                                echo "                                CompetitivePriceId\n";
                                echo "                                    " . $competitivePrice->getCompetitivePriceId() . "\n";
                            }
                            if ($competitivePrice->isSetPrice()) {
                                echo "                                Price\n";
                                $price = $competitivePrice->getPrice();
                                if ($price->isSetLandedPrice()) {
                                    echo "                                    LandedPrice\n";
                                    $landedPrice = $price->getLandedPrice();
                                    if ($landedPrice->isSetCurrencyCode()) {
                                        echo "                                        CurrencyCode\n";
                                        echo "                                            " . $landedPrice->getCurrencyCode() . "\n";
                                    }
                                    if ($landedPrice->isSetAmount()) {
                                        echo "                                        Amount\n";
                                        echo "                                            " . $landedPrice->getAmount() . "\n";
                                    }
                                }
                                if ($price->isSetListingPrice()) {
                                    echo "                                    ListingPrice\n";
                                    $listingPrice = $price->getListingPrice();
                                    if ($listingPrice->isSetCurrencyCode()) {
                                        echo "                                        CurrencyCode\n";
                                        echo "                                            " . $listingPrice->getCurrencyCode() . "\n";
                                    }
                                    if ($listingPrice->isSetAmount()) {
                                        echo "                                        Amount\n";
                                        echo "                                            " . $listingPrice->getAmount() . "\n";
                                    }
                                }
                                if ($price->isSetShipping()) {
                                    echo "                                    Shipping\n";
                                    $shipping = $price->getShipping();
                                    if ($shipping->isSetCurrencyCode()) {
                                        echo "                                        CurrencyCode\n";
                                        echo "                                            " . $shipping->getCurrencyCode() . "\n";
                                    }
                                    if ($shipping->isSetAmount()) {
                                        echo "                                        Amount\n";
                                        echo "                                            " . $shipping->getAmount() . "\n";
                                    }
                                }
                            }
                        }
                    }
                    if ($competitivePricing->isSetNumberOfOfferListings()) {
                        echo "                        NumberOfOfferListings\n";
                        $numberOfOfferListings = $competitivePricing->getNumberOfOfferListings();
                        $offerListingCountList = $numberOfOfferListings->getOfferListingCount();
                        foreach ($offerListingCountList as $offerListingCount) {
                            echo "                            OfferListingCount\n";
                            if ($offerListingCount->isSetCondition()) {
                                echo "                        condition";
                                echo "\n";
                                echo "                                " . $offerListingCount->getCondition() . "\n";
                            }
                            if ($offerListingCount->isSetValue()) {
                                echo "                        Value";
                                echo "\n";
                                echo "                                " . $offerListingCount->getValue() . "\n";
                            }
                        }
                    }
                    if ($competitivePricing->isSetTradeInValue()) {
                        echo "                        TradeInValue\n";
                        $tradeInValue = $competitivePricing->getTradeInValue();
                        if ($tradeInValue->isSetCurrencyCode()) {
                            echo "                            CurrencyCode\n";
                            echo "                                " . $tradeInValue->getCurrencyCode() . "\n";
                        }
                        if ($tradeInValue->isSetAmount()) {
                            echo "                            Amount\n";
                            echo "                                " . $tradeInValue->getAmount() . "\n";
                        }
                    }
                }
                if ($product->isSetSalesRankings()) {
                    echo "                    SalesRankings\n";
                    $salesRankings = $product->getSalesRankings();
                    $salesRankList = $salesRankings->getSalesRank();
                    foreach ($salesRankList as $salesRank) {
                        echo "                        SalesRank\n";
                        if ($salesRank->isSetProductCategoryId()) {
                            echo "                            ProductCategoryId\n";
                            echo "                                " . $salesRank->getProductCategoryId() . "\n";
                        }
                        if ($salesRank->isSetRank()) {
                            echo "                            Rank\n";
                            echo "                                " . $salesRank->getRank() . "\n";
                        }
                    }
                }
                if ($product->isSetLowestOfferListings()) {
                    echo "                    LowestOfferListings\n";
                    $lowestOfferListings = $product->getLowestOfferListings();
                    $lowestOfferListingList = $lowestOfferListings->getLowestOfferListing();
                    foreach ($lowestOfferListingList as $lowestOfferListing) {
                        echo "                        LowestOfferListing\n";
                        if ($lowestOfferListing->isSetQualifiers()) {
                            echo "                            Qualifiers\n";
                            $qualifiers = $lowestOfferListing->getQualifiers();
                            if ($qualifiers->isSetItemCondition()) {
                                echo "                                ItemCondition\n";
                                echo "                                    " . $qualifiers->getItemCondition() . "\n";
                            }
                            if ($qualifiers->isSetItemSubcondition()) {
                                echo "                                ItemSubcondition\n";
                                echo "                                    " . $qualifiers->getItemSubcondition() . "\n";
                            }
                            if ($qualifiers->isSetFulfillmentChannel()) {
                                echo "                                FulfillmentChannel\n";
                                echo "                                    " . $qualifiers->getFulfillmentChannel() . "\n";
                            }
                            if ($qualifiers->isSetShipsDomestically()) {
                                echo "                                ShipsDomestically\n";
                                echo "                                    " . $qualifiers->getShipsDomestically() . "\n";
                            }
                            if ($qualifiers->isSetShippingTime()) {
                                echo "                                ShippingTime\n";
                                $shippingTime = $qualifiers->getShippingTime();
                                if ($shippingTime->isSetMax()) {
                                    echo "                                    Max\n";
                                    echo "                                        " . $shippingTime->getMax() . "\n";
                                }
                            }
                            if ($qualifiers->isSetSellerPositiveFeedbackRating()) {
                                echo "                                SellerPositiveFeedbackRating\n";
                                echo "                                    " . $qualifiers->getSellerPositiveFeedbackRating() . "\n";
                            }
                        }
                        if ($lowestOfferListing->isSetNumberOfOfferListingsConsidered()) {
                            echo "                            NumberOfOfferListingsConsidered\n";
                            echo "                                " . $lowestOfferListing->getNumberOfOfferListingsConsidered() . "\n";
                        }
                        if ($lowestOfferListing->isSetSellerFeedbackCount()) {
                            echo "                            SellerFeedbackCount\n";
                            echo "                                " . $lowestOfferListing->getSellerFeedbackCount() . "\n";
                        }
                        if ($lowestOfferListing->isSetPrice()) {
                            echo "                            Price\n";
                            $price1 = $lowestOfferListing->getPrice();
                            if ($price1->isSetLandedPrice()) {
                                echo "                                LandedPrice\n";
                                $landedPrice1 = $price1->getLandedPrice();
                                if ($landedPrice1->isSetCurrencyCode()) {
                                    echo "                                    CurrencyCode\n";
                                    echo "                                        " . $landedPrice1->getCurrencyCode() . "\n";
                                }
                                if ($landedPrice1->isSetAmount()) {
                                    echo "                                    Amount\n";
                                    echo "                                        " . $landedPrice1->getAmount() . "\n";
                                }
                            }
                            if ($price1->isSetListingPrice()) {
                                echo "                                ListingPrice\n";
                                $listingPrice1 = $price1->getListingPrice();
                                if ($listingPrice1->isSetCurrencyCode()) {
                                    echo "                                    CurrencyCode\n";
                                    echo "                                        " . $listingPrice1->getCurrencyCode() . "\n";
                                }
                                if ($listingPrice1->isSetAmount()) {
                                    echo "                                    Amount\n";
                                    echo "                                        " . $listingPrice1->getAmount() . "\n";
                                }
                            }
                            if ($price1->isSetShipping()) {
                                echo "                                Shipping\n";
                                $shipping1 = $price1->getShipping();
                                if ($shipping1->isSetCurrencyCode()) {
                                    echo "                                    CurrencyCode\n";
                                    echo "                                        " . $shipping1->getCurrencyCode() . "\n";
                                }
                                if ($shipping1->isSetAmount()) {
                                    echo "                                    Amount\n";
                                    echo "                                        " . $shipping1->getAmount() . "\n";
                                }
                            }
                        }
                        if ($lowestOfferListing->isSetMultipleOffersAtLowestPrice()) {
                            echo "                            MultipleOffersAtLowestPrice\n";
                            echo "                                " . $lowestOfferListing->getMultipleOffersAtLowestPrice() . "\n";
                        }
                    }
                }
                if ($product->isSetOffers()) {
                    echo "                    Offers\n";
                    $offers = $product->getOffers();
                    $offerList = $offers->getOffer();
                    foreach ($offerList as $offer) {
                        echo "                        Offer\n";
                        if ($offer->isSetBuyingPrice()) {
                            echo "                            BuyingPrice\n";
                            $buyingPrice = $offer->getBuyingPrice();
                            if ($buyingPrice->isSetLandedPrice()) {
                                echo "                                LandedPrice\n";
                                $landedPrice2 = $buyingPrice->getLandedPrice();
                                if ($landedPrice2->isSetCurrencyCode()) {
                                    echo "                                    CurrencyCode\n";
                                    echo "                                        " . $landedPrice2->getCurrencyCode() . "\n";
                                }
                                if ($landedPrice2->isSetAmount()) {
                                    echo "                                    Amount\n";
                                    echo "                                        " . $landedPrice2->getAmount() . "\n";
                                }
                            }
                            if ($buyingPrice->isSetListingPrice()) {
                                echo "                                ListingPrice\n";
                                $listingPrice2 = $buyingPrice->getListingPrice();
                                if ($listingPrice2->isSetCurrencyCode()) {
                                    echo "                                    CurrencyCode\n";
                                    echo "                                        " . $listingPrice2->getCurrencyCode() . "\n";
                                }
                                if ($listingPrice2->isSetAmount()) {
                                    echo "                                    Amount\n";
                                    echo "                                        " . $listingPrice2->getAmount() . "\n";
                                }
                            }
                            if ($buyingPrice->isSetShipping()) {
                                echo "                                Shipping\n";
                                $shipping2 = $buyingPrice->getShipping();
                                if ($shipping2->isSetCurrencyCode()) {
                                    echo "                                    CurrencyCode\n";
                                    echo "                                        " . $shipping2->getCurrencyCode() . "\n";
                                }
                                if ($shipping2->isSetAmount()) {
                                    echo "                                    Amount\n";
                                    echo "                                        " . $shipping2->getAmount() . "\n";
                                }
                            }
                        }
                        if ($offer->isSetRegularPrice()) {
                            echo "                            RegularPrice\n";
                            $regularPrice = $offer->getRegularPrice();
                            if ($regularPrice->isSetCurrencyCode()) {
                                echo "                                CurrencyCode\n";
                                echo "                                    " . $regularPrice->getCurrencyCode() . "\n";
                            }
                            if ($regularPrice->isSetAmount()) {
                                echo "                                Amount\n";
                                echo "                                    " . $regularPrice->getAmount() . "\n";
                            }
                        }
                        if ($offer->isSetFulfillmentChannel()) {
                            echo "                            FulfillmentChannel\n";
                            echo "                                " . $offer->getFulfillmentChannel() . "\n";
                        }
                        if ($offer->isSetItemCondition()) {
                            echo "                            ItemCondition\n";
                            echo "                                " . $offer->getItemCondition() . "\n";
                        }
                        if ($offer->isSetItemSubCondition()) {
                            echo "                            ItemSubCondition\n";
                            echo "                                " . $offer->getItemSubCondition() . "\n";
                        }
                        if ($offer->isSetSellerId()) {
                            echo "                            SellerId\n";
                            echo "                                " . $offer->getSellerId() . "\n";
                        }
                        if ($offer->isSetSellerSKU()) {
                            echo "                            SellerSKU\n";
                            echo "                                " . $offer->getSellerSKU() . "\n";
                        }
                    }
                }
            }
            if ($getMatchingProductResult->isSetError()) {
                echo "                Error\n";
                $error = $getMatchingProductResult->getError();
                if ($error->isSetType()) {
                    echo "                    Type\n";
                    echo "                        " . $error->getType() . "\n";
                }
                if ($error->isSetCode()) {
                    echo "                    Code\n";
                    echo "                        " . $error->getCode() . "\n";
                }
                if ($error->isSetMessage()) {
                    echo "                    Message\n";
                    echo "                        " . $error->getMessage() . "\n";
                }
            }
        }
        if ($response->isSetResponseMetadata()) {
            echo "            ResponseMetadata\n";
            $responseMetadata = $response->getResponseMetadata();
            if ($responseMetadata->isSetRequestId()) {
                echo "                RequestId\n";
                echo "                    " . $responseMetadata->getRequestId() . "\n";
            }
        } 
        echo "            ResponseHeaderMetadata: " . $response->getResponseHeaderMetadata() . "\n";
    } catch (MarketplaceWebServiceProducts_Exception $ex) {
        echo "Caught Exception: " . $ex->getMessage() . "\n";
        echo "Response Status Code: " . $ex->getStatusCode() . "\n";
        echo "Error Code: " . $ex->getErrorCode() . "\n";
        echo "Error Type: " . $ex->getErrorType() . "\n";
        echo "Request ID: " . $ex->getRequestId() . "\n";
        echo "XML: " . $ex->getXML() . "\n";
        echo "ResponseHeaderMetadata: " . $ex->getResponseHeaderMetadata() . "\n";
    }
}


/**
  * Get Get Competitive Pricing For ASIN Action Sample
  * Gets competitive pricing and related information for a product identified by
  * the MarketplaceId and ASIN.
  *
  * @param MarketplaceWebServiceProducts_Interface $service instance of MarketplaceWebServiceProducts_Interface
  * @param mixed $request MarketplaceWebServiceProducts_Model_GetCompetitivePricingForASIN or array of parameters
*/
function invokeGetCompetitivePricingForASIN(MarketplaceWebServiceProducts_Interface $service, $request, $region){
  try {
    $response = $service->GetCompetitivePricingForASIN($request);

    // echo ("Service Response\n");
    // echo ("=============================================================================\n");

    $dom = new DOMDocument();
    $dom->loadXML($response->toXML());
    $dom->preserveWhiteSpace = true;
    $dom->formatOutput = true;
    // $txt = print_r($dom->textContent,TRUE);
    // echo $txt;
    // if (preg_match('/(B0\w{8}).*?USD.*?([\d\.]+).*?USD/',$txt,$m)) {$asin=$m[1]; $price=$m[2]; }
		
    $xmlstr = $dom->saveXML();
    $xml=new SimpleXMLElement($xmlstr);
    // echo $dom->__toString() ."<hr>";
/*
 		$json = json_encode($xml);
		$array = json_decode($json,TRUE);
		echo "<pre>"; print_r($array); echo "<pre>"; 

		foreach ($xml->GetCompetitivePricingForASINResult->Product->CompetitivePricing->CompetitivePrices->CompetitivePrice as $offer) {
			if ( stristr($offer['condition'],'New')<>FALSE) echo '<H2>'. $offer->Price->LandedPrice->Amount ."</H2>";
		}
*/			
    // echo "<pre>"; print_r($xml->GetCompetitivePricingForASINResult->Product); echo "<pre><HR>"; //testing, print out all xml structure
    $asin = $xml->GetCompetitivePricingForASINResult->Product->Identifiers->MarketplaceASIN->ASIN;
    $LandedPrice = $xml->GetCompetitivePricingForASINResult->Product->CompetitivePricing->CompetitivePrices->CompetitivePrice->Price->LandedPrice->Amount;
    $PriceLowest = $LandedPrice;
    foreach ($xml->GetCompetitivePricingForASINResult->Product->CompetitivePricing->CompetitivePrices as $ComPrice) {
    	$condition = $ComPrice->CompetitivePrice->Price['condition'];
    	$ListingPrice = $ComPrice->CompetitivePrice->Price->ListingPrice->Amount;
    	// echo "<pre>"; print_r($ListingPrice); echo "<pre><HR>"; 
    	if (stristr($condition,'New')<>FALSE) {
    	  if ($PriceLowest > $ListingPrice) $PriceLowest = $ListingPrice;
    	}
    }
    
    $condition = $xml->GetCompetitivePricingForASINResult->Product->CompetitivePricing->CompetitivePrices->CompetitivePrice['condition'];
		$ListingPrice = $xml->GetCompetitivePricingForASINResult->Product->CompetitivePricing->CompetitivePrices->CompetitivePrice->Price->ListingPrice->Amount;
		$currency = $xml->GetCompetitivePricingForASINResult->Product->CompetitivePricing->CompetitivePrices->CompetitivePrice->Price->LandedPrice->CurrencyCode;
		$category1 = $xml->GetCompetitivePricingForASINResult->Product->SalesRankings->SalesRank[0]->ProductCategoryId;
		$rank1 = $xml->GetCompetitivePricingForASINResult->Product->SalesRankings->SalesRank[0]->Rank;
		$category2 = $xml->GetCompetitivePricingForASINResult->Product->SalesRankings->SalesRank[1]->ProductCategoryId;
		$rank2 = $xml->GetCompetitivePricingForASINResult->Product->SalesRankings->SalesRank[1]->Rank;
			
		echo "<hr> $asin <br> $condition <br>ListingPrice= $ListingPrice <br>LandedPrice= $LandedPrice <br>LowestPrice= $PriceLowest <br>  $currency <br> $category1 <br> $rank1 <br> $category2 <br> $rank2";
      
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

function invokeGetMatchingProduct(MarketplaceWebServiceProducts_Interface $service, $request, $region){
  try {
    // header("Content-type: text/xml");
    $response = $service->GetMatchingProduct($request);
    $dom = new DOMDocument();
    $dom->loadXML($response->toXML());
    // echo $dom->saveXML(); die; //test printing out object content
		$xmlstr = $dom->saveXML();
    $xml=new SimpleXMLElement($xmlstr);
		echo "<pre>"; print_r($xml); echo "<pre>"; exit;
    $product_data = array();
    $csv_db_data = array();
    $category_data = array();
    $products = $dom->getElementsByTagName("Product");
    foreach ($products as $product) {
      // Fetching Seller SKU for product
      $sku_info = $product->getElementsByTagName("ASIN");
      $sku = $sku_info[0]->nodeValue;
      $product_data['asin'] = $sku;
      
      //Fetching Sales Ranking
      $category =  $product->getElementsByTagName("ProductCategoryId");
      $rank = $product->getElementsByTagName("Rank");
      $rank_array = array();
      $category_array = array();
      for($i = 0; $i < $category->length; $i++){
      	$rank_array['rank'.$i] = $rank[$i]->nodeValue;
      	$category_array['category'.$i] = $category[$i]->nodeValue;
      }
      
      $ProductAttributeSets = $product->getElementsByTagName("AttributeSets");
      // echo "<pre>"; print_r($competitivePrices); echo "</pre>";  exit;
      foreach ($ProductAttributeSets as $key => $value) {
          // Fetching List Price of product
        $ListPrice = $value->getElementsByTagName("ListPrice"); //the $ListPrice is not the price in the buybox -John 2019-09-06
        // $ListPrice = $landedPrice; //the $ListPrice is not the price in the buybox -John 2019-09-06
    	// echo $key .'='. $value .'<br>'; continue;
    	// echo "<pre>"; print_r($ListPrice); echo "</pre>"; exit;
    	// echo "<pre>"; print_r($ListingPrice); echo "</pre>"; 
    	
        foreach ($ListPrice as $k => $val) {
            // Fetching list price of product
          echo "<pre>"; print_r($val); echo "<pre>"; 
      	  $amount = $val->getElementsByTagName("Amount");
          $product_data['price'] = $amount[0]->nodeValue;
            // Currency Code of list price of product
          $currencyCode = $val->getElementsByTagName("CurrencyCode");
          $product_data['currency'] = ($currencyCode[0]->nodeValue == "USD") ? 1 : 0;

					$csv_array_datas = $GLOBALS['csv_array'];
					foreach($csv_array_datas as $csv_array_data){
					  $sku_comp_data = strpos($csv_array_data[4],$product_data['asin']);
					  $url_comp_data = strpos($csv_array_data[0],$product_data['asin']);
					  if($sku_comp_data > -1 || $url_comp_data > -1 ){
					    $product_data['tier'] = $csv_array_data[2];
					  	$product_data['owner'] = $csv_array_data[3];
					  	break;
					  }
					}
          $select_sql = "SELECT * FROM csv WHERE product LIKE '%".$product_data['asin']."%' ORDER BY id LIMIT 1";
          $mysqli = mws_mysqlConnect();
          $result = $mysqli->query($select_sql);
          $rows = $result->fetch_assoc();
          $product_data['csv_id'] = $rows['id'];
          $product_data['tier'] = $rows['tier'];
          $owner = $rows['owner'];
          $select_owner_sql = $mysqli->query("SELECT ID FROM mws_owner WHERE Name = '".$owner."'");
    	  foreach($select_owner_sql as $select_owner){
    		  $product_data['owner'] = $select_owner['ID'];
    	  }
          if($rows['group_asin'] == $product_data['asin']){
            $product_data['type'] = 1;
          }else{
            $product_data['type'] = 2;
          }
            //Fetching list model of product
          $model = $value->getElementsByTagName("Model");	 
          $sku = $model[0]->nodeValue;
          
          $select_sku_sql = $mysqli->query("SELECT ID FROM mws_sku WHERE Sku = '".$sku."'");
          if($select_sku_sql->num_rows == 0){
		      $insert_sku_sql = "INSERT INTO mws_sku (Sku) VALUES ('".$sku."')";
              $mysqli->query($insert_sku_sql);
              $sku_id = $mysqli->insert_id;
              $product_data['sku'] = $sku_id;
          }else{
        	  foreach($select_sku_sql as $select_sku){
        		  $product_data['sku'] = $select_sku['ID'];
        	  }
          }
          
          $category1 = $category_array['category0'];
          $select_category1_sql = $mysqli->query("SELECT ID FROM mws_category1 WHERE Category = '".$category1."'");
          if($select_category1_sql->num_rows == 0){
		      $insert_category1_sql = "INSERT INTO mws_category1 (Category) VALUES ('".$category1."')";
              $mysqli->query($insert_category1_sql);
              $category1_id = $mysqli->insert_id;
              $product_data['category1'] = $category1_id;
          }else{
        	  foreach($select_category1_sql as $select_cat1){
        		  $product_data['category1'] = $select_cat1['ID'];
        	  }
          }
          
          $product_data['rank1'] =  $rank_array['rank0'];
          $category2 = $category_array['category1'];
          $select_category2_sql = $mysqli->query("SELECT * FROM mws_category2 WHERE Category = '".$category2."'");
          if($select_category2_sql->num_rows == 0){
		      $insert_category2_sql = "INSERT INTO mws_category2 (Category) VALUES ('".$category2."')";
	          $mysqli->query($insert_category2_sql);
	          $category2_id = $mysqli->insert_id;
	          $product_data['category2'] = $category2_id;
          }else{
        	  foreach($select_category2_sql as $select_cat2){
        		  $product_data['category2'] = $select_cat2['ID'];
        	  }
          }
          $product_data['rank2'] =  $rank_array['rank1'];
          $values = implode("','", $product_data);
          $insert_sql = "INSERT INTO mws_".$region." (asin,price,currency,tier,owner,csv_id,type,sku,category1,rank1,category2,rank2) VALUES ('".$values."')";
          $mysqli->query($insert_sql);
       	}
      } 

    }
    $_SESSION['success'] = 'Data Fetched Successfully.';
  }catch (MarketplaceWebServiceProducts_Exception $ex) {
    echo("Caught Exception: " . $ex->getMessage() . "\n");
    echo("Response Status Code: " . $ex->getStatusCode() . "\n");
    echo("Error Code: " . $ex->getErrorCode() . "\n");
    echo("Error Type: " . $ex->getErrorType() . "\n");
    echo("Request ID: " . $ex->getRequestId() . "\n");
    echo("XML: " . $ex->getXML() . "\n");
    echo("ResponseHeaderMetadata: " . $ex->getResponseHeaderMetadata() . "\n");
  }
}


