<?

////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
include_once("inc/common.inc.php");
include_once("inc/utils.inc.php");
include_once("inc/securityhelper.inc.php");

// API mode only!
//
if (!isset($_REQUEST["api"])) {
	exit();
}

$CUSTOMERURL = substr($_SERVER["SCRIPT_NAME"],1);
$CUSTOMERURL = strtolower(substr($CUSTOMERURL,0,strpos($CUSTOMERURL,"/")));

$result = Array();
$result["id"] = getSystemSetting("_customerid");
$result["name"] = getSystemSetting("displayname");
$result["key"] = $CUSTOMERURL;

header('Content-Type: application/json');

exit(json_encode(cleanObjects($result)));

?>
