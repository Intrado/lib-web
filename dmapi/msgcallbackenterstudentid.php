<?
include_once("inboundutils.inc.php");

global $BFXML_VARS;

function enterstudentid($error, $studentids) {
?>
<voice>
	<message name="choosestudentid">
			<field type="dtmf" name="studentid" timeout="10000">
			<prompt repeat="2">
			    <tts gender="female" language="english">Using your  touch tone phone, please enter the ID number for any of your students. Then press the pound key.</tts>
			</prompt>
			<?
				while($row = DBGetRow($studentids)) {
					$numeric = makenumeric($row[0]);
					?>
					<choice digits="<? echo $numeric; ?>">
						<setvar name="success" value="1" />
					</choice>

			<?	}?>
			<default>
	        	<tts gender="female" language="english">I'm sorry, but I was not able to locate that Student ID Number. </tts>
			</default>
			<timeout>
				<tts gender="female" language="english">I'm sorry, but I was not able to locate that Student ID Number. Please verify your Student ID Number and try again. goodbye!</tts>
				<hangup />
			</timeout>
		</field>
	</message>
</voice>
<?
}

function errorhangup() {
?>
<voice>
	<message name="hangup">
	       	<tts gender="female">I'm sorry, but there was an error while processing your request. Please call back and try again. goodbye</tts>
	       	<hangup />

	</message>
</voice>
<?
}

function nomatchhangup() {
?>
<voice>
	<message name="hangup">
	       	<tts gender="female">I'm sorry, but I was not able to locate that Student ID Number. Please verify your Student ID Number and try again. goodbye!</tts>
	       	<hangup />

	</message>
</voice>
<?
}


if ($REQUEST_TYPE == "new") {
	?>
	<error>msgcallbackenterstudentid: wanted result or continue, got new </error>
	<hangup />
	<?
} else if($REQUEST_TYPE == "continue") {
	if (isset($BFXML_VARS['studentid'])) {
		if (isset($BFXML_VARS['success'])) {
			forwardToPage("msgcallbackgetlist.php");
		} else {
			nomatchhangup();
		}
	} else {
		// first time through, gather valid studentids for this caller's phone
		$phonenumber = $_SESSION['contactphone'];
		$query = "select y.pkey as id from phone x, person y where x.phone=$phonenumber and x.personid=y.id and y.pkey is not null";
		$results = Query($query);

		if ($results) {
			enterstudentid(0,$results);
		} else {
			errorhangup();
		}
	}
} else {
	//huh, they must have hung up
	$SESSIONDATA = null;
?>
	<ok />
<?
}

 ?>