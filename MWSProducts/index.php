<?php require_once 'db.php';?>
<!DOCTYPE html>
<html>
<head>
	<title>MWS Proudct Ranking</title>
	<style type="text/css">
		.success{
			font-size: 22px;
			color: #00a900;
		}
	</style>
</head>
<body>
	<?php if(isset($_SESSION['success'])):?>
		<div class="success"><?php echo $_SESSION['success'];?></div>
	<?php endif;?>
	<a href="fetch_data.php">Fetch Data</a>&nbsp;&nbsp;<a href="dummy_mws_xml.php">Dummy XML from MWS</a>
</body>
</html>
<?php session_destroy();?>