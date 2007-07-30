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

		 echo htmlentities($title) . '</th>';
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
				$cel = htmlentities($obj->$index);
			}
			echo $cel . "</td>";
		}

		echo "</tr>\n";
	}
	echo "</table>";
	echo '</div>';
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

				//echo the td first so if fn outputs directly and returns empty string, it will still display correctly
				echo "<td>";
				if (isset($formatters[$index])) {
					$fn = $formatters[$index];
					$cel = $fn($row,$index);
				} else {
					$cel = htmlentities($row[$index]);
				}
				echo $cel . "</td>";
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
?>