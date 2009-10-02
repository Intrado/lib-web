<?

// prompt for activation code
function promptCode($retry = false) {
?>
<voice>
	<message name="activate">
		<field name="code" type="dtmf" timeout="10000" max="20">
			<prompt repeat="2">
<?			if ($retry) { ?>
				<tts gender="female" language="english">Sorry, the activation code is not valid or has expired.</tts>
<?  		} ?>
				<tts gender="female" language="english">Please enter your activation code, followed by the pound key.</tts>
			</prompt>
			<timeout>
				<goto message="error" />
			</timeout>
		</field>
	</message>

	<message name="error">
		<tts gender="female" language="english">I was not able to understand your response.  Goodbye.</tts>
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
function okGoodbye($hassubscriber) {
	$text = "Thank you, your contacts have been associated with your account.  You may now login to the web application to edit your contact information.  Goodbye.";
	if ($hassubscriber) {
		$text = "Thank you, this phone number has been associated with your account.  You may now login to the web application to edit your contact preferences.  Goodbye.";
	}
?>
<voice>
	<message name="welcome">
    	<tts gender="female" language="english"><?=$text?></tts>
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
    	<tts gender="female" language="english">Sorry, the activation code is not valid or has expired.  Goodbye.</tts>
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
	$hassubscriber = QuickQuery("select value from setting where name='_hasselfsignup'");
	if ($hassubscriber == "1") {
		$hassubscriber = true;
	} else {
		$hassubscriber = false;
	}
	// if not subscriber, then must be contact manager to be here already
	
	if (isset($BFXML_VARS['code'])) {
		$code = $BFXML_VARS['code'];
		$callerid = $_SESSION['callerid'];
		$ok = false;
		if ($hassubscriber) {
			$ok = inboundSubscriberPhoneActivation($callerid, $code);
		} else {
			$ok = inboundPortalPhoneActivation($callerid, $code);
		}
		
		if ($ok) {
			okGoodbye($hassubscriber);
		} else {
			$_SESSION['phoneattempts']++;
			if ($_SESSION['phoneattempts'] >= 3) {
				failGoodbye();
			} else {
				promptCode(true);
			}
		}
	} else if (isset($_SESSION['callerid'])) {
		// inboundPortalFindCallerid works for both contact manager and subscriber features, simple lookup
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