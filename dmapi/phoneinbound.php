<?
// inbound phone service: play the welcome message then set next page to inboundlogin
//
// each step/state of the inbound phone system is handled within additional source files "inboundXXX.php"

include_once("inboundutils.inc.php");

global $SESSIONDATA, $BFXML_VARS;


function welcome()
{
	global $SESSIONID;
?>
<voice sessionid="<?= $SESSIONID ?>">
	<message name="welcome">
		<audio cmid="file://prompts/inbound/Welcome.wav" />
	</message>
</voice>
<?
}


////////////////////////////////////////

if ($REQUEST_TYPE == "new") {
	glog("///////////////////////");

	// TODO $inboundNumber = $BFXML_VARS['exten']; // exten is NULL, so is callerid and callername info - needs setup
	$inboundNumber = "8316001095";
	glog("inboundNumber: ".$inboundNumber);
	$SESSIONDATA['inboundNumber'] = $inboundNumber;

	welcome();

	setNextPage("inboundlogin.php");
} else {
	setNextPage("inboundgoodbye.php");
}

?>