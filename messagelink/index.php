<?
setlocale(LC_ALL, 'en_US.UTF-8');
mb_internal_encoding('UTF-8');

// $SETTINGS required by appserver.inc.php
$SETTINGS = parse_ini_file("messagelinksettings.ini.php",true);

date_default_timezone_set("US/Pacific");

//for logging
apache_note("CS_APP","ml");

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


