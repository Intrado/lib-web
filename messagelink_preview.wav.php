<?
$SETTINGS = parse_ini_file("inc/settings.ini.php",true);

require_once("XML/RPC.php");
require_once("inc/DBMappedObject.php");
require_once("inc/db.inc.php");
require_once("inc/auth.inc.php");
require_once('inc/securityhelper.inc.php');
require_once('inc/content.inc.php');
require_once("obj/Content.obj.php");
require_once("obj/Message.obj.php");
require_once("obj/MessagePart.obj.php");
require_once("obj/AudioFile.obj.php");
require_once("obj/Voice.obj.php");
require_once("obj/FieldMap.obj.php");
require_once("inc/reportutils.inc.php");
require_once("inc/utils.inc.php");

session_write_close();//WARNING: we don't keep a lock on the session file, any changes to session data are ignored past this point

if(isset($_GET['jobcode'])) {
	$messageinfo = loginMessageLink($_GET['jobcode']);

	$historicdata = QuickQueryRow("select rp.". FieldMap::GetFirstNameField(). ", rp.". FieldMap::GetLastNameField(). generateFields("rp"). ", rp.messageid as messageid 
		from reportperson rp 
		where rp.jobid =?
			and rp.personid =?
			and rp.type = 'phone'", false, false, array($messageinfo['jobid'], $messageinfo['personid']));
	Message::playAudio($messageinfo['messageid'], $historicdata,"mp3");
}
?>
