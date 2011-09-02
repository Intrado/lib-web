<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
require_once("inc/common.inc.php");
require_once("obj/Phone.obj.php");
require_once("inc/html.inc.php");
require_once("inc/table.inc.php");
require_once("inc/utils.inc.php");
require_once("inc/securityhelper.inc.php");
require_once("inc/formatters.inc.php");

require_once("obj/Validator.obj.php");
require_once("obj/Form.obj.php");
require_once("obj/FormItem.obj.php");
////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$USER->authorize('blocknumbers')) {
	redirect('unauthorized.php');
}

////////////////////////////////////////////////////////////////////////////////
// Validators
////////////////////////////////////////////////////////////////////////////////

class ValBlockedPhoneExists extends Validator {
	var $onlyserverside = true;
	function validate ($value, $args,$requiredvalues) {
		$type = $requiredvalues[$args['field']];;
		$phonevalue = Phone::parse($value);
		$exists = QuickQueryList("select type from blockeddestination where  destination = ?", false, false, array($phonevalue));
		if ($exists && ($type == 'both' || $type == 'phone') && in_array('phone', $exists)) {
			return _L('That number is already blocked from receiving phone calls');
		} else if($exists && ($type == 'both' || $type == 'sms') && in_array('sms', $exists)) {
			return _L('That number is already blocked from receiving text messages');
		} 
		return true;
	}
}

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

if (isset($_GET['delete'])) {
	$deleteid = DBSafe($_GET['delete']);
	$blockinfo = QuickQueryRow("select `userid`, `destination`, `type` from blockeddestination where id = ?", true, false, array($deleteid));
	$ownerid = $blockinfo['userid'];
	if ($ACCESS->getValue('callblockingperms') == 'editall' ||
		($ACCESS->getValue('callblockingperms') == 'addonly' && $USER->id == $ownerid)) {
		QuickUpdate("delete from blockeddestination where id=?", false, array($deleteid));
		notice(_L("%1s for %2s are now unblocked.", $blockinfo['type'] == 'phone' ? escapehtml(_L('Phone calls')) : escapehtml(_L('Text messages')), escapehtml(Phone::format($blockinfo['destination']))));
	}
	redirect();
}

// clear display options
if (isset($_GET['clear'])) {
	unset($_SESSION['blockedphoneoptions']);
	redirect();
}

// Default display options
$settings = array(
	"displaycontact" => false,
	"downloadcsv" => false,
	"searchtext" => ""
);

// Check options
if (isset($_SESSION['blockedphoneoptions'])) {
	$settings = json_decode($_SESSION['blockedphoneoptions'],true);
}
if (isset($_GET['displaycontact'])) {
	$settings["displaycontact"] = $_GET['displaycontact'] == 'true'?true:false;
}
if (isset($_REQUEST["searchtext"])) {
	$settings["searchtext"] = trim($_REQUEST['searchtext']);
}
if(isset($_GET['displaycontact']) || isset($_REQUEST["searchtext"])) {
	$_SESSION['blockedphoneoptions'] = json_encode($settings);
	redirect();
}

// if csv download, else html
$settings["downloadcsv"] = isset($_GET['csv'])?true:false;
$_SESSION['blockedphoneoptions'] = json_encode($settings);



$helpstepnum = 1;
$helpsteps = array("TODO");
$formdata = array();

if(getSystemSetting("_hassms", false)){
	$types = array("both" => "Block Calls and Text Messages", "sms" => "Block Text Messages only","phone" => "Block Calls only");
} else {
	$types = array("both" => "Block Calls");
}
$formdata["type"] = array(
	"label" => _L('Block Type'),
	"value" => '',
	"validators" => array(
		array("ValRequired"),
		array("ValInArray", "values" => array_keys($types))
	),
	"control" => array("SelectMenu", "values" => $types),
	"helpstep" => $helpstepnum
);
$formdata["phone"] = array(
	"label" => _L('Phone'),
	"value" => '',
	"validators" => array(
		array("ValRequired"),
		array("ValPhone"),
		array("ValBlockedPhoneExists","field" => "type")
	),
	"control" => array("TextField","size"=>35),
	"requires" => array("type"),
	"helpstep" => $helpstepnum
);

$formdata["reason"] = array(
	"label" => _L('Reason'),
	"value" => '',
	"validators" => array(
		array("ValRequired"),
		array("ValLength","max" => 200)
	),
	"control" => array("TextField","size"=>35),
	"helpstep" => $helpstepnum
);

$buttons = array(submit_button(_L("Add"),"add","add"));
$form = new Form("blockedlist",$formdata,false,$buttons);

////////////////////////////////////////////////////////////////////////////////
// Form Data Handling
////////////////////////////////////////////////////////////////////////////////

//check and handle an ajax request (will exit early)
//or merge in related post data
$form->handleRequest();

$datachange = false;
$errors = false;
//check for form submission
if ($button = $form->getSubmit()) {
	//checks for submit and merges in post data
	$ajax = $form->isAjaxSubmit(); //whether or not this requires an ajax response

	if ($form->checkForDataChange()) {
		$datachange = true;
	} else if (($errors = $form->validate()) === false) {
		//checks all of the items in this form
		$postdata = $form->getData(); //gets assoc array of all values {name:value,...}
		Query("BEGIN");
		$phone = Phone::parse($postdata["phone"]);
		if($postdata["type"] == 'both' || $postdata["type"] == 'phone')
			$result = QuickUpdate("insert into blockeddestination (userid, description, destination, type, createdate)
						values (?, ?, ?, 'phone', now())", false, array($USER->id, $postdata["reason"], $phone));
		if($postdata["type"] == 'both' || $postdata["type"] == 'sms')
			$result = QuickUpdate("insert into blockeddestination (userid, description, destination, type, createdate)
						values (?, ?, ?, 'sms', now())", false, array($USER->id, $postdata["reason"], $phone));
		if ($result) {
			if ($postdata["type"] == 'both') {
				notice(_L("Both phone calls and text messages for %s are now blocked.", escapehtml(Phone::format($phone))));
			} else {
				notice(_L("%1s for %2s are now blocked.", $postdata["type"] == 'phone' ? escapehtml(_L('Phone calls')) : escapehtml(_L('Text messages')), escapehtml(Phone::format($phone))));
			}
		}

		Query("COMMIT");
		if ($ajax)
		$form->sendTo("blockedphone.php");
		else
		redirect("blockedphone.php");
	}
}
	
$formatters = array(
	"10" => "fmt_bntype",
	"4" => 'fmt_phone',
	"7" => 'fmt_blocking_actions',
	"1" => 'fmt_persontip');

$start = 0 + (isset($_GET['pagestart']) ? $_GET['pagestart'] : 0);
$limit = 500;

$titles = array(
	"4" => '#Phone Number',
	"10" => "#Type",
	"5" => '#Reason for Blocking',
	"6" => '#Blocked by',
	"11" => 'Blocked on');

if ($ACCESS->getValue('callblockingperms') == 'editall' || $ACCESS->getValue('callblockingperms') == 'addonly') {
	$titles = $titles + array("7" => 'Actions');
}

$extrasql = "";
$dataqueryargs = array();
if ($settings["searchtext"] != "") {
	$extrasql .= " and (b.destination like ? or b.description like ?";
	$dataqueryargs[] = "%{$settings["searchtext"]}%";
	$dataqueryargs[] = "%{$settings["searchtext"]}%";
	
	/*
	If phone number is formatted like  (831) 342-2322
	then do an extra search of the parsed phone number.
	Since it is formatted, it does not make sense
	to search an extra search unless there are more than 2 digits
	*/
	$phoneseach = Phone::parse($settings["searchtext"]);
	if (strlen($phoneseach) > 2) {
		$extrasql .= " or b.destination like ?";
		$dataqueryargs[] = "%$phoneseach%";
	}
	
	$extrasql .= ")";
}

if ($settings["displaycontact"]) {
	$personfields = array(
		"1" => _L("ID #"),
		"2" => _L("First Name"),
		"3" => _L("Last Name"));
	$titles = $personfields + $titles; // prepend the person fields, keeping the indecies in place
	
	// must have pid index 0, pkey index 1, for fmt_persontip to work
	$dataquery = "(select SQL_CALC_FOUND_ROWS p.id, p.pkey, p.f01, p.f02,
			b.destination, b.description, CONCAT(u.firstname, ' ', u.lastname) as fullname, b.id, b.userid, '" .
			$ACCESS->getValue('callblockingperms') . "' as permission, b.type, b.createdate 
		from blockeddestination b
		join user u on (u.id = b.userid)
		left join phone ph on (ph.phone = b.destination)
		left join person p on (p.id = ph.personid)
		where b.userid = u.id and b.type = 'phone'
		$extrasql
		)
		union
		(select p.id, p.pkey, p.f01, p.f02,
			b.destination, b.description, CONCAT(u.firstname, ' ', u.lastname) as fullname, b.id, b.userid, '" .
			$ACCESS->getValue('callblockingperms') . "' as permission, b.type, b.createdate 
		from blockeddestination b
		join user u on (u.id = b.userid)
		left join sms s on (s.sms = b.destination)
		left join person p on (p.id = s.personid)
		where b.userid = u.id and b.type = 'sms'
		$extrasql
		)
		order by createdate desc, type";
	
	// duplicate search arguments because the union in this query
	$dataqueryargs = array_merge($dataqueryargs,$dataqueryargs);
} else {
	// must stub in dummy contact details for pid and pkey index order, if we do the same query with person details we get duplicate rows when multiple people share a phone
	$dataquery = "select SQL_CALC_FOUND_ROWS 'pid', 'pkey', 'f01', 'f02', b.destination, b.description, CONCAT(u.firstname, ' ', u.lastname) as fullname, b.id, b.userid, '" .
			$ACCESS->getValue('callblockingperms') . "' as permission, b.type, b.createdate
			from blockeddestination b
			join user u on (u.id = b.userid) 
			where b.userid = u.id and b.type in ('phone', 'sms')
			$extrasql
			order by createdate desc, type";
}
//////////////////////////////////
// Functions
/////////////////////////////////

function fmt_blocking_actions($row, $index) {
	global $USER;
	$id = $row[$index];
	$ownerid = $row[$index + 1];
	$perm = $row[$index + 2];
	
	// Only show the delete link in 'addonly' mode for blocked calls created by this user
	if ($perm == 'editall' ||
		($perm == 'addonly' && $USER->id == $ownerid)) {
		return action_links(action_link(_L("Delete"),"cross","blockedphone.php?delete=$id","return confirmDelete();"));
	} else {
		return '';
	}
}

function fmt_bntype ($row, $index) {
	if ($row[$index] == "sms")
		return "Text Messages";
	else
		return "Phone Calls";
}

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
if ($settings["downloadcsv"]) {

	$titles = array(
		"4" => 'Phone Number',
		"10" => 'Type',
		"5" => 'Reason for Blocking',
		"6" => 'Blocked by',
		"11" => 'Blocked on');
	
	if ($settings["displaycontact"]) {
		$personfields = array(
			"1" => _L("ID #"),
			"2" => _L("First Name"),
			"3" => _L("Last Name"));
		$titles = $personfields + $titles; // prepend the person fields, keeping the indecies in place
	}
	
	header("Pragma: private");
	header("Cache-Control: private");
	header("Content-disposition: attachment; filename=blockedphone.csv");
	header("Content-type: application/vnd.ms-excel");

	session_write_close();//WARNING: we don't keep a lock on the session file, any changes to session data are ignored past this point
	
	// write column titles
	echo '"' . implode('","', $titles) . '"';
	echo "\r\n";

	$limit = 1000;
	$start = 0;
	$data = QuickQueryMultiRow($dataquery .  " limit $start, $limit",false,false,$dataqueryargs);
	
	while (count($data) > 0) {
	
		// write out the rows of data
		foreach ($data as $row) {
			// 	[10] type
			if ($row[10]) {
				if ($row[10] == "sms")
					$row[10] = "Text Messages";
				else
					$row[10] = "Phone Calls";
			}
			// [4] destination number
			if ($row[4]) {
				$row[4] = Phone::format($row[4]);
			}
		
			if ($settings["displaycontact"])
				$displaydata = array($row[1], $row[2], $row[3], $row[4], $row[10], $row[5], $row[6], $row[11]);
					else
				$displaydata = array($row[4], $row[10], $row[5], $row[6], $row[11]);
		
			echo '"' . implode('","', $displaydata) . '"';
			echo "\r\n";
		}
		
		$start += $limit;
		$data = QuickQueryMultiRow($dataquery .  " limit $start, $limit",false,false,$dataqueryargs);
	}
	exit();
}

$data = QuickQueryMultiRow($dataquery .  " limit $start, $limit",false,false,$dataqueryargs);
$total = QuickQuery("select FOUND_ROWS()");
	
$PAGE = "system:blocked";
$TITLE = _L('Systemwide Blocked Phone Numbers');

include_once("nav.inc.php");

// Optional Load Custom Form Validators
?>
<script type="text/javascript">
<? Validator::load_validators(array("ValBlockedPhoneExists")); ?>
</script>
<?


if ($ACCESS->getValue('callblockingperms') == 'addonly' || $ACCESS->getValue('callblockingperms') == 'editall') {
	startWindow(_L('Add Phone Number') , 'padding: 3px;', false, true);
	echo $form->render();
	endWindow();
}


startWindow(_L('Blocked Phones') . help('Blocked_SystemwideBlocked'), 'padding: 3px;', false, true);
?>
<table style="margin-top: 5px;" border="0" cellpadding="5" cellspacing="5">
	<tr>
		<td>
		<form id="searchform">
		<input style='float:left;' id='searchtext' size="75" value='<?= $settings["searchtext"] ?>'><?= icon_button(_L("Search"),"magnifier","if($('searchtext').getStyle('color') != 'gray') {window.location='?searchtext=' + encodeURIComponent($('searchtext').value);} else {window.location='?searchtext='}"); ?>
		</form>
		</td>
		<td>
		<input type='checkbox' id='checkboxDisplayContact' onclick='location.href="?displaycontact=" + this.checked + "&pagestart=" + "<? echo($start); ?>"' <?=$settings["displaycontact"] ? 'checked' : ''?>><label for='checkboxDisplayContact'><?=_L('Display Contacts')?></label> 
		</td>
		<td>
		<a href='blockedphone.php?csv'><?= _L("CSV Download"); ?></a>
		</td>
	</tr>
</table>

<?
if(count($data) > 0) {
	showPageMenu($total, $start, $limit);
	echo '<table width="100%" cellpadding="3" cellspacing="1" class="list sortable" id="blocked_numbers">';
	showTable($data, $titles, $formatters);
	echo "\n</table>";
	showPageMenu($total, $start, $limit);
} else {
	echo "<div class='destlabel'><img src='img/largeicons/information.jpg' align='middle'> " . _L("No blocked phones found") . "<div>";
}
endWindow();

?>
<script type="text/javascript">
	var searchBox = $('searchtext');
	blankFieldValue('searchtext', 'Search phone numbers or block reasons');
	searchBox.focus();
	searchBox.blur();
</script>
<?
include_once("navbottom.inc.php");


?>
