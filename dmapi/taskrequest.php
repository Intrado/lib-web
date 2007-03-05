<?

//define some helper functions
//loads a page and adds the current page to the stack
function setNextPage ($thepage) {
	global $SESSIONDATA;
	$SESSIONDATA['_nav_curpage'] = $thepage;
}

function forwardToPage ($thepage, $setpage = true) {
	//NOTE: must declare any globals to share with the script
	global $BFXML_ELEMENT, $BFXML_VARS, $SESSIONID, $SESSIONDATA, $REQUEST_TYPE, $RESOURCEID, $SETTINGS;

	if ($setpage)
		setNextPage($thepage);
	include($thepage);
}

//parse task request vars into $BFXML_VARS
$BFXML_VARS = array();
if ($data = findChild($BFXML_ELEMENT,"DATA")) {
	if ($datums = findChildren($data,"DATUM")) {
		foreach ($datums as $datum) {
			$name = $datum['attrs']['NAME'];
			$value = (isset($datum['txt']) ? $datum['txt'] : "");
			$BFXML_VARS[$name] = $value;
		}
	}
}

//look for a sessionid on the taskrequest, if so, load the session data from the DB
$SESSIONDATA = array();
if (isset($BFXML_ELEMENT['attrs']['SESSIONID'])) {
	$SESSIONID = $BFXML_ELEMENT['attrs']['SESSIONID'];

	//only load sessiondata if the sessionid doesn't have the "outbound_" marker
	if (strpos($SESSIONID,"outbound_") === false)
		$SESSIONDATA = loadSessionData($SESSIONID);
} else {
	$SESSIONID = uniqid(mt_rand(), true);
}

$REQUEST_TYPE = $BFXML_ELEMENT['attrs']['REQUEST'];
$RESOURCEID = $BFXML_ELEMENT['attrs']['RESOURCEID'];


//var_dump($BFXML_ELEMENT);

//is this a new task request?
if ($REQUEST_TYPE == "new") {
	switch($BFXML_ELEMENT['attrs']['TYPE']) {
		case "voice":
			forwardToPage("phoneoutbound.php");
			break;
		case "voiceinbound":
			forwardToPage("phoneinbound.php");
			break;
		case "email":
			forwardToPage("email.php");
			break;
		case "print":
			forwardToPage("print.php");
			break;
	}
//check for outbound, it doesnt have any sessiondata so we need to check and forward directly to it
} else if (strpos($SESSIONID,"outbound_") === 0) {
	switch($BFXML_ELEMENT['attrs']['TYPE']) {
		case "voice":
			forwardToPage("phoneoutbound.php");
			break;
		case "email":
			forwardToPage("email.php");
			break;
	}
} else {
	//do we have a current page set? I hope so!
	if (isset($SESSIONDATA['_nav_curpage']) && $SESSIONDATA['_nav_curpage']) {
		include($SESSIONDATA['_nav_curpage']);
	} else {
?>
	<error>No page set!</error>
<?
	$SESSIONDATA = null;
	}
}

//a SESSIONID was generated, but perhaps the script has opted to not used it
if ($SESSIONID != null) {
	//save or delete the session data
	if ($SESSIONDATA === null)
		eraseSessionData($SESSIONID);
	else
		storeSessionData ($SESSIONID, 0, $SESSIONDATA);
}

?>