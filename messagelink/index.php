<?
setlocale(LC_ALL, 'en_US.UTF-8');
mb_internal_encoding('UTF-8');

// $SETTINGS required by appserver.inc.php
$SETTINGS = parse_ini_file("messagelinksettings.ini.php",true);

date_default_timezone_set("US/Pacific");

//for logging
apache_note("CS_APP","ml");

require_once("inc/appserver.inc.php");

// load the thrift api requirements.
$thriftdir = '../Thrift';
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
require_once("{$thriftdir}/packages/messagelink/Types.php");
require_once("{$thriftdir}/packages/messagelink/MessageLink.php");

use messagelink\MessageLinkClient;
use messagelink\MessageLinkCodeNotFoundException;

require_once("messagelinkmodel.obj.php");
require_once("messagelinkitemview.obj.php");
require_once("messagelinkcontroller.obj.php");

/*********************************************************************************/

$request = array_intersect_key($_GET, array('s'=>true, 'mal'=>true));

if (!(isset($request['s']) || isset($request['mal']))) {
	// Handle Google Analytics as a special case, otherwise redirect to support page.
	$mask16 = 0xffff0000; // 255.255.0.0
	$googleAnalytics = 0x4a7d0000; // 74.125.0.0

	$referer = isset($_SERVER["HTTP_REFERER"]) ? $_SERVER["HTTP_REFERER"] : "";
	$clientNetblock = isset($_SERVER["REMOTE_ADDR"]) ? ip2long($_SERVER["REMOTE_ADDR"]) & $mask16 : "0";
	if (strpos($referer, "www.google.com/analytics") !== false || $clientNetblock == $googleAnalytics) {
		header("location: https://asp.schoolmessenger.com/testpacific");
	} else {
		header("location: http://www.schoolmessenger.com/support/");
	}
	exit();
}

// get MessageLink's thrift protocol and transport objects; calls appserver.inc.php > initMessageLinkApp()
list($protocol, $transport) = initMessageLinkApp();

// instantiate controller with request params (s, mal)
$messageLinkController = new MessageLinkController($request, $protocol, $transport);

// instantiate model
$messageLinkModel = new MessageLinkModel($protocol, $transport);
$messageLinkModel->initialize();

// instantiate view based on model
$messageLinkController->initView($messageLinkModel);

/**
 * possible view types:
 * 1) Voice Message Delivery (legacy "messagelink"); s only
 * 2) SDD Password (password-protected); s + mal;
 * 3) SDD Auto-download (non-password-protected); s + mal
 * 4) Error view; if anything goes wrong, bad/missing/modifed codes, etc
 */

// render the view to the user!
$messageLinkController->renderView();

?>


