<?php
include "../lib/functions.php";
define(URL, basename(__FILE__));
// configuration
// $url = 'editor_scTu4UTrYBGA4arkTBPvfA7Uu5cn14jfhMcMvQ91i8.php';
if (!$_GET['file']) {
	listFile();
	exit;
}
function listFile() {
	foreach(glob('*.csv') as $csv) {
		echo "<a target=_blank href=editor.php?file=$csv>$csv</a><br>";
	}
}
$file = $_GET['file'];
if (!file_exists($file) ) {
	echo "$file NOT found ";
	exit;
}
// read the textfile
$text = file_get_contents($file);

// check if form has been submitted
if (isset($_POST['text']))
{
    // save the text contents
		if (!empty($_POST['SaveAs']) && substr($_POST['NewFileName'],-5) == '.html') {
			file_put_contents($_POST['NewFileName'], $_POST['text']);
		} else {
			file_put_contents($file, $_POST['text']);
		}
		file_put_contents($file . date('s')%3, $_POST['text']);

		// save to mysql db
		//~ $i = 0;
		foreach(explode(PHP_EOL,$_POST['text']) as $link) {
			//~ preg_match_all('/\?s=(?P<cat>[\w\-]+)|keywords=(?P<kw>[\w\+]+)/',$link,$matches,PREG_SET_ORDER );
			preg_match('/\?s=(?P<cat>[\w\-]+)/',$link,$matches);
			$cat = $matches['cat'];
			preg_match('/keywords=(?P<kw>[\w\+]+)/',$link,$matches);
			$kw  = $matches['kw'];
			$lid = hash('crc32',$link);
			//~ $values .= "('$cat','$kw','$lid','$link'),";
			$q =  "insert ignore into seolinks_amz (cat,kw,lid,link) values ('$cat','$kw','$lid','$link');";
			echo $q ."<br>\n";
			sqlquery($q);
			//~ $i++; if ($i > 3) break;
		}
		//~ $q = "insert ignore into seolinks_amz (cat,kw,lid,link) values $values";
		//~ echo $q;
		//~ exit;
		//~ sqlquery($q);

    // redirect to $url
    header(sprintf('Location: %s', $url));
    printf('<a href="%s">Moved</a>.', htmlspecialchars($url));
    exit();
}

function csvToSqlTable($file) {
	$table = preg_replace("/\W+/",'_',substr(basename($file),0,64));

	// get structure from csv and insert db
	ini_set('auto_detect_line_endings',TRUE);
	$handle = fopen($file,'r');
	// first row, structure
	if ( ($data = fgetcsv($handle) ) === FALSE ) {
			echo "Cannot read from csv $file";die();
	}
	$fields = array();
	$field_count = 0;
	for($i=0;$i<count($data); $i++) {
			$f = strtolower(trim($data[$i]));
			if ($f) {
					// normalize the field name, strip to 20 chars if too long
					$f = substr(preg_replace ('/[^0-9a-z]/', '_', $f), 0, 20);
					$field_count++;
					$fields[] = $f.' VARCHAR(50)';
			}
	}

	$sql = "CREATE TABLE $table (" . implode(', ', $fields) . ')';
	echo $sql . "<br /><br />";
	// $db->query($sql);
	while ( ($data = fgetcsv($handle) ) !== FALSE ) {
			$fields = array();
			for($i=0;$i<$field_count; $i++) {
					$fields[] = '\''.addslashes($data[$i]).'\'';
			}
			$sql = "Insert into $table values(" . implode(', ', $fields) . ')';
			echo $sql;
			// $db->query($sql);
	}
	fclose($handle);
	ini_set('auto_detect_line_endings',FALSE);


}

?>
<!-- HTML form -->
<form action="" method="post">
<input type="submit" name="SaveAs" value="Save As"/>
<input type="text" name="NewFileName"/>
<br>
<textarea name="text" rows="30" cols="150"><?php echo htmlspecialchars($text) ?></textarea><br>
<input type="submit" value="Save"/>

</form>