<?
require_once("common.inc.php");

header('Content-Type: application/json');

if (isset($_REQUEST["type"]))
	echo false;

switch ($_REQUEST["type"]) {
	case "setfieldview":
		if (isset($_REQUEST["page"]) && isset($_REQUEST["field"]) && isset($_REQUEST["value"])) {
			$displayfield = ltrim($_REQUEST["field"],"@#");
			$_SESSION['fieldview'][$_REQUEST["page"] . ":" . $displayfield] = ($_REQUEST["value"]=="true");				
			echo true;
			exit();
		}
		break;		
	//...
}
echo false;
?>
