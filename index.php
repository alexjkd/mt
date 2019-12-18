<!-- saved from url=(0055)http://www.treemenu.net/treeviewfiles/demoFrameset.html -->
<html><head><meta http-equiv="Content-Type" content="text/html; charset=windows-1252">
<link rel="shortcut icon" href="mt.ico" type="image/x-icon">
<title>Market Tracker</title>
<script>
function op() { //This function is used with folders that do not open pages themselves. See online docs.
}
</script>
</head>

<!--
(Please keep all copyright notices.)
This frameset document includes the Treeview script.
Script found in: http://www.treeview.net
Author: Marcelino Alves Martins

You may make other changes, see online instructions,
but do not change the names of the frames (treeframe and basefrm)
-->

<?php
/*
foreach (glob('uploads/*.HomeDepotOrders.csv') as $f) {
	//~ rename($f, str_replace('/','/HomeDepotOrdersCsv/',$f));
	$nf = str_replace('uploads/','uploads/HomeDepotOrdersCsv/',$f);
	if (rename($f, $nf)) echo $f .' -> '. $nf ."<br>";
}
*/
?>

<!-- <frameset cols="225,*" onresize="if (navigator.family == &#39;nn4&#39;) window.location.reload()">
  <frame src="FrameTree.php" name="FrameTree">
  <frame src="dashboard.php?list=top30" name="topright" id="FrameContent">
  <frame src="notification.php" name="bottomright">
</frameset> -->


<FRAMESET COLS="225,*" onresize="if (navigator.family == &#39;nn4&#39;) window.location.reload()">
  <FRAME SRC="FrameTree.php" NAME="left">
  <FRAMESET ROWS="100%">
    <FRAME SRC="dashboard.php?list=top30" NAME="FrameContent" frameborder="0" scrolling="yes" id="FrameContent">
    <!--<FRAME SRC="dashboard.php?tier=1&list=top30&region=us" NAME="FrameContent" frameborder="0" scrolling="yes" id="FrameContent"> -->
    <!-- <FRAME SRC="notification.php" NAME="bottomright" frameborder="0"> -->
  </FRAMESET>
</FRAMESET>


</html>
