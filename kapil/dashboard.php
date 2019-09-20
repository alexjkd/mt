<?php require_once '../db.php';

$mysqli = new mysqli($dbHost, $dbUser, $dbPwd, $dbName);
// Check connection
if (!$mysqli) {
  die("Connection failed: " . mysqli_connect_error());
}
if (mysqli_connect_errno()) {
  printf("Connect failed: %s\n", mysqli_connect_error());
exit();
}
?>
<head>
  <link rel="shortcut icon" href="mt.ico" type="image/x-icon" />
  <link href="lib/sortable/sortable_table.css" media="screen" rel="stylesheet" type="text/css" />
  <script src="https://code.jquery.com/jquery-3.1.1.min.js"></script>
  <script src="http://code.highcharts.com/stock/highstock.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.22.1/moment.js"></script>
<style>
a.ispring:link, a.ispring:visited {
  background-color: blue;
  color: white;
  padding: 4px 25px;
  text-align: center;
  text-decoration: none;
  display: inline-block;
}
a.ispring:hover, a.ispring:active {
  color: blue;
  background-color: white;
}
a.compe:link, a.compe:visited {
  background-color: green;
  color: white;
  padding: 4px 25px;
  text-align: center;
  text-decoration: none;
  display: inline-block;
}
a.compe:hover, a.compe:active {
  color: green;
  background-color: white;
}
.dot {
  height: 12px;
  width: 12px;
  background-color: #bbb;
  border-radius: 50%;
  display: inline-block;
}
table {
  width: 10px;
  border: 1px solid black;
  empty-cells: hide;
}

table th > div, table td > div {
  overflow: hidden;
  height: 15px;
  font-size: 0.875em;
  padding: 0px;
  border-bottom: 1px solid #ddd;
  vertical-align: top;
  text-align: center;
}
tr.hover:hover {
  background-color:#f5f5f5;
}
.noty_theme__metroui.noty_bar .noty_body{
  overflow: overlay;
}
.graph{  
  min-width: 310px;
  height: auto;
  margin: 0 auto;
  border:1px solid grey;
  margin-bottom: 50px;
}
</style>
</head>
<?php
$region = $_GET['region'];
if(empty($_GET) || isset($_GET['list']) || isset($_GET['data'])){
$csvFile = file('https://docs.google.com/spreadsheets/d/e/2PACX-1vR2gY22xgcaR4JUr3naK5nXbFzw3pL_Ogn4msFRDGfVA8nILfEs-BOdxDRt2Jvhx9Yz31eAF8IfpjBn/pub?gid=0&single=true&output=csv');
$data = [];
$csv_data = [];
foreach ($csvFile as $line) {
  $csv_check_data = str_getcsv($line);
  $data[] = $csv_check_data;
}
$i = 1;
foreach($data as $csv_array_data){
  $csv_product_asin = substr($csv_array_data[0], strrpos($csv_array_data[0], '/') + 1);
	  $csv_product_len = strlen($csv_product_asin);
  if($csv_product_len == 10 && !preg_match('/[^A-Za-z0-9]/', $csv_product_asin)){
    $csv_array_data[5] = $csv_product_asin;
    $csv_data[] = $csv_product_asin;
    if ($i == 30) {
      break;
    }
    $i++;
  }   
}
}
if (isset($_GET['owner'])) {
	$owner = $_GET['owner'];
	$owner_asin = array();
	$owner_query = $mysqli->query("SELECT DISTINCT(asin),csv_id FROM mws_$region WHERE owner = (SELECT ID FROM mws_owner WHERE Name ='".$owner."') AND type = 1");
	foreach ($owner_query as $owner_data) {
	  $owner_asin[] = $owner_data['asin'];
	}
}

?>  
<body>
<!-- <div id="container"></div> -->
<script type="text/javascript">
	var top30 = '<?php echo $_GET['list']; ?>';
	var region = '<?php echo $_GET['region']; ?>';
	if (top30) {
	  $(document).ready(function(){
	    var x = 1;
	    var js_array = [<?php echo '"'.implode('","', $csv_data).'"' ?>];
	    // console.log(js_array);
	    js_array.forEach(function(entry) {
	      $.ajax({ 
	        type: 'get',
	        url: 'select_graph_data.php',
	        dataType: "json",
	        data: {"asin": entry,"region": region},
	        success: function (data) {
	   	      x++;
			// var chartContainer = document.createElement("div");
		      var $div = $("<div>", {id: "container"+x, "class": "graph", entry});
			// chartContainer.attr("class", "div"+x);
			  $("body").append($div);
			  var text = 'Product Rank & Price Chart';
			  var select = 0;
			  // var range = 604800000;
			  chartDraw(data, "container"+x,entry,text,select);
		    },
		    error: function (data) {            
		      console.log('An error occurred.');
	   	    },
	      });
	    }); 
	  });
	}
	var owner = '<?php echo $_GET['owner']; ?>';
	if (owner) {
	  $(document).ready(function(){
	    var x = 1;
	    var js_array = [<?php echo '"'.implode('","', $owner_asin).'"' ?>];
	    // console.log(js_array);
	    js_array.forEach(function(entry) {
	      $.ajax({ 
	        type: 'get',
	        url: 'select_graph_data.php',
	        dataType: "json",
	        data: {"asin": entry,"region": region},
	        success: function (data) {
	   	      x++;
			  var $div = $("<div>", {id: "container"+x, "class": "graph", entry});
			// chartContainer.attr("class", "div"+x);
			  $("body").append($div);
			  var text = 'Product Rank & Price Chart By '+owner;
			  var select = 0;
			  var range = 604800000;
			  chartDraw(data, "container"+x,entry,text,select,range);
		    },
		    error: function (data) {            
		      console.log('An error occurred.');
	   	    },
	      });
	    }); 
	  });
	}
	var asin = '<?php echo $_GET['asin']; ?>';
	if (asin) {
	 $(document).ready(function(){
        $.ajax({ 
          type: 'get',
          url: 'select_graph_data.php',
          dataType: "json",
          data: {"asin": asin,"region": region},
          success: function (data) {
			// var chartContainer = document.createElement("div");
		    var $div = $("<div>", {id: "container1", "class": "graph", asin});
			// chartContainer.attr("class", "div"+x);
			$("body").append($div);
			var text = 'Product Rank & Price Chart Of '+ asin;
			var select = 0;
			var range = 604800000;
			chartDraw(data, "container1",asin,text,select,range);
		  },
		  error: function (data) {            
		    console.log('An error occurred.');
	   	  },
	    });
	  }); 
	}

	var weekly = '<?php echo $_GET['data']; ?>';
	if (weekly) {
	  $(document).ready(function(){
	    var x = 1;
	    var js_array = [<?php echo '"'.implode('","', $csv_data).'"' ?>];
	    // console.log(js_array);
	    js_array.forEach(function(entry) {
	      $.ajax({ 
	        type: 'get',
	        url: 'select_graph_data.php',
	        dataType: "json",
	        data: {"asin": entry,"region": region,"date_range": 604800000},
	        success: function (data) {
	   	      x++;
			// var chartContainer = document.createElement("div");
		      var $div = $("<div>", {id: "container"+x, "class": "graph", entry});
			// chartContainer.attr("class", "div"+x);
			  $("body").append($div);
			  var text = 'Product Rank & Price Chart';
			  var select = 0;
			  var range = 604800000;
			  chartDraw(data, "container"+x,entry,text,select,range);
		    },
		    error: function (data) {            
		      console.log('An error occurred.');
	   	    },
	      });
	    }); 
	  });
	}

	function chartDraw(data,div,asin,title,select,range){
	var btn_index = select;
	var jsonArray = data;
	console.log(jsonArray);
    var chart = $('#'+div).highcharts();
    //var categoryDate = data.date;
    Highcharts.chart(div, {
	  title: {
	    text: title
	  }, 
	    rangeSelector: {
	      verticalAlign: 'right',
	      buttonSpacing: 10,
	      buttonTheme: {
	        width: 50,
		  },
		  buttonPosition: {
	        x: -20,
	        y: 28
		  },
          allButtonsEnabled: true,
          enabled:true,
          inputEnabled:false,
          buttons: [{
	        type: 'day',
            count: 1,
            text: '1 Day'
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
	      selected: btn_index,
	      relativeTo: 'chart',
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
      	      rank1 += '<td>'+ this.points[i].point.rank1 +'</td>';
			  asin2 += '<td><b>'+ this.points[i].point.asin2 +'</b></td>';
			  rank2 += '<td>'+ this.points[i].point.rank2 +'</td>';
			  price += '<td>'+ this.points[i].point.price +'</td>';
			  date = new Date(parseInt(this.points[i].point.date));
			  // var format = ((date.getDate() < 10) ? '0' : '') + date.getDate() + '/' + (((date.getMonth() + 1) < 10) ? '0' : '') + (date.getMonth() + 1) + '/' + date.getFullYear();			  
 		      var format =  date.getFullYear() + '/' + (((date.getMonth() + 1) < 10) ? '0' : '') + (date.getMonth() + 1) + '/' + ((date.getDate() < 10) ? '0' : '') + date.getDate();
			  var hours = date.getHours();
			  var minutes = date.getMinutes();
			  var ampm = hours >= 12 ? 'pm' : 'am';
			  // hours = hours % 12;
			  // hours = hours ? hours : 12;
			  // minutes = minutes < 10 ? '0'+minutes : minutes;
			  // var strTime = hours + ':' + minutes + ' ' + ampm;
			  var strTime = hours + ' ' + ampm;
			  var finalDate = format + ' ' + strTime;
			}
	        sharedPoints += '<div style="text-align:center; padding-bottom:5px;color:black "><b>Date: '+ finalDate +'<br></b></div><table border="1" cellpadding="3" cellspacing="0" style="font-size:10px;"><tr><td>Sku</td> <b>'+ asin2 + '</b></tr><tr><td>Rank 1</td> <b>' + rank1 + '</b></tr><tr><td>Rank2</td> <b>'+ rank2 +'</b></tr><tr><td>Price</td> <b>'+ price +'</b></tr></table>';
	        return sharedPoints;
	      },
	       positioner: function(labelWidth, labelHeight, point) {
		      var leftHalf = point.plotX < (this.chart.plotWidth / 2);
		      var topHalf = point.plotY < (this.chart.plotHeight / 2);
		      return {
		        x: leftHalf ? this.chart.plotLeft + this.chart.plotWidth - labelWidth : this.chart.plotLeft,
		        y: topHalf ? this.chart.plotTop + this.chart.plotHeight - labelHeight : this.chart.plotTop
		      }
		    },
		  shadow: false,
		  borderWidth: 0,
		  borderColor: "#d4d4d4",
		  backgroundColor: '#f3f3f3',
		  followPointer: false,
		  shared: true,
		  crosshairs: {
		  width: 1,
		  color: 'gray',
		  dashStyle: 'shortdot'
		  }
			},
			plotOptions: {
			  series: {
			    allowPointSelect: true,
			    marker: {
				  enabled: true
				}
			  }
			},
			chart: {
			  height: 300,
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
		  type: 'datetime',
		  showEmpty: false,
		  labels: {
		    formatter: function () {
		      var date =  new Date(parseInt(this.value));
		      console.log(range);
		      if (range == 31536000000) {
 		        var format =  date.getFullYear() + '/' + (((date.getMonth() + 1) < 10) ? '0' : '') + (date.getMonth() + 1);
		        return format;
			  }else if(range == 86400000 || typeof range === "undefined"){
				  var format = ((date.getDate() < 10) ? '0' : '') + date.getDate() + '/' + (((date.getMonth() + 1) < 10) ? '0' : '') + (date.getMonth() + 1) + '/' + date.getFullYear();
				  var hours = date.getHours();
				  var minutes = date.getMinutes();
				  var ampm = hours >= 12 ? 'pm' : 'am';
				  hours = hours % 12;
				  hours = hours ? hours : 12;
				  minutes = minutes < 10 ? '0'+minutes : minutes;
				  var strTime = hours + ':' + minutes + ' ' + ampm;
		        return strTime;
		      }else{
 		        var format =  date.getFullYear() + '/' + (((date.getMonth() + 1) < 10) ? '0' : '') + (date.getMonth() + 1) + '/' + ((date.getDate() < 10) ? '0' : '') + date.getDate();
		        return format;		      	
		      }
		    }
		  },
		  events : {
		  	afterSetExtremes: function(e) {
               afterSetExtremes(e, asin,div,title)
            }
		    // afterSetExtremes : afterSetExtremes
		  },
	    },      
		  yAxis: [{
			title: {
			  text: 'Rank1'
			},
			  height: '70%'
			},{
			title: {
			  text: 'Price'
			},
			height: '30%',
			top: '70%',
			opposite: true,
		    },
		  ],
		  series: jsonArray,
		  legend: {
		  align: 'right',
		  verticalAlign: 'top',
		  floating: true,
		  backgroundColor: '#FFFFFF',
		  y: 25,
		  x: -20,
		},    
	  });
	}

	function afterSetExtremes(e,asin,container,title) {
	  var chart = $('#'+container).highcharts();
	  if(typeof (e.rangeSelectorButton) == 'undefined'){
	    return false;
	  }
	  if(e.rangeSelectorButton._range != ''){ 
	    chart.showLoading('Loading data from server...');
	    var range = e.rangeSelectorButton._range;
	    if (range == 86400000){
	  	  var select = 0;
		}else if(range == 604800000){
	      var select = 1;
	    }else if(range == 2592000000){
	      var select = 2;
	    }else if(range == 31536000000){
	      var select = 3;
	    }
		// console.log(range);
		$.ajax({ 
	      type: 'get',
	      data: {"date_range": range,"asin":asin ,"region": region},
	      url: 'select_graph_data.php',
		  dataType: "json",
		  success: function (data) {
		    chart.hideLoading();
			chartDraw(data,container,asin,title,select,range);
		  },
		  error: function (data) {            
		    console.log('An error occurred.');
		  },
	    });
	  }
	} 
</script>
</body>