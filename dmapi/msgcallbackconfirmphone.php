<?
include_once("inboundutils.inc.php");

global $BFXML_VARS;

function confirmcallerid($callerid) {
glog("confirmcallerid");
?>
<voice>
	<message name="choosecallerid">
			<field name="callerid" type="menu" timeout="5000" sticky="false">
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
	forwardToPage("inboundstart.php");
} else if($REQUEST_TYPE == "continue") {
	glog("continue");
	if(isset($BFXML_VARS['callerid'])){
		if($BFXML_VARS['callerid'] == 1){
			$_SESSION['contactphone'] = $_SESSION['callerid']; // set the phone number used to playback messages
			glog("Caller id confirmed: implement correct forward. Lookup if student id is needed");

			forwardToPage("msgcallbackenterstudentid.php");
		} else {
			glog("Need to enter number");
			forwardToPage("msgcallbackenterphone.php");
		}
	} else if(isset($_SESSION['callerid'])){   // Neet to test this from a unknown number
		glog("option of entering number");
		confirmcallerid($_SESSION['callerid']);
	} else {
		glog("have to enter number");
		forwardToPage("msgcallbackenterphone.php");
	}

}




 ?>