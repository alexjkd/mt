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
		// save the text to mysql DB
		//~ $text = file_get_contents($file);
		$text = $_POST['text'];
		$lines = explode(PHP_EOL,$text);
		foreach ($lines as $line) {
			$vs = str_getcsv($line);
			$kw = $vs[0];
			$cat = $vs[1];
			$url = trim($vs[2]);
			if (stripos($url,'http') <> 0) continue;
			$q .= "insert ignore amazon_com_kw (kw,cat,url) values ('$kw','$cat','$url');\n";
		}
		if (strlen($q) > count($text)) {
			sqlquery('TRUNCATE seolinkgroups');
			sqlquery($q);
		}

    // redirect to $url
    header(sprintf('Location: %s', $url));
    printf('<a href="%s">Moved</a>.', htmlspecialchars($url));
    exit();
}

//~ $q = "select * from seolinkgroups order by gid, LENGTH(link)";
//~ $qr = sqlquery($q);
//~ $last_gid = 0;
//~ foreach($qr as $r) {
	//~ if ($last_gid <> $r['gid']) {	$text .= "\n";}
	//~ $text .= $r['link'] ."\n";
	//~ $last_gid = $r['gid'];
//~ }
//~ file_put_contents('linkgroups.tsv',$text);
//~ $text = file_get_contents($file);

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