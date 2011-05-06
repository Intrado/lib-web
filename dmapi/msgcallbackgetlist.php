<?
include_once("inboundutils.inc.php");

include_once("../obj/MessageGroup.obj.php");
include_once("../obj/Message.obj.php");
include_once("../obj/MessagePart.obj.php");
include_once("../obj/Voice.obj.php");
include_once("../obj/AudioFile.obj.php");


global $BFXML_VARS;


function nomessages() {
?>
<voice>
	<message name="nomessages">
		<tts gender="female">There are no new messages for this phone number. To check for messages sent to a different phone number, please hang up and call again.  goodbye! </tts>
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
		$query = "select j.id, j.userid, j.messagegroupid, rc.personid, rc.sequence, rc.starttime
			from job j
			left join phone ph on 
				(ph.phone=?)
			left join jobsetting js on 
				(js.jobid=j.id and js.name='translationexpire')
			inner join reportcontact rc on 
				(rc.jobid = j.id and rc.type='phone' and rc.personid = ph.personid and rc.phone=?)
			inner join reportperson rp on 
				(rp.jobid = j.id and rp.personid=rc.personid and rp.type='phone')
			where
				j.startdate <= curdate() and j.startdate >= date_sub(curdate(),interval 30 day)
				and j.status in ('active', 'complete')
				and j.questionnaireid is null
				and (js.value is null or js.value >= curdate())
				and rc.result not in ('duplicate','blocked','notattempted')
			group by rc.jobid, rc.personid, rc.type, rc.sequence
			order by rc.starttime desc
			limit 10";

//error_log($query);
		$resultlist = QuickQueryMultiRow($query,false,false,array($_SESSION['contactphone'],$_SESSION['contactphone']));
		$messagelist = array();
		foreach ($resultlist as $row) {
			$msg = array();
			$msg['jobid'] = $row[0];
			$msg['userid'] = $row[1];
			$msg['messagegroupid'] = $row[2];
			$query = "select f01, f02, f03, f04, f05, f06, f07, f08, f09, f10,f11, f12, f13, f14, f15, f16, f17, f18, f19, f20 from reportperson " 
					."where jobid=? and personid=? and type='phone'";
			$reportpersonfields = QuickQueryRow($query, true,false,array($row[0],$row[3]));
			
			$messagegroup = DBFind('MessageGroup', 'from messagegroup where id = ?', false, array($msg['messagegroupid']));
			$messageforperson = $messagegroup->getMessageOrDefault("phone", "voice", $reportpersonfields['f03'], false);
			
			$msg['messageid'] = $messageforperson->id;
			$msg['messageparts'] = DBFindMany("MessagePart", "from messagepart where messageid=? order by sequence",false,array($messageforperson->id));

			$msg['personid'] = $row[3];
			$msg['personfields'] = $reportpersonfields;
			$msg['sequence'] = $row[4];
			$msg['starttime'] = $row[5];
			$msg['leavemessage'] = QuickQuery("select value from jobsetting where jobid=? and name='leavemessage'",false,array($row[0]));
			$msg['messageconfirmation'] = QuickQuery("select value from jobsetting where jobid=? and name='messageconfirmation'",false,array($row[0]));

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
