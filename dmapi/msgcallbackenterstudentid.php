<?
include_once("inboundutils.inc.php");

global $BFXML_VARS;

function enterstudentid($pkeys) {
?>
<voice>
	<message name="choosestudentid">
			<field type="dtmf" name="studentid" timeout="10000">
			<prompt repeat="2">
			    <tts gender="female" language="english">Using your  touch tone phone, please enter the ID number for any of your students. Then press the pound key.</tts>
			</prompt>
			<?
				foreach ($pkeys as $pkey) {
					$numeric = makenumeric($pkey);
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

function nomatchhangup() {
?>
<voice>
	<message name="hangup">
	       	<tts gender="female">Please verify your Student ID Number and try again. goodbye!</tts>
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
		$query = "select p.pkey from phone ph, person p where ph.phone=? and ph.personid=p.id and p.pkey is not null";
		$pkeys = QuickQueryList($query,false,false,array($_SESSION['contactphone']));
		enterstudentid($pkeys);
	}
} else {
	//huh, they must have hung up
	$SESSIONDATA = null;
?>
	<ok />
<?
}

 ?>