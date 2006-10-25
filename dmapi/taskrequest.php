<?

//define some helper functions
//loads a page and adds the current page to the stack
function setNextPage ($thepage) {
	global $SESSIONDATA;
	$SESSIONDATA['_nav_curpage'] = $thepage;
}

function forwardToPage ($thepage, $setpage = true) {
	//NOTE: must declare any globals to share with the script
	global $BFXML_ELEMENT, $BFXML_VARS, $SESSIONID, $SESSIONDATA, $REQUEST_TYPE;

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
			$value = $datum['txt'];
			$BFXML_VARS[$name] = $value;
		}
	}
}

//look for a sessionid on the taskrequest, if so, load the session data from the DB
if (isset($BFXML_ELEMENT['attrs']['SESSIONID'])) {
	$SESSIONID = $BFXML_ELEMENT['attrs']['SESSIONID'];
	$SESSIONDATA = loadSessionData($SESSIONID);
} else {
	$SESSIONID = uniqid(mt_rand(), true);
	$SESSIONDATA = array();
}

$REQUEST_TYPE = $BFXML_ELEMENT['attrs']['REQUEST'];

//var_dump($BFXML_ELEMENT);

//is this a new task request?
if ($REQUEST_TYPE) {
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
} else {
	//do we have a current page set? I hope so!
	if (isset($SESSIONDATA['_nav_curpage']) && $SESSIONDATA['_nav_curpage']) {
		include($SESSIONDATA['_nav_curpage']);
	} else {
?>
	<error>No page set!</error>
<?
	}
}

//save the session data
if ($SESSIONDATA === null)
	eraseSessionData($SESSIONID);
else
	storeSessionData ($SESSIONID, 0, $SESSIONDATA);

?>