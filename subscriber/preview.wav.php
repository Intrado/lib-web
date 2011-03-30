<?
//Used only in portal
include_once("common.inc.php");

include_once('../inc/securityhelper.inc.php');
include_once('../inc/content.inc.php');
include_once("../obj/Content.obj.php");
include_once("../obj/Message.obj.php");
include_once("../obj/MessagePart.obj.php");
include_once("../obj/AudioFile.obj.php");
include_once("../obj/Voice.obj.php");
include_once("../obj/FieldMap.obj.php");
require_once("../inc/reportutils.inc.php");

session_write_close();//WARNING: we don't keep a lock on the session file, any changes to session data are ignored past this point

$pid = $_SESSION['personid'];

if (isset($_GET['jid'])) {
	$jid = $_GET['jid'] +0;
	$query = "select
			rp." . FieldMap::GetFirstNameField() . ",
			rp." . FieldMap::GetLastNameField()
			. generateFields("rp")
			. "
			from reportperson rp
			where rp.jobid = '$jid'
			and rp.personid = '$pid'
			and rp.type = 'phone'";

	$historicdata = QuickQueryRow($query, true);
	
	if (isset($_GET['langcode']))
		$languagecode = $_GET['langcode'];
	else
		$languagecode = "en";
	$messagegroupid = QuickQuery("select messagegroupid from job where id = ?", false, array($jid));
	$messageid = QuickQuery("select id from message where messagegroupid = ? and type = 'phone' and languagecode = ? and autotranslate in ('none', 'translated')", false, array($messagegroupid, $languagecode));

//error_log("playing messageid=".$messageid." lang=".$languagecode);
	Message::playAudio($messageid, $historicdata, "mp3");
}
?>