<?
//Used only in portal
include_once("common.inc.php");

include_once('../inc/securityhelper.inc.php');
include_once('../inc/content.inc.php');
include_once("../obj/Content.obj.php");
include_once("../obj/Message.obj.php");
include_once("../obj/MessagePart.obj.php");
include_once("../obj/AudioFile.obj.php");
include_once("../obj/Voice.obj.php");
include_once("../obj/FieldMap.obj.php");

session_write_close();//WARNING: we don't keep a lock on the session file, any changes to session data are ignored past this point

if (isset($_GET['id']) && isset($_GET['customerid'])) {
	$id = $_GET['id']+0;
	$customerid = $_GET['customerid']+0;
	$custinfo = QuickQueryRow("select s.dbhost, c.dbusername, c.dbpassword, c.urlcomponent, c.enabled from customer c inner join shard s on (c.shardid = s.id) where c.id = ?", false, false, array($customerid));
	$_dbcon = DBConnect($custinfo[0], $custinfo[1], $custinfo[2], "c_" . $customerid);
	if (!$_dbcon) {
		exit("Connection failed for customer: $custinfo[0], db: c_" . $customerid);
	}
	$fields = array();
	for ($i=1; $i <= 20; $i++) {
		$fieldnum = sprintf("f%02d", $i);
		if(isset($_REQUEST[$fieldnum]))
			$fields[$fieldnum] = $_REQUEST[$fieldnum];
	}
	Message::playAudio($id, $fields);
}
?>