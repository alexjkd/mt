<?php
require_once 'db.php';
$mysqli = mws_mysqlConnect();
if (mysqli_connect_errno()) {
printf("Connect failed: %s\n", mysqli_connect_error());
exit();
}
?>
<!DOCTYPE HTML>
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<title>Chart</title>
		<script src="https://code.jquery.com/jquery-3.1.1.min.js"></script>
		<script src="http://code.highcharts.com/stock/highstock.js"></script>
		<!--<script src="highcharts.js"></script>-->
	</head>
	<body>
<?php
$query = $mysqli->query("SELECT id, product FROM csv WHERE group_asin = 'B009AEJWZG'");
$res_array = array();
$price_array = array();
$date_array = array();
$color = array('red', 'black', 'lime', 'olive', 'navy', 'saddle brown', 'purple');
$i = 0;
foreach($query as $que){
?>  	<div id="container" style="min-width: 310px; height: 700px; margin: 0 auto;  border:1px solid grey;"></div>
<?php
  $concat_que = "'".str_replace(',', "','", $que['product'])."'";
  $result = $mysqli->query("SELECT mws_us.updated AS update_date, mws_us.rid,mws_us.asin, mws_us.sku, mws_us.price, mws_us.rank1, mws_us.rank2 FROM mws_us WHERE mws_us.asin IN (".$concat_que.") AND mws_us.csv_id = ".$que['id']." ORDER BY mws_us.updated DESC LIMIT 100");
  foreach($result as $res){ 
  
	$date = date("Y-m-d h A", strtotime($res['update_date']));
	$date = date('d-m-y h:i A', strtotime($date));
  	$date_array[] = $date;
  	if(isset($res_array[$res['asin']])){	
  	  array_unshift($res_array[$res['asin']]['data'], array('date'=> $date,'y'=>intval($res['rank1']),'rank2'=>intval($res['rank2']),'asin2'=>$res['sku'],'price' => floatval($res['price'])));
  	  array_unshift($price_array[$res['asin']]['data'], array('date'=> $date,'y'=>floatval($res['price'])));
  	}else{
  	  $res_array[$res['asin']] = array('name' => $res['sku'], 'data' => array(array('date'=> $date,'y'=>intval($res['rank1']),'rank2'=>intval($res['rank2']),'asin2'=>$res['sku'],'price' => floatval($res['price']))), 'type' => 'line', 'color' => $color[$i]);
  	  $price_array[$res['asin']] = array('name' => $res['sku'], 'data' => array(array('date'=> $date,'y'=>floatval($res['price']))), 'yAxis' => 1, 'type' => 'column', 'showInLegend' => FALSE, 'color' => $color[$i]);
  	  $i++;
  	}
  }
  $unique_date = array_unique($date_array);
  $res_array = array_values($res_array);
  $price_array = array_values($price_array);
  $category_date = array_reverse($unique_date);
  $category_date = json_encode($category_date);
  $res_array = array_merge($res_array, $price_array);
  print_r($res_array); 
  $res_array = json_encode($res_array);
 ?>
    <script type="text/javascript">
	    var json_array = JSON.parse('<?php echo $res_array; ?>');
	    console.log(json_array);
	    var category_date = <?php echo $category_date; ?>;
                
            Highcharts.chart('container', {
                title: {
                    text: 'Product Rank & Price Chart'
                }, 
                rangeSelector: {
                	verticalAlign: 'top',
                	buttonSpacing: 10,
                	buttonTheme: {
                		width: 50,
                	},
				    buttonPosition: {
				    	x: 580
				    },
				    allButtonsEnabled: true,
                    enabled:true,
                    inputEnabled:false,
			        buttons: [{
			           type: 'day',
	                   count: 1,
	                   text: '24 Hour'
				    }, {
				       type: 'week',
                       count: 1,
                       text: '1 Week'
				    }, {
				       type: 'month',
	                   count: 1,
	                   text: '1 Month'
				    }, {
				       type: 'year',
	                   count: 1,
	                   text: '1 Year'
				    }],
				    selected: 0,
			    },

                tooltip: {
			        backgroundColor: null,
			        borderWidth: 0,
			        shadow: true,
			        useHTML: true,
			        style: {
			            padding: 0
			        },
                    formatter: function () {
			            var points = this.points;
			            var sharedPoints = '';
			            var rank1 = '';
			            var rank2 = '';
			            var asin2 = '';
			            var price = '';
			            var date = '';
			            for (var i = 0; i < points.length/2; i++) {
			            	rank1 += '<td>'+ this.points[i].point.y +'</td>';
			            	asin2 += '<td><b>'+ this.points[i].point.asin2 +'</b></td>';
			            	rank2 += '<td>'+ this.points[i].point.rank2 +'</td>';
			            	price += '<td>'+ this.points[i].point.price +'</td>';
			            	date = this.points[i].point.date;
			            }
			            sharedPoints += '<div style="text-align:center; padding-bottom:5px;color:black "><b>Date: '+ date +'<br></b></div><table border="1" cellpadding="3" cellspacing="0" style="font-size:10px;"><tr><td>Sku</td> <b>'+ asin2 + '</b></tr><tr><td>Rank 1</td> <b>' + rank1 + '</b></tr><tr><td>Rank2</td> <b>'+ rank2 +'</b></tr><tr><td>Price</td> <b>'+ price +'</b></tr></table>';
			            return sharedPoints;
                    },
                    positioner: function () {
                        return { x: 80, y: 80 };
                    },
                    shadow: false,
                    borderWidth: 0,
                    borderColor: "#d4d4d4",
                    backgroundColor: '#f3f3f3',
                    followPointer: true,
                    shared: true,
        			crosshairs: {
			           width: 1,
			           color: 'gray',
			           dashStyle: 'shortdot'
			        }
                },
                chart: {
			        zoomType: 'x',
			        plotBackgroundColor: "rgba(10,0,0,0)",
			        events: {
			            load: function(){
			                if(this.plotBackground) {
			                    this.plotBackground.toFront().css({ 
			                        cursor: "crosshair"  
			                    });
			                }
			            }
			        }
			    },

                xAxis: {
                    categories:category_date,
                },      
				yAxis: [{
					    title: {
					        text: 'Rank1'
					    },
					    height: '70%',
					},{
						title: {
					        text: 'Price'
					    },
					    height: '30%',
					    top: '70%',
					    opposite: true,
					}
				],
                series:	json_array,
                legend: {
		            align: 'left',
		            verticalAlign: 'top',
		            floating: true,
		            backgroundColor: '#FFFFFF',
		            y: 25
		        },
                
            });
        </script>
        <?php
  //print_r(json_decode($category_date));
  //echo print_r(json_decode($res_array)); die;
}

?>  
	</body>

</html>
<?php

?>
