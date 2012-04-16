<?
require_once("common.inc.php");
require_once("JmxClient.obj.php");

header('Content-Type: application/json');

switch ($_REQUEST["type"]) {
	case "setfieldview":
		if (isset($_REQUEST["page"]) && isset($_REQUEST["field"]) && isset($_REQUEST["value"])) {
			$displayfield = ltrim($_REQUEST["field"],"@#");
			$_SESSION['fieldview'][$_REQUEST["page"] . ":" . $displayfield] = ($_REQUEST["value"]=="true");
			$result = true;
		}
		break;
		
	case "jmxrequest":
		$result = array("error" => "", "value" => array());
		if (!$SETTINGS['servermanagement']['manageservers'] || !$MANAGERUSER->authorized("manageserver")) {
			$result["error"] = "Not Authorized";
			break;
		}
		if (isset($_REQUEST['url']) && isset($_REQUEST['mbean']) && isset($_REQUEST['jmxtype'])) {
			$jmxClient = new JmxClient($_REQUEST['url']);
			switch ($_REQUEST['jmxtype']) {
				case "read":
					if (isset($_REQUEST['attrib']))
						$jmxresult = $jmxClient->read($_REQUEST['mbean'], $_REQUEST['attrib']);
					else
						$jmxresult = $jmxClient->read($_REQUEST['mbean']);
					break;
					
				case "exec":
					if (isset($_REQUEST['op'])) {
						if (isset($_REQUEST['args']))
							$jmxresult = $jmxClient->exec($_REQUEST['mbean'], $_REQUEST['op'], explode(",",$_REQUEST['args']));
						else
							$jmxresult = $jmxClient->exec($_REQUEST['mbean'], $_REQUEST['op']);
					} else {
						$jmxresult = array("error" => "Missing operation");
					}
					break;
				default:
					$jmxresult = array("error" => "Unknown request type");
			}
			$result = $jmxresult;
		} else {
			$result["error"] = "bad/missing request parameters";
		}
		break;
	
	default:
		$result = false;
}

echo json_encode($result);
?>
