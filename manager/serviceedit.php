<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
require_once("common.inc.php");
require_once("../inc/table.inc.php");
require_once("../inc/html.inc.php");
require_once("../inc/utils.inc.php");
require_once("../obj/Validator.obj.php");
require_once("../obj/Form.obj.php");
require_once("../obj/FormItem.obj.php");
require_once("Server.obj.php");
require_once("Service.obj.php");
////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$SETTINGS['servermanagement']['manageservers'] || !$MANAGERUSER->authorized("manageserver"))
	exit("Not Authorized");

////////////////////////////////////////////////////////////////////////////////
// Action/Request Processing
////////////////////////////////////////////////////////////////////////////////
if (isset($_GET['id'])) {
	$_SESSION['serviceedit'] = array();
	$_SESSION['serviceedit']['serviceid'] = $_GET['id'] + 0;
	redirect();
}

////////////////////////////////////////////////////////////////////////////////
// Form 
////////////////////////////////////////////////////////////////////////////////
if (isset($_SESSION['serviceedit']['serviceid'])) {
	$serviceid = $_SESSION['serviceedit']['serviceid'];
} else {
	$serviceid = false;
}
$service = new Service($serviceid);

switch ($service->type) {
	case "commsuite":
		require_once("serviceeditcommsuite.php");
		break;
	case "kona":
		require_once("serviceeditkona.php");
		break;
	default:
		exit("Unknown service type!");
}
?>