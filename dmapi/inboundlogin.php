<?
// phone inbound, login prompt and authentication

include_once("inboundutils.inc.php");
include_once("../obj/User.obj.php");
include_once("../obj/Access.obj.php");
include_once("../obj/Permission.obj.php");

global $SESSIONDATA, $BFXML_VARS;


function login($playerror)
{
	global $SESSIONID;
?>
<voice sessionid="<?= $SESSIONID ?>">
	<message name="login">

<?	if ($playerror) { ?>
		<audio cmid="file://prompts/inbound/AuthenticationFailed.wav" />
<?	} ?>

		<field name="code" type="dtmf" timeout="5000" max="20">
			<prompt repeat="2">
				<audio cmid="file://prompts/inbound/EnterUserID.wav" />

			</prompt>
			<timeout>
				<goto message="error" />
			</timeout>
		</field>

		<field name="pin" type="dtmf" timeout="5000" max="20">
			<prompt repeat="2">
				<audio cmid="file://prompts/inbound/EnterPINCode.wav" />

			</prompt>
			<timeout>
				<goto message="error" />
			</timeout>
		</field>
	</message>

	<message name="error">
		<audio cmid="file://prompts/inbound/Error.wav" />
		<hangup />
	</message>
</voice>
<?
}

///////////////////////////////////

	// if login prompt has played, gather code/pin to authenticate
	if (isset($BFXML_VARS['code'])) {
		$code = $BFXML_VARS['code'];
		$pin = $BFXML_VARS['pin'];
		$inboundNumber = $SESSIONDATA['inboundNumber'];

		// find user and authenticate them against database
		$userid = User::doLoginPhone($code, $pin, $inboundNumber);
		glog("userid: ".$userid);

		if ($userid) {
			$user = new User($userid);
			$access = new Access($user->accessid);

			// check their permissions
			if ($access->getPermission("loginphone") &&
				$access->getPermission("sendphone")) {

// TODO set timezone based on customer
/*
if (isset($_SESSION['timezone'])) {
	@date_default_timezone_set($_SESSION['timezone']);
	QuickUpdate("set time_zone='" . $_SESSION['timezone'] . "'");
}
*/

				// successful login, save the userid and move on
				$SESSIONDATA['userid'] = $userid;
				forwardToPage("inboundmessage.php");
			}
		}
		// authentication failure
		if (!isset($SESSIONDATA['userid'])) {
			$SESSIONDATA['authcount']++;
			// only allow 3 attempts to login, then hangup
			if ($SESSIONDATA['authcount'] >= 3) {
				forwardToPage("inboundgoodbye.php");
			} else {
				login(true);  // true, playback auth error and relogin
			}
		}
	// else play login prompt
	} else {
		login(false);  // this is likely the first login prompt
		$SESSIONDATA['authcount'] = 0;
	}
?>