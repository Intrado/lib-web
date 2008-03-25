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

// SpecialTask
// Params:
//		param[0] = sessionid
//		param[1] = taskid
function specialtask($methodname, $params){
	$ERROR="";

	$SESSIONID = $params[0];
	$SESSIONDATA = loadSessionData($SESSIONID);
	//error_log(print_r($SESSIONDATA, true));
	doDBConnect($SESSIONDATA);

	$REQUEST_TYPE = "new";
	$task = new SpecialTask($params[1]);
	if(strtolower($task->type) == "easycall"){
		include("easycall.php");
	} else if($task->type == "callme"){
		include("callme.php");
	} else {
		$ERROR = "Unknown Special Task Type";
	}

	return response($ERROR, $SESSIONID, $SESSIONDATA);
}

// continuecompletetask
// Params:
//		param[0] = sessionid
//		param[1] = called number
//		param[2] = callerid
function inboundtask($sessionid, $callednumber, $callerid){
	$ERROR="";

	$SESSIONID = $params[0];
	$SESSIONDATA = loadSessionData($SESSIONID);
	doDBConnect($SESSIONDATA);

	$REQUEST_TYPE = "new";
	$SESSIONDATA['inboundNumber'] = $param[1];
	forwardToPage("inboundlogin.php");
	return response($ERROR, $SESSIONID, $SESSIONDATA);
}


// continuecompletetask
// Params:
//		param[0] = sessionid
//		param[1] = result data
function continuecompletetask($methodname, $params){
	$ERROR="";

	$SESSIONID = $params[0];
	$SESSIONDATA = loadSessionData($SESSIONID);
	doDBConnect($SESSIONDATA);
	if($methodname = "continuetask"){
		$REQUEST_TYPE = "continue";
	} else if($methodname == "completetask"){
		$REQUEST_TYPE = "result";
	}
	if ($datums = findChildren($param[1],"DATUM")) {
		foreach ($datums as $datum) {
			$name = $datum['attrs']['NAME'];
			$value = (isset($datum['txt']) ? $datum['txt'] : "");
			$BFXML_VARS[$name] = $value;
		}
	}

	if (isset($SESSIONDATA['_nav_curpage']) && $SESSIONDATA['_nav_curpage']) {
		include($SESSIONDATA['_nav_curpage']);
	} else {
		$ERROR = "No page set!";
		$SESSIONDATA = null;
	}

	return response($ERROR, $SESSIONID, $SESSIONDATA);
}


//function to handle all responses
function response($ERROR, $SESSIONID, $SESSIONDATA){
	global $SETTINGS;


	//a SESSIONID was generated, but perhaps the script has opted to not used it
	if ($SESSIONID != null) {
		//save or delete the session data
		if ($SESSIONDATA === null)
			eraseSessionData($SESSIONID);
		else
			storeSessionData ($SESSIONID, 0, $SESSIONDATA);
	}

	$output = ob_get_contents();
	ob_end_clean();

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
		fwrite($fp,$HTTP_RAW_POST_DATA);
		fwrite($fp,"-------------RESPONSE----------\n");
		fwrite($fp,$output);
		fwrite($fp,"time: " . (microtime(true) - $time) . "\n");
		fwrite($fp,"-------------------------------\n");
		fclose($fp);
	}
	$resultcode = "success";
	if($ERROR){
		$resultcode = "failure";
	}
	return array("result" => $output, "resultcode" => $resultcode, "resultdescription" => $ERROR);
}

ob_start();

//do the xmlrpc stuff
$xmlrpc_server = xmlrpc_server_create();

xmlrpc_server_register_method($xmlrpc_server, "specialtask", "specialtask");
xmlrpc_server_register_method($xmlrpc_server, "inbound", "inbound");
xmlrpc_server_register_method($xmlrpc_server, "continuetask", "continuecompletetask");
xmlrpc_server_register_method($xmlrpc_server, "completetask", "continuecompletetask");

//error_log(print_r($HTTP_RAW_POST_DATA, true));

echo xmlrpc_server_call_method($xmlrpc_server, $HTTP_RAW_POST_DATA, '');

xmlrpc_server_destroy($xmlrpc_server);



?>