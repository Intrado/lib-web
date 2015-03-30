<?
// Moving out common parts from edit message pages that was inconsistent between edit pages 

// Setting editmessage session value but also referer info to determine done button link 
function setEditMessageSession() {
	if (isset($_GET['id']) && $_GET['id'] != "new") {
		$_SESSION['editmessagereferer'] = (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : NULL);
		// this is an edit for an existing message
		$_SESSION['editmessage'] = array("messageid" => $_GET['id']);
		redirect();
	} else if (isset($_GET['mgid'])) {
		$_SESSION['editmessagereferer'] = (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : NULL);
		$_SESSION['editmessage'] = array("messagegroupid" => $_GET['mgid']);
		
		if (isset($_GET['languagecode']))
			$_SESSION['editmessage']["languagecode"] = $_GET['languagecode'];
		
		// subtype is optional but will tell the form item if it should load the "plain" editor
		// default behavior loads the "html" editor
		if (isset($_GET['subtype']))
			$_SESSION['editmessage']['subtype'] = $_GET['subtype'];
		
		if (isset($_GET['stationeryid'])) {
			$_SESSION['editmessage']['stationeryid'] = $_GET['stationeryid'];
			$_SESSION['editmessagereferer'] = "mgeditor.php";
		}
		redirect();
	}
}

//Logic to determine done button send to
function getEditMessageSendTo($id) {
	// where to send back to
	if ($_SESSION['editmessagereferer']) {
		$endscript = strpos($_SESSION['editmessagereferer'], "?");
		if ($endscript > 0)
			$sendto = substr($_SESSION['editmessagereferer'], 0, $endscript);
		else
			$sendto = $_SESSION['editmessagereferer'];
	} else {
		$sendto = "mgeditor.php";
	}
	// if we came from the message group editor (default) add the id into the url
	if (strpos($sendto, "mgeditor.php") !== false)
		$sendto .= "?id=$id";
	
	return $sendto;
}

?>