<?
// inbound phone service: play the goodbye message

function goodbye($jobSubmit=false)
{
	global $SESSIONID;
?>
<voice sessionid="<?= $SESSIONID ?>">
	<message name="goodbye">
<?	if ($jobSubmit) { ?>
		<audio cmid="file://prompts/inbound/Goodbye.wav" />
<?	} else { ?>
		<audio cmid="file://prompts/GoodBye.wav" />
<?	} ?>
		<hangup />
	</message>
</voice>
<?
}


////////////////////////////////////////
	if (isset($SESSIONDATA['jobSubmit'])) {
		goodbye(true);
	} else {
		goodbye();
	}
?>