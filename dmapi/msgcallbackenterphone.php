<?	
include_once("inboundutils.inc.php");

global $BFXML_VARS;

function entercallerid($attempt) {
glog("confirmcallerid");
?>	
<voice>
	<message name="entercallerid">
			<setvar name="attempt" value="<?echo $attempt;?>" />
			<?if ($attempt > 0) { ?>
					<tts gender="female" language="english">Invalid phone number. Phone number must be 10 digits including area code.</tts>
			<?} ?>
			<field name="phone" type="dtmf" timeout="5000" max="11">
				<prompt repeat="2">
				<tts gender="female" language="english">Please enter the phone number that recieved the call followed by the pound key</tts>
				</prompt>
				<timeout>
					<tts gender="female" language="english">I was not able to understand your response. Goodbye.</tts>
					<hangup />
				</timeout>	
			</field>
	</message>
</voice>
<?
}
function invalidend() {
?>	
<voice>
	<message name="invalidend">
	       	<tts gender="female">Invalid phone number. Please call back and try again. Goodbye.</tts>
	       	<hangup />
	</message>
</voice>
<?
}




if($REQUEST_TYPE == "new") {
	forwardToPage("inboundstart.php");
} else if($REQUEST_TYPE == "continue") {
	glog("continue");	
	if(isset($BFXML_VARS['phone'])){
		$phonenumber = $BFXML_VARS['phone'];
		glog("Entered phone number \"$phonenumber\"");	

		if(strlen($phonenumber) == 7 && substr($phonenumber,0,1) != "0") {
			$query = "select value as areacode from setting where name=\"defaultareacode\"";
			
			$areacode = QuickQuery($query);
			glog("Getting area code $areacode");	
			
			if($areacode !== false){
				$phonenumber = $areacode . $phonenumber;
			}	
		}
		if(strpos($phonenumber,'*') === false && strlen($phonenumber) == 10 && substr($phonenumber,0,1) != "1" && substr($phonenumber,0,1) != "0" ){
			glog("Valid; implement correct forward. Lookup if student id is needed");
			$_SESSION['callerid'] = $phonenumber;
			forwardToPage("msgcallbackenterstudentid.php");
		} else {
			$attempt = $BFXML_VARS['attempt'];
			$attempt++;
			glog("Not Valid Attempt " . $attempt);
			if($attempt > 2) {
				invalidend();	
			} else {
				entercallerid($attempt);	
			}
		}
	} else {
		$_SESSION['phoneattempts'] = 0;
		entercallerid(0);
	}
}
	
	

		
 ?>