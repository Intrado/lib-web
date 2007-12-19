<?
include_once("inc/common.inc.php");

include_once('inc/securityhelper.inc.php');
include_once('inc/content.inc.php');
include_once("obj/Content.obj.php");
include_once("obj/Message.obj.php");
include_once("obj/MessagePart.obj.php");
include_once("obj/AudioFile.obj.php");
include_once("obj/Voice.obj.php");
include_once("obj/FieldMap.obj.php");

session_write_close();//WARNING: we don't keep a lock on the session file, any changes to session data are ignored past this point

if(isset($_GET['id'])) {
	$id = DBSafe($_GET['id']);
	if (userOwns("message",$id)) {
		$fields=array();
		for($i=1; $i <= 20; $i++){
			$fieldnum = sprintf("f%02d", $i);
			if(isset($_REQUEST[$fieldnum]))
				$fields[$fieldnum] = $_REQUEST[$fieldnum];
		}
		Message::playAudio($id, $fields);
	}
}

?>
