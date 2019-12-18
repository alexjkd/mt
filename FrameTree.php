<head><meta http-equiv="Content-Type" content="text/html; charset=windows-1252">
<title>Market Tracker</title>
	<link href="./lib/sortable/sortable_table.css" media="screen" rel="stylesheet" type="text/css" />
	<script type="text/javascript" language="javascript" src="./lib/sortable/sorttable.js"></script>
	<link href="./lib/TableFilter/filtergrid.css" media="screen" rel="stylesheet" type="text/css" />
	<script type="text/javascript" language="javascript" src="./lib/TableFilter/tablefilter.js"></script>

	<link href="./lib/jquery-ui-1.10.4.css" media="screen" rel="stylesheet" type="text/css" />
	<script type="text/javascript" language="javascript" src="./lib/jquery.js"></script>
	<script type="text/javascript" language="javascript" src="./lib/jquery-ui-1.10.4.min.js"></script>
	<script type="text/javascript" language="javascript" src="./lib/jquery.dataTables.js"></script>

	<script type="text/javascript" language="javascript" src="https://code.jquery.com/jquery-1.8.0.min.js"></script>
	<script type="text/javascript" language="javascript" src="lib/EasyTree/jquery.easytree.js"></script>
	<link rel="stylesheet" href="lib/EasyTree/skin-xp/ui.easytree.css" type="text/css" media="screen, projection"/>
	<style>
		img {width: 32px;}
	</style>
</head>
<?php
include_once('lib/functions.php');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
$sPublishedGoogleSheetTsv = file('https://docs.google.com/spreadsheets/d/e/2PACX-1vR2gY22xgcaR4JUr3naK5nXbFzw3pL_Ogn4msFRDGfVA8nILfEs-BOdxDRt2Jvhx9Yz31eAF8IfpjBn/pub?gid=0&single=true&output=tsv'); //mwsTsv: IMPORTRANGE 4-LA "1-Tier!B1:F300"
if (isset($_GET['action']) and $_GET['action']='syncGoogleTsv') {SyncGoogleTsv($sPublishedGoogleSheetTsv);exit;}
//~ $aAssignees = GetAssigneeArray();
//~ echo '<pre>';print_r($aAssignees);echo '</pre>';exit; exit;
?>
<H3>Market Tracker</h3>

<div id="tree_menu">
    <ul>
        <li class="isFolder isExpanded">Amazon.COM KPI
            <ul>
                <li>&nbsp;<a target="FrameContent" href="goals.php?period=14">Goals</a></li>
                <li>&nbsp;<a target="FrameContent" href="alerts-tablefilter.php">Alerts</a></li>
                <li>&nbsp;<a target="FrameContent" href="./scraper/review.php">Reviews</a></li>
                <li><a target="FrameContent" href="dashboard.php?list=top30">US BSR</a></li>
                <li><a target="FrameContent" target="_blank" href="editor_scTu4UTrYBGA4arkTBPvfA7Uu5cn14jfhMcMvQ91i8.php?file=amazon.us_asin_sku_competitors.20191111.txt">Edit US BSR Groups</a></li>
                <li><a target="FrameContent" href="FrameTree.php?action=syncGoogleTsv">Sync Google Tsv</a></li>
                <li class="isFolder" title="SKUs">SKUs
									<ul>
									<?php
										$q = "SELECT DISTINCT salesrank,tier,sku,asin,comp FROM GoogleSheet4LA1Tier WHERE site LIKE '%amazon.com%' ORDER BY salesrank,tier,sku;";
										foreach(sqlquery($q) as $r) {
											$sr=$r['salesrank'];
											$tier=$r['tier'];
											$sku=$r['sku'];
											$asin=$r['asin'];
											$comp=$r['comp'];
											//$assignee=$r['assignee'];
											echo "<li><a target=\"FrameContent\" href=\"dashboard.php?region=us&period=15+days&items=$asin,ispr.$sku\">T$tier $sku</a></li>";
										}
									?>
									</ul>
								</li>
                <li class="isFolder isExpanded" title="SKUs by Owner">SKUs by Tier
                    <ul>
                        <?php
							for($Tier=1;$Tier<=4;$Tier++) {
								echo '<li><a target="FrameContent" title="Daily average of last 7 days" href="dashboard.php?period=7days&tier='. $Tier .'">Tier '. $Tier ."</a></li>";
							}
							?>
                    </ul>
                </li>
                <li class="isFolder isExpanded" title="SKUs by Owner">SKUs by Assignee
                    <ul>
										<?php
										$aAssignees=explode(',','Sonny,Rosa,Wency,Li,Yile,John');
										foreach($aAssignees as $assignee) {
											echo '<li><a target="FrameContent" title="'. $assignee .': Daily average of last 7 days" href="dashboard.php?period=7days&assignee='. $assignee .'">'. ucwords($assignee) ."</a></li>";
										}
										?>
                    </ul>
                </li>
        </li>
        <li><a target="FrameContent" href="dashboard_new.php">US BSR(weekly)</a></li>
        <!-- 2016-08-17 http://czyusa.com/editor_scTu4UTrYBGA4arkTBPvfA7Uu5cn14jfhMcMvQ91i8.php?file=amazon_asin_sku_competitors.txt, homeserver F:\backup\d\it\czyusa\mt\uploads\runBSR.cmd hourly task creates and uploads BSR.csv to mt/uploads/ then processed by import.php -->
        <li>&nbsp;<a target="FrameContent" href="http://czyusa.com/editor_asin_sku.php">ASIN_SKU</a></li>
        <li>&nbsp;<a target="FrameContent" href="http://czyusa.com/mt/item/asin_sku_title.php?table=1">ASIN_SKU_TITLE</a></li>
        <li><a target="FrameContent" href="uploads/amazon_kw_rank_report.php">Keywords Search Rank (KSR)</a></li>
        <!--  -->
        <li><a target="_blank" href="/editor_scTu4UTrYBGA4arkTBPvfA7Uu5cn14jfhMcMvQ91i8.php?file=bestwaterfilters/amazon_link_list.txt">LinkList Editor</a></li>
        <!--  -->
        <li><a target="_blank" href="rank/linkgroups_v2_clicks.php?action=LidSummary&period=Weekly&site=amazon">Traffics</a></li>
        <!--  -->
        <li><a target="_blank" href="rank/linkgroups_v2_clicks.php?action=OidSummary&period=Weekly">Gigs Performance</a></li>
        <li><a target="_blank" href="review/review_rating_target_report.php">Reviews Target</a></li>
        <!-- 2016-08-17 http://czyusa.com/mt/review/review_rating_target_import.php -->
        <li><a target="_blank" href="review/amazon_ispring_reviews.index.php?rows=50">iSpring Reviews</a></li>
        <!-- 2016-08-17 mt/review/amazon_ispring_reviews.import.php -->
        <li><a target="_blank" href="review/amazon_comp_reviews.index.php">Competitors Reviews</a></li>
        <li><a target="_blank" href="rank/bestsellerrank.comp.php">Competitors Best Sellers</a></li>
        <li><a target="_blank" href="uploads/offermon.import.log.html">Offer Monitor</a></li>
        </ul>
        </li>
        <li class="isFolder isExpanded" title="Bookmarks">AmazonCAN KPI
            <ul>
                <li><a target="FrameContent" href="dashboard.php?region=ca&period=7+days">CAN BSR</a></li>
                <li>&nbsp;<a target="FrameContent" href="http://czyusa.com/editor_scTu4UTrYBGA4arkTBPvfA7Uu5cn14jfhMcMvQ91i8.php?file=amazon.ca_asin_sku_competitors.txt">CAN BSR Groups</a></li>
            </ul>
        </li>
        <li class="isFolder isExpanded" title="Bookmarks">AmazonUK KPI
            <ul>
                <li><a target="FrameContent" href="dashboard.php?region=uk&period=7+days">UK BSR</a></li>
                <li>&nbsp;<a target="FrameContent" href="http://czyusa.com/editor_scTu4UTrYBGA4arkTBPvfA7Uu5cn14jfhMcMvQ91i8.php?file=amazon.uk_asin_sku_competitors.txt">UK BSR Groups</a></li>
                <li><a target="_blank" href="http://160.153.225.52/editor_scTu4UTrYBGA4arkTBPvfA7Uu5cn14jfhMcMvQ91i8.php?file=bwf/amazon_link_list.txt">EU LinkList Editor</a></li>
                <li><a target="_blank" href="http://160.153.225.52/rank/linkgroups_v2_clicks.php?action=LidSummary&period=Weekly&site=amazon">Traffics</a></li>
                <!--  -->
                <li><a target="_blank" href="http://160.153.225.52/rank/linkgroups_v2_clicks.php?action=OidSummary&period=Weekly">Gigs Performance</a></li>
            </ul>
        </li>
        <li class="isFolder" title="Bookmarks">Amazon Best Sellers In Departments
            <ul>
                <?php
							include_once(__DIR__ .'/lib/functions.php');
							$tsv2Array = tsv2ToArray('rank/amazon.bestsellerrank.categories.tsv');
							$c=0;
							foreach($tsv2Array as $urlPattern => $category) {
								if (strlen($category)<5) continue;
								$c++;
								preg_match('-/hi/(\d+)/ref-',$urlPattern,$matches);
								$idCategory = $matches[1];
								$tableName = 'abs_'. $idCategory;
								$category = preg_replace('/[\W]+/','_',$category);
								echo "<li><a target=FrameContent href=rank/bestsellerrank.amazon.php?asin=B00&tablename=$tableName&category=$category>$c $category </a></li>";
							}
							?>
                    <li><a target="_blank" href="rank/bestsellerrank.amazon.import.php">Manual Import</a></li>
            </ul>
        </li>
        <li class="isFolder isExpanded" title="Bookmarks">Amazon Keywords Search Ranks
            <ul>
                <?php
							$tsvFileName =__DIR__ . '/../kw.tsv';
							if (!is_file($tsvFileName)) {
								echo "$tsvFileName Not found";
							} else {
								echo '<li>&nbsp;<a target=_blank href="/editor_scTu4UTrYBGA4arkTBPvfA7Uu5cn14jfhMcMvQ91i8.php?file='. $tsvFileName .'">Edit keywords</a></li>';
								$tsv = preg_replace("/[\n\r]/","\r",file_get_contents($tsvFileName));
								//~ $tsv = preg_replace("/\r\r/","\r",$tsv);
								$aKws = explode("\r",$tsv);
								//~ asort($aKws);
							/*
							$qr = sqlquery("SELECT DISTINCT str as link FROM seolinkgroups_v2_clicks_hash WHERE str LIKE '%amazon%' ORDER BY str;");
							$re = "/&keywords=(.+)$/";
							foreach($qr as $r) {
								preg_match($re,$r['link'],$matches);
								$kw = strtolower(trim($matches[1]));
								if ( ($site <> '' or $site <> 'all') and (strlen($kw) < 2 or preg_match('/&|-_-|#/',$kw) or strpos($kws,$kw) > -1)) continue;
								$kws .= $kw ."\t";
							}
							$aKws = explode("\t", $kws); */
								foreach($aKws as $kw) {
									$kw = trim($kw);
									if (strlen($kw) < 2) echo '<br>';
									echo '<li>&nbsp;<a target="FrameContent" href="rank/amazon_kw_rank_index.php?kw='. str_ireplace(' ','_',$kw) .'">'. $kw .'</a></li>';
								}
							}
							?>
            </ul>
        </li>
        <li class="isFolder" title="Bookmarks">HomeDepot Categories
            <ul>
                <?php
							$tsvFileName =__DIR__ . '/homedepot/hd_kw_cat.v2.tsv';
							if (!is_file($tsvFileName))  {
								"$tsvFileName Not found";
							} else {
								$tsv = file_get_contents($tsvFileName);
								$q = "select id,type,string,catid from hd_kw_cat_v2 where type = 'cat' order by id,string ";
								ListTables($q);
							}
							function ListTables($q) {
								global $tsv;
								$rows = sqlquery($q);
								if (empty($rows)) return $q .' returns empty';
								// print_r($rows);exit;
								$parent_cat = $child_cat = '';
								$i=0;
								foreach($rows as $row) {
									$i++;
									$kid = $row['id'];
									$type = $row['type'];
									$catid = $row['catid'];
									$str = ucwords(str_ireplace('_',' ',str_ireplace('Kitchen_Water_Dispensers_Filters_','',$row['string'])));
									if (stripos($tsv,$str) >1 or stripos($tsv,str_ireplace(' ','-',$str)) >1) echo "<li><a target=FrameContent href=homedepot/index.php?kid=$kid>$i $str</a></li>";
								}
							}

						?>
            </ul>
        </li>

        <li class="isFolder isExpanded" title="Bookmarks">HomeDepot Keywords
            <ul>
                <li>Linkgroups&nbsp;<a target="FrameContent" href="rank/linkgroupclicks.php?action=lidsummary&str=HomeDepot">Clicks</a> &nbsp;
                    <a target="_blank" href="/bestwaterfilters/linkgroupseditor.php">Editor</a></li>
                <?php
							$q = "select id,type,string,catid from hd_kw_cat_v2 where type = 'kw' order by string";
							ListTables($q);
						?>
            </ul>
        </li>

        <li class="isFolder isExpanded" title="Bookmarks">eBay Keywords
            <ul>
                <li><a target="_blank" href="/editor_scTu4UTrYBGA4arkTBPvfA7Uu5cn14jfhMcMvQ91i8.php?file=mt/ebay/kw_cat.tsv">Edit ebay/kw_cat.tsv</a></li>
                <li><a target="FrameContent" href="rank/linkgroupclicks.php?action=lidsummary&str=eBay">Linkgroups Clicks</a></li>
                <li><a target="_blank" href="ebay/import.php">Manual Import</a></li>
                <li><a target=_blank href="http://ebay.promotionexpert.com/b2capp/report">ebay PR</a></li>
                <?php
						$q = "SHOW TABLES LIKE 'ebay_kw_%'";
						$rows = sqlquery($q); $i=0;
						foreach($rows as $row) {
							$i++;
							$tn = $row['Tables_in_markettracker (ebay_kw_%)'];
							$kw = str_ireplace('ebay_kw_','',$tn);
							echo '<li><a target="FrameContent" href="ebay/index.php?kw='. $kw .'">'. $i .' '. str_ireplace('-',' ',$kw) .'</a></li>';
						}
						?>
            </ul>
        </li>

        <li class="isFolder isExpanded" title="Bookmarks">Sears Keywords
            <ul>
                <li><a target="FrameContent" href="rank/linkgroupclicks_v2.php?action=LidSummary&kw=Sears">Linkgroups Clicks</a></li>
                <?php
							$q = "SHOW TABLES LIKE 'sears_%'";
							$rows = sqlquery($q); $i=0; $lis='';
							foreach($rows as $row) {
								$tn = $row['Tables_in_markettracker (sears_%)'];
								if (strlen($tn) < 7) continue;
								$kw = str_ireplace('sears_','',$tn); $i++;
								$lis .= '<li><a target="FrameContent" href="sears/sears_kw_rank_index.php?kw='. $kw .'">'. $i .' '. $kw .'</a></li>';
							}
							echo $lis;
						?>
            </ul>
        </li>
        <li class="isFolder isExpanded" title="Bookmarks">OverStock Keywords
            <ul>
                <li><a target="FrameContent" href="rank/linkgroupclicks.php?action=lidsummary&str=overstock">Linkgroups Clicks</a></li>
                <?php
							$q = "SHOW TABLES LIKE 'overstock_%'";
							$rows = sqlquery($q); $i=0;
							foreach($rows as $row) {
								$i++;
								$tn = $row['Tables_in_markettracker (overstock_%)'];
								$kw = str_ireplace('overstock_','',$tn);
								echo '<li><a target="FrameContent" href="overstock/overstock_kw_rank_index.php?kw='. $kw .'">'. $i .' '. str_ireplace('-',' ',$kw) .'</a></li>';
							}
						?>
            </ul>
        </li>
        <li class="isFolder" title="Editors">Editors
            <ul>
								<li>&nbsp;<a target="FrameContent" href="http://czyusa.com/editor_scTu4UTrYBGA4arkTBPvfA7Uu5cn14jfhMcMvQ91i8.php?file=amazon.ca_asin_sku_competitors.txt">CA BSR Groups</a></li>
                <li><a target="_blank" href="/bestwaterfilters/linkgroupseditor.php">Linkgroups</a></li>
                <li><a target="_blank" href="/bestwaterfilters/linkgroupseditor_v2.php">Linkgroups_v2</a></li>
                <li><a target="_blank" href="/mt/rank/ttorders.php">TTOrders</a></li>
                <li><a target="_blank" href="/editor_scTu4UTrYBGA4arkTBPvfA7Uu5cn14jfhMcMvQ91i8.php?file=mt/newegg/kw_cat.tsv">Neweggs kw_cat.tsv</a></li>
                <li><a target="_blank" href="lib/amazon_items_editor.php">Amazon Items</a></li>
            </ul>
        </li>
    </ul>
</div>
	<script>
    $('#tree_menu').easytree();
	</script>

<?php
function SyncGoogleTsv($sPublishedGoogleSheetTsv){
	$sqlFields='salesrank,site,asin,sku,tier,owner,comp,kw,goal,recentNegativeReviewsUrl';
	foreach($sPublishedGoogleSheetTsv as $sTsvRow)	{
		if (preg_match('-URL\t-',$sTsvRow)) continue;
		if (preg_match('-(.+?\t){8}-',$sTsvRow) == FALSE) {echo "<li>Error: GoogleSheet4LA1Tier row <b>$sTsvRow</b> NOT like: [0] => SR    [1] => URL    [2] => Model    [3] => Tier    [4] => Assignee    [5] => Top3Comp    [6] => Top3Keywords    [7] => QuarterBSRGoal    [8] => RecentNegativeReviews"; continue;}
		$aTsvRow = explode("\t", $sTsvRow);
		array_walk($aTsvRow,'trim');
		list($salesrank,$url,$sku,$tier,$comp,$kw,$assignee,$goal,$recentNegativeReviewsUrl)= $aTsvRow;
		//~ echo '<pre>';print_r($aTsvRow);echo '<pre>';
		if (preg_match('-https?://([\w\.]+?)(/[\w\-]+)?/dp/(B0\w{8})-',$url,$m) == FALSE) {echo "<li>Error: URL '<red> $url</red>' in row <b>$sTsvRow</b> NOT in format like <b><i>http://www.amazon.com/dp/B003XELTTG</i></b>"; continue;}
		$site = trim($m[1]);
		$asin = trim($m[3]);
		$sqlInsertGoogleSheet4LA1Tier = "INSERT IGNORE INTO GoogleSheet4LA1Tier ($sqlFields) VALUES ('$salesrank','$site','$asin','$sku','$tier','$comp','$kw','$assignee','$goal','$recentNegativeReviewsUrl');\n";
		// echo '<li>'. str_replace("\n",'</li>',$sqlInsertGoogleSheet4LA1Tier); //
		sqlquery($sqlInsertGoogleSheet4LA1Tier);
	}
	$qLastUpdated = 'SELECT DISTINCT lastupdated FROM GoogleSheet4LA1Tier ORDER BY lastupdated DESC limit 1';
	$rLastUpdated = sqlquery($qLastUpdated);
	$lastupdated=$rLastUpdated[0]['lastupdated'];
	$q = "SELECT DISTINCT $sqlFields ,lastupdated FROM GoogleSheet4LA1Tier ORDER BY lastupdated DESC,tier,salesrank limit 500";
	$r = sqlquery($q);
	//~ echo '<pre>'; print_r($r); echo '</pre>'; exit;
	$htmlTable=$htmlTableRows='';
	$htmlTableRows = '<tr><th>'. str_replace(',','</th><th>',$sqlFields) .'</th><th>LastUpdated</th></tr>';
	foreach($r as $row) {
		$htmlTableRows .= '<tr>';
		foreach($row as $key=>$value) {
			$htmlTableRows .= '<td>'. $value .'</td>';
		}
		$htmlTableRows .= '</tr>';
	}
	$htmlTable='<table border=1 id="table1" class="filterable sortable" cellpadding="0" cellspacing="0" width="100%">'. $htmlTableRows .'</table>';
	echo $htmlTable;
}
function GetAssigneeArray(){
	$q="SELECT DISTINCT owner AS assignee FROM GoogleSheet4LA1Tier";
	$r=sqlquery($q);
	//~ echo '<pre>';print_r($r);echo '</pre>';exit;
	$aAssignees = $r[0];
	//~ $aAssignees = explode(',','John,Sonny,Rosa,Wency,Li,Jerry,Peter');
	//~ array_multisort($aAssignees);
	foreach($r as $row) {
		foreach($row as $key=>$assignee) {
			$a[]=$assignee;
		}
	}
	return $a;
}
?>
