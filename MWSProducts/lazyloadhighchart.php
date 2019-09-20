<?php
require_once 'db.php';
$mysqli = mws_mysqlConnect();

if (mysqli_connect_errno()) {
printf("Connect failed: %s\n", mysqli_connect_error());
exit();
}
$result = $mysqli->query('SELECT DISTINCT product_sku FROM mws WHERE csv_group_product = "group"');
?>
<html>
<head>
<link href="http://www.jqueryscript.net/css/jquerysctipttop.css" rel="stylesheet" type="text/css">
<link href="http://73.106.162.5:3080/codiad/workspace/mws/MWSProducts/src/css/file-explore.css" rel="stylesheet" type="text/css">
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">  
<script src="https://code.jquery.com/jquery-3.1.1.min.js"></script>
<script src="https://code.highcharts.com/stock/highstock.js"></script>
<script src="http://code.highcharts.com/stock/modules/exporting.js"></script>
<style  type="text/css">
   body{
   	 margin: 0px;
   	 padding: 0px;
   }
   #chart_annotation_box{
     width: 17%;
     height: 100%;
     overflow: auto;
     border: 2px solid #808080;
     display: inline-block;
   }
   .google-visualization-table-td {
      border-width: 0;
      border-bottom-width: 1px;
      vertical-align: top;
      font-size: .75em;
   }
   #container{
     float: right;
     margin-right: 230px;
	 width: 1000px; height: 300px;
     border: 1px solid #808080;
   }
   .google-visualization-table-td span.title, span.rank-title, span.price-title {
	 font-weight: bold;
   }
   .google-visualization-table-td	span.date {
	 color: #666;
	 font-size: .75em;
	 white-space: nowrap;
   }
   .google-visualization-table-table thead{
     display: none;
   }
   .content {
	 padding: 0 18px;
	 display: none;
	 overflow: hidden;
	 background-color: #f1f1f1;
   }
   .highcharts-credits{
   	 display: none;
   }
</style>
</head>
<body>
	<div id="chart_wrapper_annotation_box">
	    <div id="chart_annotation_box">
	      <div class="chart_box">
	      	<div class="container">
			  <ul class="file-tree">
			  	<?php
			  	$count = 0;
			  	foreach($result as $res){
			  		$select_csv_query = $mysqli->query("SELECT model from csv where id in (select csv_id from mws where `product_sku`='".$res['product_sku']."')");
			  		foreach($select_csv_query as $csv_model){
			  	?>
			    <li id="collapse-icon" class="<?php echo ($count == 0) ?'first':''; ?>"><a href="#"><?php echo $csv_model['model']; ?></a>
			      <ul>
			      	<li class='compet'><a href="https://www.amazon.com/dp/<?php echo $res['product_sku']; ?>" target="_blank"><?php echo $res['product_sku'];?></a></li>
			      	<?php 
						$comp_result = $mysqli->query("SELECT DISTINCT product_sku from mws where csv_group_product='competitior' AND csv_id in (select id from csv where `group_asin`='".$res['product_sku']."')");
						foreach($comp_result as $comp_res){
					?>
			      	<li class='comp'><a href="https://www.amazon.com/dp/<?php echo $comp_res['product_sku']; ?>" target="_blank"><?php echo $comp_res['product_sku']; ?></a></li>
			      	<?php
						}
			      	}
			      	?>
			      </ul>
			    </li>
			    <?php } ?>
			  </ul>
			</div>
	      </div>
	    </div>
	    <div id="container" class="chart"></div>
    	</div>
	</div>
  </body>
<script type="text/javascript">
$(document).ready(function(){
  var loadData = $('li.compet').first('a').text();
  $.ajax({ 
        type: 'post',
        url: 'lazyloadhighchartajax.php',
        data: {"group_asin": loadData},
        dataType: "json",
        success: function (data) {
        	addData(data);
        },
       error: function (data) {            
            console.log('An error occurred.');
      },
  });
});
$(document).ready(function(){	
 $('ul.file-tree li a').click(function(e) 
	    { 
	       var productName =$(this).next('ul').find('li').first().text();
    $.ajax
    ({ 
        url: 'lazyloadhighchartajax.php',
        data: {"group_asin": productName},
        type: 'post',
        dataType: "json",
    		success: function (data) {
    			// console.log(data);
            	addData(data);
           },
           error: function (data) {            
                console.log('An error occurred.');
          },
   });
 });

});
</script>
<script>
function addData(json_data){
  var axisData = [];
  var priceData = []; 
  $.each(json_data, function(key, value) {
		var dataPoints = [];
		var pricePoints = [];
	    for (var i = 0; i < json_data[key].length; i++) {
		  dataPoints.push([
		    json_data[key][i].date,
			json_data[key][i].rank,
		  ]);
        pricePoints.push([
          json_data[key][i].date,
          json_data[key][i].price,      
        ]);
		}

      axisData.push({labels: {align: 'left'},height: '80%',resize: {enabled: true}}, {labels: {align: 'left'},top: '80%',height: '20%',offset: 0 }, );
      priceData.push({type: 'line',id: key+'-line',name: key+'-Rank',data: dataPoints},{type: 'column',id: 'price-ohlc',name: key+'-Price',data: pricePoints,yAxis: 1});
	  });
	  Highcharts.stockChart('container', {
	  	  chart: {
                height: 300,
            },
	      yAxis: axisData,
	      series: priceData,
	      plotOptions: {
		   series: {
		     marker: {
		       enabled: true
		       }
		    }
		  },
	  });

}
</script>
<script src="http://73.106.162.5:3080/codiad/workspace/mws/MWSProducts/src/js/file-explore.js"></script> 
<script>
jQuery(document).ready(function() {
            jQuery(".file-tree").filetree();
	        });
</script>
<script type="text/javascript">

  var _gaq = _gaq || [];
  _gaq.push(['_setAccount', 'UA-36251023-1']);
  _gaq.push(['_setDomainName', 'jqueryscript.net']);
  _gaq.push(['_trackPageview']);

  (function() {
    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
  })();

</script>
</html>