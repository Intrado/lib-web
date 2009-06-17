<?
$time = microtime(true);

$SETTINGS = parse_ini_file("../inc/settings.ini.php",true);
$IS_COMMSUITE = $SETTINGS['feature']['is_commsuite'];

require_once("XML/RPC.php");
require_once("XML/RPC/Server.php");

require_once("../inc/auth.inc.php");
require_once("../inc/sessionhandler.inc.php");

require_once("../inc/db.inc.php");
require_once("../inc/DBMappedObject.php");
require_once("../inc/DBRelationMap.php");
require_once("../inc/utils.inc.php");
require_once("../obj/SpecialTask.obj.php");
// need these objects for serialization of sessiondata used by inbound message callback
require_once("../obj/Message.obj.php");
require_once("../obj/MessagePart.obj.php");
require_once("../obj/Person.obj.php");
require_once("../obj/Voice.obj.php");
require_once("../obj/AudioFile.obj.php");
require_once("../obj/VoiceReply.obj.php");

require_once("XmlToArray.obj.php");

// SpecialTask
// Params:
//		params[0] = sessionid
//		params[1] = taskid
function specialtask($msg){
	global $REQUEST_TYPE;
	$ERROR="";

	$SESSIONID = $msg->getParam(0);
	session_id($SESSIONID->scalarval());
	doStartSession();

	$REQUEST_TYPE = "new";
	$task = new SpecialTask($msg->getParam(1)->scalarval());
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
function inboundtask($msg){
	global $REQUEST_TYPE;
	$ERROR="";

	$SESSIONID = $msg->getParam(0);
	session_id($SESSIONID->scalarval());
	doStartSession();

	$REQUEST_TYPE = "new";
	$_SESSION['inboundNumber'] = $msg->getParam(1)->scalarval();
	$_SESSION['callerid'] = $msg->getParam(2)->scalarval();
	$_SESSION['customerid'] = $msg->getParam(3)->scalarval();
	$_SESSION['timezone'] = getSystemSetting("timezone");

	ob_start();

	forwardToPage("inboundstart.php");

	$output = ob_get_contents();
	ob_end_clean();

	return response($ERROR, $output);
}


// completetask
// Params:
//		params[0] = sessionid
//		params[1] = result data
function completetask($msg){
	global $BFXML_VARS, $REQUEST_TYPE;
	$ERROR="";

	$SESSIONID = $msg->getParam(0);
	session_id($SESSIONID->scalarval());
	doStartSession();
	
	$REQUEST_TYPE = "result";

	$BFXML_VARS = XML_RPC_decode($msg->getParam(1));
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

// continuetask
// Params:
//		params[0] = sessionid
//		params[1] = result data
function continuetask($msg){
	global $BFXML_VARS, $REQUEST_TYPE;
	$ERROR="";

	$SESSIONID = $msg->getParam(0);
	session_id($SESSIONID->scalarval());
	doStartSession();
	
	$REQUEST_TYPE = "continue";

	$BFXML_VARS = XML_RPC_decode($msg->getParam(1));
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
            
	$result = new XML_RPC_Value(array(
		"taskxml" => new XML_RPC_Value($output, "string"), 
		"resultcode" => new XML_RPC_Value($resultcode, "string"), 
		"resultdescription" => new XML_RPC_Value($ERROR, "string")), "struct");
	
	return new XML_RPC_Response($result);
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

$functionMap = array(
	"specialtask" => array("function" => "specialtask", "signature" => array(array("string","string","int")), "docstring" => ""),
	"inboundtask" => array("function" => "inboundtask", "signature" => array(array("string","string","string","string","int")), "docstring" => ""),
	"continuetask" => array("function" => "continuetask", "signature" => array(array("string","string","struct")), "docstring" => ""),
	"completetask" => array("function" => "completetask", "signature" => array(array("string","string","struct")), "docstring" => ""));

ob_start();
$xmlrpc_server = new XML_RPC_Server($functionMap);
$output = ob_get_contents();
ob_end_clean();
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
