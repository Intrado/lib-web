<?
$SETTINGS = parse_ini_file("inc/settings.ini.php",true);

//get the customer URL
$CUSTOMERURL = substr($_SERVER["SCRIPT_NAME"],1);
$CUSTOMERURL = strtolower(substr($CUSTOMERURL,0,strpos($CUSTOMERURL,"/")));

apache_note("CS_APP","upload"); //for logging
apache_note("CS_CUST",urlencode($CUSTOMERURL)); //for logging

require_once("inc/db.inc.php");
require_once("inc/utils.inc.php");
require_once("inc/memcache.inc.php");
require_once("inc/DBMappedObject.php");
require_once("inc/DBRelationMap.php");
require_once("XML/RPC.php");
require_once("inc/auth.inc.php");
require_once("inc/sessionhandler.inc.php");

require_once("inc/date.inc.php");
require_once("obj/User.obj.php");
require_once("obj/Access.obj.php");
require_once("obj/Permission.obj.php");

require_once("inc/utils.inc.php");
require_once("inc/date.inc.php");
require_once("obj/User.obj.php");
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
			$_SESSION['length'] = $sess['remaining'] = $length + 0;
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
			return array ("resumeLength" => 0,
						"errorMsg" => "Unknown session",
						"errorCode" => "INVALID_SESSION");

		//find the authcode
		if (! (isset($_SESSION['authcode']) && $_SESSION['authcode'] == $authcode) )
			return array ("resumeLength" => 0,
						"errorMsg" => "Unknown authcode",
						"errorCode" => "UNAUTHORIZED");

		//have we already uploaded? (handle duplicate confirmation requests)
		if ($_SESSION['uploadsuccess']) {
			return array ("resumeLength" => 0,
						"errorMsg" => "",
						"errorCode" => "NO_ERROR");
		}

		//check for the file
		if (!(isset($_SESSION['filename']) && file_exists($_SESSION['filename'])))
			return array ("resumeLength" => $_SESSION['length'],
						"errorMsg" => "No HTTP POST received",
						"errorCode" => "PARTIAL_FILE");

		//see if its all there
		if (filesize($_SESSION['filename']) < $_SESSION['length'])
			return array ("resumeLength" => ($_SESSION['length'] - filesize($_SESSION['filename'])),
						"errorMsg" => "Partial file received",
						"errorCode" => "PARTIAL_FILE");
		//got too much?
		if (filesize($_SESSION['filename']) < $_SESSION['length'])
			return array ("resumeLength" => 0,
						"errorMsg" => "Too much data for original file size",
						"errorCode" => "CHECKSUM_FAILURE");

		//check teh md5
		$currentmd5 = md5_file($_SESSION['filename']);
		if ($currentmd5 != $_SESSION['md5'])
			return array ("resumeLength" => 0,
						"errorMsg" => "Md5 is different. Request mdd5 = " . $_SESSION['md5'] . " Md5 of file on server: $currentmd5",
						"errorCode" => "CHECKSUM_FAILURE");

		$timezone = getSystemSetting("timezone");
		@date_default_timezone_set($timezone);
		QuickUpdate("set time_zone='$timezone'");

		//do the upload
		$import = new Import($_SESSION['importid']);
		$data = file_get_contents($_SESSION['filename']);
		unlink($_SESSION['filename']);
		if (!$data  || !$import->upload($data))
			return array ("resumeLength" => 0,
						"errorMsg" => "There was an error uploading the file",
						"errorCode" => "UPLOAD_ERROR");

		//should we kick off the import?
		if ($import->type == "automatic") {
			$import->runNow();
		}

		//save the session data
		//set uploadsuccess in case the client misses our reply
		$_SESSION['uploadsuccess'] = true;

		return array ("resumeLength" => 0,
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
			unlink($sess['filename']);
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

NOTE: All arguments are strings. Return values are in an xml-rpc struct with string names and string values
except RequestUploadConfirmation resumeLength, which is an integer (<i4> or <int>).

Authorize (String customer, String identity)
	Params
		string customer = the urlcomponent for the customer. eg "springfieldisd"
		string identity = the UUID for the import, aka upload key.
	Return values:
		string sessionId = the session id to use if authorized. tracks this session.
		string errorMsg = the error message if failure.
		string errorCode = NO_ERROR | UNAUTHORIZED

RequestUpload (String sessionId, String length, String md5checksum)
	Params
		string sessionId
		string length
		string md5checksum
	Return values:
		string authcode = request authorization code/token. tracks this upload request. used in http post data.
		string errorMsg = the error message if failure.
		string errorCode = NO_ERROR | INVALID_SESSION

**use http post to send the binary data, (application/octet-stream not application/x-www-form-urlencoded) **
**send the authcode and sessionid in the GET query ex: "?authCode=xxx&sessionId=xxx" **
**response is usually HTTP 200 OK, with a minimal debug message, use RequestUploadConfirmation for programatic error handling **

RequestUploadConfirmation (String sessionId, String authcode)
	Params
		string sessionId
		string authcode
	Return values:
		int resumeLength = the number of remaining bytes, if any, that need to be sent. normally 0 unless errorCode=PARTIAL_FILE
		string errorMsg = the error message if failure.
		string errorCode = NO_ERROR | INVALID_SESSION | UNAUTHORIZED | CHECKSUM_FAILURE | PARTIAL_FILE | UPLOAD_ERROR

CloseSession (String sessionId)
	Params
		string sessionId
	Return values:
		string errorMsg = the error message if failure.
		string errorCode = NO_ERROR | INVALID_SESSION


 */
?>
