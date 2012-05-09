<?
//Used only in portal
require_once("common.inc.php");

require_once('../inc/securityhelper.inc.php');
require_once('../inc/appserver.inc.php');
require_once('../inc/content.inc.php');
require_once("../obj/Content.obj.php");
require_once("../obj/Message.obj.php");
require_once("../obj/MessagePart.obj.php");
require_once("../obj/AudioFile.obj.php");
require_once("../obj/Voice.obj.php");
require_once("../obj/FieldMap.obj.php");
require_once("../inc/reportutils.inc.php");
require_once("parentportalutils.inc.php");

// load the thrift api requirements.
require_once('thrift/Thrift.php');
require_once $GLOBALS['THRIFT_ROOT'].'/protocol/TBinaryProtocol.php';
require_once $GLOBALS['THRIFT_ROOT'].'/transport/TSocket.php';
require_once $GLOBALS['THRIFT_ROOT'].'/transport/TBufferedTransport.php';
require_once $GLOBALS['THRIFT_ROOT'].'/transport/TFramedTransport.php';
require_once($GLOBALS['THRIFT_ROOT'].'/packages/commsuite/CommSuite.php');

session_write_close();//WARNING: we don't keep a lock on the session file, any changes to session data are ignored past this point

if(isset($_GET['pid'])){
	$ids = getContactIDs($_SESSION['portaluserid']);
	if(!in_array($_GET['pid'], $ids)){
		redirect("unauthorized.php");
	}
}

if (isset($_GET['jid']) && isset($_GET['pid'])) {
	$jid = DBSafe($_GET['jid']);
	$pid = DBSafe($_GET['pid']);
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