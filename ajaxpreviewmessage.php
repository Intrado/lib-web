<?
require_once("inc/common.inc.php");

header('Content-Type: application/json');
// Sets session data with message parts from a json encoded string of massage parts
if (isset($_REQUEST['message'])) {
	$messageparts = json_decode($_REQUEST['message']);
	if (is_array($messageparts)) {
		$uid = uniqid();
		$_SESSION["previewmessage"] = array("uid" => $uid, "parts" => $messageparts);
		echo json_encode($uid);
		exit();
	}
}
echo "false";


?>
