<?

function showObjects ($data, $titles, $formatters = array(), $scrolling = false, $sorttable = false) {
	static $tablecounter = 100;

	$tableid = "tableid" . $tablecounter++;

	echo '<div ' . ($scrolling ? 'class="scrollTableContainer"' : '') . '>';
	echo '<table class="list' . ($sorttable ? " sortable" : "")  . '" id="' . $tableid . '">';
	echo '<tr class="listHeader">';
	foreach ($titles as $title) {
		//make column sortable?
		if (!$sorttable || strpos($title,"#") === false) {
			echo '<th class="nosort">' ;
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


function prepareAjaxTableObjects ($data, $titles, $formatters = array(),$row_action_formatter = false) {
	$tableData = array("titles" => array(),"rows" => array());
	foreach ($titles as $index => $title) {
		$tableData["titles"][] = escapehtml($title);
	}
	
	foreach ($data as $obj) {
		$row = array("cols" => array());
		//only show cels with titles
		foreach ($titles as $index => $title) {
			if (isset($formatters[$index])) {
				$fn = $formatters[$index];
				$cel = $fn($obj,$index);
			} else {
				$cel = escapehtml($obj->$index);
			}
			$row["cols"][] = $cel;
		}
		if ($row_action_formatter) {
			$row["action"]  = $row_action_formatter($obj);
		}
		
		$tableData["rows"][] = $row;
	}
	return $tableData;
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

function showCsvData ($data, $titles, $formatters = array()) {
	echo array_to_csv($titles) . "\r\n";
	foreach ($data as $row) {
		//only show cells with titles
		$filteredrow = array();
		foreach ($titles as $index => $title) {
			if (isset($formatters[$index])) {
				$fn = $formatters[$index];
				$cell = $fn($row,$index);
			} else {
				$cell = $row[$index]; //no default formatter
			}
			$filteredrow[] = $cell;
		}
		echo array_to_csv($filteredrow) . "\r\n";
	}
}

function startWindow($title) {
	$theme = getBrandTheme();

?>

<div class="window">
	
	<div class="window_title_wrap">
		<div class="window_title_l"></div>
		<h2 class="window_title"><?= $title ?></h2>
		<div class="window_title_r"></div>
	</div>
	
	<div class="window_body_wrap">
	
		<div class="window_left">
			<div class="window_right">
				<div class="window_body cf">
<?
}

function endWindow() {
	$theme = getBrandTheme();

?>
				</div><!-- window_body -->
			</div>
		</div>
			
	</div><!-- window_body_wrap -->
</div><!-- window -->

<div class="window_foot_wrap">  
	<div class="window_foot_left">
		<div class="window_foot_right">
		</div>
	</div>
</div>

<?
}


function showPageMenu ($total, $start, $perpage) {
	$numpages = ceil($total/$perpage);
	$curpage = ceil($start/$perpage) + 1;

	$displayend = ($start + $perpage) > $total ? $total : ($start + $perpage);
	$displaystart = ($total) ? $start +1 : 0;
?>
<div class="pagenav" style="text-align:right;"> Showing <?= $displaystart ?>-<?= $displayend ?> of <?= $total ?> records<span class='noprint'> on <?= $numpages ?> pages</span>. <select class='noprint' onchange="location.href='?pagestart=' + this.value + '<?= isset($_GET["iframe"])?"&iframe":"" ?>';">
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

/**
 * shows a menu for selecting fields to sort by
 * @param $fields array of field -> display name
 * @param $sortdata existing sort data to prepopulate
 * @return unknown_type
 */
function showSortMenu ($fields, $sortdata) {
?>
	<div class="sort_menu">
		<h3>Sort By</h3>
<?
			$count = min(count($fields),3);
			for ($x = 0; $x < $count; $x++) {
				list($selectedfield,$desc) = isset($sortdata[$x]) ? $sortdata[$x] : array(false,false);
				echo '<span><select onchange="location.href=\'?sort'.$x.'=\' + this.value' . (isset($_GET["iframe"])?"&iframe":"") . ';" name="sort'.$x.'"><option value="">- None -</option>';
				foreach ($fields as $field => $name) {
					$selected = $field == $selectedfield ? "selected" : "";
					echo '<option value="'.escapehtml($field).'" '.$selected .'>'.escapehtml($name).'</option>';
				}
				echo '</select></span>';
				
				//TODO add asc/desc toggle
			}
?>
	</div> <!-- /.sort_menu -->
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
		$headerHtml .= '<th ';

		$style = '';
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

	$togglersHtml = "<div class='togglers'><table class='list'>";
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
				$togglerHeaderHtml .= "<th class='listHeader'><label class='label' for=\"$checkboxID\">$title</label></th>";
				$togglerCheckboxesHtml .= "<td><input type=\"checkbox\" id=\"$checkboxID\" $checked value=\"$index\" onclick=\"$onclick\"></td>";
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
	return "<div class='scrolltable'>$togglersHtml $multisortHtml<div $scrollClass>"
		. '<table class="list"><tbody>'
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
	$info = "<div class='pagenavinfo'>Showing $displaystart - $displayend of $total records<span class='noprint'> on $numpages pages</span>.&nbsp;&nbsp;</div>";
	$selectbox = "<div class='pagenavselect'><select class='noprint' onchange=\"$onchange\">";
	for ($x = 0; $x < $numpages; $x++) {
		$offset = $x * $perpage;
		$selected = ($curpage == $x+1) ? "selected" : "";
		$page = $x + 1;
		$selectbox .= "<option value='$offset' $selected>Page $page</option>";
	}
	$selectbox .= "</select></div>";
	return "<div class='pagenav cf'>" .  $selectbox . $info . "<div id='{$containerID}_tableprogressbar' class='tableprogressbar'>&nbsp</div>" . "</div>";
}

// table with headers and each column is a list (ex: admin-settings page)
function drawTableOfLists($headers, $lists) {
	// calculate longest list, other lists will need empty lines appended so they all appear to start with top alignment
	$maxListCount = 0;
	foreach ($lists as $list) {
		$maxListCount = max($maxListCount, count($list));
	}
	
?>
	<table class="list">
		<tr class="listHeader">
<?			foreach ($headers as $header) {
?>
			<th align="left" class="nosort"><?=$header?></th>
<?			}
?>
		</tr>
		<tr align="left" valign="top">
<?			foreach ($lists as $list) {
?>
			<td><ul>
<?				foreach ($list as $li) {
?>
				<li><?=$li?></li>
<?				}
				// now fill the empty list items for all columns to have equal length and display consistent height
				for ($i = count($list); $i < $maxListCount; $i++) {
?>
				<li>&nbsp;</li>
<?				}
?>
			</ul></td>
<?			}
?>
		</tr>
	</table>
<?
}

?>