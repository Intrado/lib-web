<?
// read-only view of an imported contact with all their metadata

////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
include_once("inc/common.inc.php");
include_once("inc/utils.inc.php");
include_once("inc/securityhelper.inc.php");
include_once("inc/form.inc.php");
include_once("inc/html.inc.php");
include_once("inc/table.inc.php");
include_once("obj/FieldMap.obj.php");
include_once("obj/Person.obj.php");
include_once("obj/Address.obj.php");
include_once("obj/Phone.obj.php");
include_once("obj/Email.obj.php");
include_once("obj/Language.obj.php");
include_once("obj/JobType.obj.php");
include_once("obj/Sms.obj.php");

////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////

if(isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'],"viewcontact.php") === false && strpos($_SERVER['HTTP_REFERER'],"editcontact.php") === false){
	$_SESSION['contact_referer'] = $_SERVER['HTTP_REFERER'];
}
$method = "view";
include("contactdetails.inc.php");
?>