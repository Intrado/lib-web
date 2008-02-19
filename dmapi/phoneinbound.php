<?
// inbound phone service: play the welcome message then set next page to inboundlogin
//
// each step/state of the inbound phone system is handled within additional source files "inboundXXX.php"

include_once("inboundutils.inc.php");

global $SESSIONDATA, $BFXML_VARS;


////////////////////////////////////////

if ($REQUEST_TYPE == "new") {
	glog("///////////////////////");

	$inboundNumber = (isset($BFXML_VARS['exten']) ? $BFXML_VARS['exten'] : "0000000000");

	glog("inboundNumber: ".$inboundNumber);
	$SESSIONDATA['inboundNumber'] = $inboundNumber;

	forwardToPage("inboundlogin.php");
} else {
	forwardToPage("inboundgoodbye.php");
}

?>