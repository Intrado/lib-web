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
//require_once("parentportalutils.inc.php");

session_write_close();//WARNING: we don't keep a lock on the session file, any changes to session data are ignored past this point

$pid = $_SESSION['personid'];

if (isset($_GET['jid'])) {
	$jid = $_GET['jid'] +0;
	$query = "select
			rp." . FieldMap::GetFirstNameField() . ",
			rp." . FieldMap::GetLastNameField()
			. generateFields("rp")
			. "
			,rp.messageid as messageid
			from reportperson rp
			where rp.jobid = '$jid'
			and rp.personid = '$pid'
			and rp.type = 'phone'";

	$historicdata = QuickQueryRow($query, true);
	Message::playAudio($historicdata['messageid'], $historicdata);
}
?>