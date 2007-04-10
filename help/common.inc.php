<?
$SETTINGS = parse_ini_file("../inc/settings.ini.php",true);
$IS_COMMSUITE = $SETTINGS['feature']['is_commsuite'];

require_once("../inc/db.inc.php");
require_once("../inc/DBMappedObject.php");
require_once("../inc/DBRelationMap.php");

require_once("../inc/utils.inc.php");
require_once("../obj/User.obj.php");
require_once("../obj/Access.obj.php");
require_once("../obj/Permission.obj.php");
require_once("../obj/Rule.obj.php"); //for search and sec profile rules


//get the customer URL

if ($IS_COMMSUITE) {
	$CUSTOMERURL = "default";
} /*CSDELETEMARKER_START*/ else {
	$CUSTOMERURL = substr($_SERVER["SCRIPT_NAME"],1);
	$CUSTOMERURL = strtolower(substr($CUSTOMERURL,0,strpos($CUSTOMERURL,"/")));
} /*CSDELETEMARKER_END*/

session_name($CUSTOMERURL . "_session");
session_start();

if (!isset($isindexpage) || !$isindexpage) {

	//force ssl?
	if ($SETTINGS['feature']['force_ssl'] && !isset($_SERVER["HTTPS"])) {
		redirect("../?logout=1"); //the index page will redirect to https
	}

	if (!isset($_SESSION['user']) || !isset($_SESSION['access'])) {
		$_SESSION['lasturi'] = $_SERVER['REQUEST_URI'];
		redirect("../?logout=1");
	} else {
		$USER = &$_SESSION['user'];
		$USER->refresh();
		$USER->optionsarray = false; /* will be reconstructed if needed */

		$ACCESS = &$_SESSION['access'];
		if($USER->accessid != $ACCESS->id || $ACCESS->modified < QuickQuery("select modified from access where id = $ACCESS->id"))
			$ACCESS->refresh(NULL, true);

		if (!$USER->enabled || $USER->deleted || !$USER->authorize('loginweb')) {
			redirect( "../?logout=1");
		}
	}
}
?>