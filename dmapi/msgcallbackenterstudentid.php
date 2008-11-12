<?
include_once("inboundutils.inc.php");

global $BFXML_VARS;

function enterstudentid($error, $studentids) {
?>
<voice>
	<message name="choosestudentid">
			<field type="dtmf" name="studentid" timeout="5000">
			<prompt repeat="2">
			    <tts gender="female" language="english">Please enter the numeric Student I. D. for one of your students, followed by the pound key.</tts>
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
	        	<tts gender="female" language="english">Sorry. That student number did not match our records. Remember that letters in the students I. D. correspond to a number on you phone. For example the letter A. is represented by the number 2. </tts>
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

function errorhangup() {
?>
<voice>
	<message name="hangup">
	       	<tts gender="female">An error occurred while processing your request. Please call back and try again. Goodbye.</tts>
	       	<hangup />

	</message>
</voice>
<?
}

function nomatchhangup() {
?>
<voice>
	<message name="hangup">
	       	<tts gender="female">Sorry. That student number did not match our records.  Please verify your student I. D. and try again later. Goodbye.</tts>
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
}

 ?>