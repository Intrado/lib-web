<?
// inbound phone service: play the goodbye message

function goodbye()
{
	global $SESSIONID;
?>
<voice sessionid="<?= $SESSIONID ?>">
	<message name="goodbye">
		<audio cmid="file://prompts/GoodBye.wav" />
		<hangup />
	</message>
</voice>
<?
}


////////////////////////////////////////

	goodbye();
?>