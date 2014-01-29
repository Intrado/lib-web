<?
require_once('common.inc.php');
require_once('../inc/content.inc.php');
require_once('../inc/appserver.inc.php');

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
require_once("{$thriftdir}/packages/commsuite/Types.php");
require_once("{$thriftdir}/packages/commsuite/CommSuite.php");

if (isset($_GET['id'])) {
	$currentid = $_GET['id']+0;
	$custinfo = QuickQueryRow("select s.dbhost, c.dbusername, c.dbpassword, c.urlcomponent, c.enabled from customer c inner join shard s on (c.shardid = s.id) where c.id = '$currentid'");
	$custdb = DBConnect($custinfo[0], $custinfo[1], $custinfo[2], "c_$currentid");
	if (!$custdb) {
		exit("Connection failed for customer: $custinfo[0], db: c_$currentid");
	}
} else {
	exit();
}

$setting = "_loginpicturecontentid";
if (isset($_GET['subscriber'])) {
	$setting = "_subscriberloginpicturecontentid";
}

$query = "select c.id from content c inner join setting s on (s.value = c.id) where s.name = ?";
$contentid = QuickQuery($query, $custdb, array($setting));
if ($contentid) {
	list($contenttype, $data) = contentGetForCustomerId($currentid, $contentid);

	$ext = substr($contenttype, strpos($contenttype, "/")+1);
	header("Content-disposition: filename=logo." . $ext);
	header ("Content-type: " . $contenttype);
	echo $data;
} else {
	return false;
}