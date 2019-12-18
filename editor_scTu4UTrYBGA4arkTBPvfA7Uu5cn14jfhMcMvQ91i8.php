<?php

// configuration
$file = $_GET['file'];
if (substr($file,-4) == '.php') exit;
$url = 'editor_scTu4UTrYBGA4arkTBPvfA7Uu5cn14jfhMcMvQ91i8.php?file='. $file;
if (!file_exists($file)) {
	echo "$file NOT found.";
	exit;
}

// check if form has been submitted
if (isset($_POST['text']))
{
    // save the text contents
		$text = '';
		foreach(explode("\n",$_POST['text']) as $line) {
			$line = trim($line);
			//~ if (strpos($text,$line) > -1) continue; //exclude duplicate lines
			$text .= $line ."\n";
		}
		if (!empty($_POST['SaveAs']) && substr($_POST['NewFileName'],-5) == '.html') {
			file_put_contents($_POST['NewFileName'], $text);
		} else {
			file_put_contents($file, $text);
		}

    // redirect to $url
    //~ header(sprintf('Location: %s', $url));
    //~ printf('<a href="%s"><h3>Back</h3></a>.', htmlspecialchars($url));
		echo "Only distinct rows are saved. <button type=\"button\" onclick=\"javascript:history.back()\">Back</button><table border=1><tr><td><b>". str_replace("\n",'<br>',$text) ."</b></td></tr></table>";
    exit();
}

// read the textfile
$text = file_get_contents($file);

?>
<!-- HTML form -->
<form action="" method="post">
<!-- input type="submit" name="SaveAs" value="Save As"/>
<input type="text" name="NewFileName" size="100" / >
<br -->
<textarea name="text" rows="45" cols="150"><?php echo htmlspecialchars($text) ?></textarea><br>
<input type="submit" value="Save"/>
</form>
</html>