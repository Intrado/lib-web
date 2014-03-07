<?
setlocale(LC_ALL, 'en_US.UTF-8');
mb_internal_encoding('UTF-8');

// $SETTINGS required by appserver.inc.php
$SETTINGS = parse_ini_file("messagelinksettings.ini.php",true);

date_default_timezone_set("US/Pacific");

//for logging
apache_note("CS_APP","ml");

function escapeHtml($string) {
	return htmlentities($string, ENT_COMPAT, 'UTF-8') ;
}

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

require_once("messagelinkitemview.obj.php");
require_once("messagelinkcontroller.obj.php");

/*********************************************************************************/

$request = array();

if (isset($_GET['s'])) {
	$request['s'] = $_GET['s'];
}
if (isset($_GET['mal'])) {
	$request['mal'] = $_GET['mal'];
}

/*
 * Create new (Message|Attachment)LinkController with request params,
 * Initialize the (thrift) MessageLinkClient and render the appropriate view
 */
$messageLinkController = new MessageLinkController($request);
$messageLinkController->initApp();
$messageLinkController->renderView();

?>


