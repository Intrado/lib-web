<?
include_once("inc/common.inc.php");
include_once("inc/securityhelper.inc.php");
include_once("inc/content.inc.php");
include_once("inc/appserver.inc.php");
include_once("obj/Content.obj.php");

// Check against $_SESSION['usercontentids'] for security; don't allow a user to view arbitrary content.
if (!isset($_GET['id']) || !contentAllowed($_GET['id']))
	exit();

$contentid = $_GET['id'] + 0;

if ($content = contentGet($contentid)) {
	list($contenttype,$data) = $content;
	
	if($data) {
		header("HTTP/1.0 200 OK");
		header("Expires: " . gmdate('D, d M Y H:i:s', time() + 60*60) . " GMT"); //exire in 1 hour, but if theme changes so will hash pointing to this file
		header('Content-type: ' . $contenttype);
		header("Pragma: private");
		header("Cache-Control: private");
		header("Content-Length: " . strlen($data));
		header("Connection: close");
		echo $data;
	}
}

?>