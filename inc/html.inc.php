<?

function help($title, $extrahtml = NULL, $color = NULL) {
	$contents = @file_get_contents('hover' . DIRECTORY_SEPARATOR . $title . '.txt');
	//$contents = nl2br(preg_replace('/($|\\r\\n\\r\\n)[^\\r\\n:]+?:/', '<span class="hovertitle">\\0</span>', $contents ));

	if (substr($contents,0,1) == "@" && ($contentpos = strpos($contents,"\n")) !== false) {
		$link = trim(substr($contents,1,$contentpos));
		$contents = trim(substr($contents,$contentpos+1));
	} else {
		$link = "";
	}

	$contents = nl2br($contents);

	$hover = '<span class="hoverhelpicon ' . ($link != "" ? "helpclick" : "") . '" ' . $extrahtml . '>';
	$hover .= '<img align="absmiddle" src="img/helpcenter' . ($color ? '_' . $color : "") . '.gif"';
	$hover .= ' onmouseover="this.nextSibling.style.display = \'block\'; setIFrame(this.nextSibling);"';
	$hover .= ' onmouseout="this.nextSibling.style.display = \'none\'; setIFrame(null);"';
	if ($link != "")
		$hover .= " onclick=\"window.open('$link', '_blank', 'width=750,height=500,location=no,menubar=yes,resizable=yes,scrollbars=yes,status=no,titlebar=no,toolbar=yes');\"";
	$hover .= '><div class="hoverhelp" >' . $contents . '</div></span>';

	return $hover;
}

function state($field, $set = false, $page = false) {
	if (!isset($_SESSION['state']))
		$_SESSION['state'] = array();


	$pageindex = $page ? $page : $_SERVER['SCRIPT_NAME'];
	if (!isset($_SESSION['state'][$pageindex]))
			$_SESSION['state'][$pageindex] = array();

	if($set !== false)
	{
		$_SESSION['state'][$pageindex][$field] = $set;
	}
	return (isset($_SESSION['state'][$pageindex][$field]) ? $_SESSION['state'][$pageindex][$field] : false);
}

function status() {
	$messages = func_get_args();
	foreach($messages as $message)
	{
		if(is_array($message)) {
			foreach($message as $txt)
			{
				$GLOBALS['STATUS'][] = $txt;
			}
		} elseif($message) {
			$GLOBALS['STATUS'][] = $message;
		}
	}
}

function error() {
	$messages = func_get_args();
	foreach($messages as $message)
	{
		if(is_array($message)) {
			foreach($message as $txt)
			{
				$GLOBALS['ERRORS'][] = $txt;
			}
		} elseif($message) {
			$GLOBALS['ERRORS'][] = $message;
		}
	}
}

function button($name, $onclick = NULL, $href = NULL, $extrahtml = NULL) {
	$btn = "";

	if ($href !== NULL) {
		$btn .= '<a href="' . htmlentities($href) . '">';
	}

	$btn .= '<img class="button" alt="' . $name . '" src="img/b1_' . $name . '.gif" ' . $extrahtml . ' border="0" align="absmiddle" onMouseOver="this.src=\'img/b2_' . $name . '.gif\';" onMouseOut="this.src=\'img/b1_' . $name . '.gif\';" ' . (isset($onclick) ? 'onClick="' . $onclick . '"' : "") . '>';

	if ($href !== NULL) {
		$btn .= '</a>';
	}
	return $btn;
}

function submit($form, $section, $name = 'submit', $image = 'submit') {
	ob_start();
	NewFormItem($form, $section, $name, 'image', $image);
	$html = ob_get_contents();
	ob_end_clean();
	return $html;
}

function add($name, $file = 'add') {
	return '<input type="image" name="' . $name . '" value="' . $name . '" src="img/b1_' . $file . '.gif" onMouseOver="this.src=\'img/b2_' . $file . '.gif\';" onMouseOut="this.src=\'img/b1_' . $file . '.gif\';">';
}

function buttons() {
	static $buttons;
	if(!$buttons) {
		$buttons = func_get_args();
		print '<table border="0" cellspacing="0" cellpadding="0" style="margin-bottom: 5px;" class="noprint"><tr><td>' .  implode('</td><td>', $buttons) . '</td></tr></table>';
	} else
		print '<table border="0" cellspacing="0" cellpadding="0" style="margin-top: 5px;" class="noprint"><tr><td>' .  implode('</td><td>', $buttons) . '</td></tr></table>';
}

/*
	Function to create a bar of buttons and UI elements from the list of input parameters,
		which are each HTML strings.
*/
function button_bar() {
	$buttons = func_get_args();
	print '<div class="buttonbar" style="margin-bottom: 5px;"><table border="0" cellspacing="0" cellpadding="0" class="noprint"><tr><td class="buttonbaritem">' .  implode('</td><td class="buttonbaritem">', $buttons) . '</td><tr></table></div>';
}

function time_select($form, $section, $field, $none = NULL, $inc = NULL, $start = NULL, $stop = NULL, $extraHtml = NULL) {
	if(!$inc) $inc = 15;
	if(!$start) $start = '12:00 am';
	if(!$stop) $stop = '11:45 pm';
	$current = strtotime($start);
	$end = strtotime($stop);
	$customtime = GetFormData($form, $section, $field);
	$customtime = strtotime($customtime);
	NewFormItem($form, $section, $field, 'selectstart', NULL, NULL, "id=\"$field\"" . " $extraHtml ");
	if($none)
		NewFormItem($form, $section, $field, 'selectoption', $none, '');
	while($current <= $end)
	{
		NewFormItem($form, $section, $field, 'selectoption', date('g:i a', $current), date('g:i a', $current));
		if( ($customtime > $current) && ($customtime < ($current+($inc *60))) ) {
			NewFormItem($form, $section, $field, 'selectoption', date('g:i a', $customtime), date('g:i a', $customtime));
		}
		$current += $inc *60;
	}
	NewFormItem($form, $section, $field, 'selectend');
}

function audio($name) {
	global $USER;
	$files = DBFindMany('AudioFile', "from audiofile where userid = $USER->id and deleted != 1 order by name");
	?>
	<select id="audio" name="<? print $name; ?>" >
		<option value="0">- Select an Audio File -</option>
	<?
	foreach($files as $audio)
		print "<option value=\"$audio->id\">$audio->name</option>";
	?>
	</select>
	<?
}
?>