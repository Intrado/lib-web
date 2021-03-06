<?

function help($title, $extrahtml = NULL, $style = NULL) {
	global $LOCALE;
	$hoverfolder = "locale/$LOCALE/hover/$title.txt";
	if (!file_exists($hoverfolder))
		$hoverfolder = "locale/en_US/hover/$title.txt";
	$contents = @file_get_contents($hoverfolder);

	if (substr($contents,0,1) == "@" && ($contentpos = strpos($contents,"\n")) !== false) {
		$link = trim(substr($contents,1,$contentpos));
		$contents = trim(substr($contents,$contentpos+1));
	} else {
		$link = "";
	}
	
	$link = ""; //DISABLE ALL HELP LINKS

	$contents = nl2br($contents);
	
	if (!isset($GLOBALS['TIPS']))
		$GLOBALS['TIPS'] = array();
	$tipid = "tip_" . count($GLOBALS['TIPS']);
	$GLOBALS['TIPS'][] = array($tipid,$contents); //navbotom.inc will load these for us

	$hover = '<span id="'.$tipid.'" class="hoverhelpicon ' . ($link != "" ? "helpclick" : "") . '" ' . $extrahtml . '>';
	$hover .= '<img src="assets/img/helpcenter' . ($style ? '_' . $style : "") . '.png" alt=""';
	if ($link != "")
		$hover .= " onclick=\"window.open('$link', '_blank', 'width=750,height=500,location=no,menubar=yes,resizable=yes,scrollbars=yes,status=no,titlebar=no,toolbar=yes');\"";
	$hover .= '></span>';	
	
	return $hover;
}

function action_link ($title, $icon, $href = "#", $onclick = null) {
	global $USER;
	if (isset($USER)) {
		$actionlinkmode = $USER->getSetting("actionlinks","both");
	} else {
		$actionlinkmode = "both";
	}
	$href = $href == null ? "#" : $href;
	$onclick = $onclick == null ? "" : 'onclick="'.$onclick.'"';
	$autoid = autoID("action_link", $title, $icon);	
	$str = '<a id="' . $autoid . '" href="'.$href.'" '.$onclick.' class="actionlink" title="'.escapehtml($title).'">';
	if ($actionlinkmode == "both" || $actionlinkmode == "icons")
		$str .= '<img src="assets/img/icons/'.$icon.'.png" alt="'.escapehtml($title).'">';
	if ($actionlinkmode == "both" || $actionlinkmode == "text")
		$str .= escapehtml($title);
	$str .= '</a>';
	return $str;
}

function action_links_vertical ($array) {
	$links = is_array($array) ? $array : func_get_args();
	foreach ($links as $key => $link)
		if ($link == "")
			unset($links[$key]);
	return "<div class=\"actionlinks\">" . implode("</div><div class=\"actionlinks\">", $links) . "</div>";
}

function action_links ($array) {
	$links = is_array($array) ? $array : func_get_args();
	foreach ($links as $key => $link)
		if ($link == "")
			unset($links[$key]);
	return '<div class="actionlinks">' . implode("",$links).'</div>';
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

function buttons() {
	static $buttons;
	if(!$buttons) {
		$buttons = func_get_args();
	}
?>
		<div class="cf"></div>
<?
		echo implode('', $buttons);
?>
		<div class="cf"></div>
<?
}

function button_bar() {
	$buttons = func_get_args();
?>
		<div class="cf"></div>
<?
		echo implode('', $buttons);
?>
		<div class="cf"></div>
<?

}


function hidden_submit_button($value = "submit") {
	$btn = '<input type="submit" class="btn_hide" name="submit" value="'.escapehtml($value).'" onclick="return form_submit(event,\''.escapehtml($value).'\');" />';
	return $btn;
}

function submit_button($name, $value = "submit", $icon = null, $beforeSubmit = null) {
	$btn = '<button class="btn" type="submit" name="submit" value="' . escapehtml($value) . '" onclick="' . (strlen($beforeSubmit) ? $beforeSubmit : '') . 'return form_submit(event,\'' . escapehtml($value) . '\');"><span class="btn_wrap cf"><span class="btn_left"></span><span class="btn_middle">';
	
	if ($icon == null)
		$btn .= '<img src="assets/img/pixel.png" alt="" height="16" width="1">';		
	else
		$btn .= '<img src="assets/img/icons/' . $icon . '.png" alt="">';
	$btn .= escapehtml($name) . '</span><span class="btn_right"></span></span></button>';
	
	return $btn;
}

function icon_button($name,$icon,$onclick = NULL, $href = NULL, $extrahtml = NULL) {
	$autoid = $extrahtml == null ? ('id="' . autoID("icon_button", $name, $icon) . '"') : "";
	$btn = '<button ' . $autoid . ' class="btn" type="button"';
	if ($onclick)
		$btn .= ' onclick="' . $onclick . ';" ';
	else if ($href)
		$btn .= ' onclick="window.location=\'' . $href . '\';" ';

	if ($extrahtml)
		$btn .= $extrahtml;
	$btn .= '><span class="btn_wrap cf"><span class="btn_left"></span><span class="btn_middle"><img class="btn_middle_icon" src="assets/img/icons/'.$icon.'.png" alt="">' . escapehtml($name) .'</span><span class="btn_right"></span></span></button>';
	return $btn;
}

function button($name,$onclick = NULL, $href = NULL, $extrahtml = NULL) {
	$autoid = $extrahtml == null ? ('id="' . autoID("button", $name) . '"') : "";
	$btn = '<button ' . $autoid . ' class="btn" type="button"';
	if ($onclick)
		$btn .= ' onclick="' . $onclick . ';" ';
	else if ($href)
		$btn .= ' onclick="window.location=\'' . $href . '\';" ';

	if ($extrahtml)
		$btn .= $extrahtml;
	$btn .= '><div class="btn_wrap cf"><span class="btn_left"></span><span class="btn_middle">' . escapehtml($name) .'</span><span class="btn_right"></span></div></button>';
	return $btn;
}


function submit($form, $section, $name = 'Submit',$val = null, $icon = "accept") {
	//ugly hack. in order for enter key to submit form, either we need to add JS to each text field, or there must be an actual submit button
	//so we make a submit button and hide it off screen.
	$ret = '<input class="btn_hide" type="submit" value="submit" name="submit[' . $form . '][' . $section . ']" />';

	if ($val !== null) {
		$ret .= icon_button($name,$icon,"submitForm('$form','$section','$val');");
	} else {
		$ret .= icon_button($name,$icon,"submitForm('$form','$section');");
	}
	return $ret;
}

function add($name, $file = 'add') {
	return '<input type="image" name="' . $name . '" value="' . $name . '" src="assets/img/b1_' . $file . '.gif" onMouseOver="this.src=\'assets/img/b2_' . $file . '.gif\';" onMouseOut="this.src=\'img/b1_' . $file . '.gif\';">';
}


function newform_time_select($inc = NULL, $start = NULL, $stop = NULL, $customtime = "") {
	$values = array();
	if (!$inc) $inc = 5;
	if (!$start) $start = '12:00 am';
	if (!$stop) $stop = '11:59 pm';
	$current = strtotime($start);
	$end = strtotime($stop);
	$customtime = strtotime($customtime);
	$didend = false;
	while($current <= $end) {
		$values[date('g:i a', $current)] = date('g:i a', $current);
		if( ($customtime > $current) && ($customtime < ($current+($inc *60))) ) {
			$values[date('g:i a', $customtime)] = date('g:i a', $customtime);
		}

		if ($current == $end)
			$didend = true;
		
		$current += $inc *60;
	}
	
	if (!$didend)
		$values[date('g:i a', $end)] = date('g:i a', $end);
	
	return $values;
}


function time_select($form, $section, $field, $none = NULL, $inc = NULL, $start = NULL, $stop = NULL, $extraHtml = NULL) {
	if(!$inc) $inc = 5;
	if(!$start) $start = '12:00 am';
	if(!$stop) $stop = '11:55 pm';
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
		<option value="0"> -- Select an Audio File -- </option>
	<?
	foreach($files as $audio)
		print "<option value=\"$audio->id\">" . escapehtml($audio->name) . "</option>";
	?>
	</select>
	<?
}

// Returns a plain text version of the provided $html text.
// NOTE: This function may be enhanced in the future to better represent the contents of the $html text,
// such as replacing HR tags with dashes, or formatting tables as fixed width, etc..
function html_to_plain($html) {
	// Escape data-field-insert tags.
	$html = str_replace(array('<<', '>>'), array('&lt;&lt;', '&gt;&gt;'), $html);
	
	$plain = strip_tags($html);
	
	// Unescape data-field-insert tags, and &nbsp; characters.
	$plain = str_replace(array('&lt;&lt;','&gt;&gt;', '&nbsp;'), array('<<', '>>', ''), $plain);
	
	return trim($plain);
}

/**
 * Generates an id usable in html based on arguments passed.
 * If this is called twice with the same args it returns a new unique id by appending an incrementing suffix.
 * The first arg, or prefix, is directly used in the id for readability.
 */
function autoID($prefix /*, arg1, arg2, ... */) {
	static $usedcounters = array();
	
	$k = $prefix . crc32(implode(":", func_get_args()));
	@$usedcounters[$k]++;
	if ($usedcounters[$k] > 1)
		$k .= "_" . $usedcounters[$k];
	return $k;
}

?>
