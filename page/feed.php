<?

require_once("../inc/appserver.inc.php");
require_once('../thrift/Thrift.php');
require_once $GLOBALS['THRIFT_ROOT'].'/protocol/TBinaryProtocol.php';
require_once $GLOBALS['THRIFT_ROOT'].'/transport/TSocket.php';
require_once $GLOBALS['THRIFT_ROOT'].'/transport/TBufferedTransport.php';
require_once $GLOBALS['THRIFT_ROOT'].'/transport/TFramedTransport.php';
require_once($GLOBALS['THRIFT_ROOT'].'/packages/commsuite/CommSuite.php');

$SETTINGS = parse_ini_file("pagesettings.ini.php",true);

// parse params
$maxPost = 0;
if (isset($_GET['items'])) {
	$maxPost = $_GET['items'] +0;
}
$maxDays = 0;
if (isset($_GET['age'])) {
	$maxPost = $_GET['age'] +0;
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
expireCategories($customer, $categories); // TODO remove this test call

// echo the xml doc, http error codes handled within method
echo generateFeed($customer, $categories, $maxPost, $maxDays);


?>