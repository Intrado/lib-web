<?
include_once("inboundutils.inc.php");

include_once("../obj/Message.obj.php");
include_once("../obj/MessagePart.obj.php");
include_once("../obj/Voice.obj.php");
include_once("../obj/AudioFile.obj.php");


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
if ($REQUEST_TYPE == "new") {
	?>
	<error>msgcallbackgetlist: wanted result or continue, got new </error>
	<hangup />
	<?
} else if ($REQUEST_TYPE == "continue") {

	if (isset($_SESSION['contactphone'])) {
		$query = "select j.id, j.userid, rp.messageid, rc.personid, rc.sequence
		from job j
		left join jobsetting js on (js.jobid=j.id and js.name='translationexpire')
		left join reportcontact rc on (rc.jobid = j.id and rc.type='phone')
		left join reportperson rp on (rp.jobid = j.id and rp.personid=rc.personid)
		where
		j.startdate <= curdate() and j.startdate >= date_sub(curdate(),interval 30 day)
		and j.status in ('active', 'complete')
		and j.questionnaireid is null
		and rc.phone='".$_SESSION['contactphone']."'
		and (js.value is null or js.value >= date_sub(curdate(),interval 15 day))
		order by j.startdate desc, j.starttime, j.id desc";

//error_log($query);
		$resultlist = QuickQueryMultiRow($query);
		$messagelist = array();
		foreach ($resultlist as $row) {
			$msg = array();
			$msg['jobid'] = $row[0];
			$msg['userid'] = $row[1];
			$msg['messageid'] = $row[2];
			$msg['messageparts'] = DBFindMany("MessagePart", "from messagepart where messageid=$row[2]");
			$reportpersonfields = QuickQueryRow("select f01, f02, f03, f04, f05, f06, f07, f08, f09, f10, " .
					"f11, f12, f13, f14, f15, f16, f17, f18, f19, f20 from reportperson " .
					"where jobid=$row[0] and personid=$row[3] and type='phone'", true);
			$msg['personid'] = $row[3];
			$msg['personfields'] = $reportpersonfields;
			$msg['sequence'] = $row[4];
			$msg['leavemessage'] = QuickQuery("select value from jobsetting where jobid=$row[0] and name='leavemessage'");
			$msg['messageconfirmation'] = QuickQuery("select value from jobsetting where jobid=$row[0] and name='messageconfirmation'");

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
		error_log("msgcallbackgetlist is missing required argument 'contactphone'");
		?>
		<error>msgcallbackgetlist: continue requires contactphone </error>
		<hangup />
		<?
	}

} else {
	//huh, they must have hung up
	$SESSIONDATA = null;
?>
	<ok />
<?
}

?>
