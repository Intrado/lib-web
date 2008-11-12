<?
// phone inbound helper routines (aka utilities)

function loadUser()
{
	// load up the user and access info, used extensively throughout inbound routines
	if (isset($_SESSION['userid'])) {
		global $USER, $ACCESS;

		$USER = $_SESSION['user'] = new User($_SESSION['userid']);
		$ACCESS = $_SESSION['access'] = new Access($USER->accessid);
	} else {
		error_log("ERROR: inboundutils->loadUser() called before SESSIONDATA[userid] was set");
	}
}

function loadTimezone()
{

	if (!isset($_SESSION['timezone'])) {
		$USER = new User($_SESSION['userid']);
		$_SESSION['timezone'] = getSystemSetting("timezone");
		//error_log("setting timezone: ".$_SESSION['timezone']);
	}

	@date_default_timezone_set($_SESSION['timezone']);
	QuickUpdate("set time_zone='" . $_SESSION['timezone'] . "'");
}

function makenumeric($input) {
	$tonumeric = strtolower($input);
	$numeric = "";
	$tmp = "";
	while($tonumeric != "") {
		$tmp = substr($tonumeric,0,1);
		if (is_numeric($tmp)) {
			$numeric .= $tmp;
		} elseif ($tmp == 'a' || $tmp == 'b' || $tmp == 'c') {
			$numeric .= "2";
		} elseif ($tmp == 'd' || $tmp == 'e' || $tmp == 'f') {
			$numeric .= "3";
		} elseif ($tmp == 'g' || $tmp == 'h' || $tmp == 'i') {
			$numeric .= "4";
		} elseif ($tmp == 'j' || $tmp == 'k' || $tmp == 'l') {
			$numeric .= "5";
		} elseif ($tmp == 'm' || $tmp == 'n' || $tmp == 'o') {
			$numeric .= "6";
		} elseif ($tmp == 'p' || $tmp == 'q' || $tmp == 'r' || $tmp == 's') {
			$numeric .= "7";
		} elseif ($tmp == 't' || $tmp == 'u' || $tmp == 'v') {
			$numeric .= "8";
		} elseif ($tmp == 'w' || $tmp == 'x' || $tmp == 'y' || $tmp == 'z') {
			$numeric .= "9";
		}
		$tonumeric = substr($tonumeric,1);
	}
	return $numeric;
}

?>
