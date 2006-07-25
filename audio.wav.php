<?
/* Use this if audio doesn't play, sometimes external apps start new sessions
*/

require_once("inc/db.inc.php");
require_once("inc/DBMappedObject.php");
require_once("inc/DBRelationMap.php");

require_once("inc/utils.inc.php");
require_once("obj/User.obj.php");
require_once("obj/Access.obj.php");
require_once("obj/Permission.obj.php");
require_once("obj/Rule.obj.php"); //for search and sec profile rules

include_once('inc/securityhelper.inc.php');
include_once('obj/AudioFile.obj.php');
include_once('obj/Content.obj.php');

if(isset($_GET['id'])) {
	$id = DBSafe($_GET['id']);
	if (userOwns("audiofile",$id)) {
		$af = new AudioFile($id);
		$c = new Content($af->contentid);
		header('Content-type: ' . $c->contenttype);

		$data = base64_decode($c->data);
		$size = strlen($data);

		header("HTTP/1.0 200 OK");
		header('Pragma: private');
		header('Cache-control: private, must-revalidate');
		header("Content-Length: $size");
		header("Connection: close");


		echo $data;
	}
}
?>
