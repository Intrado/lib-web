<?
$SETTINGS = parse_ini_file("inc/settings.ini.php",true);
$IS_COMMSUITE = $SETTINGS['feature']['is_commsuite'];

//get the customer URL
if ($IS_COMMSUITE) {
	$CUSTOMERURL = "default";
} /*CSDELETEMARKER_START*/ else {
	$CUSTOMERURL = substr($_SERVER["SCRIPT_NAME"],1);
	$CUSTOMERURL = strtolower(substr($CUSTOMERURL,0,strpos($CUSTOMERURL,"/")));
} /*CSDELETEMARKER_END*/

require_once("inc/db.inc.php");
require_once("inc/DBMappedObject.php");
require_once("inc/DBRelationMap.php");
require_once("XML/RPC.php");
require_once("inc/auth.inc.php");
require_once("inc/sessionhandler.inc.php");



require_once("inc/utils.inc.php");
require_once("inc/date.inc.php");
require_once("obj/User.obj.php");
require_once("obj/Access.obj.php");
require_once("obj/Permission.obj.php");

require_once("inc/utils.inc.php");
require_once("inc/date.inc.php");
require_once("obj/User.obj.php");
require_once("obj/Customer.obj.php");
require_once("obj/Import.obj.php");





//call 	doStartSession(); manually

//1st, see if this is a POST with some raw post data, if so we should check the sessionid and make sure it is valid, then append/create the file
if (isset($_GET['authCode']) && isset($_GET['sessionId'])) {
	$authcode = $_GET['authCode'];
	$sessionid = $_GET['sessionId'];

	//fire up the session
	session_id($sessionid);
	doStartSession();
	if (!isset($_SESSION['importid']))
		exit("error Bad session");


	if ($_SESSION['authcode'] != $_GET['authCode'])
		exit("error Bad authcode");

	if ($_SESSION['uploadsuccess'])
		exit("already uploaded");

	if (!$fp = fopen($_SESSION['filename'],"ab"))
		exit("error can't write file");

//	error_log("writing to file " . $_SESSION['filename']);

	if (false !== ($numbytes = fwrite($fp,$HTTP_RAW_POST_DATA)))
		exit("ok $numbytes written");

} else {

	function rcp_authorize ($method_name, $params, $app_data) {
		global $CUSTOMERURL;
		$customer = $params[0];
		$identity = $params[1];

		if ($customer == $CUSTOMERURL) {

			//authorize the upload key
			if ($importid = authorizeUploadImport($identity, $CUSTOMERURL)) {
				doStartSession();
				$_SESSION['importid'] = $importid;

				return array ("sessionId" => session_id(),
								"errorMsg" => "",
								"errorCode" => "NO_ERROR");
			} else {
				return array ("sessionId" => "",
							"errorMsg" => "Unknown identity",
							"errorCode" => "UNAUTHORIZED");
			}
		} else {
			return array ("sessionId" => "",
						"errorMsg" => "Unknown customer",
						"errorCode" => "UNAUTHORIZED");
		}
	}

	function rcp_requestupload ($method_name, $params, $app_data) {
		global $SETTINGS;
		$sessionid = $params[0];
		$length = $params[1];
		$md5checksum = $params[2];

		//fire up the session and make an authcode
		session_id($sessionid);
		doStartSession();
		if (isset($_SESSION['importid'])) {
			$newauthcode = md5(mt_rand() . microtime() . $_SERVER['REMOTE_ADDR']);
			$_SESSION['authcode'] = $newauthcode;
			$_SESSION['uploadsuccess'] = false;
			$_SESSION['length'] = $sess['remaining'] = $length;
			$_SESSION['md5'] = $md5checksum;
			$_SESSION['filename'] = secure_tmpname("autoupload",".csv");
			return array ("authCode" => $newauthcode,
						"errorMsg" => "",
						"errorCode" => "NO_ERROR");
		} else {
			return array ("authCode" => "",
						"errorMsg" => "Unknown session",
						"errorCode" => "INVALID_SESSION");
		}
	}

	function rcp_requestuploadconfirmation ($method_name, $params, $app_data) {
		global $SETTINGS;
		$sessionid = $params[0];
		$authcode = $params[1];


		//fire up the session
		session_id($sessionid);
		doStartSession();
		if (!isset($_SESSION['importid']))
			return array ("resumeLength" => "0",
						"errorMsg" => "Unknown session",
						"errorCode" => "INVALID_SESSION");

		//find the authcode
		if (! (isset($_SESSION['authcode']) && $_SESSION['authcode'] == $authcode) )
			return array ("resumeLength" => "0",
						"errorMsg" => "Unknown authcode",
						"errorCode" => "UNAUTHORIZED");

		//have we already uploaded? (handle duplicate confirmation requests)
		if ($_SESSION['uploadsuccess']) {
			return array ("resumeLength" => "0",
						"errorMsg" => "",
						"errorCode" => "NO_ERROR");
		}

		//check for the file
		if (!(isset($_SESSION['filename']) && file_exists($_SESSION['filename'])))
			return array ("resumeLength" => $_SESSION['length'],
						"errorMsg" => "No HTTP POST recieved",
						"errorCode" => "PARTIAL_FILE");

		//see if its all there
		if (filesize($_SESSION['filename']) < $_SESSION['length'])
			return array ("resumeLength" => ($_SESSION['length'] - filesize($_SESSION['filename'])),
						"errorMsg" => "Partial file recieved",
						"errorCode" => "PARTIAL_FILE");
		//got too much?
		if (filesize($_SESSION['filename']) < $_SESSION['length'])
			return array ("resumeLength" => "0",
						"errorMsg" => "Too much data for original file size",
						"errorCode" => "CHECHSUM_FAILURE");

		//check teh md5
		$currentmd5 = md5_file($_SESSION['filename']);
		if ($currentmd5 != $_SESSION['md5'])
			return array ("resumeLength" => "0",
						"errorMsg" => "Md5 is different. Request mdd5 = " . $_SESSION['md5'] . " Md5 of file on server: $currentmd5",
						"errorCode" => "CHECHSUM_FAILURE");

		$timezone = getSystemSetting("timezone");
		@date_default_timezone_set($timezone);
		QuickUpdate("set time_zone='$timezone'");

		//do the upload
		$import = new Import($_SESSION['importid']);
		$data = file_get_contents($_SESSION['filename']);
		if (!$data  || !$import->upload($data))
			return array ("resumeLength" => "0",
						"errorMsg" => "There was an error uploading the file",
						"errorCode" => "UPLOAD_ERROR");

		@unlink($_SESSION['filename']);

		//should we kick off the import?
		if ($import->type == "automatic") {
			$import->runNow();
		}

		//save the session data
		//set uploadsuccess in case the client misses our reply
		$_SESSION['uploadsuccess'] = true;

		return array ("resumeLength" => "0",
						"errorMsg" => "",
						"errorCode" => "NO_ERROR");

	}

	function rcp_closesession ($method_name, $params, $app_data) {
		$sessionid = $params[0];

		//fire up the session
				session_id($sessionid);
				doStartSession();
		if (!isset($_SESSION['importid'])) {
			return array ("errorMsg" => "Unknown session",
						"errorCode" => "INVALID_SESSION");
		}

		//delete any remaining files still hanging around
		if (isset($sess['filename']))
			@unlink($sess['filename']);
		//delete the session
		$_SESSION = array();

		return array ("errorMsg" => "",
					"errorCode" => "NO_ERROR");
	}

	//do the xmlrpc stuff
	$xmlrpc_server = xmlrpc_server_create();

	xmlrpc_server_register_method($xmlrpc_server, "Authorize", "rcp_authorize");
	xmlrpc_server_register_method($xmlrpc_server, "RequestUpload", "rcp_requestupload");
	xmlrpc_server_register_method($xmlrpc_server, "RequestUploadConfirmation", "rcp_requestuploadconfirmation");
	xmlrpc_server_register_method($xmlrpc_server, "CloseSession", "rcp_closesession");

	echo xmlrpc_server_call_method($xmlrpc_server, $HTTP_RAW_POST_DATA, '');

	xmlrpc_server_destroy($xmlrpc_server);
}

/*
Authorize (String customer, String identity)
	Params
		customer = some string representing the customer. TBD
		identity = the UUID of this data integration tool.
	Return values:
		sessionId = the session id to use if authorized. tracks this session.
		errorMsg = the error message if failure.
		errorCode = NO_ERROR | UNAUTHORIZED

RequestUpload (String sessionId, String length, String md5checksum)
	Params
		sessionId
		length
		md5checksum
	Return values:
		authcode = request authorization code/token. tracks this upload request. used in http post data.
		errorMsg = the error message if failure.
		errorCode = NO_ERROR | INVALID_SESSION | UNAUTHORIZED | UNKNOWN_TYPE

**use http post to send the binary data, sending the authcode in the GET query ex: "?authCode=xxx" **

RequestUploadConfirmation (String sessionId, String authcode)
	Params
		sessionId
		authcode
	Return values:
		resumeLength = the number of remaining bytes, if any, that need to be sent.
		errorMsg = the error message if failure.
		errorCode = NO_ERROR | INVALID_SESSION | UNAUTHORIZED | CHECHSUM_FAILURE | PARTIAL_FILE | UPLOAD_ERROR

CloseSession (String sessionId)
	Params
		sessionId
	Return values:
		errorMsg = the error message if failure.
		errorCode = NO_ERROR | INVALID_SESSION


 */
?>