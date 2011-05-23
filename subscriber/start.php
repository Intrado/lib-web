<?
require_once("common.inc.php");
require_once("../inc/securityhelper.inc.php");
require_once("../inc/html.inc.php");
require_once("../inc/table.inc.php");
require_once("../obj/FieldMap.obj.php");
require_once("../obj/Person.obj.php");
require_once("../inc/formatters.inc.php");
require_once("../obj/Message.obj.php");
require_once("../obj/MessageGroup.obj.php");
require_once("../obj/Phone.obj.php");


if (isset($_SESSION['firstlogin'])) {
	unset($_SESSION['firstlogin']);
	redirect("notificationpreferences.php");
}



////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

	$firstnameField = FieldMap::getFirstNameField();
	$lastnameField = FieldMap::getLastNameField();

	$pid = $_SESSION['personid'];
	$contactCount=array();
	$allData = array();
	$contactCount[$pid] = 1;
	$allData[$pid] = array();

	// find all jobids for these persons
	$jobids = array(); // key jobid, value array of personids
	$types = array('phone', 'email', 'sms');
	foreach ($types as $type) {
		$query = "select j.id, rp.personid from
						job j
						inner join reportperson rp on (rp.jobid=j.id)
						where
						j.startdate <= curdate() and j.startdate >= date_sub(curdate(),interval 30 day)
						and j.type = 'notification'
						and not exists (select * from jobsetting js where js.jobid = j.id and js.name='translationexpire' and js.value < curdate())
						and rp.type='" . $type . "'
						and rp.personid = " . $pid;
		$result = Query($query);
		while ($row = DBGetRow($result)) {
			// index the person array with the personid to make unique set
			$jobids[$row[0]][$row[1]] = $row[1];
		}
	}
	$jobListString = implode("','", array_keys($jobids));

	// index 1 = jobid
	// 2 = startdate
	// 3 = name
	// 4 = messagegroupid
	// 5 = firstname
	// 6 = lastname
	// 7 = personid
	$query = "select j.id, j.startdate, j.name, j.messagegroupid, u.firstname, u.lastname
				from job j
				inner join user u on (u.id = j.userid)
				where
				j.id in ('" . $jobListString . "')
				order by j.startdate desc, j.starttime, j.id desc";
	$result = Query($query);
	while ($row = DBGetRow($result)) {
		foreach ($jobids[$row[0]] as $personid) {
			// create new array for personrow, otherwise adds elements to $row for all persons
			$personrow = array();
			// start with an incremental id
			$personrow[] = $contactCount[$personid];
			// copy row values
			foreach ($row as $v) {
				$personrow[] = $v;
			}
			// append personid
			$personrow[] = $personid;
			
			// store data for display by person
			$allData[$personid][] = $personrow;
			$contactCount[$personid]++;
		}
	}
	
	$titles = array("0" => "##",
					"2" => _L("Date"),
					"3" => "#" . _L("Job Name"),
					"SentBy" => "#" . _L("Sent By"),
					"Actions" => _L("Actions")
				);

	$formatters = array("2" => "format_date",
						"SentBy" => "sender",
						"Actions" => "message_action"
					);


////////////////////////////////////////////////////////////////////////////////
// Functions
////////////////////////////////////////////////////////////////////////////////

function message_action($row, $index){
	//index 1 is job id
	//index 4 is messagegroupid
	//index 7 is person id

	$messagegroup = new MessageGroup($row[4]);

	if ($messagegroup->hasMessage("phone")) {
		$buttons[] = button(_L("Play"), "popup('previewmessage.php?jobid=" . $row[1] . "&personid=" . $row[7] . "&type=phone', 400, 500,'preview');",null);
	}
	if ($messagegroup->hasMessage("email")) {
		$buttons[] = button(_L("Read Email"), "popup('previewmessage.php?jobid=" . $row[1] . "&personid=" . $row[7] . "&type=email', 400, 500,'preview');",null);
	}
	if ($messagegroup->hasMessage("sms")) {
		$buttons[] = button(_L("Read SMS"), "popup('previewmessage.php?jobid=" . $row[1] . "&personid=" . $row[7] . "&type=sms', 400, 500,'preview');",null);
	}
	
	return "<table><tr><td>" . implode("</td><td>", $buttons) . "</td></tr></table>";
}

function format_date($row, $index){
	return date("M j, Y", strtotime($row[$index]));
}

function sender($row, $index){
	//index 1 is jobid
	//index 4 is messagegroupid
	//index 5 is first name
	//index 6 is last name
	//index 7 is personid
	
	// all email messages have same sent from data, so it does not matter if this is plain or html
	$emailmsgid = QuickQuery("select id from message where messagegroupid = ? and type = 'email'", false, array($row[4]));
	if (isset($emailmsgid) && $emailmsgid != 0) {
		$message = DBFind("Message", "from message where id=?", false, array($emailmsgid));
		$messagedata = sane_parsestr($message->data);
		return "<a href='mailto:" . $messagedata['fromemail'] . "'>" . escapehtml($messagedata['fromname']) . "</a>";
	} else {
		return escapehtml($row[5]) . " " . escapehtml($row[6]);
	}
}

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
$TITLE=_L('Welcome - %1$s %2$s', escapehtml($_SESSION['subscriber.firstname']), escapehtml($_SESSION['subscriber.lastname']));
$PAGE = 'messages:messages';
require_once("nav.inc.php");
	?><div><b><?=_L("Messages from the last 30 days")?></b></div><br><?

	// if customer has message callback feature, let the user know about it
	if (getCustomerSystemSetting("_hascallback", "0")) {
		echo _L('You may call %s at any time to listen to your phone messages.', Phone::format(getCustomerSystemSetting("inboundnumber"))) . "<BR><BR>";
	}

		$data = $allData[$pid];
		$person = new Person($pid);

		startWindow(escapehtml($person->$firstnameField) . " " . escapehtml($person->$lastnameField), 'padding: 3px;', true);
		if (count($data) == 0) {
?>
			<div><?= _L("No Messages") ?></div>
<?
		} else {
			$scroll="";
			if(count($data) > 6)
				$scroll = 'class="scrollTableContainer"';
?>
			<div <?=$scroll?>>
				<table width="100%" cellpadding="3" cellspacing="1" class="list sortable" id="tableid subscriber">
<?
					showTable($data, $titles, $formatters);
?>
				</table>
			</div>
<?
		}
		endWindow();
?>
		<br>
<?
require_once("navbottom.inc.php");
?>
