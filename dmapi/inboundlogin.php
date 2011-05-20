<?
// phone inbound, login prompt and authentication

//FIXME remove includes already included with request.php, the parent php file
include_once("../obj/User.obj.php");
include_once("../obj/Access.obj.php");
include_once("../obj/Permission.obj.php");
require_once("../inc/auth.inc.php");
require_once("../inc/utils.inc.php");
include_once("inboundutils.inc.php");

global $BFXML_VARS;


function login($playerror)
{
?>
<voice>
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

?>
<voice>
	<message name="goodbye">
		<audio cmid="file://prompts/inbound/AuthenticationFailed.wav" />
		<audio cmid="file://prompts/inbound/CheckIDandTryLater.wav" />
		<audio cmid="file://prompts/GoodBye.wav" />
		<hangup />
	</message>
</voice>
<?
}

function welcome()
{
?>

<voice>
	<message name="welcome">
		<audio cmid="file://prompts/inbound/Welcome.wav" />
	</message>
</voice>
<?
}

///////////////////////////////////

if($REQUEST_TYPE == "new" ||
   $REQUEST_TYPE == "continue") {

	$success = false; // check login successful

	// if login prompt has played, gather code/pin to authenticate
	if (isset($BFXML_VARS['code'])) {
		$code = DBSafe($BFXML_VARS['code']);
		$pin = DBSafe($BFXML_VARS['pin']);
		$inboundNumber = $_SESSION['inboundNumber'];

		//error_log("inbound ".$inboundNumber);

		// do not allow empty code/pin
		if ($code != "" && $pin != "") {
			// find user and authenticate them against database
			$query = "from user where enabled=1 and deleted=0 and accesscode='".$code."' and (pincode=password('".$pin."') or pincode=old_password('".$pin."'))";
			$user = DBFind("User", $query);
			if ($user) {
				$access = new Access($user->accessid);

				// check their permissions
				if ($access->getPermission("loginphone") &&
					$access->getPermission("sendphone")) {

					// now check if LDAP customer/user
					if (getSystemSetting("_hasldap") && $user->ldap) {
						if (doLoginPhoneUserEnabled($code, $pin, $inboundNumber)) {
							$success = true;
						}
					} else {
						$success = true;
					}
				} // access
			} // user
		} // code pin
	}
	if ($success) {
		// successful login, save the userid and move on
		$_SESSION['userid'] = $user->id;
		forwardToPage("inboundmessage.php");
	} else {
		// count authorization attempts, kick them out after 3
		if (isset($_SESSION['authcount'])) {
			$_SESSION['authcount']++; // increment
		} else {
			$_SESSION['authcount'] = 0;
		}
		// only allow 3 attempts to login, then hangup
		if ($_SESSION['authcount'] >= 3) {
			authFailure();
		} else {
			// play the prompt
			login($_SESSION['authcount'] > 0);
		}
	}
} else {
	//huh, they must have hung up
	$_SESSION = array();
	?>
	<ok />
	<?
}

?>
