<?
// inbound phone service: play the goodbye message

function goodbye($jobSubmit=false)
{
?>
<voice>
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
if($REQUEST_TYPE == "new"){
	?>
	<error>inboundgoodbye: wanted result or continue, got new </error>
	<hangup />
	<?
} else if($REQUEST_TYPE == "result"){
	//huh, they must have hung up
	$_SESSION = array();
	?>
	<ok />
	<?
} else {
	if (isset($_SESSION['jobSubmit'])) {
		goodbye(true);
	} else {
		goodbye();
	}
}

?>
