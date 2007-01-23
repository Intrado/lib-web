<?
//remember to embed the $SESSIONID manually in each task element
//when you are done with the session, set $SESSIONDATA to null

//$SESSIONDATA = array_merge($SESSIONDATA,$BFXML_VARS);

function loginscreen ($error) {
?>
	<voice sessionid="<?= $SESSIONID ?>">
<? if ($error) { ?>
		<tts><?= $error ?></tts>
<? } ?>
		<message name="login">
			<field name="accesscode" type="dtmf" min="4">
				<prompt>
					<tts>Please enter your user i d followed by the pound key</tts>
				</prompt>
			</field>

			<field name="pin" type="dtmf" min="4">
				<prompt>
					<tts>Please enter your pin code followed by the pound key</tts>
				</prompt>
			</field>
		</message>
	</voice>
<?
}



if ($REQUEST_TYPE == "new") {
	loginscreen(false);
} else if ($REQUEST_TYPE == "continue") {
	if ($BFXML_VARS['accesscode'] = "1234" and $BFXML_VARS['pin'] = "1234") {
		//yay logged in
		$SESSIONDATA['isloggedin'] = true;
		forwardToPage ("phone_example2.php");
	} else {
		loginscreen("Bad access code or pin code.");
	}

} else if ($REQUEST_TYPE == "result") {
	//huh, they must have hung up
	$SESSIONDATA = null;
?>
	<ok />
<?
}



?>