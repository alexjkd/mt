<?php
ini_set('default_charset', 'UTF-8');
ini_set("memory_limit", "512M");
ini_set('max_execution_time', 300); //300 seconds = 5 minutes
date_default_timezone_set('America/New_York');
include_once('simple_html_dom.php');
include_once('db.php');
require_once(dirname(__FILE__) . '/phplot.6.1.0.php');
$db = db::getInstance();

// define('ROOTDIR','/home/czyusa1973/public_html');
define('ROOTDIR', '/var/www/html/uat');
$aColors = array('red', 'blue', 'DarkGreen', 'orange', 'cyan', 'SkyBlue', 'green', 'SlateBlue', 'DimGrey', 'gold', 'grey', 'ivory', 'PeachPuff');

function download_processed($file)
{
	$file_url = "$file";
	// echo "Download <h1><a href=$file_url>$file_url</a></h1>";
	// return;
	header('Content-Type: application/octet-stream');
	header("Content-Transfer-Encoding: Binary");
	header("Content-disposition: attachment; filename=\"" . basename($file_url) . "\"");
	readfile($file_url);
}

function getHtml($sl, $useProxy = 1, $html_file_name = 'html/gethtml.html')
{
	global $cmd;
	rename($html_file_name, $html_file_name . '.old');
	if ($useProxy == 0) return get_html_curl($sl, $html_file_name);
	//~ $sProxiesFile=file_get_contents('proxies.csv');
	if ($useProxy) {
		$sProxiesFile = file_get_contents(ROOTDIR . '/mt/lib/proxybonanza.csv');
		$aLines = explode("\n", $sProxiesFile);
		$line = trim($aLines[rand(0, count($aLines) - 1)]);
		if (preg_match("/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}\t\d{2,6}/", $line)) {
			//~ echo($line .'<br>');
			$a = explode("\t", $line);
			$proxy = " -e http-proxy=" . $a[0] . ':' . $a[1] . " https-proxy=" . $a[0] . ':' . $a[1] . " --proxy-user=" . $a[2] . " --proxy-password=" . $a[3];
			//~ $sBadProxies=file_get_contents('badProxies.txt');
			$aUserAgents = explode("\n", file_get_contents(ROOTDIR . '/mt/lib/user_agent_strings.txt'));
			$aLines = explode("\n", $sProxiesFile);
			$ua = trim($aUserAgents[rand(0, count($aUserAgents) - 1)]);
			//~ $html_file_name='html/'. str_replace(':','-',$proxy) . '_ads_amazon.html';
			$cmd = "wget '$sl' -O '$html_file_name' $proxy --tries=3 --timeout=9 --random-wait --wait=1 -U '$ua' ";
			//~ echo $cmd; exit;
			$rev = system($cmd);
			//~ print_r($rev); exit;
			if (!file_exists($html_file_name) or filesize($html_file_name) < 123456) {
				file_put_contents(basename(__FILE__, 'php') . 'error.log', date('Y-m-d H:i:s') . "\t" . $cmd . "\n", FILE_APPEND);
				//~ if (strpos($sBadProxies,$proxy)==false) file_put_contents('badProxies.txt',$proxy,FILE_APPEND);
				return get_html_curl($sl, 'html/amazon.html');
			} else {
				$html = file_get_html($html_file_name);
				if (stripos($html, 'robot') <> FALSE) {
					file_put_contents(basename(__FILE__, 'php') . 'error.log', date('Y-m-d H:i:s') . "\tRobot, cmd=" . $cmd . "\n", FILE_APPEND);
					// continue;
				}
				file_put_contents('goodProxies.log', $proxy . "\n", FILE_APPEND);
				return $html;
			}
		}
	}
}

function get_html_curl($url, $useProxy = 0, $html_file_name = 'webpage.html')
{
	//~ $url='https://www.amazon.com/iSpring/b/ref=w_bl_hsx_s_hi_web_3031803011?ie=UTF8&node=3031803011&field-lbr_brands_browse-bin=iSpring';
	$ch = curl_init($url);
	$fp = fopen($html_file_name, "w+");
	if ((time() - filemtime('../lib/user_agent_strings.txt')) > 3600 * 24 * 7) {
		$fUa = file_get_contents('https://docs.google.com/spreadsheets/d/e/2PACX-1vR2gY22xgcaR4JUr3naK5nXbFzw3pL_Ogn4msFRDGfVA8nILfEs-BOdxDRt2Jvhx9Yz31eAF8IfpjBn/pub?gid=10357669&single=true&output=tsv');
		file_put_contents(ROOTDIR . '/mt/lib/user_agent_strings.txt', $fUa);
	}
	$aUserAgents = explode("\n", file_get_contents(ROOTDIR . '/mt/lib/user_agent_strings.txt'));
	$sUa = trim($aUserAgents[rand(0, count($aUserAgents) - 1)]);
	if (strlen($sUa) < 10) $sUa = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/63.0.3239.132 Safari/537.36';
	//~ echo $sUa; exit;

	if ($useProxy) {
		if ((time() - filemtime('../lib/proxybonanza.csv')) > 3600 * 24) {
			$fProxy = file_get_contents('https://docs.google.com/spreadsheets/d/e/2PACX-1vR2gY22xgcaR4JUr3naK5nXbFzw3pL_Ogn4msFRDGfVA8nILfEs-BOdxDRt2Jvhx9Yz31eAF8IfpjBn/pub?gid=308001853&single=true&output=tsv');
			file_put_contents('../lib/proxybonanza.csv', $fProxy);
		}
		$sProxiesFile = file_get_contents(ROOTDIR . '/mt/lib/proxybonanza.csv');
		$aLines = explode("\n", $sProxiesFile);
		for ($i = 1; $i < 10; $i++) {
			$line = trim($aLines[rand(0, count($aLines) - 1)]);
			if (preg_match("/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}\t\d{2,6}/", $line)) {
				$a = explode("\t", $line);
				$proxy = trim($a[0]) . ':' . trim($a[1]);
				$proxyauth = trim($a[2]) . ':' . trim($a[3]);
				//~ echo $proxy .' '. $proxyauth; exit;
				curl_setopt($ch, CURLOPT_PROXY, $proxy);
				curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxyauth);
				break;
			}
		}
	}

	// if (file_exists('webpage.html')) unlink('webpage.html');
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_FILE, $fp);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_HEADER, 1);
	// curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1)');
	// curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 5.1; rv:44.0) Gecko/20100101 Firefox/44.0');
	curl_setopt($ch, CURLOPT_USERAGENT, $sUa);
	curl_setopt($ch, CURLOPT_TIMEOUT, 10);
	$html = curl_exec($ch);
	//~ if ( == FALSE) return '';
	curl_close($ch);
	fclose($fp);
	if (strlen($html) > 12345) {
		file_put_contents($html_file_name, $html);
		return str_get_html($html);
	}
	return 0;
}

function find_latest_file($wildcard = '.', $path = __DIR__)
{
	// echo $path;
	// echo $wildcard;
	$latest_ctime = 0;
	$d = dir($path);
	while (false !== ($entry = $d->read())) {
		$filepath = "{$path}/{$entry}";
		// could do also other checks than just checking whether the entry is a file
		if (is_file($filepath) && strpos($filepath, $wildcard) > 0 && filectime($filepath) > $latest_ctime) {
			$latest_ctime = filectime($filepath);
			$latest_filename = $entry;
		}
	}
	return $path . '/' . $latest_filename;
}

function find_latest_dir($wildcard = '.', $path = __DIR__)
{
	// echo $path;
	// echo $wildcard;
	$latest_ctime = 0;

	$d = dir($path);
	while (false !== ($entry = $d->read())) {
		$filepath = "{$path}/{$entry}";
		// could do also other checks than just checking whether the entry is a file
		if (is_dir($filepath) && strpos($filepath, $wildcard) > 0 && filectime($filepath) > $latest_ctime) {
			$latest_ctime = filectime($filepath);
			$latest_dir = $entry;
		}
	}
	return $latest_dir;
}

function unzip($zipfile, $path_extracted_to = '.')
{
	if (!file_exists($zipfile)) {
		echo "Zip file $zipfile not found";
		return -1;
	}
	if (!file_exists($path_extracted_to)) mkdir($path_extracted_to);
	$obj_zip = new ZipArchive;
	if ($obj_zip->open($zipfile) === TRUE) {
		$obj_zip->extractTo($path_extracted_to);
		$obj_zip->close();
		return $path_extracted_to;
	} else {
		return 0;
	}
}

//check if a string has been worked on in the past xxx seconds
function worked_log($string, $worked = 0)
{
	$now = time();
	$string = mysql_escape_string($string);
	if ($worked = 1) {
		sqlquery("insert into worked_log (time,string, success) values ($now,'$string',1) ");
		return true;
	}
	$qr = sqlquery("select time from worked_log where success = 1 and string = '$string'");
	$time = $qr[0]['time'];
	if ($time < $now) {
		return $now - $time;
	} else {
		return false;
	}
}

function my_print_r($val)
{
	echo preg_replace("/\n|\r/", "<br>\n", print_r($val, true));
}

function echoError($msg)
{
	echo "<p><font color=red> $msg </font></p>";
}
function tsv2ToArray($filename)
{
	$file = file_get_contents($filename) or die('cant open file');
	$Lines = explode("\n", $file);
	$ra = array();
	foreach ($Lines as $line) {
		if (strlen($line) < 3 or strpos($line, '--') === 0) continue;
		// echo "$line \n";
		// $tsv = explode($delim,$line);
		$tsv = preg_split("/[,\t]+/", $line);
		$type = trim($tsv[0]);
		$val = trim($tsv[1]);
		$ra[$val] = $type;
	}
	// print_r($ra);
	return $ra;
}

function OLDtsv2ToArray($filename, $delim = "\t")
{
	$fileCategories = file_get_contents($filename);
	$tsvLines = explode("\n", $fileCategories);
	$categories = array();
	foreach ($tsvLines as $tsv) {
		echo "$tsv <br>";
		$tsv = explode($delim, trim($tsv));
		$category = trim($tsv[0]);
		$url = trim($tsv[1]);
		$categories[$category] = $url;
	}
	return $categories;
}

function mysqltabletocsv($q)
{
	if (!preg_match('/select \b([\w, ]+?)\b from/', $q, $matches)) {
		echo "Failed to pre_match /select \b([\w, ]+?)\b from/ with $q";
		return -1;
	};
	$valueNames = $matches[1];
	preg_match('/from \b(\w+)\b/', $q, $matches);
	$tablename = $matches[1];
	$valueNamesArray = explode(',', $valueNames);
	$colspan = substr_count($valueNames, ',');
	$rows = sqlquery($q);
	foreach ($rows as $row) {
		foreach ($valueNamesArray as $vn) {
			$$vn = $row[$vn];
			$csvrow .= $$vn . ',';
		}
		if (substr_count($csvrow, ',') == $colspan + 1) $csv .= trim($csvrow, ',') . "\n";
		$csvrow = '';
	}
	// file_put_contents($tablename .'.csv', $csv);
	return trim($csv);
}
function is_sqltableexist($tablename)
{
	// $sql = "SELECT COUNT(*) as exist FROM information_schema.tables WHERE table_name = '". $tablename ."';";
	$sql = "show tables like '" . $tablename . "';";
	$query = sqlquery($sql);
	if (!empty($query)) return true;
	// return empty($check);
	// echo $check[0]['exist'];
	// return $check[0]['exist'];
}
function sqlquery($q)
{
	global $db;
	$q = trim($q);
	if (!preg_match('/^show +tables |^select |^update |^insert |^delete +from|^create +?table +?|^truncate |^drop +table /i', $q)) return false;
	if (preg_match('/create\s+?table\s+/i', $q)) {
		/*preg_match('/table `(.+?)`/i',$q,$matches);
		$tablename=$matches[1];
		$query = $db->query("SHOW TABLES LIKE '$tablename'");
		$rows = $query->fetchAll(PDO::FETCH_ASSOC);
		if (!empty($rows)) return $rows;*/
		if (stripos($q, 'IF NOT EXISTS') == false) $q = preg_replace('/create\s+table/i', 'CREATE TABLE IF NOT EXISTS ', $q);
		return $db->query($q);
	}
	try {
		$query = $db->query($q);
	} catch (Exception $e) {
		echo '<BR><font color=red>' . $q . '<br>Caught exception: ',  $e->getMessage(), "</font><br>\n";
		return false;
	}
	// echo "\n<br>". $q;
	if (preg_match('/^show |^select /i', $q)) {
		$rows = $query->fetchAll(PDO::FETCH_ASSOC);
		return $rows;
	}
	return $query;
}

function asin_sku_conversion($s)
{
	$asin = $sku = '';
	$today = date('Ymd');
	$tb = 'amazon_items'; //ispring_asin_sku
	if (strlen($s) == 10 && strpos('01' . $s, 'B0') == 2) {
		$asin = $s;
		$q = "select * from $tb where asin = '$asin'";
	} else {
		$sku = $s;
		$q = "select * from $tb where sku = '$sku'";
	}
	$rows = sqlquery($q);
	if (count($rows) == 0) {
		$q = "insert ignore into $tb (tier,regions,seller,asin,sku,name,dateadded) values (0,'','','$asin','$sku','',$today); ";
		sqlquery($q);
	}
	foreach ($rows as $row) {
		if ($asin == $s and $row['sku'] != null) return $row['sku'];
		if ($sku == $s and $row['asin'] != null) return $row['asin'];
	}
}

function old_get_asin_sku_array()
{
	$today = date('Ymd');
	$q = "select asin,sku from amazon_items";
	$rows = sqlquery($q);
	$array_sql_asin_sku = array();
	foreach ($rows as $row) {
		$array_sql_asin_sku[$row['asin']] = $row['sku'];
	}
	$csv = file_get_contents(ROOTDIR . '/mt/amazon_items.csv');
	$lines = explode("\n", trim($csv));
	$sql_add_values = '';
	foreach ($lines as $line) {
		$vs = explode("|", $line); //tier	regions	seller	asin	sku	name
		if (count($vs) < 6) continue;
		//~ echo "$line <br>";
		//~ $tier=$vs[0];
		//~ $regions=$vs[1];
		//~ $seller=$vs[2];
		$asin = $vs[0];
		$sku = $vs[1];
		//~ $name=$vs[5];
		if ($array_sql_asin_sku[$asin] != $sku) { //csv has new line not in mysql db
			$sql_add_values .= "('$tier','$regions','$seller','$asin','$sku','$name','$today'),";
			$array_sql_asin_sku[$asin] = $sku;
		}
	}
	$sql_add_values = trim($sql_add_values, ',');
	//~ echo $sql_add_values ."<br>\n";
	if (strlen($sql_add_values) > 30) sqlquery("insert ignore into amazon_items (tier,regions,seller,asin,sku,name,dateadded) values $sql_add_values ");
	return $array_sql_asin_sku;
}

function get_asin_sku_array($returntype = 'array')
{
	//~ $q = "select DISTINCT asin,sku from amazon_items";
	$q = "SELECT DISTINCT asin,sku FROM `asin_us_strings` WHERE asin<>'' AND sku<>'' UNION SELECT asin,sku FROM amazon_items  ORDER BY sku";
	$rows = sqlquery($q);
	$array_asin_sku = array();
	foreach ($rows as $row) {
		$array_asin_sku[$row['asin']] = $row['sku'];
	}
	/*$q = "select DISTINCT asin,sku from asin_us_strings";
	$rows = sqlquery($q);
	foreach ($rows as $row) {
		$array_asin_sku[$row['asin']] = $row['sku'];
	}*/
	$csv = file_get_contents(ROOTDIR . '/mt/amazon_items.csv');
	foreach (explode("\n", trim($csv)) as $line) {
		$vs = explode(',', $line);
		$asin = $vs[0];
		$sku = $vs[1];
		if (!isset($array_asin_sku[$asin]) or $array_asin_sku[$asin] <> $sku) $array_asin_sku[$asin] = $sku;
	}
	if ($returntype == 'array') return $array_asin_sku;
	foreach ($array_asin_sku as $asin => $sku) {
		if (strlen($asin) == 10 and strlen($sku) > 2) $csv .= $asin . ',' . $sku . "\n";
	}
	file_put_contents('asin_sku.csv', $csv);
	download_processed('asin_sku.csv');
	//~ echo implode("\t",$array_asin_sku);
}

function html_find($html, $object, $attributes = '', $all = 0)
{
	$value = $values = '';
	// if(preg_match('/,/',$attributes)) $all = 1;
	$attribute_array;
	$finds = $html->find($object);
	if ($attributes <> '') {
		foreach ($finds as $find) {
			$attribute_array = explode(',', $attributes);
			$i = 0;
			foreach ($attribute_array as $attr) {
				$value = $find->$attr;
				if ($value == '') continue;
				$values .= $value . "<li>";
				if ($all == 0) {
					$values = $value;
					break;
				}
			}
			$i++;
			if ($i > 24) break;
		}
		return $values;
	} else {
		return $finds;
	}
}

function new_curl_get_html($url)
{
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 10.0; Windows NT 6.1; WOW64; Trident/6.0)');
	$html = curl_exec($ch);

	// We DO force the tags to be terminated.
	$dom = new simple_html_dom(null, $lowercase, $forceTagsClosed, $target_charset, $stripRN, $defaultBRText, $defaultSpanText);
	// For sourceforge users: uncomment the next line and comment the retreive_url_contents line 2 lines down if it is not already done.
	$contents = $html;
	// Paperg - use our own mechanism for getting the contents as we want to control the timeout.
	if (empty($contents) || strlen($contents) > MAX_FILE_SIZE) {
		return false;
	}
	// The second parameter can force the selectors to all be lowercase.
	$dom->load($contents, $lowercase, $stripRN);
	return $dom;
}

function old_html_find($html, $object, $attributes = '', $all = 0)
{
	$value = $values = '';
	// if(preg_match('/,/',$attributes)) $all = 1;
	$attribute_array;
	$finds = $html->find($object);
	if ($attributes <> '') {
		foreach ($finds as $find) {
			$attribute_array = explode(',', $attributes);
			foreach ($attribute_array as $attr) {
				$value = $find->$attr;
				if ($value == '') continue;
				$values .= $value . "\t";
				if ($all == 0) {
					$values = $value;
					break;
				}
			}
		}
		return $values;
	} else {
		return $finds;
	}
}

function get_checked($checked_log_dir, $hours)
{
	$files = glob($checked_log_dir . '/' . '*.log');
	$checked = '';
	foreach ($files as $file) {
		if (filemtime($file) > time() - 3600 * $hours) {
			$checked .= file_get_contents($file);
			// $last_import_checked_log .= file_get_contents($file);
		} else {
			unlink($file);
		}
	}
	return $checked;
}

function add_checked($checked_log_dir, $item)
{
	return file_put_contents($checked_log_dir . '/' . date('H') . '.log', $item . "\n", FILE_APPEND);
}
function tsvToArray($tsv, $delimitor = "\t")
{
	$result = array();
	$fp = fopen($tsv, 'r');
	if (($headers = fgetcsv($fp, 0, $delimitor)) !== FALSE)
		if ($headers)
			while (($line = fgetcsv($fp, 0, $delimitor)) !== FALSE)
				if ($line)
					if (sizeof($line) == sizeof($headers))
						$result[] = array_combine($headers, $line);
	fclose($fp);
	// print_r($result);
	return $result;
}
function csvToHtmlTable($csv)
{
	if (substr_count($csv, ',') > 2)  $delimit = ',';
	if (substr_count($csv, "\t") > 2) $delimit = "\t";
	$csv = trim($csv, $delimit);
	$lineArray = explode("\n", $csv);
	foreach ($lineArray as $line) {
		$html .= "<tr>";
		$cells = explode($delimit, $line);
		foreach ($cells as $cell) {
			$html .= "<td>" . htmlspecialchars($cell) . "</td>";
		}
		$html .=  "</tr>";
	}
	return $html;
}

# Callback for 'data_points': Generate 1 <area> line in the image map:
//~ function mystore_map($img, $passthru, $shape, $row, $column, $x, $y)
function mystore_map($img, $passthru, $shape = 'dot', $row, $column, $x, $y)
{
	global $image_map, $aTooltipData;

	# Link URL, for demonstration only:
	$href = "javascript:alert('($row, $col)')";
	# Convert coordinates to integers:
	//~ $coords = sprintf("%d,%d,%d,%d", $x1, $y1, $x2, $y2);
	# Append the record for this data point shape to the image map string:
	//~ $image_map .= "  <area shape=\"rect\" coords=\"$coords\""
	//~ .  " title=\"$title\" alt=\"$alt\" href=\"$href\">\n";
	//~ define('MAP_RADIUS', 10); // Capture area circle radii
	$coords = sprintf("%d,%d,%d", $x, $y, 5);
	//~ $coords = sprintf("%d,%d,%d,%d", $x1, $y1, $x2, $y2);
	//~ $tooltip='$row='. $row .', $column='. $column .', $x='. $x .', $y='. $y; //$chartdata[1][$column];
	//~ $tooltip='$row='. $row .', $column='. $column; //$chartdata[1][$column];
	//~ $tooltip=$aTooltipData[$row]['dtime'] .', B003XELTTG='. $aTooltipData[$row]['B003XELTTG'] .', B00I0ZGOZM='. $aTooltipData[$row]['B00I0ZGOZM'] ; //$chartdata[1][$column];
	//~ $tooltip=$aTooltipData['header'] ."\n". implode(' | ',$aTooltipData[$row]);
	$tooltip = $aTooltipData['header'] . "\n" . preg_replace("/\n\t/", "\n", implode("\t", $aTooltipData[$row]));
	# Required alt-text:
	$alt = $aTooltipData['header'];
	$image_map .= "  <area shape=\"circle\" coords=\"$coords\" title=\"$tooltip\">\n";
}

// drawchart($array2d,$kw .' '. $asin .' '. substr($text,0,70),'date','rank');
function drawchart($array2d, $title_c = '', $title_x = '', $yLegents = '', $width = 1600, $height = 120)
{
	//~ print_r($array2d); exit;
	global $aColors, $image_map;
	if (count($array2d) > 48) {
		$arrays = array_chunk($array2d, 48);
		foreach ($arrays as $array) {
			$width = count($array) * 32;
			if ($width < 640) $width = 640;
			drawchart($array, $title_c, $title_x, $yLegents, $width, $height);
		}
		return;
	}
	# This global string accumulates the image map AREA tags.

	//create a PHPlot object with 800x600 pixel image
	$plot = new PHPlot($width, $height);
	# Disable error images, since this script produces HTML:
	$plot->SetFailureImage(False);
	// $plot->SetDefaultTTFont('/home/czyusa1973/public_html/mt/lib/arial.ttf');
	$plot->SetPrintImage(False);  // Do not output the image
	// $plot->SetFont('y_label', 2, 12);
	// $plot->SetFont('x_label', 2, 12);
	$plot->SetFontTTF('x_label', '/home/czyusa1973/public_html/mt/lib/arial.ttf', 9);
	$plot->SetFontTTF('y_label', '/home/czyusa1973/public_html/mt/lib/arial.ttf', 7);
	if (count($array2d) >= 21) {
		$plot->SetXLabelAngle(45);
	}
	$plot->SetYDataLabelAngle(90);
	//Define some data
	/*   	$example_data = array(
			 array('x1',3),
			 array('x2',5),
			 array('x3',7),
			 array('x4',8),
			 array('x5',2),
			 array('x6',6),
			 array('x7',7)
	);
 	$plot->SetDataValues($example_data);
	// print_r($example_data);
	// return;
 */	//
	//~ $plot->SetPlotType('linepoints');
	//~ $plot->SetDataType('text-data');
	$plot->SetDataValues($array2d);

	//Set titles
	$plot->SetTitle($title_c);
	//~ $plot->SetXTitle($title_x);
	//~ $plot->SetYTitle($yLegents);

	//Turn off X axis ticks and labels because they get in the way:
	//~ $plot->SetXTickLabelPos('none');
	//~ $plot->SetXTickPos('none');
	$plot->SetXTickIncrement(1);
	$plot->SetDrawXGrid(True);
	$plot->SetDrawYGrid(False);
	$plot->SetXTickAnchor(0.5);
	$plot->SetXTickLabelPos('none');
	$plot->SetXDataLabelPos('plotdown');
	$plot->TuneYAutoRange(1, 'R', 0);
	$plot->SetYDataLabelPos('plotin');
	//~ $plot->SetXDataLabelAngle(90);
	$plot->SetYDataLabelAngle(0);
	$plot->SetPlotType('linepoints');
	$plot->SetYTickLabelPos('none');
	$plot->SetDataColors($aColors);
	$plot->SetLineStyles('solid');
	$plot->SetLineWidths(1);

	//Add legend for multi-line chart
	if (strpos($yLegents, ',') > 2) {
		//~ $plot->SetMarginsPixels(80);
		$legend = explode(',', $yLegents);
		$plot->SetLegend($legend);
		//~ if ($width <= 1300) {
		//~ $plot->SetPlotAreaWorld(NULL,NULL,$height,$width+300);
		//~ $plot->SetLegendPixels($width, 1);
		//~ } else {
		$plot->SetLegendPixels(250, 1);
		//~ }
	} else {
		if (strlen($yLegents) > 1) $plot->SetLegend($yLegents);
		$plot->SetLegendPosition(1, 0, 'plot', 0.5, 0);
	}
	//~ $plot->SetPlotAreaWorld(NULL, NULL, NULL, 5);
	//Draw it
	# Set the data_points callback which will generate the image map.
	//~ $plot->SetCallback('data_points', 'mystore_map'); //imagemap
	$plot->DrawGraph();
	echo "<img src=\"" . $plot->EncodeImage() . "\">\n";
	//~ $mapId=rand();
	//~ echo '<map name="map'. $mapId .'">' . $image_map .'</map><img src="'. $plot->EncodeImage() .'" alt="Plot Image" usemap="#map'. $mapId .'">' ."\n"; //imagemap
	$image_map = '';
}

function stringToColorCode($str)
{
	$code = dechex(crc32($str));
	$code = substr($code, 0, 6);
	return $code;
}

function color_inverse($color)
{
	$color = str_replace('#', '', $color);
	if (strlen($color) != 6) {
		return '000000';
	}
	$rgb = '';
	for ($x = 0; $x < 3; $x++) {
		$c = 255 - hexdec(substr($color, (2 * $x), 2));
		$c = ($c < 0) ? 0 : dechex($c);
		$rgb .= (strlen($c) < 2) ? '0' . $c : $c;
	}
	return '#' . $rgb;
}

function sendmail($subject, $message, $from = 'MarketTracker <john@ispringfilter.com>')
{
	$send_mail_log = file_get_contents('sendmail.log');
	if (strpos($send_mail_log, $subject)) {
		echo "<BR> $subject was already sent in email.";
		return -1;
	}
	$message = "<html><head><style>.h1, .h3color {color: #E47911;}</style><title>$subject</title></head>$message</html>\n";
	// file_put_contents(basename(__FILE__, 'php') . 'report.html',$message,FILE_APPEND );
	$headers  = 'MIME-Version: 1.0' . "\r\n";
	$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
	$headers .= "From: $from" . "\r\n";
	// mail('sales@ispringfilter.com,john@ispringfilter.com','Review Monitor - iSpring',$message,$headers);
	if (mail(EMAIL_RECEPIENTS, $subject, $message, $headers)) {
		echo "<BR><B>Email sent:</B> $subject";
		file_put_contents('sendmail.log', date('Y-m-d H:m') . ',' . $subject);
	}
}

function phpmailer($subject = '', $message, $attachment = '')
{
	$message = '<html><head><title>Reviews Target Report</title></head><b>' . date('l, Y-m-d h:m:s') . '</b><br>' . $message . "</html>\n\n";
	// file_put_contents(basename(__FILE__, 'php') . 'report.html',$message,FILE_APPEND );
	$email = new PHPMailer();
	$email->From      = EMAIL_RECEPIENTS;
	$email->FromName  = 'John Chen';
	$email->Subject   = $subject;
	$email->Body      = $message;
	$email->AddAddress('john@iSpringFilter.com');
	if ($attachment <> '') {
		$file_to_attach = $attachment;
		$email->AddAttachment($file_to_attach, 'NameOfFile.pdf');
	}
	return $email->Send();
}
