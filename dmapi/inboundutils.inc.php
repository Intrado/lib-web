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
		//error_log("ERROR: inboundutils->loadUser() called before SESSIONDATA[userid] was set");
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

?>
