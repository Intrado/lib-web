<?
$time = microtime(true);

$SETTINGS = parse_ini_file("../inc/settings.ini.php",true);
$IS_COMMSUITE = $SETTINGS['feature']['is_commsuite'];

require_once("XML/RPC.php");
require_once("../inc/auth.inc.php");
require_once("../inc/db.inc.php");
require_once("../inc/DBMappedObject.php");
require_once("../inc/DBRelationMap.php");
require_once("../inc/utils.inc.php");
require_once("../obj/SpecialTask.obj.php");
// need these objects for serialization of sessiondata used by inbound message callback
include_once("../obj/Message.obj.php");
include_once("../obj/MessagePart.obj.php");
include_once("../obj/Person.obj.php");
include_once("../obj/Voice.obj.php");
include_once("../obj/AudioFile.obj.php");
include_once("../obj/VoiceReply.obj.php");

include_once("XmlToArray.obj.php");


//TODO: MAKE SURE CHARACTER ENCODING DOES NOT BREAK WITH XMLRPC TRAFFIC

// SpecialTask
// Params:
//		params[0] = sessionid
//		params[1] = taskid
function specialtask($methodname, $params){
	global $REQUEST_TYPE;
	$ERROR="";

	$SESSIONID = $params[0];
	session_id($SESSIONID);
	doStartSession();
	connectDatabase($SESSIONID);

	$REQUEST_TYPE = "new";
	$task = new SpecialTask($params[1]);
	$_SESSION['specialtaskid'] = $task->id;

	ob_start();
	if(strtolower($task->type) == "easycall"){
		forwardToPage("easycall.php");
	} else if(strtolower($task->type) == "callme"){
		forwardToPage("callme.php");
	} else {
		$ERROR = "Unknown Special Task Type";
	}
	$output = ob_get_contents();
	ob_end_clean();

	return response($ERROR, $output);
}

// continuecompletetask
// Params:
//		params[0] = sessionid
//		params[1] = called number
//		params[2] = callerid
//		params[3] = customerid
function inboundtask($methodname, $params){
	global $REQUEST_TYPE;
	$ERROR="";

	$SESSIONID = $params[0];
	session_id($SESSIONID);
	doStartSession();
	connectDatabase($SESSIONID);

	$REQUEST_TYPE = "new";
	$_SESSION['inboundNumber'] = $params[1];
	$_SESSION['callerid'] = $params[2];
	$_SESSION['customerid'] = $params[3];

	ob_start();

	forwardToPage("inboundstart.php");

	$output = ob_get_contents();
	ob_end_clean();

	return response($ERROR, $output);
}


// continuecompletetask
// Params:
//		params[0] = sessionid
//		params[1] = result data
function continuecompletetask($methodname, $params){
	global $BFXML_VARS, $REQUEST_TYPE;
	$ERROR="";

	$SESSIONID = $params[0];
	session_id($SESSIONID);
	doStartSession();
	connectDatabase($SESSIONID);


	if($methodname == "continuetask"){
		$REQUEST_TYPE = "continue";
	} else if($methodname == "completetask"){
		$REQUEST_TYPE = "result";
	}

	$BFXML_VARS = $params[1];

	ob_start();
	if (isset($_SESSION['_nav_curpage']) && $_SESSION['_nav_curpage']) {
		forwardToPage($_SESSION['_nav_curpage']);
	} else {
		$ERROR = "No page set!";
		$_SESSION = array();
	}

	$output = ob_get_contents();
	ob_end_clean();

	return response($ERROR, $output);
}


//function to handle all responses
function response($ERROR, $output){
	global $SETTINGS;

	$resultcode = "success";
	if($ERROR){
		$resultcode = "failure";
	}

	return array("taskxml" => $output, "resultcode" => $resultcode, "resultdescription" => $ERROR);
}

//define some helper functions
//loads a page and adds the current page to the stack
function setNextPage ($thepage) {
	$_SESSION['_nav_curpage'] = $thepage;
}

function forwardToPage ($thepage, $setpage = true) {
	//NOTE: must declare any globals to share with the script
	global $BFXML_VARS, $REQUEST_TYPE, $SETTINGS;

	if ($setpage)
		setNextPage($thepage);
	include($thepage);
}

//do the xmlrpc stuff
$xmlrpc_server = xmlrpc_server_create();

xmlrpc_server_register_method($xmlrpc_server, "specialtask", "specialtask");
xmlrpc_server_register_method($xmlrpc_server, "inboundtask", "inboundtask");
xmlrpc_server_register_method($xmlrpc_server, "continuetask", "continuecompletetask");
xmlrpc_server_register_method($xmlrpc_server, "completetask", "continuecompletetask");

//error_log(print_r($HTTP_RAW_POST_DATA, true));

$output = xmlrpc_server_call_method($xmlrpc_server, $HTTP_RAW_POST_DATA, '');

echo $output;

if ($SETTINGS['feature']['log_dmapi']) {
	$logfilename = $SETTINGS['feature']['log_dir'] . "output.txt";

	//rotate log?

	if (file_exists($logfilename) && filesize($logfilename) > 1000000000) {
		if (file_exists($logfilename . ".1"))
			unlink($logfilename . ".1");
		rename($logfilename,$logfilename . ".1");
	}

	$fp = fopen($logfilename,"a");
	fwrite($fp,"------" . date("Y-m-d H:i:s") . "------\n");
	fwrite($fp,"-------------REQUEST----------\n");
	fwrite($fp,$HTTP_RAW_POST_DATA . "\n");
	fwrite($fp,"-------------RESPONSE----------\n");
	fwrite($fp,html_entity_decode($output) . "\n");
	fwrite($fp,"time: " . (microtime(true) - $time) . "\n");
	fwrite($fp,"-------------------------------\n");
	fclose($fp);
}

xmlrpc_server_destroy($xmlrpc_server);

?>
