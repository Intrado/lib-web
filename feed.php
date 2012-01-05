<?

require_once("inc/appserver.inc.php");
require_once('thrift/Thrift.php');
require_once $GLOBALS['THRIFT_ROOT'].'/protocol/TBinaryProtocol.php';
require_once $GLOBALS['THRIFT_ROOT'].'/transport/TSocket.php';
require_once $GLOBALS['THRIFT_ROOT'].'/transport/TBufferedTransport.php';
require_once $GLOBALS['THRIFT_ROOT'].'/transport/TFramedTransport.php';
require_once($GLOBALS['THRIFT_ROOT'].'/packages/commsuite/CommSuite.php');

$SETTINGS = parse_ini_file("inc/settings.ini.php",true);

// TODO parse params
$categories = array();
$categories[] = "12";
$categories[] = "23";
$maxPost = 30;
$maxDays = 30;

//get the customer URL
$CUSTOMERURL = substr($_SERVER["SCRIPT_NAME"],1);
$CUSTOMERURL = strtolower(substr($CUSTOMERURL,0,strpos($CUSTOMERURL,"/")));

apache_note("CS_APP","feed"); //for logging
apache_note("CS_CUST",urlencode($CUSTOMERURL)); //for logging


// call appserver
expireCategories($CUSTOMERURL, $categories); // TODO remove this test call

// echo the xml doc, http error codes handled within method
echo generateFeed($CUSTOMERURL, $categories, $maxPost, $maxDays);


?>