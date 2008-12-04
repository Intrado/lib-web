<?php
//include_once("inboundutils.inc.php");

global $BFXML_VARS;

function invalidgoodbye() {
?>
<voice>
	<message name="welcome">
    	<tts gender="female" language="english">I did not understand your response.  Goodbye.</tts>
		<hangup />
	</message>
</voice>
<?
}

function welcomemessage($hascallback, $hasphoneactivation) {
?>
<voice>
	<message name="welcome">
		<tts gender="female" language="english">Welcome to the Schoolmessenger Phone Service.</tts>
		<goto message="choose" />
	</message>
	<message name="choose">
			<field name="redirect" type="menu" timeout="5000">
			<prompt repeat="2">
<?				if ($hascallback) { ?>
					<tts gender="female" language="english">To retrieve messages sent to you, press 1.</tts>
<?				} ?>
					<tts gender="female" language="english">If you are a user and want to log in, press 2.</tts>
<?				if ($hasphoneactivation) { ?>
					<tts gender="female" language="english">To activate contacts, press 3.</tts>
<?				} ?>
			</prompt>

<?			if ($hascallback) { ?>
				<choice digits="1" />
<?			} ?>
				<choice digits="2" />
<?			if ($hasphoneactivation) { ?>
				<choice digits="3" />
<?			} ?>

			<default>
				<tts gender="female" language="english">Sorry. That was not a valid option.</tts>
			</default>

			<timeout>
				<tts gender="female" language="english">I was not able to understand your response. Goodbye.</tts>
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
		welcomemessage($hascallback, $hasphoneactivation);
	} else {
		forwardToPage("inboundlogin.php");
	}
} else if ($REQUEST_TYPE == "continue") {
	if ($BFXML_VARS['redirect'] == 3) {
		forwardToPage("portalphoneactivation.php");
	} else if ($BFXML_VARS['redirect'] == 2) {
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