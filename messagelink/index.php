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

$request = array();

// s param = message link code
if (isset($_GET['s'])) {
	$request['s'] = $_GET['s'];
}

// mal param = message attachment link code
if (isset($_GET['mal'])) {
	$request['mal'] = $_GET['mal'];
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


