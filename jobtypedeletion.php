<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
include_once("inc/common.inc.php");
include_once("inc/securityhelper.inc.php");
include_once("inc/table.inc.php");
include_once("inc/html.inc.php");
include_once("inc/utils.inc.php");
include_once("inc/form.inc.php");
include_once("obj/NotificationType.obj.php");
include_once("obj/Setting.obj.php");
include_once("obj/Phone.obj.php");

if (!isset($_REQUEST['api'])) {
	header("HTTP/1.1 404 NotFound");
	exit();
}

if (!$USER->authorize('managesystem')) {
	header("HTTP/1.1 403 Forbidden");
	header("Content-Type: application/json");
	exit();
}

$ntid = $_REQUEST['ntid'];

if (!strlen($ntid)) {
	header("HTTP/1.1 404 NotFound");
	exit();
}

$systemprioritynames = array(
	"1" => "Emergency",
	"2" => "High Priority",
	"3" => "General");

foreach($systemprioritynames as $index => $name){
	$types[$index] = DBFindMany('NotificationType', "from notificationtype where deleted=0 and systempriority = '" . $index . "' and type = 'job' order by name");
}

$maxphones = getSystemSetting("maxphones", 3);
$maxemails = getSystemSetting("maxemails", 2);
$maxsms = getSystemSetting("maxsms", 2);
$maxcolumns = max($maxphones, $maxemails, $maxsms);

$dtype = null;

foreach($systemprioritynames as $index => $name) {
	foreach($types[$index] as $type) {
		if ($ntid == $type->id) {
			$dtype = $type;
			break;
		}
	}
}

if ($dtype) {
	if ($dtype->systempriority != 3) {
		header("HTTP/1.1 403 Forbidden");
		exit();
	}

	if (QuickQuery("select count(*) from userjobtypes where jobtypeid = '" . DBSafe($ntid) . "'")) {
		header("HTTP/1.1 409 Conflict");
		header("Content-Type: application/json");
		exit(json_encode(Array("code" => "notificationTypeInUse")));
	}

	$dtype->deleted = 1;
	$dtype->update();
} else {
	header("HTTP/1.1 404 NotFound");
	exit();
}
