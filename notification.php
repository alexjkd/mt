<?php
	//database connection setting
	require_once '../db.php';

	$mysqli = new mysqli($dbHost, $dbUser, $dbPwd, $dbName);
	// Check connection
	if (!$mysqli) {
	   die("Connection failed: " . mysqli_connect_error());
	}
	if (mysqli_connect_errno()) {
	   printf("Connect failed: %s\n", mysqli_connect_error());
	exit();
	}
	$select_all_asin_query = $mysqli->query("SELECT DISTINCT(asin) FROM mws_us");
	$dist_asin = array();
	$all_asin = array();
	foreach ($select_all_asin_query as $select_all_asin){
	  $all_asin[] = $select_all_asin['asin'];
	}
	$select_group_asin_query = $mysqli->query("SELECT DISTINCT(asin) FROM mws_us WHERE type = 1");
	foreach ($select_group_asin_query as $select_dist_asin){
	  $dist_asin[] = $select_dist_asin['asin'];
	}
?>
<script src="https://code.jquery.com/jquery-3.1.1.min.js"></script>
<link href="noty-lib/noty.css" rel="stylesheet">
<link href="noty-lib/themes/metroui.css" rel="stylesheet">
<script src="noty-lib/noty.js" type="text/javascript"></script>
<script type="text/javascript">
$(document).ready(function(){
  <?php foreach($all_asin as $asin){ 
  	?>
  	var a = <?php echo "'$asin'"; ?>;
  	   $.ajax({ 
	      type: 'get',
		  url: 'alert_ajax_call.php',
		  dataType: "json",
		  data: {"asin": a},
		  success: function (data) {
		  	console.log(data);
		  	if (data !== "") {
	  		  for(var i = 0; i < data.length; i++){
		        new Noty({
			      theme: 'metroui',
			      text: data[i],
				  layout: 'bottomRight',
				  type: 'error',
				  closeWith: ['click', 'button'],
			    }).show();
		      }
			}
		  },
		  error: function (data) {            
		      console.log('An error occurred.');
		  },
	  });
  <?php 
  } 

   foreach($dist_asin as $dist_asin){ 
  	?>
  	var b = <?php echo "'$dist_asin'"; ?>;
  	   $.ajax({ 
	      type: 'get',
		  url: 'alert_crawl_ajax_call.php',
		  // dataType: "json",
		  data: {"asin": b},
		  success: function (result) {
		  	console.log(result);
		  	if (result !== "") {
		        new Noty({
			      theme: 'metroui',
			      text: result,
				  layout: 'bottomRight',
				  type: 'error',
				  closeWith: ['click', 'button'],
			    }).show();
			}
		  },
		  error: function (data) {            
		    console.log('An error occurred.');
		  },
	  });
  <?php 
  } 
  ?>  
});
</script>