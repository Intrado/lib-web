<?
//include_once("inboundutils.inc.php");

global $BFXML_VARS;

function entercallerid($attempt) {
?>
<voice>
	<message name="entercallerid">
			<?if ($attempt > 0) { ?>
					<tts gender="female" language="english">Invalid phone number. Phone number must be 10 digits including area code.</tts>
			<?} ?>
			<field name="phone" type="dtmf" timeout="5000" max="11">
				<prompt repeat="2">
				<tts gender="female" language="english">Please enter the phone number that recieved the call followed by the pound key.</tts>
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
	?>
	<error>msgcallbackenterphone: wanted result or continue, got new </error>
	<hangup />
	<?
} else if($REQUEST_TYPE == "continue") {
	if(isset($BFXML_VARS['phone'])){
		$phonenumber = $BFXML_VARS['phone'];

		if(strlen($phonenumber) == 7 && substr($phonenumber,0,1) != "0") {
			$query = "select value as areacode from setting where name=\"defaultareacode\"";

			$areacode = QuickQuery($query);

			if($areacode !== false){
				$phonenumber = $areacode . $phonenumber;
			}
		}
		if(strpos($phonenumber,'*') === false && strlen($phonenumber) == 10 && substr($phonenumber,0,1) != "1" && substr($phonenumber,0,1) != "0" ){
			$_SESSION['contactphone'] = $phonenumber; // store the phone number used to playback messages

			$query = "select value from setting where name=\"msgcallbackrequireid\"";
			$requirestudentid = QuickQuery($query);

			if($requirestudentid == 1){
				forwardToPage("msgcallbackenterstudentid.php");
			} else {
				forwardToPage("msgcallbackgetlist.php");
			}
		} else {
			$_SESSION['phoneattempts']++;
			if($_SESSION['phoneattempts'] > 2) {
				invalidend();
			} else {
				entercallerid($_SESSION['phoneattempts']);
			}
		}
	} else {
		$_SESSION['phoneattempts'] = 0;
		entercallerid($_SESSION['phoneattempts']);
	}
} else {
	//huh, they must have hung up
	$SESSIONDATA = null;
?>
	<ok />
<?
}


 ?>