<?
//remember to embed the $SESSIONID manually in each task element
//when you are done with the session, set $SESSIONDATA to null

$SESSIONDATA = array_merge($SESSIONDATA,$BFXML_VARS);

if ($REQUEST_TYPE == "new") {
?>
	<voice sessionid="<?= $SESSIONID ?>">
		<dial>8316001331</dial>
		<message name="foo">
			<setvar name="foo" value="1" />
			<tts> this is a test</tts>
			<hangup />
		</message>
	</voice>
<?
} else if ($REQUEST_TYPE == "continue") {
?>
	<voice sessionid="<?= $SESSIONID ?>">
		<message name="foo">
			<hangup />
		</message>
	</voice>
<?
} else {

	echo $BFXML_VARS['callprogress'];
	$SESSIONDATA = NULL; //tell session machine we dont need session data any more.
?>
	<ok />
<?
}


?>