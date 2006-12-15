<?

$SETTINGS = parse_ini_file("../inc/settings.ini.php",true);
$IS_COMMSUITE = $SETTINGS['feature']['is_commsuite'];

require_once("../inc/db.inc.php");
require_once("../inc/DBMappedObject.php");
require_once("../inc/DBRelationMap.php");
require_once("../inc/utils.inc.php");

require_once("ParentUser.obj.php");


if (!$IS_COMMSUITE) {
	$CUSTOMERURL = substr($_SERVER["SCRIPT_NAME"],1);
	$CUSTOMERURL = strtolower(substr($CUSTOMERURL,0,strpos($CUSTOMERURL,"/")));
} else {
	$CUSTOMERURL = "default";
}

session_name($CUSTOMERURL . "_parentsession");
session_start();

if(!isset($parentloginbypass) || !$parentloginbypass){
	if(!isset($_SESSION["parentuser"])) {
		redirect("index.php?logout=1");
	} else {
		$PARENTUSER = $_SESSION['parentuser'];
		$PARENTUSER->refresh();
		$CHILDLIST = $_SESSION['childlist'];
	}
}



?>