<?
// phone inbound helper routines (aka utilities)
include_once("../obj/User.obj.php");
include_once("../obj/Access.obj.php");
include_once("../inc/utils.inc.php");


function glog($s)
{
	//echo "GJB ".$s."\n";
}

function loadUser()
{
	// load up the user and access info, used extensively throughout inbound routines
	global $SESSIONDATA;
	if (isset($SESSIONDATA['userid'])) {
		global $USER, $ACCESS;

		$USER = new User($SESSIONDATA['userid']);
		$ACCESS = new Access($USER->accessid);
	} else {
		glog("ERROR: inboundutils->loadUser() called before SESSIONDATA[userid] was set");
	}
}

function loadTimezone()
{
	global $SESSIONDATA;

	if (!isset($SESSIONDATA['timezone'])) {
		$USER = new User($SESSIONDATA['userid']);
		$SESSIONDATA['timezone'] = getSystemSetting("timezone");
		glog("setting timezone: ".$SESSIONDATA['timezone']);
	}

	@date_default_timezone_set($SESSIONDATA['timezone']);
	QuickUpdate("set time_zone='" . $SESSIONDATA['timezone'] . "'");
}

?>