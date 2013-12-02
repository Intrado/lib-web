<?

include_once("inc/common.inc.php");
include_once('inc/securityhelper.inc.php');
include_once('inc/content.inc.php');
include_once('inc/appserver.inc.php');
include_once('obj/Content.obj.php');
require_once("obj/Job.obj.php");

session_write_close();//WARNING: we don't keep a lock on the session file, any changes to session data are ignored past this point

// id => tai_messageattachment.messageid
if(isset($_GET['id'])) {
	$id = DBSafe($_GET['id']);
	
	if (userCanSee("taimessageattachment", $id)) {
		$contentid = QuickQuery("select contentid from tai_messageattachment where messageid=?", false, array($id));

		if ($content = contentGet($contentid)){
			list($contenttype, $data) = $content;
		}

		if ($data) {
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
}
?>
