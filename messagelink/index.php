<?
setlocale(LC_ALL, 'en_US.UTF-8');
mb_internal_encoding('UTF-8');

// $SETTINGS required by appserver.inc.php
$SETTINGS = parse_ini_file("inc/settings.ini.php",true);

date_default_timezone_set("US/Pacific");

//for logging
apache_note("CS_APP","ml");

function escapeHtml($string) {
	return htmlentities($string, ENT_COMPAT, 'UTF-8') ;
}

require_once("inc/appserver.inc.php");

$thriftRequires = array(
    "Base/TBase.php",
    "Protocol/TProtocol.php",
    "Protocol/TBinaryProtocol.php",
    "Protocol/TBinaryProtocolAccelerated.php",
    "Transport/TTransport.php",
    "Transport/TSocket.php",
    "Transport/TBufferedTransport.php",
    "Transport/TFramedTransport.php",
    "Exception/TException.php",
    "Exception/TTransportException.php",
    "Exception/TProtocolException.php",
    "Exception/TApplicationException.php",
    "Type/TType.php",
    "Type/TMessageType.php",
    "StringFunc/TStringFunc.php",
    "Factory/TStringFuncFactory.php",
    "StringFunc/Core.php",
    "packages/messagelink/Types.php",
    "packages/messagelink/MessageLink.php"
);

foreach ($thriftRequires as $require) {
    require_once("Thrift/{$require}");
}

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


