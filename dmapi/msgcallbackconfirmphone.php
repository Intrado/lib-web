<?
//include_once("inboundutils.inc.php");

global $BFXML_VARS;

function confirmcallerid($callerid) {
?>
<voice>
	<message name="choosecallerid">
			<field name="callerid" type="menu" timeout="5000" sticky="true">
			<prompt repeat="2">
			    <tts gender="female" language="english">It looks like you are calling from </tts>
	    	    <tts gender="female" language="english"><? echo substr($callerid,0,3) . " " . substr($callerid,3,3)   . " " . substr($callerid,6); ?>. </tts>
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



if($REQUEST_TYPE == "new") {
	?>
	<error>msgcallbackconfirmphone: wanted result or continue, got new </error>
	<hangup />
	<?
} else if($REQUEST_TYPE == "continue") {
	if(isset($BFXML_VARS['callerid'])){
		if($BFXML_VARS['callerid'] == 1){
			$_SESSION['contactphone'] = $_SESSION['callerid']; // set the phone number used to playback messages

			$query = "select value from setting where name=\"msgcallbackrequireid\"";
			$requirestudentid = QuickQuery($query);

			if($requirestudentid == 1){
				forwardToPage("msgcallbackenterstudentid.php");
			} else {
				forwardToPage("msgcallbackgetlist.php");
			}
		} else {
			forwardToPage("msgcallbackenterphone.php");
		}
	} else if(isset($_SESSION['callerid'])){   // Neet to test this from a unknown number
		confirmcallerid($_SESSION['callerid']);
	} else {
		forwardToPage("msgcallbackenterphone.php");
	}

}




 ?>