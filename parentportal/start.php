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

	instrumentation_add_custom_parameter("numContacts", count($contactList));

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
	$count = 0;
	while ($row = DBGetRow($result)) {
		foreach ($jobids[$row[0]] as $personid) {
			$count++;
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

	instrumentation_add_custom_parameter("numMessages", $count);
	
	$titles = array(
					"2" => _L("Date"),
					"3" => "#" . _L("%s Name", getJobsTitle()),
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
	$actionlinks = array();
	$buttons = array();
	
	if ($messagegroup->hasMessage("phone")) {
		$title = _L("Play");
		$icon = "fugue/control";
		$onclick = "popup('previewmessage.php?jobid=" . $row[1] . "&personid=" . $row[7] . "&type=phone', 400, 500,'preview');";
		
		$buttons[] = icon_button($title,$icon,$onclick);
		$actionlinks[] = action_link($title, $icon,null,$onclick);
	}
	if ($messagegroup->hasMessage("email")) {
		$title = _L("Read Email");
		$icon = "email_open";
		$onclick = "popup('previewmessage.php?jobid=" . $row[1] . "&personid=" . $row[7] . "&type=email', 400, 500,'preview');";
				
		$buttons[] = icon_button($title,$icon,$onclick);
		$actionlinks[] = action_link($title, $icon,null,$onclick);
	}
	if ($messagegroup->hasMessage("sms")) {
		$title = _L("Read Text");
		$icon = "comment";
		$onclick = "popup('previewmessage.php?jobid=" . $row[1] . "&personid=" . $row[7] . "&type=sms', 400, 500,'preview');";
		
		$buttons[] = icon_button($title,$icon,$onclick);
		$actionlinks[] = action_link($title, $icon,null,$onclick);
	}
	
	return "<ul class=\"message_view_actions cf\"><li>" . implode("</li><li>", $buttons) . "</li></ul>
			<div  id=\"j$row[1]p$row[7]\" class=\"message_view_action_button\">" . action_link(_L("View"), "magnifier", null,null) . "</div>
			<div id=\"j$row[1]p$row[7]_content\" class=\"message_view_actionlinks\">" . action_links($actionlinks) . "</div>";
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
			<div id="feeditems"><img src="img/largeicons/information.jpg" /><?=_L("No Messages")?></div>
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
	}
} else {
	startWindow("Getting Started", 'padding: 3px;');
?>
	<table cellpadding="3">
	<tr><td>
		<?=_L("The Contact Manager allows you to customize your message delivery preferences and enables you to review past messages.")?>
	</td></tr>
<?
if (isset($_SESSION['userlogintype']) && ($_SESSION['userlogintype'] == 'powerschool')) {
?>
	<tr><td>
		<?=_L("Your account is not associated with any students. Please contact your school to inform them that your students are missing from the SchoolMessenger product.")?>
	</td></tr>
	<tr><td class="bottomBorder">&nbsp;</td></tr>
<?
} else {
?>
	<tr><td class="bottomBorder">&nbsp;</td></tr>

	<tr><td>
		<? echo button(_L("Click here to begin"), NULL, "phoneactivation0.php"); ?>
	</td></tr>
<?
}
?>
	</table>
						
<?
	endWindow();
}

?>
	<script>
	document.observe('dom:loaded', function() {
		$$('div.message_view_action_button').each(function(item) {
			item.tip = new Tip(item.id, $(item.id + "_content").innerHTML, {
				style: 'protogrey',
				radius: 4,
				border: 4,
				hideOn: false,
				hideAfter: 0.5,
				stem: 'topRight',
				hook: {  target: 'bottomMiddle', tip: 'topRight'  },
				width: 'auto',
				offset: { x: 0, y: -4 }
			});
		});
	});
	</script>

<?
include_once("navbottom.inc.php");
?>
