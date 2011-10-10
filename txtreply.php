<?
echo "TXTREPLY";

require_once("inc/appserver.inc.php");
require_once('thrift/Thrift.php');
require_once $GLOBALS['THRIFT_ROOT'].'/protocol/TBinaryProtocol.php';
require_once $GLOBALS['THRIFT_ROOT'].'/transport/TSocket.php';
require_once $GLOBALS['THRIFT_ROOT'].'/transport/TBufferedTransport.php';
require_once $GLOBALS['THRIFT_ROOT'].'/transport/TFramedTransport.php';
require_once($GLOBALS['THRIFT_ROOT'].'/packages/commsuite/CommSuite.php');

$SETTINGS = parse_ini_file("inc/settings.ini.php",true);

$sourceaddress = "";
$inboundshortcode = "";
$message = "";
$message_id = "";
$message_orig = "";
$carrier = "";
$channel = "";
$router = "";

// params sent from air2web
$sourceaddress = $_POST['device_address'];
$inboundshortcode = $_POST['inbound_address'];
$message = strtolower($_POST['message']);
$message_id = $_POST['message_id'];
$message_orig = $_POST['message_orig'];
$carrier = $_POST['carrier'];
$channel = $_POST['channel'];
$router = $_POST['router'];

// for logging
apache_note("CS_APP","txtreply");
apache_note("CS_CUST", $sourceaddress);
apache_note("CS_USER", $message_id);

// build up name-value pairs
$data = array();
$data['date'] = date("Y-m-d H:i:s");
$data['shortcode'] = $inboundshortcode;
$data['smsnumber'] = $sourceaddress;
$data['message_id'] = $message_id;
$data['message'] = $message;
$data['message_orig'] = $message_orig;
$data['carrier'] = $carrier;
$data['channel'] = $channel;
$data['router'] = $router;

// call appserver
processIncomingSms($data);

?>
