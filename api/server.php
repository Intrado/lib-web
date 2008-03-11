<?

$SETTINGS = parse_ini_file("../inc/settings.ini.php",true);
$IS_COMMSUITE = $SETTINGS['feature']['is_commsuite'];

require_once("XML/RPC.php");
require_once("../inc/auth.inc.php");
require_once("SMAPI.php");

require_once("../inc/db.inc.php");
require_once("../inc/DBMappedObject.php");
require_once("../inc/DBRelationMap.php");
require_once("../inc/sessionhandler.inc.php");

require_once("../inc/utils.inc.php");
require_once("../obj/User.obj.php");
require_once("../obj/Access.obj.php");
require_once("../obj/Permission.obj.php");
require_once("../obj/Rule.obj.php"); //for search and sec profile rules
require_once("../inc/securityhelper.inc.php");

$USER = null;

// OBJECTS
require_once("../obj/Message.obj.php");
require_once("../obj/MessagePart.obj.php");
require_once("../obj/AudioFile.obj.php");
require_once("../obj/Content.obj.php");

// API Files
require_once("API_List.obj.php");
require_once("API_Messages.obj.php");

ini_set("soap.wsdl_cache_enabled", "0"); // disabling WSDL cache
$server=new SoapServer("SMAPI.wsdl");
$server->setClass("SMAPI");
$server->handle();
//var_dump($server->getFunctions());


?>