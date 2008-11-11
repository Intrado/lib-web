<?
include_once("inboundutils.inc.php");

include_once("../obj/Message.obj.php");
include_once("../obj/MessagePart.obj.php");
include_once("../obj/Person.obj.php");
include_once("../obj/Voice.obj.php");
include_once("../obj/AudioFile.obj.php");
include_once("msgcallbackMessagePlayback.obj.php");


global $BFXML_VARS;


function nomessages() {
?>
<voice>
	<message name="nomessages">
		<tts gender="female">There are no messages at this time.  You may call back later to listen to messages that were sent to you. Goodbye.  </tts>
		<hangup />
	</message>
</voice>
<?
}

////////////////////////////////////////
if($REQUEST_TYPE == "new"){
	?>
	<error>msgcallbackgetlist: wanted result or continue, got new </error>
	<?
} else if($REQUEST_TYPE == "continue"){

	if (isset($_SESSION['contactphone'])) {
		$timesince = (time() - (30*24*60*60)) * 1000; // 30 days ago, in milliseconds since 1970
		$query = "select j.id, j.userid, rc.sequence, j.phonemessageid, rc.personid from reportcontact rc join job j where rc.type='phone' and rc.phone='".$_SESSION['contactphone']."' and j.id=rc.jobid and j.phonemessageid is not null and rc.starttime>$timesince order by rc.starttime desc";
		//error_log($query);
		$resultlist = QuickQueryMultiRow($query);
		$messagelist = array();
		foreach ($resultlist as $row) {
			$msg = new MessagePlayback();
			////error_log("0->".$row[0] ." 1->". $row[1] . " 2->".$row[2]);
			$msg->jobid = $row[0];
			$msg->userid = $row[1];
			$msg->sequence = $row[2];
			$msg->messageid = $row[3];
			$msg->messageparts = DBFindMany("MessagePart", "from messagepart where messageid=$row[3]");
			$msg->person = DBFind("Person", "from person where id=$row[4]"); // TODO use reportperson
			$msg->leavemessage = QuickQuery("select value from jobsetting where jobid=$row[0] and name='leavemessage'");
			$msg->messageconfirmation = QuickQuery("select value from jobsetting where jobid=$row[0] and name='messageconfirmation'");
			$messagelist[] = $msg;
		}
		if (count($messagelist) === 0) {
			nomessages();
		} else {
			$_SESSION['messagelist'] = $messagelist;
			$_SESSION['messageindex'] = 0;
			$_SESSION['messagetotal'] = count($messagelist);
			forwardToPage("msgcallbackplayback.php");
		}

	} else {
		//error_log("MISSING CONTACT PHONE");
		?>
		<error>msgcallbackgetlist: continue requires contactphone </error>
		<?
	}

} else {
	//huh, they must have hung up
	$_SESSION = array();
	?>
	<ok />
	<?
}

?>
