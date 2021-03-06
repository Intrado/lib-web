<?
/*
	WARNING: Treat this file as an example, and do not directly include it.
	It is now an outdated copy of Kona's common.inc.php. If your new project requires
	some piece of functionality, such as accessing a database, then it would be best
	to set that up in a file owned by your project.
	This file is currently included by message-sender-backbone.
**/


//######## IF  YOU EDIT THIS FILE, BE SURE TO UPDATE SUBDIRCOMMON.INC.PHP ########

// Get the path to kona's base directory
$incdir = dirname(__FILE__);
$basedir = dirname($incdir);
$objdir = "{$basedir}/objects";
$thriftdir = "{$incdir}/Thrift";

// In case the mechanism for checking if we're running under PHPUnit needs to change,
// we check it here and set our own global constant PHPUNIT that we can use everywhere
if (defined('PHPUNIT_CONTEXT')) define('PHPUNIT', true);

setlocale(LC_ALL, 'en_US.UTF-8');
mb_internal_encoding('UTF-8');

require_once("{$incdir}/settingsloader.inc.php");

//get the customer URL
$CUSTOMERURL = customerUrlComponent();
$BASEURL = "/$CUSTOMERURL";

require_once("{$incdir}/utils.inc.php");

// If PHPUNIT is NOT running
if (! defined('PHPUNIT')) {
	// db.inc.php's library functions  will be stubbed out in PHPUnit context
	require_once("{$incdir}/db.inc.php");
}
require_once("{$incdir}/memcache.inc.php");

// If PHPUNIT is NOT running
if (! defined('PHPUNIT')) {

	// Use apache_note() function to add details to the apache logs
	apache_note("CS_APP","cs"); //for logging
	apache_note("CS_CUST",urlencode($CUSTOMERURL)); //for logging

	instrumentation_add_custom_parameter("customerUrlComponent", $CUSTOMERURL);

	// Start up the memcache interface
	init_memcache();
}

require_once("{$incdir}/DBMappedObject.php");
require_once("{$incdir}/DBMappedObjectHelpers.php");
require_once("{$incdir}/DBRelationMap.php");
require_once("XML/RPC.php");
require_once("{$incdir}/auth.inc.php");
require_once("{$incdir}/sessionhandler.inc.php");

require_once("{$objdir}/User.obj.php");
require_once("{$objdir}/Access.obj.php");
require_once("{$objdir}/Permission.obj.php");
require_once("{$objdir}/Rule.obj.php"); //for search and sec profile rules
require_once("{$objdir}/Organization.obj.php"); //for search and sec profile rules
require_once("{$objdir}/Section.obj.php"); //for search and sec profile rules

// Initialize Commsuite REST API access
require_once("{$objdir}/ApiClient.obj.php");
require_once("{$objdir}/CommsuiteApiClient.obj.php");

// Initiallze GlobalRegistry REST API access
require_once("{$objdir}/GlobalRegistryApiClient.obj.php");
$grapiClient = GlobalRegistryApiClient::instance($SETTINGS);

// Initialize Dependencies Singleton
require_once("{$objdir}/Dependencies.obj.php");
Dependencies::getInstance($grapiClient);

function getUserSessionTrackingId() {
	global $USER;
	return hash("sha256", session_id() . $USER->login);
}

if ((! defined('PHPUNIT')) && (!isset($isindexpage) || !$isindexpage)) {
	doStartSession();
	//force ssl?
	if ($SETTINGS['feature']['force_ssl'] && !isset($_SERVER["HTTPS"])) {
		redirect("index.php?logout=1"); //the index page will redirect to https
	}

	if (!isset($_SESSION['user']) || !isset($_SESSION['access'])) {
		// For api requests, return Unauthorized status code
		//
		if (isset($_REQUEST['api'])) {
			header("HTTP/1.1 401 Unauthorized");
			exit();
		}

		// Probably a session expired or temp unavail, redirect to login, and give login a chance to bounce back somewhere
		redirect("$BASEURL/index.php?last=" . urlencode(urlencode($_SERVER['REQUEST_URI'])));
	} else {
		$USER = &$_SESSION['user'];
		$USER->refresh();
		$USER->optionsarray = false; /* will be reconstructed if needed */
		
		apache_note("CS_USER",urlencode($USER->login)); //for logging
		instrumentation_add_custom_parameter("userLogin", $USER->login);
		instrumentation_add_custom_parameter("customerUser", $CUSTOMERURL . ":" . $USER->login);
		instrumentation_add_custom_parameter("userSession", getUserSessionTrackingId());

		$ACCESS = &$_SESSION['access'];
		$ACCESS->loadPermissions(true);

		if (!$USER->enabled || $USER->deleted || !$USER->authorize('loginweb')) {
			if (isset($_REQUEST['api'])) {
				header("HTTP/1.1 401 Unauthorized");
				exit();
			}

			redirect("$BASEURL/index.php?logout=1");
		}
	}

	$csApi = setupCommsuiteApi();
}
else {
	$csApi = null;
}

// load customer/user locale 
//this needs the USER object to already be loaded
require_once("{$incdir}/locale.inc.php");

// load the thrift api requirements.
require_once("{$thriftdir}/Base/TBase.php");
require_once("{$thriftdir}/Protocol/TProtocol.php");
require_once("{$thriftdir}/Protocol/TBinaryProtocol.php");
require_once("{$thriftdir}/Protocol/TBinaryProtocolAccelerated.php");
require_once("{$thriftdir}/Transport/TTransport.php");
require_once("{$thriftdir}/Transport/TSocket.php");
require_once("{$thriftdir}/Transport/TBufferedTransport.php");
require_once("{$thriftdir}/Transport/TFramedTransport.php");
require_once("{$thriftdir}/Exception/TException.php");
require_once("{$thriftdir}/Exception/TTransportException.php");
require_once("{$thriftdir}/Exception/TProtocolException.php");
require_once("{$thriftdir}/Exception/TApplicationException.php");
require_once("{$thriftdir}/Type/TType.php");
require_once("{$thriftdir}/Type/TMessageType.php");
require_once("{$thriftdir}/StringFunc/TStringFunc.php");
require_once("{$thriftdir}/Factory/TStringFuncFactory.php");
require_once("{$thriftdir}/StringFunc/Core.php");
require_once("{$thriftdir}/packages/commsuite/Types.php");
require_once("{$thriftdir}/packages/commsuite/CommSuite.php");

/**
 * @return CommsuiteApiClient
 */
function setupCommsuiteApi() {
	$apiClient = new ApiClient(
		"https://{$_SERVER['SERVER_NAME']}/" . customerUrlComponent() ."/api/2",
		array(
			"X-Auth-SessionId: {$_COOKIE[strtolower(customerUrlComponent()) . '_session']}"
		)
	);

	return(new CommsuiteApiClient($apiClient));
}

/**
 * @return NetsuiteApiClient
 */
function setupNetsuiteApi() {
	global $SETTINGS;
	$apiClient = new ApiClient(
		$SETTINGS['netsuite']['url'],
		array(
			"Content-Type: application/json",
			//"Accept: '*" . '/' . "*'",
			"Accept: *" . '/' . "*",
			"Authorization: NLAuth nlauth_account={$SETTINGS['netsuite']['account']}, nlauth_email={$SETTINGS['netsuite']['user']}, nlauth_signature={$SETTINGS['netsuite']['pass']}, nlauth_role={$SETTINGS['netsuite']['role']}"
		)
	);

	return(new NetsuiteApiClient(
		$apiClient,
		$SETTINGS['netsuite']['uriFeedback']
	));
}

function customerUrlComponent() {
	$c = substr($_SERVER["SCRIPT_NAME"], 1);
	$c = strtolower(substr($c, 0, strpos($c, '/')));
	return($c);
}

/**
 * Any PageObject derived class must invoke this function to executePage()
 * This ensures that the test for PHPUNIT environment only needs to exist
 * in this one place.
 */
function executePage($pageObject) {

	// If PHPUNIT is NOT running
	if (! defined('PHPUNIT')) {

		// Then execute the page normally
		$pageObject->execute();
	}
}

?>
