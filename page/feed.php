<?

require_once("../inc/appserver.inc.php");
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
require_once("{$thriftdir}/Exception/TProtocolException.php");
require_once("{$thriftdir}/Exception/TApplicationException.php");
require_once("{$thriftdir}/Type/TType.php");
require_once("{$thriftdir}/Type/TMessageType.php");
require_once("{$thriftdir}/StringFunc/TStringFunc.php");
require_once("{$thriftdir}/Factory/TStringFuncFactory.php");
require_once("{$thriftdir}/StringFunc/Core.php");
require_once("{$thriftdir}/packages/commsuite/Types.php");
require_once("{$thriftdir}/packages/commsuite/CommSuite.php");

$SETTINGS = parse_ini_file("pagesettings.ini.php",true);

// parse params
$maxPost = 0;
if (isset($_GET['items'])) {
	$maxPost = $_GET['items'] +0;
}
$maxDays = 0;
if (isset($_GET['age'])) {
	$maxDays = $_GET['age'] +0;
}
$categories = array();
if (isset($_GET['cat'])) {
	$categories = explode(",", $_GET['cat']);
}
$customer = "";
if (isset($_GET['cust'])) {
	$customer = $_GET['cust'];
}

apache_note("CS_APP","feed"); //for logging
apache_note("CS_CUST",urlencode($customer)); //for logging


// call appserver
// echo the xml doc, http error codes handled within method
header ("Content-Type:text/xml");
echo generateFeed($customer, $categories, $maxPost, $maxDays);


?>