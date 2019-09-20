<?php

function chart($q, $ylable, $skuArray) {
	$rows = sqlquery($q);
	foreach($rows as $row) {
		$s = date('m/d H',$row['time']);
		foreach($skuArray as $asin=>$sku) {
			$s .= ','. str_replace(',','',0-$row[$sku]);
		}
		$array[] = explode(',',$s);
		//~ print_r($s ."<br>");
		$i++; if ($i>90) break;
	}
	//~ print_r($array);
	//~ $array = array_reverse($array);
	echo '<table><tr><td>';
	foreach($skuArray as $asin=>$sku) {
		$asins .= $asin .',';
		$class = strpos($sku,'_',1) > 1 ? 'compe' : 'ispring';
		echo "<a class=\"$class\" target=_blank href=\"http://www.amazon.com/dp/$asin\">$sku</a>&nbsp; <a target=_blank href=\"rank/bestsellerrank.comp.php?asin=$asin\">History</a>&nbsp;";
	}
	if (count($skuArray) > 1)	echo "&nbsp; <a target=_blank href=\"rank/bestsellerrank.comp.php?asin=$asins\">More</a>";

	echo '</td></tr><tr><td>';
	drawchart(array_reverse($array),'','time',$ylable,1300,250);
	echo '</td></tr></table>';

	$html_table_data = '<table border=1><tr><td>time&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>';
	foreach($skuArray as $asin=>$sku) {
		$html_table_data .= '<td><a target=_blank href="rank/bestsellerrank.comp.php?asin='. $asin .'">'. $sku .'</td>';
	}
	$html_table_data .= '</tr>';
	foreach($array as $row) {
		$html_table_data .= '<tr>';
		for ($r = 0; $r<=count($skuArray); $r++) {
			$html_table_data .= '<td>'. $row[$r] .'</td>';
		}
		$html_table_data .= '</tr>';
	}
	return $html_table_data .'</table>';
}

?>