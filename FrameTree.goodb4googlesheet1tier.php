<html><head><meta http-equiv="Content-Type" content="text/html; charset=windows-1252">
<title>Market Tracker</title>
<head>
	<script type="text/javascript" language="javascript" src="https://code.jquery.com/jquery-1.8.0.min.js"></script>
	<script type="text/javascript" language="javascript" src="lib/EasyTree/jquery.easytree.js"></script>
	<link rel="stylesheet" href="lib/EasyTree/skin-xp/ui.easytree.css" type="text/css" media="screen, projection"/>
	<style>
		img {width: 32px;}
	</style>
</head>
<body>
<H3>Market Tracker</h3>

<div id="tree_menu">
    <ul>
        <li class="isFolder isExpanded" title="Bookmarks">Amazon.COM KPI
					<ul>
						 <li><a target="FrameContent" href="dashboard.php?list=top30">US BSR</a></li> <!-- 2016-08-17 http://czyusa.com/editor_scTu4UTrYBGA4arkTBPvfA7Uu5cn14jfhMcMvQ91i8.php?file=amazon_asin_sku_competitors.txt, homeserver F:\backup\d\it\czyusa\mt\uploads\runBSR.cmd hourly task creates and uploads BSR.csv to mt/uploads/ then processed by import.php -->
						 <li>&nbsp;<a target="FrameContent" href="http://czyusa.com/editor_scTu4UTrYBGA4arkTBPvfA7Uu5cn14jfhMcMvQ91i8.php?file=amazon.us_asin_sku_competitors.txt">US BSR Groups</a></li>
						 <li class="isFolder isExpanded" title="SKUs by Owner">SKUs by Owner
							<ul>
								<?php
								foreach(explode(',','carole,eric,joy,sonny') as $owner) {
									echo '<li><a target="FrameContent" href="dashboard.php?owner='. $owner .'">'. ucwords($owner) ."</li>";
								}
								?>
							</ul>
						</li>

						 <li><a target="FrameContent" href="dashboard_new.php">US BSR(weekly)</a></li> <!-- 2016-08-17 http://czyusa.com/editor_scTu4UTrYBGA4arkTBPvfA7Uu5cn14jfhMcMvQ91i8.php?file=amazon_asin_sku_competitors.txt, homeserver F:\backup\d\it\czyusa\mt\uploads\runBSR.cmd hourly task creates and uploads BSR.csv to mt/uploads/ then processed by import.php -->
						 <li>&nbsp;<a target="FrameContent" href="http://czyusa.com/editor_asin_sku.php">ASIN_SKU</a></li>
						 <li>&nbsp;<a target="FrameContent" href="http://czyusa.com/mt/item/asin_sku_title.php?table=1">ASIN_SKU_TITLE</a></li>
						 <li><a target="FrameContent" href="uploads/amazon_kw_rank_report.php">Keywords Search Rank (KSR)</a></li> <!--  -->
						 <li><a target="_blank" href="/editor_scTu4UTrYBGA4arkTBPvfA7Uu5cn14jfhMcMvQ91i8.php?file=bestwaterfilters/amazon_link_list.txt">LinkList Editor</a></li> <!--  -->
						 <li><a target="_blank" href="rank/linkgroups_v2_clicks.php?action=LidSummary&period=Weekly&site=amazon">Traffics</a></li> <!--  -->
						 <li><a target="_blank" href="rank/linkgroups_v2_clicks.php?action=OidSummary&period=Weekly">Gigs Performance</a></li>
						 <li><a target="_blank" href="review/review_rating_target_report.php">Reviews Target</a></li> <!-- 2016-08-17 http://czyusa.com/mt/review/review_rating_target_import.php -->
						 <li><a target="_blank" href="review/amazon_ispring_reviews.index.php?rows=50">iSpring Reviews</a></li> <!-- 2016-08-17 mt/review/amazon_ispring_reviews.import.php -->
						 <li><a target="_blank" href="review/amazon_comp_reviews.index.php">Competitors Reviews</a></li>
						 <li><a target="_blank" href="rank/bestsellerrank.comp.php">Competitors Best Sellers</a></li>
						 <li><a target="_blank" href="uploads/offermon.import.log.html">Offer Monitor</a></li>
					</ul>
        </li>
				<li class="isFolder isExpanded" title="Bookmarks">AmazonCAN KPI
					<ul>
						<li><a target="FrameContent" href="dashboard.php?region=ca&period=124+hours">CAN BSR</a></li>
						<li>&nbsp;<a target="FrameContent" href="http://czyusa.com/editor_scTu4UTrYBGA4arkTBPvfA7Uu5cn14jfhMcMvQ91i8.php?file=amazon.ca_asin_sku_competitors.txt">CAN BSR Groups</a></li>
					</ul>
				</li>
				<li class="isFolder isExpanded" title="Bookmarks">AmazonUK KPI
					<ul>
						<li><a target="FrameContent" href="dashboard.php?region=uk&period=124+hours">UK BSR</a></li>
						<li>&nbsp;<a target="FrameContent" href="http://czyusa.com/editor_scTu4UTrYBGA4arkTBPvfA7Uu5cn14jfhMcMvQ91i8.php?file=amazon.uk_asin_sku_competitors.txt">UK BSR Groups</a></li>
						<li><a target="_blank" href="http://160.153.225.52/editor_scTu4UTrYBGA4arkTBPvfA7Uu5cn14jfhMcMvQ91i8.php?file=bwf/amazon_link_list.txt">EU LinkList Editor</a></li>
						<li><a target="_blank" href="http://160.153.225.52/rank/linkgroups_v2_clicks.php?action=LidSummary&period=Weekly&site=amazon">Traffics</a></li> <!--  -->
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
						<li>Linkgroups&nbsp;<a target="FrameContent" href="rank/linkgroupclicks.php?action=lidsummary&str=HomeDepot">Clicks</a>
						&nbsp;<a target="_blank" href="/bestwaterfilters/linkgroupseditor.php">Editor</a></li>
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
				<li class="isFolder" title="Bookmarks">Editors
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
</body>
</html>
