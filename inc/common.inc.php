<?

require_once("db.inc.php");
require_once("DBMappedObject.php");
require_once("DBRelationMap.php");

require_once("inc/utils.inc.php");
require_once("obj/User.obj.php");
require_once("obj/Access.obj.php");
require_once("obj/Permission.obj.php");
require_once("obj/Rule.obj.php"); //for search and sec profile rules


//get the customer URL
$CUSTOMERURL = substr($_SERVER["SCRIPT_NAME"],1);
$CUSTOMERURL = strtolower(substr($CUSTOMERURL,0,strpos($CUSTOMERURL,"/")));

session_name($CUSTOMERURL . "_session");
session_start();



if (!isset($isindexpage) || !$isindexpage) {


	if (!isset($_SESSION['user']) || !isset($_SESSION['access'])) {
		$_SESSION['lasturi'] = $_SERVER['REQUEST_URI'];
		redirect("./?logout=1");
	} else {
		$USER = &$_SESSION['user'];
		$USER->refresh();
		$USER->optionsarray = false; /* will be reconstructed if needed */

		$ACCESS = &$_SESSION['access'];
		if($USER->accessid != $ACCESS->id || $ACCESS->modified < QuickQuery("select modified from access where id = $ACCESS->id"))
			$ACCESS->refresh(NULL, true);

		if (!isset($_SESSION['timezone'])) {
			$_SESSION['timezone'] = QuickQuery("select timezone from customer where id=$USER->customerid");
		}

		if (!$USER->enabled || $USER->deleted || !$USER->authorize('loginweb')) {
			redirect("./?logout=1");
		}
	}
}

if (isset($_SESSION['timezone'])) {
	@date_default_timezone_set($_SESSION['timezone']);
	QuickUpdate("set time_zone='" . $_SESSION['timezone'] . "'");
}


?>