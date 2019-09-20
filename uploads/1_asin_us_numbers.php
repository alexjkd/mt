<?php
include_once('../lib/functions.php');
include_once('../lib/simple_html_dom.php');

$getFresh=1;
$sql_insert_asin_us_number = 'INSERT IGNORE INTO asin_us_numbers (time, brand, sku, asin, price, lowest, soldby, reviews, avgrating, fiveneeded, p5, p4, p3, p2, p1, qa, rank1, rank2) ';
$sql_insert_asin_us_strings = 'INSERT IGNORE INTO asin_us_strings (asin,brand,sku,title,featuresPoints)';
echo '<h2>'. $sql_insert_asin_us_number .'</h2>';
//~ echo '<h2>'. $sql_insert_asin_us_strings .'</h2>';

if (isset($_GET['asin'])) {
	$asin = $_GET['asin']; $sku='';
	if (isset($_GET['sku'])) $sku = $_GET['sku'];
	//~ $asin='B003XELTTG'; $sku='RCC7'; //testing
	$numbers=getNumbers($asin,$sku);
	echo $numbers;
} else {
	include_once('asin_all_numbers_isdone.php');
	$aLines = explode("\n",$notdone);
	foreach ($aLines as $line) {
		if (strpos($line,'--')>-1) continue;
		$a=explode(',',$line);
		$asin=$a[0];
		$sku=$a[1];
		//~ $asin='B003XELTTG'; $sku='RCC7'; //testing
		$numbers=getNumbers($asin,$sku);
		echo $numbers;
		//~ break;
	}
}

function getNumbers($asin,$sku) {
	global $getFresh;
	$html_file_name="html/$sku-$asin.html";
	//~ echo $html_file_name ."<br>";
	$fa = file_exists($html_file_name) ? time() - filectime($html_file_name) : 9999;
	$url="https://www.amazon.com/dp/$asin";
	if ($getFresh and $fa > 3600) {
		$html = getHtml($url,1,$html_file_name);
		//~ if (empty($html)) $html = getHtml($url,1,$html_file_name);
		if (empty($html)) $html = getHtml_proxybonanza($url,1,$html_file_name);
	} else {
		$html = file_get_html($html_file_name);
	}
	if (empty($html)) {
		if (!file_exists($html_file_name)) echo $html_file_name .' NOT found.';
		file_put_contents(basename(__FILE__, 'php') . 'error.log', date('Y-m-d H:i:s') ."\t Empty html: $html_file_name\n", FILE_APPEND);
		exit;
	}
	//~ print_r($html);
	$asin=$Price=$Lowest=$Soldby=$Reviews=$p1=$p2=$p3=$p4=$p5=$Rating=$FiveNeeded=$ReviewsPerStar=$qa=$Rank1=$Cate1=$Rank2=$lrs=$Brand=$Model=$Title=$featuresPoints=$cps='';

	$Brand=html_find($html,'a[id="bylineInfo"]','plaintext');
	$Brand=preg_replace('/\s\s+/','',$Brand);

	$protit=html_find($html,'span[id="productTitle"]','plaintext');
	if (strlen($protit)>10) {
		$protit=preg_replace('/\s\s+/','',$protit); //TEST echo $protit ."\n";
		$Title=$protit;
	}

	$Price=html_find($html,'span[id="priceblock_ourprice"]','innertext');
	if ($Price=='') $Price=html_find($html,'span[class="a-size-base a-color-price offer-price a-text-normal"]','innertext');

	$askATF=html_find($html,'a[id="askATFLink"]','plaintext');
	if (strpos($askATF,'answered questions')>0) {
		$askATF=preg_replace('/\s\s+/','',$askATF); //TEST echo $askATF ."\n";
		if (preg_match("/(\d+)\+* answered questions/",$askATF,$m)) $qa=$m[1];
	}

	$Soldby=html_find($html,'div[id="merchant-info"]','plaintext');
	$Soldby=preg_replace('/\s\s+/','',$Soldby);
	if (preg_match('/sold by ([\w ]+)/i',$Soldby,$m)) $Soldby=$m[1];

	$Lowest=html_find($html,'a[href*=ref=dp_olp_]','plaintext');
	$Lowest=preg_replace('/\s\s+/','',$Lowest);

	$fpoints=html_find($html,'div[id="feature-bullets"]','plaintext');
	if (strlen($fpoints)>10) {
		$fpoints=preg_replace('/\s\s+/','',$fpoints); //echo $fpoints ."\n";
		$featuresPoints=$fpoints;
	}

	$prodspec=html_find($html,'table[id="productDetails_techSpec_section_1"]','plaintext');
	if (strpos($prodspec,'Part Number')>0) {
		$prodspec=preg_replace('/\s\s+/','',$prodspec); //echo $prodspec ."\n";
		foreach(array('Part Number(.+?)Item','Item model number(.+?)Item') as $rex) {
			if (preg_match("/$rex/",$prodspec,$m)) $Model=$m[1]; //echo $Model ."\n";
			break;
		}
	} else {
		$prodspec=html_find($html,'div[id="detail-bullets"]','plaintext');
		if (stripos($prodspec,'Item model')>0) {
			$prodspec=preg_replace('/\s\s+/','',$prodspec); //echo $prodspec ."\n";
			foreach(array('Item model number: (.+?)Average') as $rex) {
				if (preg_match("/$rex/",$prodspec,$m)) $Model=$m[1]; //echo $Model ."\n";
				break;
			}
			foreach(array('ASIN: (B0\w{8})') as $rex) {
				if (preg_match("/$rex/",$prodspec,$m)) $asin=$m[1]; //echo $asin ."\n";
				break;
			}
			foreach(array('Best Sellers Rank:#([\d,]*) in (.+?) \(See Top 100 in .+?\)#(.+?)in') as $rex) {
				if (preg_match("/$rex/",$prodspec,$m)) {$Rank1=str_replace(',','',$m[1]); $Cate1=$m[2]; $Rank2=str_replace(',','',$m[3]);} // echo $asin ."\n";
				break;
			}
			foreach(array('(\d\.\d) out of 5 stars(.+?) customer reviews') as $rex) {
				if (preg_match("/$rex/",$prodspec,$m)) {$Rating=str_replace(',','',$m[1]); $Reviews=str_replace(',','',$m[2]); } // echo $asin ."\n";
				break;
			}
		}
	}

	$prodet=html_find($html,'table[id="productDetails_detailBullets_sections1"]','plaintext');
	if (strpos($prodet,'#')>0) {
		$prodet=preg_replace('/\s\s+/','',$prodet); //echo $prodet ."\n";
		if (preg_match('/ASIN(B\w{9})/',$prodet,$m)) $asin=$m[1];
		if (preg_match('/Reviews(\d\.\d) out of 5 stars/',$prodet,$m)) $Rating=$m[1];
		if (preg_match('/(\d[\d,]*?) customer reviews/',$prodet,$m) ) $Reviews=str_replace(',','',$m[1]);
		if (preg_match('/Sellers Rank#(\d[\d,]*?) /',$prodet,$m)) $Rank1=str_replace(',','',$m[1]);
		if (preg_match('/\(See Top 100 in ([ \w\&]+)\)/',$prodet,$m)) $Cate1=$m[1]; //TEST echo $Cate1 ."\n";
		if (preg_match('/\)#(\d[\d,]*?) in/',$prodet,$m)) $Rank2=str_replace(',','',$m[1]);
		if ($Rating>1 and $Reviews>1) $FiveNeeded = round((4.75-$Rating) * $Reviews * 4,1);
	}

	$reviewsHistogram=html_find($html,'table[id="histogramTable"]','plaintext');
	if (strpos($reviewsHistogram,'%')>0) {
		$reviewsHistogram=preg_replace('/\s\s+/','',$reviewsHistogram); //TEST echo $reviewsHistogram ."\n";
		for ($n=5;$n>0;$n--) {
			if (preg_match("/$n star (\d+)%/",$reviewsHistogram,$m)) $ReviewsPerStar .= round($m[1]/100*$Reviews,0) .'|';
		}
		if (strpos($ReviewsPerStar,'|')>0) {
			$aRp = explode('|',$ReviewsPerStar);
			$p5=$aRp[0];		$p4=$aRp[1];		$p3=$aRp[2];		$p2=$aRp[3];		$p1=$aRp[4];
			if ($Rating>1 and $Reviews>1) $FiveNeeded = round((4.75-$Rating) * $Reviews * 4,1);
		}
	}

	$aRevRec=html_find($html,'div[data-hook="recent-review"]');
	if (!empty($aRevRec)) {
		foreach($aRevRec as $div) {
			$revId=$div->id;
			$revHtml=$div->outertext;
		}
	}

	$cps=html_find($html,'div[data-p13n-asin-metadata*=pd_sbs_]','plaintext',1);
	$cps=preg_replace('/\s\s+/','',$cps);
	//~ $cps=preg_replace('/<li>/',"\n",$cps);

	$time = date('Y-m-d H:i:s');
	//~ $time = time();
	$vnumbers = "('$time', '$Brand', '$Model', '$asin', '$Price', '$Lowest', '$Soldby', '$Reviews', '$Rating', '$FiveNeeded', '$p5', '$p4', '$p3', '$p2', '$p1', '$qa', '$Rank1', '$Rank2')," ;
	echo '<li>'. $vnumbers;
	if (strlen($vnumbers) > 60 and count(explode("'),",$vnumbers)) > 0) {
		$qnumbers = $sql_insert_asin_us_number .' VALUES '. trim($vnumbers,',');
		$r = sqlquery($qnumbers);
	}
	$day=date('Y-m-d');
	if ($Brand<>'' && $Model<>'') $vstring .= "('$day', '$asin', '$Brand', '$Model', '$Title', '$featuresPoints'),";
	if (strlen($vstring) > 60 and count(explode("'),",$vstring)) > 0) {
		$qstring = $sql_insert_asin_us_strings .' VALUES ' . trim($vstring,',');
		sqlquery($qstring);
	}
}

exit;
foreach(glob('*.HomeDepotOrders.csv') as $f) {
	rename($f,"HomeDepotOrdersCsv/$f");
	//~ break;
}
foreach(glob('*.UP.csv') as $f) {
	rename($f,"UPcsv/$f");
	//~ rename($f,str_replace('.csv','.CSV',"UPcsv/$f"));
	//~ break;
}

exit;
$html_pattern = '#SalesRank';
$q = "select * from ispring_asin_sku where tier < 3";
// $q = "select * from ispring_asin_sku where sku <> ''";
$rows = sqlquery($q);
$checked_log_dir = str_ireplace('.php','_checkedlog',__FILE__);
/* if (!is_dir($checked_log_dir)) {
	echo "dir $checked_log_dir NOT found";
	// mkdir($checked_log_dir,0660);
}
 */
$hours = 2;
$checked = get_checked($checked_log_dir,$hours);
echo "<ol>";
importRank('B00I0ZGOZM','APEC RO-50');
importRank('B00NWZ1RCK','APEC PH-75');
importRank('B00NWZ1RCK','APEC RO-90');
importRank('B00N5D7ZFW','APEC ICEK');
importRank('B00J2DGTD8','ExpressWater RO5DX');
importRank('B005A3WM6C','HomeMaster TMAFC');
foreach($rows as $row) {
	importRank($row['asin'],$row['sku']);
}
echo "</ol>";
function importRank($asin,$sku) {
	global $html_pattern,$checked,$hours;
	if (strpos($checked,$asin) !== false) {
		echo "<li>$asin checked in $hours hours ago.";
		continue;
	}
	// $sku = $row['sku'];
	$url = "http://www.amazon.com/dp/$asin";
	echo "<li><b>$sku: $url</b></li>" ;
	// continue;
	// $html = get_html_curl($url);
	$html = file_get_html($url);
	// echo $html; exit;
	if (empty($html)) {
		echo '<li>Empty: file_get_html("http://www.amazon.$region/db/$asin")';
		continue;
	}
	$text = html_find($html,$html_pattern,'plaintext');
	if(empty($text)) $text = html_find($html,'div[class="wrapper USlocale"]','plaintext');
	if (empty($text)) $text = html_find($html,'div[class="section techD"]','plaintext');
	if (empty($text)) $text = html_find($html,'div[class="content pdClearfix"]','plaintext');
	if (empty($text)) $text = html_find($html,'div#prodDetails','plaintext');
	// $html->clear();
	// unset($html);
	// $text = preg_replace('/[^\w]/',' ',$text);
	echo "<br>html found: $text";
	//Best Sellers Rank                       #546,701 in Home Improvement (See top 100)                                                 #960       in&nbsp;Home Improvement &gt; Kitchen &amp; Bath Fixtures &gt; Water Filtration &amp; Softeners &gt; Under-Sink Filters
	preg_match_all('/#([\d,]+)\s+in\s+([\w\s]*)\s+\(See/',$text,$matches);
	$rank1 = str_ireplace(',','',$matches[1][0]);
	// $category1 = $matches[2][0];
	preg_match_all('/100\)\s*#(\d+)\s+in.+?([A-Z][\w\s\-&;]+)/',$text,$matches);
	// print_r($matches);
	$rank2 = str_ireplace(',','',$matches[1][0]);
	$category = str_ireplace(' &gt; ',";",$matches[2][0]);
	$category = preg_replace('/\s+/',' ',$category);
	echo "<br>html found: $rank1, $rank2, $category";
	if (strlen($rank1) > 0 && strlen($rank2) > 0) {
		$q = "insert ignore into bestsellerrank_ispring (date,hour,asin,rank1,rank2,category) values (". date('Ymd') .','. date('H') .",'$asin','$rank1','$rank2','$category')";
		if (sqlquery($q)) echo "<br>$q";
		add_checked($checked_log_dir,$asin);
	}
	// break; //test only 1 asin
}

?>