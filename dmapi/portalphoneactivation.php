<?

// prompt for activation code
function promptCode($retry = false) {
?>
<voice>
	<message name="activate">
		<field name="code" type="dtmf" timeout="5000" max="20">
			<prompt repeat="2">
<?			if ($retry) { ?>
				<tts gender="female" language="english">Sorry, the activation code is invalid or expired.</tts>
<?  		} ?>
				<tts gender="female" language="english">Please enter your activation code, followed by the pound key.</tts>
			</prompt>
			<timeout>
				<goto message="error" />
			</timeout>
		</field>
	</message>

	<message name="error">
		<tts gender="female" language="english">Sorry, your response was not understood.  Goodbye.</tts>
		<hangup />
	</message>
</voice>
<?
}

// callerid not found with token
function calleridUnknown() {
?>
<voice>
	<message name="welcome">
    	<tts gender="female" language="english">Sorry, there are no contacts to activate from this caller I. D.  Please be sure you are calling from one of the registered phone numbers.  Thank you, goodbye.</tts>
		<hangup />
	</message>
</voice>
<?
}

// no callerid
function calleridMissing() {
?>
<voice>
	<message name="welcome">
    	<tts gender="female" language="english">Sorry, there is no caller I. D. provided.  Please hang up and dial star 8 2 before the number to unblock your caller I. D.  Thank you, goodbye.</tts>
		<hangup />
	</message>
</voice>
<?
}

// successful to associate contacts
function okGoodbye() {
?>
<voice>
	<message name="welcome">
    	<tts gender="female" language="english">Thank you, your contacts have been associated with your account.  You may now login to the web application to edit your contact information.  Goodbye.</tts>
		<hangup />
	</message>
</voice>
<?
}

// failure to associate contacts
function failGoodbye() {
?>
<voice>
	<message name="welcome">
    	<tts gender="female" language="english">Sorry, the activation code is invalid or expired.  Goodbye.</tts>
		<hangup />
	</message>
</voice>
<?
}


///////////////////////////////////////
// process request
if ($REQUEST_TYPE == "new") {
	?>
	<error>portalphoneactivation: wanted result or continue, got new </error>
	<hangup />
	<?
} else if ($REQUEST_TYPE == "continue") {
	if (isset($BFXML_VARS['code'])) {
		$code = $BFXML_VARS['code'];
		$callerid = $_SESSION['callerid'];
		if (inboundPortalPhoneActivation($callerid, $code)) {
			okGoodbye();
		} else {
			$_SESSION['phoneattempts']++;
			if ($_SESSION['phoneattempts'] >= 3) {
				failGoodbye();
			} else {
				promptCode(true);
			}
		}
	} else if (isset($_SESSION['callerid'])) {
		if (inboundPortalFindCallerid($_SESSION['callerid'])) {
			$_SESSION['phoneattempts'] = 0;
			promptCode();
		} else {
			calleridUnknown();
		}
	} else {
		calleridMissing();
	}
} else if ($REQUEST_TYPE == "result") {
	//huh, they must have hung up
	$SESSIONDATA = null;
?>
	<ok />
<?
}

?>