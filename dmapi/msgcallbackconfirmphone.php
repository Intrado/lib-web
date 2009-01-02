<?
include_once("inboundutils.inc.php");
include_once("../obj/Phone.obj.php");

global $BFXML_VARS;

function confirmcallerid($callerid) {
?>
<voice>
	<message name="choosecallerid">
			<field name="callerid" type="menu" timeout="10000">
			<prompt repeat="2">
			    <tts gender="female" language="english">It looks like you're calling from <? echo Phone::format($callerid) ?>. </tts>
	    	    <tts gender="female" language="english">If this is the phone number which received the call,  please press 1, or press 2 to enter a different phone number.</tts>
			</prompt>
			<choice digits="1" />
			<choice digits="2" />
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
			invalidgoodbye();
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