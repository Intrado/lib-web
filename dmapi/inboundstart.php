<?php
include_once("inboundutils.inc.php");

global $BFXML_VARS;

function welcomemessage($hascallback, $hasphoneactivation, $displayname) {
?>
<voice>
	<message name="welcome">
		<tts gender="female" language="english">Welcome to the School Messenger Notification System for <?=$displayname?>.</tts>
		<goto message="choose" />
	</message>
	<message name="choose">
			<field name="redirect" type="menu" timeout="10000">
			<prompt repeat="2">
<?				if ($hascallback) { ?>
					<tts gender="female" language="english">If you have recently received a phone call, and would like to listen to the message, press 1.</tts>
<?				} ?>
<?				if ($hasphoneactivation) { ?>
					<tts gender="female" language="english">If you are calling to enter a Contact  Manager Activation Code, press 2.</tts>
<?				} ?>

					<tts gender="female" language="english">If you have a user account, with an ID and PIN code, press 9. </tts>
					<tts gender="female" language="english">To repeat this menu, press star. </tts>
			</prompt>

<?			if ($hascallback) { ?>
				<choice digits="1" />
<?			} ?>
<?			if ($hasphoneactivation) { ?>
				<choice digits="2" />
<?			} ?>

				<choice digits="9" />
			<default>
				<tts gender="female" language="english">Sorry, that was not a valid selection.</tts>
			</default>

			<timeout>
				<tts gender="female" language="english">I'm sorry, but I was not able to understand your selection. Please call back and try again. goodbye!</tts>
				<hangup />
			</timeout>
		</field>
	</message>
</voice>
<?
}

if ($REQUEST_TYPE == "new") {
	// determine inbound features available to this customer
	$hascallback = QuickQuery("select value from setting where name='_hascallback'");
	if ($hascallback == "1") {
		$hascallback = true;
	} else {
		$hascallback = false;
	}
	$hasphoneactivation = QuickQuery("select value from setting where name='portalphoneactivation'");
	$hasportal = QuickQuery("select value from setting where name='_hasportal'");
	if ($hasphoneactivation== "1" && $hasportal == "1") {
		$hasphoneactivation = true;
	} else {
		$hasphoneactivation = false;
	}
	// if customer _hascallback or _hasphoneactivation, give a choice, else inbound login only
	if ($hascallback || $hasphoneactivation) {
		$displayname = QuickQuery("select value from setting where name='displayname'");
		welcomemessage($hascallback, $hasphoneactivation, $displayname);
	} else {
		forwardToPage("inboundlogin.php");
	}
} else if ($REQUEST_TYPE == "continue") {
	if ($BFXML_VARS['redirect'] == 2) {
		forwardToPage("portalphoneactivation.php");
	} else if ($BFXML_VARS['redirect'] == 9) {
		forwardToPage("inboundlogin.php");
	} else if ($BFXML_VARS['redirect'] == 1) {
		forwardToPage("msgcallbackconfirmphone.php");
	} else {
		invalidgoodbye();
	}
} else if ($REQUEST_TYPE == "result") {
	//huh, they must have hung up
	$SESSIONDATA = null;
?>
	<ok />
<?
}


?>