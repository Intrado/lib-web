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

function startWindow($title, $style = "", $minimize = false, $usestate = true) {
	static $id = 0;
	$id++;

	$theme = getBrandTheme();

	$visible = !$usestate || state("window_$id") != "closed";

	if (!$visible)
		$style .= "; display: none";

?>
<div class="window">
<table class="windowtable" width="100%" border=0 cellpadding=0 cellspacing=0>
	<tr>
		<td><img src="img/themes/<?=$theme?>/win_tl.gif" alt="" class="noprint"></td>
		<td background="img/themes/<?=$theme?>/win_t.gif" width="100%">
			<div class="windowbar">
<?	if ($minimize) { ?>
				<div class="menucollapse" onclick="windowHide(<?=$id?>);" ><img id="window_colapseimg_<?= $id ?>" src="img/arrow_<?=  $visible ? "down" : "right" ?>.gif"></div>
<? } ?>
				<div class="windowtitle"><?= $title ?></div>
			</div>
		</td>
		<td><img src="img/themes/<?=$theme?>/win_tr.gif" alt="" class="noprint"></td>
	</tr>
	<tr>
		<td background="img/themes/<?=$theme?>/win_l.gif"></td>
		<td><div id="window_<?= $id ?>" class="windowbody" style="<?=$style?>"><div style="width: 100%;">
<?
}

function endWindow() {
	$theme = getBrandTheme();

?>
		</div></div></td>
		<td background="img/themes/<?=$theme?>/win_r.gif"></td>
	</tr>
	<tr>
		<td><img src="img/themes/<?=$theme?>/win_bl.gif" alt="" class="noprint"></td>
		<td background="img/themes/<?=$theme?>/win_b.gif"></td>
		<td><img src="img/themes/<?=$theme?>/win_br.gif" alt="" class="noprint"></td>
	</tr>
</table>
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

function ajax_table_handle_togglers($containerID) {
	global $USER;

	if (!isset($_SESSION['ajaxtabletogglers']))
		$_SESSION['ajaxtabletogglers'] = array();
	if (!isset($_SESSION['ajaxtabletogglers'][$containerID]))
		$_SESSION['ajaxtabletogglers'][$containerID] = array();

	$togglers = $_SESSION['ajaxtabletogglers'][$containerID];

	if (isset($_GET['addtoggler'])) {
		$index = $_GET['addtoggler'];
		$togglers[$index] = true;
	} else if (isset($_GET['removetoggler'])) {
		$index = $_GET['removetoggler'];
		$togglers[$index] = false;
	}

	$_SESSION['ajaxtabletogglers'][$containerID] = $togglers;

	return true;
}

function ajax_table_get_orderby($containerID, $validaliases) {
	global $USER;

	$ajaxtablesort = isset($_SESSION['ajaxtablesort']) ? $_SESSION['ajaxtablesort'] : array();
	if (!is_array($ajaxtablesort))
		$ajaxtablesort = array();
	$validorderby = array();
	if (isset($_GET['orderby'])) {
		$orderby = json_decode($_GET['orderby']);
		if (is_array($orderby)) {
			foreach ($orderby as $alias) {
				if (in_array($alias, $validaliases))
					$validorderby[$alias] = isset($_GET['descend']) ? 'descend' : 'ascend';
			}
		}
	} else if (!empty($ajaxtablesort[$containerID])) {
		$validorderby = $ajaxtablesort[$containerID];
	}

	// Bugfix 3188: The sorting information stored in $ajaxtablesort[$containerID], $validorderby, may be outdated. Make sure it is consistent with $validaliases.
	$validorderby = array_intersect_key($validorderby, array_flip($validaliases));

	if (!empty($validorderby)) {
		$orderbySQL = implode(",", array_keys($validorderby));
		// Checks if descend is set already set.
		if (in_array('descend', $validorderby))
			$descend = true;
		// $_GET['orderby'] has precedence.
		if (isset($_GET['orderby']))
			$descend = isset($_GET['descend']) ? true : false;
	}
	if (!empty($descend))
		$orderbySQL .= ' desc ';
	$ajaxtablesort[$containerID] = $validorderby;
	$_SESSION['ajaxtablesort'] = $ajaxtablesort;
	return isset($orderbySQL) ? $orderbySQL : null;
}

function ajax_show_table ($containerID, $data, $titles, $formatters = array(), $sorting = array(), $repeatedColumns = false, $groupBy = false, $maxMultisort = 0, $showColumnTogglers = false, $scroll = true) {
	global $USER;

	$ajaxtablesort = isset($_SESSION['ajaxtablesort']) ? $_SESSION['ajaxtablesort'] : array();
	$existingsort = array();
	if (is_array($ajaxtablesort) && isset($ajaxtablesort[$containerID]))
		$existingsort = $ajaxtablesort[$containerID];
	//use sparse array to use isset later
	$hiddencolumns = array();

	$headerHtml = '<tr class="listHeader">';

	foreach ($titles as $index => $title) {
		$headerHtml .= '<th align="left" ';

		$style = ' white-space: nowrap; ';
		// make column hidden
		if (strpos($title,"@") !== false) {

			if (!isset($_SESSION["ajaxtabletogglers"][$containerID]) || empty($_SESSION["ajaxtabletogglers"][$containerID][$index]))
				$style .= ' display:none; ';
			$hiddencolumns[$index] = true;
		}

		$fieldsort = false;
		// make sortable
		if (isset($sorting[$index])) {
			$field = $sorting[$index];

			if (isset($existingsort[$field]))
				$fieldsort = $existingsort[$field];

			if (!$maxMultisort) {
				$style .= ' cursor:pointer;';

				$orderby = urlencode(json_encode(array($field)));
				$onclick = "ajax_table_update('$containerID', '?ajax=orderby&orderby=$orderby&" . ($fieldsort == 'ascend' ? 'descend&' : '') . "');";
				$headerHtml .= " onclick=\"$onclick\" ";
			}
		}
		$headerHtml .= " style=\"$style\">";

		$displaytitle = $title;
		if (strpos($title,"@#") === 0)
			$displaytitle = substr($title,2);
		else if (strpos($title,"@") === 0 || strpos($title,"#") === 0)
			$displaytitle = substr($title,1);
		$titles[$index] = $displaytitle;

		$headerHtml .= escapehtml($displaytitle);
		if (!empty($fieldsort)) {
			if ($fieldsort == 'ascend')
				$headerHtml .= '&nbsp;&nbsp;&uarr;';
			else if ($fieldsort == 'descend')
				$headerHtml .= '&nbsp;&nbsp;&darr;';
		}
		$headerHtml .= "</th>";
	}
	$headerHtml .= "</tr>";

	$dataHtml = '';
	if (!empty($data)) {
		$alt = 0;
		if (!is_array($repeatedColumns))
			$repeatedColumns = array();
		else
			$repeatedColumns = array_flip($repeatedColumns);
		$previouslyGrouped = null;
		foreach ($data as $row) {
			$sameRecord = false;
			if ($groupBy !== false && isset($row[$groupBy])) {
				if ($row[$groupBy] !== $previouslyGrouped)
					$alt++;
				else
					$sameRecord = true;
				$previouslyGrouped = $row[$groupBy];
			} else {
				$alt++;
			}
			$dataTR = ($alt % 2) ? '<tr>' : '<tr class="listAlt">';
			//only show cells with titles
			foreach ($titles as $index => $title) {
				//echo the td first so if fn outputs directly and returns empty string, it will still display correctly
				$dataTD = "<td ";
				if (isset($hiddencolumns[$index]) && (!isset($_SESSION["ajaxtabletogglers"][$containerID]) || empty($_SESSION["ajaxtabletogglers"][$containerID][$index])))
					$dataTD .= ' style="display:none" ';
				$dataTD .= ">";

				$render = true;
				 if ($sameRecord  && !isset($repeatedColumns[$index]))
					$render = false;

				if ($render) {
					if (isset($formatters[$index]))
						$dataTD .= $formatters[$index]($row,$index);
					else
						$dataTD .= escapehtml($row[$index]);
				} else {
					$dataTD .= " &nbsp; ";
				}
				$dataTR .= $dataTD . "</td>";
			}
			$dataTR .= "</tr>";
			$dataHtml .= $dataTR;
		}
	}

	$togglersHtml = "<div><table class='list' cellspacing=1>";
	if ($showColumnTogglers) {
		// NOTE: javascript does not know about the noncontinuous $index that we use in $titles, so we have to treat $titles as an indexed array.
		$column = 0;
		$indexes = array_keys($titles);
		// Headers
		$togglerHeaderHtml = '';
		$togglerCheckboxesHtml = '';
		for ($column = 0; $column < count($indexes); $column++) {
			$index = $indexes[$column];
			if (isset($hiddencolumns[$index])) {
				$onclick = "var table = $('$containerID').down('table',1); if (table) { setColVisability(table, $column, this.checked); var action = this.checked ? 'addtoggler' : 'removetoggler'; cachedAjaxGet('?ajax=toggler&containerID=$containerID&'+action+'='+this.value, function() {},null,false); }";
				$checkboxID = $containerID . '_toggler_index_' . $index;
				$title = escapehtml($titles[$index]);
				$checked = "";
				if (isset($_SESSION["ajaxtabletogglers"][$containerID]) && !empty($_SESSION["ajaxtabletogglers"][$containerID][$index]))
					$checked = "checked";
				$togglerHeaderHtml .= "<th class='listHeader' style='' valign='top'><label style='padding: 2px; font-weight:normal;' for=\"$checkboxID\">$title</label></th>";
				$togglerCheckboxesHtml .= "<td valign='top' style='text-align:center'><input type=\"checkbox\" id=\"$checkboxID\" $checked value=\"$index\" onclick=\"$onclick\"></td>";
			}
		}
		$togglersHtml .= "<tr>$togglerHeaderHtml</tr><tr>$togglerCheckboxesHtml</tr>";
	}
	$togglersHtml .= "</table></div>";

	$multisortHtml = '<div>';
	if ($maxMultisort) {
		$onchange = "var selectbox=$(this);  ajax_table_update('$containerID', '?ajax=orderby&orderby=' + json_input_values(selectbox.up('div').select('select')));";
		$sortedFields = array_keys($existingsort);
		for ($i = 0; $i < $maxMultisort; $i++) {
			$optionHtml = "<option value=\"\">Choose a Field</option>";
			foreach ($sorting as $index => $alias) {
				$selected = (isset($sortedFields[$i]) && $sortedFields[$i] == $alias) ? "selected" : "";
				$optionHtml .= "<option value=\"$alias\" $selected>$titles[$index]</option>";
			}
			$multisortHtml .= "<select onchange=\"$onchange\">$optionHtml</select>";
		}
	}
	$multisortHtml .= "</div>";

	$scrollClass = (count($data) > 10 && $scroll) ? "class=\"scrollTableContainer\"" : "";
	return "<div style='clear:both'>$togglersHtml $multisortHtml<div $scrollClass>"
		. '<table width="99%"  cellpadding="3" cellspacing="1" class="list"><tbody>'
		. "$headerHtml $dataHtml </tbody></table></div></div>";
}

function ajax_table_show_menu ($containerID, $total, $start, $perpage) {
	if ($start >= $total)
		$start = $total-$perpage;
	if ($start < 0)
		$start = 0;

	$numpages = ceil($total/$perpage);
	$curpage = ceil($start/$perpage) + 1;

	$displayend = ($start + $perpage) > $total ? $total : ($start + $perpage);
	$displaystart = ($total) ? $start +1 : 0;

	$onchange = "ajax_table_update('$containerID', '?ajax=page&start='+this.value);";
	$info = "<div style='float:right'>Showing $displaystart - $displayend of $total records<span class='noprint'> on $numpages pages</span>.&nbsp;&nbsp;</div>";
	$selectbox = "<div style='float:right;padding:0;margin:0'><select class='noprint' onchange=\"$onchange\">";
	for ($x = 0; $x < $numpages; $x++) {
		$offset = $x * $perpage;
		$selected = ($curpage == $x+1) ? "selected" : "";
		$page = $x + 1;
		$selectbox .= "<option value='$offset' $selected>Page $page</option>";
	}
	$selectbox .= "</select></div>";
	return "<div class='pagenav' style='padding-top:5px; padding-right:5px; text-align:right'>" .  $selectbox . $info . "<div id='{$containerID}_tableprogressbar' style='float:right; width:16px; height:16px; margin-right: 5px'>&nbsp</div>" . "</div>";
}
?>