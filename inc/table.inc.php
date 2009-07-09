<?
function showObjects ($data, $titles, $formatters = array(), $scrolling = false, $sorttable = false) {
	static $tablecounter = 100;

	$tableid = "tableid" . $tablecounter++;

	echo '<div ' . ($scrolling ? 'class="scrollTableContainer"' : '') . '>';
	echo '<table width="100%" cellpadding="3" cellspacing="1" class="list' . ($sorttable ? " sortable" : "")  . '" id="' . $tableid . '">';
	echo '<tr class="listHeader">';
	foreach ($titles as $title) {
		//make column sortable?
		if (!$sorttable || strpos($title,"#") === false) {
			echo '<th align="left" class="nosort">' ;
		} else {
			echo '<th align="left">';
		}

		if (strpos($title,"#") === 0)
			$title = substr($title,1);

		 echo escapehtml($title) . '</th>';
	}
	echo "</tr>\n";

	$alt = 0;
	foreach ($data as $obj) {
		echo ++$alt % 2 ? '<tr>' : '<tr class="listAlt">';

		//only show cels with titles
		foreach ($titles as $index => $title) {
			//echo the td first so if fn outputs directly and returns empty string, it will still display correctly
			echo "<td>";
			if (isset($formatters[$index])) {
				$fn = $formatters[$index];
				$cel = $fn($obj,$index);
			} else {
				$cel = escapehtml($obj->$index);
			}
			echo $cel . "</td>";
		}

		echo "</tr>\n";
	}
	echo "</table>";
	echo '</div>';
	return $tableid;
}

function showTable ($data, $titles, $formatters = array(), $repeatedColumns = array(), $groupby = null) {
	//use sparse array to use isset later
	$hiddencolumns = array();
	echo '<tr class="listHeader">';
	foreach ($titles as $index => $title) {
		
		echo '<th align="left" ';
		// make column hidden
		if(strpos($title,"@") !== false){
			echo ' style="display:none" ';
			$hiddencolumns[$index] = true;
		}
		//make column sortable?
		if (strpos($title,"#") === false) {
			echo 'class="nosort">' ;
		} else {
			echo '>';
		}

		if (strpos($title,"@#") === 0){
			$displaytitle = substr($title,2);		
		} else if (strpos($title,"@") === 0){
			$displaytitle = substr($title,1);
		} else if (strpos($title,"#") === 0){
			$displaytitle = substr($title,1);
		} else {
			$displaytitle = $title;
		}
		echo escapehtml($displaytitle) . "</th>";
	}
	echo "</tr>\n";

	$alt = 0;
	if (count($data) > 0) {
		$curr = null;
		//flip the array to use isset later
		$repeatedColumns = array_flip($repeatedColumns);
		foreach ($data as $row) {
			if($groupby !== null){
				if($row[$groupby] !== $curr){
					$alt++;
				}
			} else {
				$alt++;
			}
			echo $alt % 2 ? '<tr>' : '<tr class="listAlt">';

			//only show cels with titles
			foreach ($titles as $index => $title) {

				//echo the td first so if fn outputs directly and returns empty string, it will still display correctly
				echo "<td";
				if(isset($hiddencolumns[$index]))
					echo ' style="display:none">';
				else
					echo ">";
				if( $groupby === null || (($row[$groupby] != $curr) || ($row[$groupby] == $curr && isset($repeatedColumns[$index])))){
					if (isset($formatters[$index])) {
						$fn = $formatters[$index];
						$cel = $fn($row,$index);
					} else {
						$cel = escapehtml($row[$index]);
					}
				} else {
					$cel = "&nbsp;";
				}
				echo $cel . "</td>";
			}
			if($groupby !== null){
				$curr = $row[$groupby];
			}
			echo "</tr>\n";
		}
	}
}

function startWindow($title, $style = "", $minimize = false, $usestate = true)
{
	static $id = 0;
	$id++;

	$visible = !$usestate || state("window_$id") != "closed";

	if (!$visible)
		$style .= "; display: none";

?>
<div class="window">
<table width="100%" border=0 cellpadding=0 cellspacing=0>
<tr>
	<td width="100%">
		<div class="windowborder">
			<div class="windowbar">
<?	if ($minimize) { ?>
				<div class="menucollapse" onclick="windowHide(<?=$id?>);" ><img id="window_colapseimg_<?= $id ?>" src="img/arrow_<?=  $visible ? "down" : "right" ?>.gif"></div>
<? } ?>
				<div class="windowtitle"><?= $title ?></div>
			</div>
			<div id="window_<?= $id ?>" class="windowbody" style="<?=$style?>"><div style="width: 100%;">
<?
}

function endWindow()
{
?>
				</div></div>
			</div>
		</td>
		<td width="6" valign="top" background="img/window_shadow_right.gif"><img src="img/window_shadow_topright.gif"></td>
	</tr>
	<tr>
		<td background="img/window_shadow_bot.gif"><img src="img/window_shadow_botleft.gif"></td>
		<td><img src="img/window_shadow_botright.gif"></td>
	</tr>
	</table>
</div>
<?

}

function showPageMenu ($total,$start, $perpage, $link = NULL) {
	$numpages = ceil($total/$perpage);
	$curpage = ceil($start/$perpage) + 1;

	$displayend = ($start + $perpage) > $total ? $total : ($start + $perpage);
	$displaystart = ($total) ? $start +1 : 0;
?>
<div class="pagenav" style="text-align:right;"> Showing <?= $displaystart ?>-<?= $displayend ?> of <?= $total ?> records<span class='noprint'> on <?= $numpages ?> pages</span>. <select class='noprint' onchange="location.href='?pagestart=' + this.value;">
<?
	for ($x = 0; $x < $numpages; $x++) {
		$offset = $x * $perpage;
		$selected = ($curpage == $x+1) ? "selected" : "";
		echo "<option value=\"" . $offset . "\" $selected>Page " . ($x+1) . "</option>";
	}
?>
</select>
</div>
<?
}


function ajax_table_get_orderby($containerID, $extravalidcolumns = array()) {
	global $USER;
	
	$ajaxtablesort = json_decode($USER->getSetting('ajaxtablesort', false, true), true);
	if (!is_array($ajaxtablesort))
		$ajaxtablesort = array();
	$validorderby = array();
	if (isset($_GET['orderby'])) {
		$orderby = json_decode($_GET['orderby']);
		if (is_array($orderby)) {
			foreach ($orderby as $column) {
				if ($USER->authorizeField($column) || in_array($column, $extravalidcolumns))
					$validorderby[$column] = (isset($_GET['descend']) ? 'descend' : 'ascend');
			}
		}
	} else if ($ajaxtablesort[$containerID]) {
		$validorderby = $ajaxtablesort[$containerID];
	}
	if (!empty($validorderby)) {
		$orderbySQL = implode(",", array_keys($validorderby));
		if (in_array('descend', $validorderby))
			$descend = true;
		if (isset($_GET['orderby']))
			$descend = isset($_GET['descend']) ? true : false;
	}
	if (!empty($descend))
		$orderbySQL .= ' desc ';
	$ajaxtablesort[$containerID] = $validorderby;
	$USER->setSetting('ajaxtablesort', json_encode($ajaxtablesort));
	return $orderbySQL;
}

function ajax_show_table ($containerID, $data, $titles, $formatters = array(), $sorting = array(), $scroll = true) {
	global $USER;
	$ajaxtablesort = json_decode($USER->getSetting('ajaxtablesort', false, true), true);
	if (is_array($ajaxtablesort) && isset($ajaxtablesort[$containerID]))
		$existingsort = $ajaxtablesort[$containerID];
	if (isset($existingsort))
		error_log('******   ' . json_encode($existingsort));
	//use sparse array to use isset later
	$hiddencolumns = array();
	
	$headerHtml = '<tr class="listHeader">';
	foreach ($titles as $index => $title) {
		$headerHtml .= '<th align="left" ';
		
		$style = ' white-space: nowrap; ';
		// make column hidden
		if (strpos($title,"@") !== false) {
			$style .= ' display:none; ';
			$hiddencolumns[$index] = true;
		}
		
		// make sortable
		if (isset($sorting[$index])) {
			$style .= ' cursor:pointer; border-bottom: solid 2px darkblue; ';
			$field = $sorting[$index];
			$fieldsort = false;
			if (isset($existingsort[$field]))
				$fieldsort = $existingsort[$field];
			$orderby = urlencode(json_encode(array($field)));
			$onclick = "ajax_table_update('$containerID', '?ajax=orderby&orderby=$orderby&" . ($fieldsort == 'ascend' ? 'descend&' : '') . "');";
			$headerHtml .= " onclick=\"$onclick\" ";
		}
		$headerHtml .= " style=\"$style\" >";

		$displaytitle = $title;
		if (strpos($title,"@#") === 0)
			$displaytitle = substr($title,2);
		else if (strpos($title,"@") === 0 || strpos($title,"#") === 0)
			$displaytitle = substr($title,1);
		$headerHtml .= escapehtml($displaytitle);
		if (isset($fieldsort)) {
			if ($fieldsort == 'ascend')
				$headerHtml .= '<img src="img/icons/arrow_down.gif"/>';
			else if ($fieldsort == 'descend')
				$headerHtml .= '<img src="img/icons/arrow_up.gif"/>';
		}
		$headerHtml .= "</th>";
	}
	$headerHtml .= "</tr>";

	$dataHtml = '';
	if (!empty($data)) {
		$alt = 0;
		foreach ($data as $row) {
			$alt++;
			$dataTR = ($alt % 2) ? '<tr>' : '<tr class="listAlt">';
			//only show cells with titles
			foreach ($titles as $index => $title) {
				//echo the td first so if fn outputs directly and returns empty string, it will still display correctly
				$dataTD = "<td ";
				if (isset($hiddencolumns[$index]))
					$dataTD .= ' style="display:none"';
				$dataTD .= ">";
				
				if (isset($formatters[$index]))
					$dataTD .= $formatters[$index]($row,$index);
				else
					$dataTD .= escapehtml($row[$index]);
				$dataTD .= "</td>";
				$dataTR .= $dataTD;
			}
			$dataTR .= "</tr>";
			$dataHtml .= $dataTR;
		}
	}
	return ($scroll ? '<div class="scrollTableContainer">' : '') . '<table width="100%" cellpadding="3" cellspacing="1" class="list"><tbody>' . $headerHtml . $dataHtml . '</tbody></table>' . ($scroll ? '</div>' : '');
}

function ajax_table_show_menu ($containerID, $total, $start, $perpage) {
	$numpages = ceil($total/$perpage);
	$curpage = ceil($start/$perpage) + 1;

	$displayend = ($start + $perpage) > $total ? $total : ($start + $perpage);
	$displaystart = ($total) ? $start +1 : 0;
	
	$onchange = "ajax_table_update('$containerID', '?ajax=page&start='+this.value);";
	$info = "Showing $displaystart - $displayend of $total records<span class='noprint'> on $numpages pages</span>. ";
	$selectbox = "<select class='noprint' onchange=\"$onchange\">";
	for ($x = 0; $x < $numpages; $x++) {
		$offset = $x * $perpage;
		$selected = ($curpage == $x+1) ? "selected" : "";
		$page = $x + 1;
		$selectbox .= "<option value='$offset' $selected>Page $page</option>";
	}
	$selectbox .= "</select>";
	return "<div class='pagenav' style='text-align:right;'>" . $info . $selectbox . "</div>";
}
?>