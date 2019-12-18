<html><head><meta http-equiv="Content-Type" content="text/html; charset=windows-1252">
<title>Amazon BSR Monitor</title>
<?php
//~ set_time_limit(290);
chdir(dirname(__FILE__));
ini_set("error_log", basename(__FILE__,'php') . 'error.log');
//~ include_once(__DIR__ .'../lib/functions.php');
require('D:\IT\czyusa\MT\lib\simple_html_dom.php');
define('EMAIL_RECEPIENTS','John@iSpringFilter.com');
define('LOGFILE','bsrmon.log.html');
if (filesize(LOGFILE) > 1234567) rename(LOGFILE,'old.'. LOGFILE);
$errorMsg = '';
//~ $test = isset($_GET['test']) ? $_GET['test'] : 0;

$url='https://www.amazon.com/gp/bestsellers/hi/13397611/ref=pd_zg_hrsr_hi_2_4_last';
$html=get_html_curl($url);
//~ $html=file_get_html('webpage.html');
// ref=zg_bs_13397611_1  /Woder-10K-GenII-Capacity-Connect-Filtration/dp/B0144MFPOA/ref=zg_bs_13397611_2
$bsr_url=html_find($html,'a[href*=zg_bs_13397611_1\b]','href',1);
echo $bsr_url ."\n";
//~ foreach(explode('<li>',$a_listing_urls) as $listing_url) {
	//~ echo $listing_url ."\n";
//~ }

$url='https://www.amazon.com/Best-Sellers-Home-Improvement-Water-Softeners/zgbs/hi/6810592011/ref=zg_bs_nav_hi_4_13397611';
$html=get_html_curl($url);
$bsr_url=html_find($html,'a[href*=zg_bs_6810592011_1\b]','href');
echo $bsr_url ."\n";

function html_find($html, $object, $attributes='',$all=0){
	$value = $values = '';
	// if(preg_match('/,/',$attributes)) $all = 1;
	$attribute_array;
	$finds = $html->find($object);
	if ($attributes <> '') {
		foreach($finds as $find){
			$attribute_array = explode(',',$attributes); $i = 0;
			foreach($attribute_array as $attr) {
				$value = $find->$attr;
				if($value =='') continue;
				$values .= $value . "<li>";
				if($all==0) {
					$values = $value;
					break;
				}
			}
			$i++;
			if ($i > 24) break;
		}
		return $values;
	}else{
		return $finds;
	}
}

function get_html_curl($url) {
		/*if (file_exists(__DIR__ . '/user_agent_strings.txt')) {
			$user_agent_strings = file_get_contents(__DIR__ . '/user_agent_strings.txt');
			$user_agent_array = explode("\n",$user_agent_strings) ;
			$user_agent_string = $user_agent_array[rand(1,count($user_agent_array))];
		} else {*/
			$user_agent_string = 'Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; WOW64; Trident/5.0; .NET CLR 3.5.30729; .NET CLR 3.0.30729; .NET CLR 2.0.50727; Media Center PC 6.0)';
		//~ }
		//~ echo "user_agent_string = $user_agent_string <br>";
    $ch = curl_init($url);
		// if (file_exists('webpage.html')) unlink('webpage.html');
    $fp = fopen("webpage.html", "w+");
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_FILE, $fp);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    // curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1)');
    // curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 5.1; rv:44.0) Gecko/20100101 Firefox/44.0');
    curl_setopt($ch, CURLOPT_USERAGENT, $user_agent_string);
    curl_exec($ch);
    curl_close($ch);
    fclose($fp);
    $html = file_get_contents("webpage.html");
    $html = str_get_html($html);
    return $html;
}


?>
</html>