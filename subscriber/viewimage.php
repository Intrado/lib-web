<?
include_once("common.inc.php");
include_once("../inc/content.inc.php");
include_once("../obj/Content.obj.php");

// required content id
if (!isset($_GET['id']))
	redirect("unauthorized.php");

$contentid = $_GET['id'] + 0;

// do not allow a user to view arbitrary content.
$query = "select 1 from messagepart mp " . 
	"left join message m on (m.id = mp.messageid) " .
	"left join messagegroup mg on (mg.id = m.messagegroupid) " .
	"where mp.imagecontentid = ? and mg.id = ?";

if (!QuickQuery($query, null, array($contentid, $_SESSION['previewmessagegroupid'])))
	redirect("unauthorized.php");

// ok to display image for preview message
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