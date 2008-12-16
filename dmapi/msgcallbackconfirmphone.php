<?
include_once("../obj/Phone.obj.php");

global $BFXML_VARS;

function invalidgoodbye2() {
?>
<voice>
	<message name="welcome">
    	<tts gender="female" language="english">I did not understand your response.  Goodbye.</tts>
		<hangup />
	</message>
</voice>
<?
}

function confirmcallerid($callerid) {
?>
<voice>
	<message name="choosecallerid">
			<field name="callerid" type="menu" timeout="5000">
			<prompt repeat="2">
			    <tts gender="female" language="english">It looks like you are calling from <? echo Phone::format($callerid) ?>. </tts>
	    	    <tts gender="female" language="english">Press 1 if this is the number that recieved the call, otherwise Press 2.</tts>
			</prompt>
			<choice digits="1" />
			<choice digits="2" />
			<default>
	        	<tts gender="female" language="english">Sorry. That was not a valid option</tts>
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
	?>
	<error>msgcallbackconfirmphone: wanted result or continue, got new </error>
	<hangup />
	<?
} else if ($REQUEST_TYPE == "continue") {
	if (isset($BFXML_VARS['callerid'])) {
		if ($BFXML_VARS['callerid'] == 1) {
			$_SESSION['contactphone'] = $_SESSION['callerid']; // set the phone number used to playback messages

			$query = "select value from setting where name=\"msgcallbackrequireid\"";
			$requirestudentid = QuickQuery($query);

			if($requirestudentid == 1){
				forwardToPage("msgcallbackenterstudentid.php");
			} else {
				forwardToPage("msgcallbackgetlist.php");
			}
		} else if ($BFXML_VARS['callerid'] == 2) {
			forwardToPage("msgcallbackenterphone.php");
		} else {
			invalidgoodbye2();
		}
	} else if (isset($_SESSION['callerid']) && $_SESSION['callerid'] != "Unknown") {
		confirmcallerid($_SESSION['callerid']);
	} else {
		// from blocked callerid phone "Unknown"
		forwardToPage("msgcallbackenterphone.php");
	}
} else {
	//huh, they must have hung up
	$SESSIONDATA = null;
?>
	<ok />
<?
}

?>