<?php require_once 'db.php'; ?>
<html>
<head>
	<title>MWS Display Product Table</title>
	<script src="../MWSProducts/src/js/jquery-3.3.1.min.js"></script>
	<script src="../MWSProducts/src/js/tablefilter_min.js"></script>
	<style type="text/css">
		table, td, th {
		  border: 1px solid black;
		}
		
		table {
		  border-collapse: collapse;
		  width: 100%;
		}
		
		th,td {
		  text-align: center;
		}
		.box ul, .button ul {
		  list-style-type: none;
		  margin: 0;
		  padding: 0;
		}
		.box ul li, .button ul li{
		  float:left;
		  margin-right: 40px;
		  margin-bottom: 10px;
		}
		.button ul li:last-child, .box ul li:last-child {
		  float: right;
		  margin-right: 0px;
		}
		.button li a,button {
		  background-color: #353130;
		  color: white;
		  border:0px;
		  padding: 10px 10px;
		  text-align: center;
		  text-decoration: none;
		  display: inline-block;
		}
		li a:hover {
		  background-color: #111;
		}
		button{
  		  padding: 12px 10px;
  		  cursor: pointer;
		}
		.box textarea{
			resize : none;
		}
		.button{
			clear: both; 
		}
	</style>
</head>
<body>
  <?php
		$mysqli = mws_mysqlConnect();
	  //print_r($_POST);
	     if(isset($_POST['task']) && !empty(($_POST['task']))){
	     	$select_sql = $_POST['task'];
	     }else if(isset($_POST['submit'])){
	     	$select_sql = $_POST['select_query'];
	     	if($_POST['submit'] == 'save'){
		     	$mws_insert_select_sql = "select * from select_table where saved_query='".$mysqli->escape_string($select_sql)."'";
			    $mws_insert_result = $mysqli->query($mws_insert_select_sql);
				$count = mysqli_num_rows($mws_insert_result);
				if($count == 0)
				{
				   	// $insert_mysqli = mws_mysqlConnect();
					$insert_sql = 'INSERT INTO select_table (saved_query) VALUES ("'.$select_sql.'")';
					$mysqli->query($insert_sql);
				}else{
				    echo "The Query is already present in the table" ;
				}
	     	}
	     }else{
			  $select_sql = 'SELECT * FROM mws';
	     }
	     
	     //print_r($select_sql);
	  	//   if(isset($_GET['asin'])){
			 // $select_sql = "SELECT * FROM mws WHERE created_on >= NOW() - INTERVAL '".$_GET["ago"]."' DAY AND created_on >= NOW() - INTERVAL '".$_GET["days"]."' DAY AND  product_sku IN ('".str_replace(",", "','", $_GET["asin"])."')";
		  //}else{
			 // $select_sql = "SELECT * FROM mws";
		  //}
	    $result = $mysqli->query($select_sql);
		
	  ?>
  	<div class="dropdown">
  	 <form action="" method="POST">
  	   <select name="task" id="myselect" onchange="this.form.submit()" >
  	  	 <?php  
	       //$mysqli = mws_mysqlConnect();
	       $mws_query_select_sql = "select * from select_table";
	       $mws_query_result = $mysqli->query($mws_query_select_sql);
	       while ($query_row = mysqli_fetch_row($mws_query_result)) {
		 ?>
		     <option value="<?php echo $query_row[1]; ?>" <?php echo (str_replace(array("'",'"'), '', $query_row[1]) == str_replace(array("'",'"'), '', $_POST['task']))? "selected = 'selected'":''; ?>><?php echo $query_row[1]; ?></option>
		 <?php
		   }	
	  	 ?>
	  </select>
	  </form>
	</div>
	<form action="" method="post">
    <div class ="box">
	  <ul>
	    <li class ="box1"><textarea name="select_query" rows="15" cols="100"><?php if(isset($_POST['select_query'])){ echo $_POST['select_query']; } else{ echo 'select * FROM mws'; }?></textarea></li>
	  	<li class="box2">
	  	<?php  
	      $mws_select_sql = "select Column_name from Information_schema.columns where Table_name like 'mws'";
	      $mws_result = $mysqli->query($mws_select_sql);
	      while ($row = mysqli_fetch_row($mws_result)) {
	      	if ((($key = array_search('updated_on', $row)) !== false) || ($key = array_search('category_id', $row)) !== false || ($key = array_search('currency', $row)) !== false || ($key = array_search('csv_id', $row)) !== false || ($key = array_search('csv_group_product', $row)) !== false) {
			    unset($row[$key]);
			}
		    $headers[] = $row[0];
		  }	
	  	?>
	  	<textarea name="colums" rows="15" cols="80"><?php foreach($headers as $header){ if(!empty($header)){echo $header . PHP_EOL;  }} ?></textarea></li>
	  </ul>
	</div>
	<div class ="button">
	  <ul>
	    <li class ="button1"><button type="submit" name="submit" class="btn-link">Submit</button></li>
	  	<li class="button2"><button type="submit" name="submit" value="save" class="btn-link">Save</button></li>
	  	<li class="download"><a href='downloadcsv.php'>Click to Download csv file</a></li>
	  </ul>
	</div>
  </form>
		<table id="table1" cellspacing="0" class="mytable filterable" > 
			
			<tr class="success">
			  <th>id</th>
			  <th>Asin (product_sku)</th>
			  <th>tier</th>
			  <th>owner</th>
			  <th>rank</th>
			  <th>price</th>
			  <th>Date (created_on)</th>
			</tr>
			<?php	
			  if (mysqli_num_rows($result) > 0) {
			  while ($res =mysqli_fetch_assoc($result)){
			?>
			<tr>
			  <td><?php echo $res['id'];?></td>
			  <td><?php echo $res['product_sku'];?></td>
			  <td><?php echo $res['tier'];?></td>
			  <td><?php echo $res['owner'];?></td>
			  <?php 
		     	$category_select = "select * from category where mws_id='".$res['id']."'";
			    $category_rank_select = $mysqli->query($category_select);
			    $rank_arr = [];
			    foreach($category_rank_select as $rank){
			    	$rank_arr[] = $rank['rank'];
			    }
	            $rank_values = implode(" , ", $rank_arr);
			    ?>
			  <td><?php echo $rank_values;?></td>
			  <td><?php echo $res['price'] ." ". $res['currency'];?></td>
		    <?php
			  $date = substr($res['created_on'], 2, 14);
			?>
			  <td><?php echo $date;?></td>
			</tr>
		  	<?php  }
		  	?>
		</table>
	<?php	 
	}else{
		echo "No results found... or Check the Query and submit again";
	}
    ?>
</body>
<script language="javascript" type="text/javascript">  
  var tf = setFilterGrid(".table1"); 
</script>   
</html>