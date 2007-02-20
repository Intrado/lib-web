<?
// phone inbound, login prompt and authentication

include_once("inboundutils.inc.php");
include_once("../obj/User.obj.php");
include_once("../obj/Access.obj.php");
include_once("../obj/Permission.obj.php");

global $SESSIONDATA, $BFXML_VARS;


function login($playerror)
{
	global $SESSIONID, $SESSIONDATA;
?>
<voice sessionid="<?= $SESSIONID ?>">
	<message name="login">
		<field name="code" type="dtmf" timeout="5000" max="20">
			<prompt repeat="2">

<?			if ($playerror) { ?>
				<audio cmid="file://prompts/inbound/AuthenticationFailed.wav" />
<?			} ?>

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

function authFailure()
{
	global $SESSIONID;

	// TODO need tts message
?>
<voice sessionid="<?= $SESSIONID ?>">
	<message name="goodbye">
		<audio cmid="file://prompts/inbound/AuthenticationFailed.wav" />

		<tts gender="female">Please check your I D and pin and try again later</tts>

		<audio cmid="file://prompts/GoodBye.wav" />
		<hangup />
	</message>
</voice>
<?
}

function welcome()
{
	global $SESSIONID;
?>

<voice sessionid="<?= $SESSIONID ?>">
	<message name="welcome">
		<audio cmid="file://prompts/inbound/Welcome.wav" />
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

				// successful login, save the userid and move on
				$SESSIONDATA['userid'] = $userid;
				forwardToPage("inboundmessage.php");
			}
		}
	}

	// count authorization attempts, kick them out after 3
	if (isset($SESSIONDATA['authcount'])) {
		$SESSIONDATA['authcount']++; // increment
	} else {
		$SESSIONDATA['authcount'] = 0;
	}
	// only allow 3 attempts to login, then hangup
	if ($SESSIONDATA['authcount'] >= 3) {
		//forwardToPage("inboundgoodbye.php");
		authFailure();
	}

	// play the prompt
	login($SESSIONDATA['authcount'] > 0);

?>