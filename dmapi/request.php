<?
$time = microtime(true);

$SETTINGS = parse_ini_file("../inc/settings.ini.php",true);
$IS_COMMSUITE = $SETTINGS['feature']['is_commsuite'];

require_once("XML/RPC.php");
require_once("../inc/auth.inc.php");
require_once("dmapidb.inc.php");
require_once("../inc/db.inc.php");
require_once("../inc/DBMappedObject.php");
require_once("../inc/DBRelationMap.php");
require_once("dmapisessiondata.inc.php");
require_once("../inc/utils.inc.php");
require_once("../obj/SpecialTask.obj.php");

include_once("XmlToArray.obj.php");


//TODO: MAKE SURE CHARACTER ENCODING DOES NOT BREAK WITH XMLRPC TRAFFIC

// SpecialTask
// Params:
//		params[0] = sessionid
//		params[1] = taskid
function specialtask($methodname, $params){
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
		include("easycall.php");
	} else if($task->type == "callme"){
		include("callme.php");
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
function inboundtask($methodname, $params){
	$ERROR="";

	$SESSIONID = $params[0];
	session_id($SESSIONID);
	doStartSession();
	connectDatabase($SESSIONID);

	$REQUEST_TYPE = "new";
	$_SESSION['inboundNumber'] = $params[1];

	ob_start();

	include("inboundlogin.php");

	$output = ob_get_contents();
	ob_end_clean();

	return response($ERROR, $output);
}


// continuecompletetask
// Params:
//		params[0] = sessionid
//		params[1] = result data
function continuecompletetask($methodname, $params){
	$ERROR="";

	$SESSIONID = $params[0];
	session_id($SESSIONID);
	doStartSession();
	connectDatabase($SESSIONID);


	if($methodname = "continuetask"){
		$REQUEST_TYPE = "continue";
	} else if($methodname == "completetask"){
		$REQUEST_TYPE = "result";
	}
	if ($datums = findChildren($params[1],"DATUM")) {
		foreach ($datums as $datum) {
			$name = $datum['attrs']['NAME'];
			$value = (isset($datum['txt']) ? $datum['txt'] : "");
			$BFXML_VARS[$name] = $value;
		}
	}

	ob_start();
	if (isset($_SESSION['_nav_curpage']) && $_SESSION['_nav_curpage']) {
		include($_SESSION['_nav_curpage']);
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
	fwrite($fp,$output . "\n");
	fwrite($fp,"time: " . (microtime(true) - $time) . "\n");
	fwrite($fp,"-------------------------------\n");
	fclose($fp);
}

xmlrpc_server_destroy($xmlrpc_server);

?>
