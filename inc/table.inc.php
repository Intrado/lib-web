<?
function showObjects ($data, $titles, $formatters = array(), $scrolling = false, $sorttable = false) {
	static $tablecounter = 100;

	$tableid = "tableid" . $tablecounter++;

	if(is_string($data[0]) && is_string($data[1]) && is_int($data[2]))
	{
		$count = QuickQuery('select COUNT(id) ' . $data[1]);
		$show = $data[2];
		$start = state('start');
		$data = DBFindMany($data[0], $data[1] . ' limit ' . (int)$start . ',' . $show);
		if($count) {
			ob_start();
	 ?>
	 <div align="right">
	 	<? print $start + 1; ?>-<? print ($start + $show < $count) ? $start + $show : $count; ?> of <? print $count; if($count > $show) { ?>
		<select name="start" onclick="this.blur();" onchange="setState('start', this.options[this.selectedIndex].value);">
		<? for($i = 0; $i * $show < $count; $i++) { ?>
			<option value="<? print $i * $show; ?>"<? if($i * $show == $start) print ' selected'; ?>>Page <? print $i + 1; ?></option>
		<? } ?>
		</select>
	</div>
	 <?
	 		}
			$pagination = ob_get_contents();
			ob_end_flush();
		}
	}

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

		 echo htmlentities($title) . '</th>';
	}
	echo "</tr>\n";

	$alt = 0;
	foreach ($data as $obj) {
		echo ++$alt % 2 ? '<tr>' : '<tr class="listAlt">';

		//only show cels with titles
		foreach ($titles as $index => $title) {
			if (isset($obj->$index))
				$cel = $obj->$index;
			else $cel = "";

			$cel = htmlentities($cel);

			//echo the td first so if fn outputs directly and returns empty string, it will still display correctly
			echo "<td>";
			if (isset($formatters[$index])) {
				$fn = $formatters[$index];
				$cel = $fn($obj,$index);
			}
			echo $cel . "</td>";
		}

		echo "</tr>\n";
	}
	echo "</table>";
	echo '</div>';
	echo $pagination;
	return $tableid;
}

function showTable ($data, $titles, $formatters = array()) {
	echo '<tr class="listHeader">';
	foreach ($titles as $title) {

		//make column sortable?
		if (strpos($title,"#") === false) {
			echo '<th align="left" class="nosort">' ;
		} else {
			echo '<th align="left">';
		}

		if (strpos($title,"#") === 0)
			$title = substr($title,1);
		echo htmlentities($title) . "</th>";
	}
	echo "</tr>\n";

	$alt = 0;
	if (count($data) > 0) {
		foreach ($data as $row) {
			echo ++$alt % 2 ? '<tr>' : '<tr class="listAlt">';

			//only show cels with titles
			foreach ($titles as $index => $title) {
				$cel = $row[$index];

				$cel = htmlentities($cel);

				//echo the td first so if fn outputs directly and returns empty string, it will still display correctly
				echo "<td>";
				if (isset($formatters[$index])) {
					$fn = $formatters[$index];
					$cel = $fn($row,$index);
				}
				echo $cel . "</td>";
			}

			echo "</tr>\n";
		}
	}
}

function startWindow($title, $style = NULL, $minimize = false, $usestate = true)
{
	static $id;
	$id++;
	if($usestate && state('window_' . $id))
		$display = state('window_' . $id) == 'closed' ? false : true;
	else
		$display = true;
	if($minimize) {
?><div  id="_window_off_<? print $id;?>" <? if($display) print 'style="display: none;"' ?> >
	<table width="100%" cellpadding="0" cellspacing="1" class="window noprint">
		<tr class="windowHeader">
			<td colspan="2" style="padding: 0px;"><table border="0" cellpadding="0" cellspacing="0" class="windowHeader" width="100%">
					<tr>
						<th align="left" style="padding: 1px 3px 1px 3px;"><div class="windowTitle"><? print $title; ?></div></th>
						<th align="right" height="21"><img class="noprint clickable" src="img/collapse_down.gif"
								onClick="hide('_window_off_<? print $id;?>'); show('_window_on_<? print $id; ?>'); setState('window_<? print $id; ?>', 'open');"
								></th>
					</tr>
			</table></td>
		</tr>
	</table>
	</div><?
	}
	?><div  id="_window_on_<? print $id;?>" <? if(!$display) print 'style="display: none;"' ?> >
	<table width="100%" cellpadding="0" cellspacing="1" class="window">
		<tr class="windowHeader" >
			<td style="padding: 0px;"><table border="0" cellpadding="0" cellspacing="0" class="windowHeader" width="100%">
				<tr>
						<th align="left" style="padding: 1px 3px 1px 3px;"><div class="windowTitle"><? print $title; ?></div></th>
						<th align="right" height="21"><? if($minimize) { ?><img class="noprint clickable" src="img/collapse_up.gif"
								onClick="hide('_window_on_<? print $id; ?>'); show('_window_off_<? print $id; ?>'); setState('window_<? print $id; ?>', 'closed');"
								><? } ?></th>
				</tr>
			</table></td>
		</tr>
		<tr>
			<td class="windowCell" style="<? print $style; ?>"> <?
}

function endWindow()
{
		?></div></td>
		</tr>
	</table>
	</div><?
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
?>