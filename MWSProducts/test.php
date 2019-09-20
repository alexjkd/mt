<?php
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
     //if ($i>3) break; //testing
  }

  foreach($data as $csv_array_data){
    $csv_product_asin = substr($csv_array_data[0], strrpos($csv_array_data[0], '/') + 1);
    if (count($csv_array_data)>3 and preg_match_all('/B0\w{8}/',$csv_array_data[4],$m)) {
    	// echo "<pre>"; print_r($m[0]); echo "</pre>";
    	foreach ($m[0] as $v) {
    		// echo "<pre>"; print_r($v); echo "</pre>";
    		$aCompAsins[] = $v;
    	}
    }
  }
  echo "<pre>"; print_r($aCompAsins); echo "</pre>";
?>