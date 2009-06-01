<?
include_once('common.inc.php');

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

$query = "select c.contenttype, c.data from content c inner join setting s on (s.value = c.id) where s.name = '".$setting."'";
$row = QuickQueryRow($query, false, $custdb);
if ($row) {
	$data = base64_decode($row[1]);

	$ext = substr($row[0], strpos($row[0], "/")+1);
	header("Content-disposition: filename=logo." . $ext);
	header ("Content-type: " . $row[0]);
	echo $data;
} else {
	return false;
}