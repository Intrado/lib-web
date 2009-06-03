<?

$SETTINGS = parse_ini_file("subscribersettings.ini.php",true);
$IS_COMMSUITE = $SETTINGS['feature']['is_commsuite'];

//get the customer URL
if ($IS_COMMSUITE) {
	$CUSTOMERURL = "default";
	$BASEURL = "";
} /*CSDELETEMARKER_START*/ else {
	$CUSTOMERURL = substr($_SERVER["SCRIPT_NAME"],1);
	$CUSTOMERURL = strtolower(substr($CUSTOMERURL,0,strpos($CUSTOMERURL,"/")));
	$BASEURL = "/$CUSTOMERURL";
} /*CSDELETEMARKER_END*/

require_once("XML/RPC.php");
require_once("authsubscriber.inc.php");
//require_once("subscribersessionhandler.inc.php");

require_once("../inc/db.inc.php");
require_once("../inc/DBMappedObject.php");
require_once("../inc/DBRelationMap.php");
require_once("../inc/utils.inc.php");

require_once("../obj/Form.obj.php");
require_once("../obj/FormItem.obj.php");
require_once("../obj/Validator.obj.php");

session_start();

// load subscriber locale 
require_once("../inc/locale.inc.php");

?>