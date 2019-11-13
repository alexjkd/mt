<?php
if (isset($_GET['asin'])) {
//database connection setting
require_once '../db.php';

$mysqli = new mysqli($dbHost, $dbUser, $dbPwd, $dbName);
// Check connection
if (!$mysqli) {
    die("Connection failed: " . mysqli_connect_error());
}
if (mysqli_connect_errno()) {
printf("Connect failed: %s\n", mysqli_connect_error());
exit();
}

class Crawler extends SplObjectStorage
{
    private $domxpath;
    private $regexResults;
    private $charset;

    public function __construct($node = null, $charset = "UTF-8", $regexResults = array(), $domxpath = null)
    {
        $this->regexResults = $regexResults;
        $this->domxpath = $domxpath;
        $this->charset = $charset;
        $this->add($node);
    }

    public function __destruct()
    {
        $this->clear();
        $this->regexResults = null;
        $this->domxpath = null;
        $this->charset = null;
    }

    public function __toString()
    {
        return $this->text();
    }

    /**
     * Removes all the nodes.
     */
    public function clear()
    {
        $this->removeAll($this);
    }

    /**
     * Adds a node to the current list of nodes.
     *
     * This method uses the appropriate specialized add*() method based
     * on the type of the argument.
     *
     * @param $node A node (null|DOMNodeList|array|DOMNode)
     *
     */
    public function add($node)
    {
        if ($node instanceof DOMNodeList) {
            $this->addNodeList($node);
        } elseif (is_array($node)) {
            $this->addNodes($node);
        } elseif (is_string($node)) {
            $this->addContent($node);
        } elseif (is_object($node)) {
            $this->addNode($node);
        }
    }

    /**
     * Adds HTML/XML content.
     *
     * @param string      $content A string to parse as HTML/XML
     * @param null|string $type    The content type of the string
     *
     * @return null|void
     */
    public function addContent($content, $type = null)
    {
        if (empty($type)) {
            $type = 'text/html';
        }

        // DOM only for HTML/XML content
        if (!preg_match('/(x|ht)ml/i', $type, $matches)) {
            return null;
        }

        if ('x' === $matches[1]) {
            $this->addXmlContent($content, $this->charset);
        } else {
            $this->addHtmlContent($content, $this->charset);
        }
    }

    /**
     * Adds an HTML content to the list of nodes.
     *
     * The libxml errors are disabled when the content is parsed.
     *
     * If you want to get parsing errors, be sure to enable
     * internal errors via libxml_use_internal_errors(true)
     * and then, get the errors via libxml_get_errors(). Be
     * sure to clear errors with libxml_clear_errors() afterward.
     *
     * @param string $content The HTML content
     * @param string $charset The charset
     *
     */
    public function addHtmlContent($content, $charset = "UTF-8")
    {
        $current = libxml_use_internal_errors(true);
        $disableEntities = libxml_disable_entity_loader(true);

        $dom = new DOMDocument('1.0', $charset);
        $dom->validateOnParse = true;

        if (function_exists('mb_convert_encoding') && in_array(strtolower($charset), array_map('strtolower', mb_list_encodings()))) {
            $content = mb_convert_encoding($content, 'HTML-ENTITIES', $charset);
        }

        @$dom->loadHTML($content);

        libxml_use_internal_errors($current);
        libxml_disable_entity_loader($disableEntities);

        $this->addDocument($dom);
    }

    /**
     * Adds an XML content to the list of nodes.
     *
     * The libxml errors are disabled when the content is parsed.
     *
     * If you want to get parsing errors, be sure to enable
     * internal errors via libxml_use_internal_errors(true)
     * and then, get the errors via libxml_get_errors(). Be
     * sure to clear errors with libxml_clear_errors() afterward.
     *
     * @param string $content The XML content
     * @param string $charset The charset
     *
     */
    public function addXmlContent($content, $charset = "UTF-8")
    {
        $current = libxml_use_internal_errors(true);
        $disableEntities = libxml_disable_entity_loader(true);

        $dom = new DOMDocument('1.0', $charset);
        $dom->validateOnParse = true;

        // remove the default namespace to make XPath expressions simpler
        @$dom->loadXML(str_replace('xmlns', 'ns', $content), LIBXML_NONET);

        libxml_use_internal_errors($current);
        libxml_disable_entity_loader($disableEntities);

        $this->addDocument($dom);
    }

    /**
     * Adds a \DOMDocument to the list of nodes.
     *
     * @param \DOMDocument $dom A DOMDocument instance
     *
     */
    public function addDocument(DOMDocument $dom)
    {
        if ($dom->documentElement) {
            $this->addNode($dom->documentElement);
        }
    }

    /**
     * Adds a DOMNodeList to the list of nodes.
     *
     * @param DOMNodeList $nodes A \DOMNodeList instance
     *
     */
    public function addNodeList(DOMNodeList $nodes)
    {
        foreach($nodes as $node) {
            $this->addNode($node);
        }
    }

    /**
     * Adds an array of DOMNode instances to the list of nodes.
     *
     * @param DOMNode[] $nodes An array of DOMNode instances
     *
     * @api
     */
    public function addNodes(array $nodes)
    {
        foreach($nodes as $node) {
            $this->add($node);
        }
    }

    /**
     * Adds a \DOMNode instance to the list of nodes.
     *
     * @param \DOMNode $node A \DOMNode instance
     *
     * @api
     */
    public function addNode(DOMNode $node)
    {
        if ($node instanceof DOMDocument) {
            $this->attach($node->documentElement);
        } else {
            $this->attach($node);
        }
    }

    /**
     * Returns a node given its position in the node list.
     *
     * @param integer $position The position
     *
     * @return Crawler A new instance of the Crawler with the selected node, or an empty Crawler if it does not exist.
     *
     */
    public function eq($position)
    {
        if(!empty($this->regexResults))
        {
            foreach ($this->regexResults as $i => $text) {
                if ($i == $position) {
                    return new static(null, null, array(1 => $this->regexResults[$i]));
                }
            }

            return new static(null, null);
        }
        else
        {
            foreach ($this as $i => $node) {
                if ($i == $position) {
                    return new static($node, $this->charset, null, $this->domxpath);
                }
            }

            return new static(null);
        }
    }

    /**
     * Calls an anonymous function on each node of the list.
     *
     * The anonymous function receives the position and the node as arguments.
     *
     * Example:
     *
     *     $crawler->find('h1')->each(function ($node, $i)
     *     {
     *       return $node->nodeValue;
     *     });
     *
     * @param Closure $closure An anonymous function
     *
     * @return array An array of values returned by the anonymous function
     *
     */
    public function each(Closure $closure)
    {
        $data = array();
        foreach ($this as $node) {
            $data[] = $closure(new static($node, $this->charset, null, $this->domxpath));
        }

        return $data;
    }

    /**
     * Returns the first node of the current selection
     *
     * @return Crawler A Crawler instance with the first selected node
     *
     */
    public function first()
    {
        return $this->eq(0);
    }

    /**
     * Returns the last node of the current selection
     *
     * @return Crawler A Crawler instance with the last selected node
     *
     */
    public function last()
    {
        if(!empty($this->regexResults)) return $this->eq(count($this->regexResults) - 1);
        return $this->eq(count($this) - 1);
    }

    /**
     * Returns the attribute value of the first node of the list.
     *
     * @param string $attribute The attribute name
     *
     * @return string The attribute value or empty string if node list is empty
     *
     */
    public function attr($attribute)
    {
        if (!count($this) || empty($this->getNode(0)->attributes)) return "";

        return $this->getNode(0)->getAttribute($attribute);
    }

    /**
     * Extracts information from the list of nodes.
     *
     * You can extract attributes or/and the node value (text).
     *
     * Example:
     *
     * $crawler->filter('h1 a')->extract(array('h1::text', 'a::href'));
     *
     * @param array $attributes An array of attributes
     *
     * @return array An array of extracted values
     *
     * @api
     */
    public function extract($attributes)
    {
        $attributes = (array) $attributes;

        $data = array();
        $elements = array();
        foreach ($this as $node) {
            $nodeName = $node->nodeName;

            foreach ($attributes as $attribute) {
                $tagName = $this->regex("/(.*?)\:/", $attribute)->text();
                $attributeName = $this->regex("/\:\:(.*)/", $attribute)->text();

                if($tagName === $nodeName) {
                    if("text" === $attributeName)
                    {
                        $text = Crawler::strip($node->nodeValue);
                    } else {
                        $text = $node->getAttribute($attributeName);
                    }

                    $elements[$attributeName] = $text;
                }
            }

            if(count($elements) == count($attributes)) {
                $data[] = $elements;
                unset($elements);
            }
        }

        return $data;
    }

    /**
     * Returns the node value of the first node of the list.
     *
     * @return string The node value or empty string if node list is empty
     *
     */
    public function text()
    {
        if(!empty($this->regexResults))
        {
            return Crawler::strip($this->regexResults[1]);
        }
        else
        {
            if (!count($this)) return "";
            return Crawler::strip($this->getNode(0)->nodeValue);
        }
    }

    function textAll($glue = ". ")
    {
        $textAll = "";
        foreach ($this as $node) {
            $textAll .= Crawler::strip($node->nodeValue) . $glue;
        }

        return trim(preg_replace("/$glue$/", "", $textAll));
    }

    public function html()
    {
        if (!count($this)) return "";
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->appendChild($dom->importNode($this->getNode(0), true));
        return $dom->saveHTML();
    }

    /**
     * Returns the extracted number in the node value of the first node of the list.
     *
     * @return string The extracted number in node value
     *
     */
    public function number()
    {
        if(!empty($this->regexResults)) return Crawler::toNumber($this->regexResults[1]);
        return Crawler::toNumber($this->text());
    }

    public function price()
    {
        $text = $this->text();
        return preg_replace("/[^\d\.]/", "", $text);
    }

    public function regex($reg, $text = "")
    {
        if(!empty($text))
        {
            if(preg_match($reg, $text, $match)) return new static(null, null, $match);
        }
        else
        {
            if(preg_match($reg, $this->text(), $match)) {
                return new static(null, null, $match);
            }
        }

        return new static();
    }

    /**
     * Find the list of nodes with an XPath expression.
     *
     * @param string $xpath An XPath expression
     *
     * @return Crawler A new instance of Crawler with the matched list of nodes
     *
     */
    public function find($xpath)
    {
        if (!count($this)) return new static(null);

        if(empty($this->domxpath)) {
            $this->domxpath = new DOMXPath($this->getNode(0)->ownerDocument);
        }

        $xpath = ".//" . str_replace("| ", "| .//", $xpath);
        return new static($this->domxpath->query($xpath, $this->getNode(0)),
            $this->charset,
            null,
            $this->domxpath);
    }

    /**
     * Converts string for XPath expressions.
     *
     * Escaped characters are: quotes (") and apostrophe (').
     *
     *  Examples:
     *  <code>
     *     echo Crawler::xpathLiteral('foo " bar');
     *     //prints 'foo " bar'
     *
     *     echo Crawler::xpathLiteral("foo ' bar");
     *     //prints "foo ' bar"
     *
     *     echo Crawler::xpathLiteral('a\'b"c');
     *     //prints concat('a', "'", 'b"c')
     *  </code>
     *
     * @param string $s String to be escaped
     *
     * @return string Converted string
     *
     */
    public static function xpathLiteral($s)
    {
        if (false === strpos($s, "'")) {
            return sprintf("'%s'", $s);
        }

        if (false === strpos($s, '"')) {
            return sprintf('"%s"', $s);
        }

        $string = $s;
        $parts = array();
        while (true) {
            if (false !== $pos = strpos($string, "'")) {
                $parts[] = sprintf("'%s'", substr($string, 0, $pos));
                $parts[] = "\"'\"";
                $string = substr($string, $pos + 1);
            } else {
                $parts[] = "'$string'";
                break;
            }
        }

        return sprintf("concat(%s)", implode($parts, ', '));
    }

    public static function cleanHtml($html)
    {
        $tidy_config = array(
            "clean" => true,
            "output-xhtml" => true,
            "wrap" => 0,
        );

        $tidy = tidy_parse_string($html, $tidy_config);
        $tidy->cleanRepair();
        return $tidy->value;
    }

    /**
     * Example convert 500.00 to 500 (500,00 to 500)
     * and 500.000 to 500000 (500,000 to 500000)
     *
     * @param string $str
     * @return integer
     */
    public static function toNumber($str)
    {
        $value = "";
        $str = preg_replace("/(,\d{2})$|(\.\d{2})$|\s|\+\/-/", "", $str);
        $str = preg_replace("/,(\d{3})|\.(\d{3})/",  "$1$2", $str);
        if(preg_match("/(-?\d+)/", $str, $match)) $value = intval($match[1]);

        return $value;
    }

    public static function strip($str)
    {
        $str = str_replace(chr(194) . chr(160), " ", $str);
        $str = preg_replace("/\s+|\n/", " ", $str);
        return trim($str);
    }

    public static function test($obj)
    {
        echo "<pre>";
        print_r($obj);
        echo "</pre>";
        exit;
    }

    public static function generateId($text)
    {
        $str = crc32($text);
        $id = sprintf("%u", $str);
        return $id;
    }

    private function getNode($position)
    {
        foreach ($this as $i => $node) {
            if ($i == $position) {
                return $node;
            }
        }

        return null;
    }
}
class Client
{
    private $proxyList;
    private $proxyUserPwd;
    private $requestHandler = null;
    private $minDelay = 0;
    private $maxDelay = 0;
    private $curlTimeout = 80;
    private $cookieEnabled = false;
    private $headers = array(
        "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8",
        //"User-Agent: Googlebot/2.1 (http://www.googlebot.com/bot.html)",
        "User-Agent: Mozilla/5.0 (Windows NT 6.3; rv:36.0) Gecko/20100101 Firefox/36.0",
        "Accept-Language: en-us,en;q=0.5",
        "Accept-Charset: utf-8;q=0.7,*;q=0.7",
        "Connection: keep-alive"
    );

    public function __construct($proxyList = array(), $proxyUserPwd = "")
    {
        $this->proxyList = $proxyList;
        $this->proxyUserPwd = $proxyUserPwd;
        $this->requestHandler = curl_init();
    }

    public function __destruct()
    {
        // echo "\n\nClient::__destruct function called: cURL handler is closed!!";
        curl_close($this->requestHandler);
        $this->requestHandler = null;
    }

    public function delay($min, $max)
    {
        $this->minDelay = $min;
        $this->maxDelay = $max;
    }

    public function enableCookies()
    {
        $this->cookieEnabled = true;
    }

    public function setProxyInfo($proxyList, $proxyUserPwd) {
        $this->proxyList = $proxyList;
        $this->proxyUserPwd = $proxyUserPwd;
    }

    public function setCurlTimeout($timeout)
    {
        $this->curlTimeout = $timeout;
    }

    public function setXMLHTTPRequest($value)
    {
        if($value) $this->headers[] = "X-Requested-With: XMLHttpRequest";
    }

    public function setHeaders($headers)
    {
        $this->headers = $headers;
    }

    public function get($url, $options = null)
    {
        sleep(rand($this->minDelay, $this->maxDelay));

        curl_setopt($this->requestHandler, CURLOPT_URL, str_replace(" ", "%20", $url));
        curl_setopt($this->requestHandler, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->requestHandler, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($this->requestHandler, CURLOPT_MAXREDIRS, 5);
        curl_setopt($this->requestHandler, CURLOPT_FRESH_CONNECT, true);
        curl_setopt($this->requestHandler, CURLOPT_AUTOREFERER, true);
        curl_setopt($this->requestHandler, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($this->requestHandler, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($this->requestHandler, CURLOPT_TIMEOUT, $this->curlTimeout);
        curl_setopt($this->requestHandler, CURLOPT_ENCODING, 'gzip,deflate');
        curl_setopt($this->requestHandler, CURLOPT_HTTPHEADER, $this->headers);
        curl_setopt($this->requestHandler, CURLOPT_REFERER, 'http://www.google.com');

        if($this->cookieEnabled)
        {
            curl_setopt($this->requestHandler, CURLOPT_COOKIEFILE, "cookie.txt");
            curl_setopt($this->requestHandler, CURLOPT_COOKIEJAR, "cookie.txt");
        }

        if(!empty($options)) curl_setopt_array($this->requestHandler, $options);

        if(!empty($this->proxyList))
        {
            $proxy = $this->proxyList[array_rand($this->proxyList)];
            curl_setopt($this->requestHandler, CURLOPT_PROXY, $proxy);
            if(!empty($this->proxyUserPwd)) curl_setopt($this->requestHandler, CURLOPT_PROXYUSERPWD, $this->proxyUserPwd);
        }

        $response = curl_exec($this->requestHandler);

        return $response;
    }

    public function post($url, $postData = null, $options = null)
    {
        sleep(rand($this->minDelay, $this->maxDelay));

        curl_setopt($this->requestHandler, CURLOPT_URL, str_replace(" ", "%20", $url));
        curl_setopt($this->requestHandler, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->requestHandler, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($this->requestHandler, CURLOPT_MAXREDIRS, 5);
        curl_setopt($this->requestHandler, CURLOPT_FRESH_CONNECT, true);
        curl_setopt($this->requestHandler, CURLOPT_AUTOREFERER, true);
        curl_setopt($this->requestHandler, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($this->requestHandler, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($this->requestHandler, CURLOPT_TIMEOUT, 40);
        curl_setopt($this->requestHandler, CURLOPT_ENCODING, 'gzip,deflate');
        curl_setopt($this->requestHandler, CURLOPT_HTTPHEADER, $this->headers);
        curl_setopt($this->requestHandler, CURLOPT_REFERER, 'http://www.google.com');
        if($this->cookieEnabled)
        {
            curl_setopt($this->requestHandler, CURLOPT_COOKIEFILE, "cookie.txt");
            curl_setopt($this->requestHandler, CURLOPT_COOKIEJAR, "cookie.txt");
        }

        if(!empty($postData))
        {
            curl_setopt($this->requestHandler, CURLOPT_POST, true);
            curl_setopt($this->requestHandler, CURLOPT_POSTFIELDS, $postData);
        }

        if(!empty($options)) curl_setopt_array($this->requestHandler, $options);

        if(!empty($this->proxyList))
        {
            $proxy = $this->proxyList[array_rand($this->proxyList)];
            curl_setopt($this->requestHandler, CURLOPT_PROXY, $proxy);
            if(!empty($this->proxyUserPwd)) curl_setopt($this->requestHandler, CURLOPT_PROXYUSERPWD, $this->proxyUserPwd);
        }

        return curl_exec($this->requestHandler);
    }

    public function postJSON($url, $postData = null)
    {
        sleep(rand($this->minDelay, $this->maxDelay));

        curl_setopt($this->requestHandler, CURLOPT_URL, str_replace(" ", "%20", $url));
        curl_setopt($this->requestHandler, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->requestHandler, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($this->requestHandler, CURLOPT_MAXREDIRS, 5);
        curl_setopt($this->requestHandler, CURLOPT_FRESH_CONNECT, true);
        curl_setopt($this->requestHandler, CURLOPT_AUTOREFERER, true);
        curl_setopt($this->requestHandler, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($this->requestHandler, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($this->requestHandler, CURLOPT_TIMEOUT, 40);
        curl_setopt($this->requestHandler, CURLOPT_ENCODING, 'gzip,deflate');
        curl_setopt($this->requestHandler, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen($postData))
        );
        curl_setopt($this->requestHandler, CURLOPT_REFERER, 'http://www.google.com');
        if($this->cookieEnabled)
        {
            curl_setopt($this->requestHandler, CURLOPT_COOKIEFILE, "cookie.txt");
            curl_setopt($this->requestHandler, CURLOPT_COOKIEJAR, "cookie.txt");
        }

        if(!empty($postData))
        {
            curl_setopt($this->requestHandler, CURLOPT_POST, true);
            curl_setopt($this->requestHandler, CURLOPT_POSTFIELDS, $postData);
        }

        if(!empty($options)) curl_setopt_array($this->requestHandler, $options);

        if(!empty($this->proxyList))
        {
            $proxy = $this->proxyList[array_rand($this->proxyList)];
            curl_setopt($this->requestHandler, CURLOPT_PROXY, $proxy);
            if(!empty($this->proxyUserPwd)) curl_setopt($this->requestHandler, CURLOPT_PROXYUSERPWD, $this->proxyUserPwd);
        }

        return curl_exec($this->requestHandler);
    }

    public function getRedirectUrl()
    {
        return curl_getinfo($this->requestHandler, CURLINFO_EFFECTIVE_URL);
    }

    public function getHttpCode()
    {
        return curl_getinfo($this->requestHandler, CURLINFO_HTTP_CODE);
    }
}
$client = new Client();
$proxyList = array();
$proxyUserPwd = "";
//download proxies server IP from below address
$proxyLines = explode("\n", $client->get("https://docs.google.com/spreadsheets/d/e/2PACX-1vR2gY22xgcaR4JUr3naK5nXbFzw3pL_Ogn4msFRDGfVA8nILfEs-BOdxDRt2Jvhx9Yz31eAF8IfpjBn/pub?gid=308001853&single=true&output=tsv"));
foreach($proxyLines as $line) {
    if(preg_match("/([\d\.]+)\t(\d+)\t(.*)\t(.*)/", $line, $match)) {
        $proxyList[] = $match[1] . ":" . $match[2];
        $proxyUserPwd = trim($match[3] . ":" . $match[4]);
    }
}

if(!empty($proxyList)) {
    $client->setProxyInfo($proxyList, $proxyUserPwd);
}

$asin = $_GET['asin'];
$startUrl = "https://www.amazon.com/gp/offer-listing/".$asin."/ref=dp_olp_all_mbc?ie=UTF8&condition=new";
$pageIndex = 1;
    $html = $client->get($startUrl . $pageIndex);
    $pageCounter += 1;    
    $crawler = new Crawler($html);
    $seller_data = $crawler->find("div[contains(@class, 'a-row a-spacing-mini olpOffer')]")->each(function($node) use($pageCounter) {
        $data = array();
        $data['condition'] = $node->find("span[@class = 'a-size-medium olpCondition a-text-bold']")->text();
        $data['seller_info'] = $node->find("span[@class = 'a-size-medium a-text-bold']")->find("a")->attr("href");       
        $condition = strtolower($data['condition']);
        $url = 'https://www.amazon.com'.$data['seller_info'];
        $parts = parse_url($url);
        $query = $parts['query'];
        parse_str($parts['query'], $query);
        $seller = $query['seller'];
        $asin = $query['asin'];
        if($seller != 'A2AB4EJKHRN74A' && $condition == 'new' && !empty($seller)){
           echo "Illegal Seller of ".$asin ." url is: ". $url;
           echo "<br>";
        }
    }); 
}
?>