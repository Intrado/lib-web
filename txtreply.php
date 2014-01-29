<?
echo "TXTREPLY";

require_once("inc/appserver.inc.php");
// load the thrift api requirements.
$thriftdir = 'Thrift';
require_once("{$thriftdir}/Base/TBase.php");
require_once("{$thriftdir}/Protocol/TProtocol.php");
require_once("{$thriftdir}/Protocol/TBinaryProtocol.php");
require_once("{$thriftdir}/Protocol/TBinaryProtocolAccelerated.php");
require_once("{$thriftdir}/Transport/TTransport.php");
require_once("{$thriftdir}/Transport/TSocket.php");
require_once("{$thriftdir}/Transport/TBufferedTransport.php");
require_once("{$thriftdir}/Transport/TFramedTransport.php");
require_once("{$thriftdir}/Exception/TException.php");
require_once("{$thriftdir}/Exception/TProtocolException.php");
require_once("{$thriftdir}/Exception/TApplicationException.php");
require_once("{$thriftdir}/Type/TType.php");
require_once("{$thriftdir}/Type/TMessageType.php");
require_once("{$thriftdir}/StringFunc/TStringFunc.php");
require_once("{$thriftdir}/Factory/TStringFuncFactory.php");
require_once("{$thriftdir}/StringFunc/Core.php");
require_once("{$thriftdir}/packages/commsuite/Types.php");
require_once("{$thriftdir}/packages/commsuite/CommSuite.php");

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
