<?
require_once("common.inc.php");
require_once("../inc/securityhelper.inc.php");
require_once("../inc/html.inc.php");
require_once("parentportalutils.inc.php");
require_once("../inc/table.inc.php");
require_once("../obj/FieldMap.obj.php");
require_once("../obj/Person.obj.php");
require_once("../inc/formatters.inc.php");
require_once("../obj/Message.obj.php");
require_once("../obj/MessageGroup.obj.php");
require_once("../obj/Phone.obj.php");


////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

if(isset($_SESSION['customerid']) && $_SESSION['customerid']){
	$firstnameField = FieldMap::getFirstNameField();
	$lastnameField = FieldMap::getLastNameField();
	$contactList = getContactIDs($_SESSION['portaluserid']);

	$contactListString = implode("','", $contactList);
	$contactCount=array();
	$allData = array();
	foreach($contactList as $personid){
		$contactCount[$personid] = 1;
		$allData[$personid] = array();
	}

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
						and rp.personid in ('" . $contactListString . "')";
		$result = Query($query);
		while ($row = DBGetRow($result)) {
			// index the person array with the personid to make unique set
			$jobids[$row[0]][$row[1]] = $row[1];
		}
	}
	$jobListString = implode("','", array_keys($jobids));

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
} else {
	$result = portalGetCustomerAssociations();
	if($result['result'] == ""){
		$customerlist = $result['custmap'];
		$customeridlist = array_keys($customerlist);
	}
	if(isset($customerlist) && sizeof($customerlist) > 0){
		redirect("choosecustomer.php");
	}
}
////////////////////////////////////////////////////////////////////////////////
// Functions
////////////////////////////////////////////////////////////////////////////////

function message_action($row, $index){
	//index 1 is job id
	//index 4 is messagegroup id
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
	//index 4 is messagegroupid
	//index 5 is first name
	//index 6 is last name
	
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
$TITLE=_L('Welcome - %1$s %2$s', escapehtml($_SESSION['portaluser']['portaluser.firstname']), escapehtml($_SESSION['portaluser']['portaluser.lastname']));
$PAGE = 'messages:messages';
include_once("nav.inc.php");

if(isset($contactList) && $contactList){
	?><div><b><?=_L("Messages from the last 30 days")?></b></div><br><?

	// if customer has message callback feature, let the user know about it
	if ($INBOUND_MSGCALLBACK) {
		echo _L('You may call %s at any time to listen to your phone messages.', Phone::format($INBOUND_MSGCALLBACK)) . "<BR><BR>";
	}

	$counter = 1000;
	foreach($contactList as $personid){
		$counter++;
		$data = $allData[$personid];
		$person = new Person($personid);
		// if person id deleted and has no messages, do not show
		if ($person->deleted && count($data) == 0) continue;

		startWindow(escapehtml($person->$firstnameField) . " " . escapehtml($person->$lastnameField), 'padding: 3px;', true);
		if (count($data) == 0) {
?>
			<div><?=_L("No Messages")?></div>
<?
		} else {
			$scroll="";
			if(count($data) > 6)
				$scroll = 'class="scrollTableContainer"';
?>
			<div <?=$scroll?>>
				<table width="100%" cellpadding="3" cellspacing="1" class="list sortable" id="tableid<?=$counter?>">
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
	}
} else {
	startWindow("Getting Started", 'padding: 3px;');
?>
	<table cellpadding="3">
	<tr><td>
		<?=_L("The Contact Manager allows you to customize your message delivery preferences and enables you to review past messages.")?>
	</td></tr>

	<tr><td class="bottomBorder">&nbsp;</td></tr>

	<tr><td>
		<? echo button(_L("Click here to begin"), NULL, "phoneactivation0.php"); ?>
	</td></tr>
	</table>

<?
	endWindow();
}
include_once("navbottom.inc.php");
?>
