<?

function help($title, $extrahtml = NULL, $style = NULL) {
	$theme = getBrandTheme();
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

	$contents = nl2br($contents);
	
	if (!isset($GLOBALS['TIPS']))
		$GLOBALS['TIPS'] = array();
	$tipid = "tip_" . count($GLOBALS['TIPS']);
	$GLOBALS['TIPS'][] = array($tipid,$contents); //navbotom.inc will load these for us

	$hover = '<span id="'.$tipid.'" class="hoverhelpicon ' . ($link != "" ? "helpclick" : "") . '" ' . $extrahtml . '>';
	$hover .= '<img align="absmiddle" src="img/themes/' . $theme . '/helpcenter' . ($style ? '_' . $style : "") . '.gif"';
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
	$str = '<a href="'.$href.'" '.$onclick.' class="actionlink" title="'.escapehtml($title).'">';
	if ($actionlinkmode == "both" || $actionlinkmode == "icons")
		$str .= '<img src="img/icons/'.$icon.'.gif" alt="'.escapehtml($title).'">';
	if ($actionlinkmode == "both" || $actionlinkmode == "text")
		$str .= escapehtml($title);
	$str .= '</a>';
	return $str;
}

function action_links ($array) {
	$links = is_array($array) ? $array : func_get_args();
	foreach ($links as $key => $link)
		if ($link == "")
			unset($links[$key]);
	return '<div class="actionlinks">' . implode("&nbsp;|&nbsp;",$links).'</div>';
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
		<div style="clear: left; height: 1px; margin-top: -1px;"><img src="img/pixel.gif" alt=""/></div>
<?
		echo implode('', $buttons);
?>
		<div style="clear: left; height: 1px; margin-top: -1px;"><img src="img/pixel.gif" alt=""/></div>
<?
}

function button_bar() {
	$buttons = func_get_args();
?>
		<div style="clear: both; height: 1px; margin-top: -1px;"><img src="img/pixel.gif" alt=""/></div>
<?
		echo implode('', $buttons);
?>
		<div style="clear: both; height: 1px; margin-top: -1px;"><img src="img/pixel.gif" alt=""/></div>
<?

}


function hidden_submit_button($value = "submit") {
	$btn = '<button style="position: absolute; left: -1000px; top: -1000px;" type="submit" name="submit" value="'.escapehtml($value).'" onclick="return form_submit(event,\''.escapehtml($value).'\');"></button>';
	return $btn;
}

function submit_button($name, $value = "submit", $icon = null) {
	$theme = getBrandTheme();
	$btn = '<button class="button" type="submit" name="submit" value="'.escapehtml($value).'" onmouseover="btn_rollover(this);" onmouseout="btn_rollout(this);" onclick="return form_submit(event,\''.escapehtml($value).'\');"><table><tr><td><img class="left" src="img/themes/' . $theme. '/button_left.gif" alt=""></td><td class="middle">';

	if ($icon == null)
		$btn .= '<img src="img/pixel.gif" alt="" height="16" width="1">';		
	else
		$btn .= '<img src="img/icons/'.$icon.'.gif" alt="">';
	
	$btn .= escapehtml($name) . '</td><td><img class="right" src="img/themes/' . $theme . '/button_right.gif" alt=""></td></tr></table></button>';
	
	return $btn;
}

function icon_button($name,$icon,$onclick = NULL, $href = NULL, $extrahtml = NULL) {
	$theme = getBrandTheme();
	$btn = '<button class="button" type="button" onmouseover="btn_rollover(this);" onmouseout="btn_rollout(this);"';
	if ($onclick)
		$btn .= ' onclick="' . $onclick . ';" ';
	else if ($href)
		$btn .= ' onclick="window.location=\'' . $href . '\';" ';

	if ($extrahtml)
		$btn .= $extrahtml;
	$btn .= '><table><tr><td><img class="left" src="img/themes/' . $theme. '/button_left.gif" alt=""></td><td class="middle"><img src="img/icons/'.$icon.'.gif" alt="">' . escapehtml($name) . '</td><td><img class="right" src="img/themes/' . $theme . '/button_right.gif" alt=""></td></tr></table></button>';
	
	return $btn;
}

function button($name,$onclick = NULL, $href = NULL, $extrahtml = NULL) {
	$theme = getBrandTheme();
	$btn = '<button class="button" type="button" onmouseover="btn_rollover(this);" onmouseout="btn_rollout(this);"';
	if ($onclick)
		$btn .= ' onclick="' . $onclick . ';" ';
	else if ($href)
		$btn .= ' onclick="window.location=\'' . $href . '\';" ';

	if ($extrahtml)
		$btn .= $extrahtml;
	$btn .= '><table><tr><td><img class="left" src="img/themes/' . $theme. '/button_left.gif" alt=""></td><td class="middle"><img src="img/pixel.gif" alt="" height="16" width="1">' . escapehtml($name) . '</td><td><img class="right" src="img/themes/' . $theme . '/button_right.gif" alt=""></td></tr></table></button>';
	
	return $btn;
}


function submit($form, $section, $name = 'Submit',$val = null) {
	//ugly hack. in order for enter key to submit form, either we need to add JS to each text field, or there must be an actual submit button
	//so we make a submit button and hide it off screen.
	$ret = '<input type="submit" value="submit" name="submit[' . $form . '][' . $section . ']" style="position: absolute; left: -1000px; top: -1000px;">';

	if ($val !== null) {
		$ret .= button($name,"submitForm('$form','$section','$val');");
	} else {
		$ret .= button($name,"submitForm('$form','$section');");
	}
	return $ret;
}

function add($name, $file = 'add') {
	return '<input type="image" name="' . $name . '" value="' . $name . '" src="img/b1_' . $file . '.gif" onMouseOver="this.src=\'img/b2_' . $file . '.gif\';" onMouseOut="this.src=\'img/b1_' . $file . '.gif\';">';
}


function newform_time_select($inc = NULL, $start = NULL, $stop = NULL, $customtime = "") {
	$values = array();
	if (!$inc) $inc = 5;
	if (!$start) $start = '12:00 am';
	if (!$stop) $stop = '11:55 pm';
	$current = strtotime($start);
	$end = strtotime($stop);
	$customtime = strtotime($customtime);
	while($current <= $end) {
		$values[date('g:i a', $current)] = date('g:i a', $current);
		if( ($customtime > $current) && ($customtime < ($current+($inc *60))) ) {
			$values[date('g:i a', $customtime)] = date('g:i a', $customtime);
		}
		$current += $inc *60;
	}
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
?>