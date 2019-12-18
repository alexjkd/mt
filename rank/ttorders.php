<html><head><title>Test Order Tracker</title></head>
<?php
include "../lib/functions.php";

// configuration
$url = basename(__FILE__);
$headers = 'time,sku,pid,site,po,kw'; $errors='';
$file = 'ttorders.tsv';
if (!file_exists($file) ) {
	file_put_contents($file,$headers);
	//~ echo "$file NOT found ";
	//~ exit;
}

// check if form has been submitted
if (isset($_POST['text']))
{
		// prepare and correct the text
		//~ $text = file_get_contents($file); //for testing
		$text = $_POST['text'];
		$text = preg_replace("/[\r\n]/","\t",$text);
		$text = preg_replace("/\t\t/","\t",$text);
		$text = preg_replace("/\t\t\t/","\t\t",$text);

		// save the text contents
		$fileText = preg_replace("/\t/","\n",$text);
		if (empty($_POST['SaveAs']) && substr($_POST['NewFileName'],-5) == '.html') {
			file_put_contents($_POST['NewFileName'], $fileText);
		} else {
			file_put_contents($file, $fileText); //Save what's in the text box to the file
		}
		file_put_contents($file . '.bak', "\n\n". date('Y-m-d H:i') ."\n". $fileText, FILE_APPEND ); //Create 3 backups

		//~ file_put_contents($file . '.test.txt', $text); //for testing
		// save the text to mysql DB */
		//~ echo count($rows);exit; //for testing
		//~ $q = "INSERT IGNORE INTO `ttorders_archive`\nSELECT DISTINCT * FROM ttorders;\n";
		//~ $q .= "TRUNCATE ttorders;\n";
		$lastline = '';
		foreach(explode("\t",$text) as $line) {
			if (strlen($line) < 10 or $line == $headers) continue;
			$line = trim($line);
			$vs = explode(',',$line);
			if (!preg_match('#\d{4}-\d{2}-\d{2}#',trim($vs[0]))) { $errors .= "$line<br><font color=red>". $vs[0] ."</font> NOT in the format: <b>2016-07-08,</b> or <b>2016-11-03,</b><br>"; continue;}
			if (strpos($line,',') == FALSE ) { $errors .= "<font color=red>$line</font> NOT contain exact 6 commas<br>"; continue;}
			if (count($vs) <> 6 ) { continue;}
			$values = '';
			if ($line <> $lastline) {
				$i = 0; $lastline = $line;
				foreach(explode(',',$headers) as $h) {$$h = trim($vs[$i]); $values .= "'". $$h ."',";$i++;}
				$values = trim($values, ',');
				//~ print_r($vs); //for testing
				//~ $q .= "INSERT IGNORE INTO ttorders ($headers) VALUES ('$date','$kw','$site','$sku','$pid','$time','$po');\n";
				$q .= "INSERT IGNORE INTO ttorders ($headers) VALUES ($values);\n";
				$htmlTable .= '<tr><td>'. str_replace(',','</td><td>',$line) .'</td></tr>';
			}
		}
		if (strlen($q) > 60 and sqlquery($q) <> false) {
			//~ echo $q;
			//~ sqlquery('TRUNCATE ttorders');
			//~ header(sprintf('Location: %s', $url));
			//~ sprintf('<a href="%s">Saved</a>.', htmlspecialchars($url) .'<br>'. str_replace("\t",'<br>',$text));
			$htmlTable = '<tr><th>'. str_replace(',','</th><th>',$headers) .'</th></tr>'. $htmlTable;
			print("<a href=$url><h3>Back</h3></a>Only distinct rows are saved.<br><table border=1>$htmlTable</table>");
			//~ echo str_replace("\t",'<br>',$text));
		} else {
			echo "<h3>Errors:</h3>$errors ";
		}

    // redirect to $url
    exit();
}

// read the textfile
$text = file_get_contents($file);//It is much easier to get the text back from the file than from the database
/*$q = "select * from ttorders order by date desc, gid, LENGTH(data)";
$qr = sqlquery($q);
$last_gid = 0;
foreach($qr as $r) {
	if ($last_gid <> $r['gid']) {	$text .= "\n";}
	$text .= $r['type'] .",". $r['data'] ."\n";
	$last_gid = $r['gid'];
}
file_put_contents($file,$text);
file_put_contents("$file.allbak","\n". date('Y-m-d_H:i:s') ."\n". $text ."\n",FILE_APPEND);
*/
//~ function sqlquery() { } //for testing
?>
<!-- HTML form -->
<form action="" method="post">
<input type="submit" name="SaveAs" value="Save As"/>
<input type="text" name="NewFileName"/>
<br>
<textarea name="text" rows="30" cols="150"><?php echo htmlspecialchars($text) ?></textarea><br>
<input type="submit" value="Save"/>
</form>
</HTML>