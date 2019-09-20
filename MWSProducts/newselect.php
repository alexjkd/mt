<!DOCTYPE HTML>
<html>
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Chart</title>
	<script src="https://code.jquery.com/jquery-3.1.1.min.js"></script>
	<script src="http://code.highcharts.com/stock/highstock.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.22.1/moment.js"></script>
	<!--<script src="highcharts.js"></script>-->
  </head>
  <body>
  	<div id="container" style="min-width: 310px; height: auto; margin: 0 auto;  border:1px solid grey;"></div>
    <script type="text/javascript">
    function afterSetExtremes(e) {	
		var chart = $('#container').highcharts();
		if(e.rangeSelectorButton._range != ''){ 
		chart.showLoading('Loading data from server...');
		var range = e.rangeSelectorButton._range;
		if(range == 604800000){
			var select = 1;
		}else if(range == 2592000000){
			var select = 2;
		}else if(range == 31536000000){
			var select = 3;
		}
		 // var chart = $('#container').highcharts();
	     $.ajax({ 
            type: 'get',
	        data: {"date_range": range},
	        url: 'demoselectdata.php',
		    dataType: "json",
		    success: function (data) {
		    	// console.log(data);
		    	chart.hideLoading();
		    	chartDraw(data,select);
		    },
		       error: function (data) {            
		            console.log('An error occurred.');
		    },
        });
	  }
	}
    $(document).ready(function(){
	  $.ajax({ 
	        type: 'post',
		    url: 'demoselectdata.php',
		    dataType: "json",
		    success: function (data) {
		    	var selected = 0;
		    	// console.log(data);
		    	chartDraw(data,selected);
		       },
		       error: function (data) {            
		            console.log('An error occurred.');
		      },
		  });
		});
		function chartDraw(data,select){
			 var btn_index = select;
              var jsonArray = data;
			  //var chart = $('#container').highcharts();
		      //console.log(select);
		      //var categoryDate = data.date;
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
					    selected: btn_index,
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
				              	// var timeStamp = Highcharts.dateFormat('%d/%m/%Y %H:%M', date);
				            	var format = ((date.getDate() < 10) ? '0' : '') + date.getDate() + '/' + (((date.getMonth() + 1) < 10) ? '0' : '') + (date.getMonth() + 1) + '/' + date.getFullYear();
				            	// console.log(date.toLocaleString());
					            var hours = date.getHours();
								var minutes = date.getMinutes();
								var ampm = hours >= 12 ? 'pm' : 'am';
								hours = hours % 12;
								hours = hours ? hours : 12; // the hour '0' should be '12'
								minutes = minutes < 10 ? '0'+minutes : minutes;
								var strTime = hours + ':' + minutes + ' ' + ampm;
								var finalDate = format + ' ' + strTime;
				            	// console.log(strTime);
				            }
				            sharedPoints += '<div style="text-align:center; padding-bottom:5px;color:black "><b>Date: '+ finalDate +'<br></b></div><table border="1" cellpadding="3" cellspacing="0" style="font-size:10px;"><tr><td>Sku</td> <b>'+ asin2 + '</b></tr><tr><td>Rank 1</td> <b>' + rank1 + '</b></tr><tr><td>Rank2</td> <b>'+ rank2 +'</b></tr><tr><td>Price</td> <b>'+ price +'</b></tr></table>';
				            return sharedPoints;
		                    },
		                    positioner: function () {
		                        return { x: 80, y: 80 };
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
		                chart: {
   	                	    height: 300,
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
		                      type: 'datetime',
		                      showEmpty: false,
				                labels: {
				                    formatter: function () {
				                        var date =  new Date(parseInt(this.value));
				                    	var format = ((date.getDate() < 10) ? '0' : '') + date.getDate() + '/' + (((date.getMonth() + 1) < 10) ? '0' : '') + (date.getMonth() + 1) + '/' + date.getFullYear();
						            	// console.log(date.toLocaleString());
							   //         var hours = date.getHours();
										// var minutes = date.getMinutes();
										// var ampm = hours >= 12 ? 'pm' : 'am';
										// hours = hours % 12;
										// hours = hours ? hours : 12; // the hour '0' should be '12'
										// minutes = minutes < 10 ? '0'+minutes : minutes;
										// var strTime = hours + ':' + minutes + ' ' + ampm;
										// var finalDate = format + ' ' + strTime;
				                    return format;
				                    }
				                },
		                events : {
		                    afterSetExtremes : afterSetExtremes
		                },
		                // minRange: 3600 * 1000 // one hour
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
		                series:	jsonArray,
		                legend: {
				            align: 'left',
				            verticalAlign: 'top',
				            floating: true,
				            backgroundColor: '#FFFFFF',
				            y: 25
				        },
		                
		            });
		}
	  
	  
           
        </script>
 	</body>
</html>