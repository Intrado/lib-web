<?
require_once("common.inc.php");
require_once("../inc/form.inc.php");
require_once("../inc/html.inc.php");
require_once("../inc/table.inc.php");
require_once("../inc/utils.inc.php");
require_once("../inc/themes.inc.php");
require_once("../obj/Template.obj.php");
require_once("../obj/MessageGroup.obj.php");
require_once("../obj/Message.obj.php");

if (!$MANAGERUSER->authorized("editcustomer"))
	exit("Not Authorized");

if (!isset($_GET['cid']))
	exit("Missing customer id");

if (!isset($_GET['id']))
	exit("Missing messagegroup id");
$messagegroupid = $_GET['id'] + 0;

$currentid = $_GET['cid'] + 0;
$custinfo = QuickQueryRow("select s.dbhost, c.dbusername, c.dbpassword, c.urlcomponent from customer c inner join shard s on (c.shardid = s.id) where c.id = '$currentid'");
$custdb = DBConnect($custinfo[0], $custinfo[1], $custinfo[2], "c_$currentid");
if (!$custdb) {
	exit("Connection failed for customer: $custinfo[0], db: c_$currentid");
}

$templatetype = QuickQuery("select type from template where messagegroupid = ?", $custdb, array($messagegroupid));
// get this jobs messagegroup and it's messages
$messagesbylangcode = array();
// TODO new 'template' type of messagegroup
$messagegroup = DBFind("MessageGroup", "from messagegroup where id = ? and type = 'notification'", false, array($messagegroupid), $custdb);
if ($messagegroup) {
	$messages = DBFindMany("Message", "from message where messagegroupid = ? and type = 'email' and subtype = 'html'", false, array($messagegroupid), $custdb);
	if ($messages) {
		foreach ($messages as $id => $message) {
			$messagesbylangcode[$message->languagecode] = $message;
			$messagesbylangcode[$message->languagecode]->readHeaders();
		}
	}
}

// get the customer default language data
//$defaultcode = Language::getDefaultLanguageCode();
//$defaultlanguage = Language::getName(Language::getDefaultLanguageCode());
//$languagemap = Language::getLanguageMap();





include_once("nav.inc.php");

?>
<h3>Edit Email Template (<?= $templatetype?>) for Customer: <?= $custinfo[3]?></h3>



<?
include_once("navbottom.inc.php");
?>
