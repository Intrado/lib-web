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

function welcomemessage() {
?>
<voice>
	<message name="welcome">
    	<tts gender="female" language="english">Welcome to the Schoolmessenger Phone Service.</tts>
		<goto message="choose" />
	</message>
	<message name="choose">
			<field name="redirect" type="menu" timeout="5000">
			<prompt repeat="2">
	    	    <tts gender="female" language="english">To retrieve messages sent to you, press 1. If you are a user and want to log in, press 2</tts>
			</prompt>
			<choice digits="1" />
			<choice digits="2" />
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
	// if customer _hascallback feature, give a choice, else inbound login only
	$hascallback = QuickQuery("select value from setting where name='_hascallback'");
	if ($hascallback === "1") {
		welcomemessage();
	} else {
		forwardToPage("inboundlogin.php");
	}
} else if ($REQUEST_TYPE == "continue") {
	if ($BFXML_VARS['redirect'] == 2) {
		forwardToPage("inboundlogin.php");
	} else if ($BFXML_VARS['redirect'] == 1) {
		forwardToPage("msgcallbackconfirmphone.php");
	} else {
		invalidgoodbye();
	}
}


?>