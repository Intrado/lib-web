<?
include_once('common.inc.php');
require_once('../inc/content.inc.php');
require_once('../inc/appserver.inc.php');

// load the thrift api requirements.
require_once('thrift/Thrift.php');
require_once $GLOBALS['THRIFT_ROOT'].'/protocol/TBinaryProtocol.php';
require_once $GLOBALS['THRIFT_ROOT'].'/transport/TSocket.php';
require_once $GLOBALS['THRIFT_ROOT'].'/transport/TBufferedTransport.php';
require_once $GLOBALS['THRIFT_ROOT'].'/transport/TFramedTransport.php';
require_once($GLOBALS['THRIFT_ROOT'].'/packages/commsuite/CommSuite.php');

if(isset($_GET['id'])) {
	$currentid = $_GET['id']+0;
	$custinfo = QuickQueryRow("select s.dbhost, c.dbusername, c.dbpassword, c.urlcomponent, c.enabled from customer c inner join shard s on (c.shardid = s.id) where c.id = '$currentid'");
	$custdb = DBConnect($custinfo[0], $custinfo[1], $custinfo[2], "c_$currentid");
	if(!$custdb) {
		exit("Connection failed for customer: $custinfo[0], db: c_$currentid");
	}
} else {
	exit();
}

$query = "select c.id from content c inner join setting s on (s.value = c.id) where s.name = '_logocontentid'";
$contentid = QuickQuery($query, $custdb);
if ($contentid) {
	list($contenttype, $data) = contentGetForCustomerId($currentid, $contentid);

	$ext = substr($contenttype, strpos($contenttype, "/")+1);
	header("Content-disposition: filename=logo." . $ext);
	header ("Content-type: " . $contenttype);
	echo $data;
} else {
	return false;
}