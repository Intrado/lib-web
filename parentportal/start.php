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

	$result = Query("select j.id, j.startdate, j.name, j.type, u.firstname, u.lastname, rp.personid
		from job j
		left join jobsetting js on (js.jobid=j.id and js.name='translationexpire')
		left join reportperson rp on (rp.jobid = j.id)
		inner join user u on (u.id = j.userid)
		where
		j.startdate <= curdate() and j.startdate >= date_sub(curdate(),interval 30 day)
		and rp.personid in ('" . $contactListString . "')
		and j.status in ('active', 'complete')
		and j.type = 'notification'
		and (js.value is null or js.value >= curdate())
		and rp.messageid != 0
		group by j.id, rp.personid
		order by j.startdate desc, j.starttime, j.id desc");
	while ($row = DBGetRow($result)) {
			array_splice($row, 0, 0, $contactCount[$row[6]]);
			$allData[$row[7]][] = $row;
			$contactCount[$row[7]]++;
	}
	$titles = array("0" => "##",
					"2" => _L("Date"),
					"3" => "#" . _L("Job Name"),
					"SentBy" => "#" . _L("Sent By"),
					//"4" => "#" . _L("Delivery Type"),
					"Actions" => _L("Actions")
				);

	$formatters = array("2" => "format_date",
						"SentBy" => "sender",
						"4" => "fmt_delivery_type_list",
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
	//index 7 is person id

	$messagetypes = QuickQueryList("select type, type from reportperson where jobid=? and personid=? and messageid != 0", true, false, array($row[1], $row[7]));

	if (isset($messagetypes['phone'])) {
		$buttons[] = button(_L("Play"), "popup('previewmessage.php?jobid=" . $row[1] . "&personid=" . $row[7] . "&type=phone', 400, 500,'preview');",null);
	}
	if (isset($messagetypes['email'])) {
		$buttons[] = button(_L("Read Email"), "popup('previewmessage.php?jobid=" . $row[1] . "&personid=" . $row[7] . "&type=email', 400, 500,'preview');",null);
	}
	if (isset($messagetypes['sms'])) {
		$buttons[] = button(_L("Read SMS"), "popup('previewmessage.php?jobid=" . $row[1] . "&personid=" . $row[7] . "&type=sms', 400, 500,'preview');",null);
	}
	
	return "<table><tr><td>" . implode("</td><td>", $buttons) . "</td></tr></table>";
}

function format_date($row, $index){
	return date("M j, Y", strtotime($row[$index]));
}

function sender($row, $index){
	//index 1 is jobid
	//index 5 is first name
	//index 6 is last name
	//index 7 is personid
	
	$emailmsgid = QuickQuery("select messageid from reportperson where jobid=? and personid=? and type='email'", false, array($row[1], $row[7]));
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
