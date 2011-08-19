<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
require_once("inc/common.inc.php");
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

class ValBlockedEmailExists extends Validator {
	var $onlyserverside = true;
	function validate ($value, $args) {
		$exists = QuickQuery("select count(id) from blockeddestination where type = 'email' and blockmethod = 'manual' and destination = ?", false, array($value));
		if ($exists) {
			return _L('That email is already blocked');
		}
		return true;
	}
}

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

if (isset($_GET['delete'])) {
	$deleteid = DBSafe($_GET['delete']);
	$blockinfo = QuickQueryRow("select `userid`, `destination` from blockeddestination where id = ?", true, false, array($deleteid));
	$ownerid = $blockinfo['userid'];
	if ($ACCESS->getValue('callblockingperms') == 'editall' ||
		($ACCESS->getValue('callblockingperms') == 'addonly' && $USER->id == $ownerid)) {
		QuickUpdate("delete from blockeddestination where id='$deleteid'");
		notice(_L("Emails for %s are now unblocked.", escapehtml($blockinfo['destination'])));
	}
	redirect();
}

// clear display options
if (isset($_GET['clear'])) {
	unset($_SESSION['blockedemailoptions']);
	redirect();
}

// Default display options
$settings = array(
	"displaycontact" => false,
	"downloadcsv" => false,
	"searchtext" => ""
);

// Check options
if (isset($_SESSION['blockedemailoptions'])) {
	$settings = json_decode($_SESSION['blockedemailoptions'],true);
}

if (isset($_GET['displaycontact'])) {
	$settings["displaycontact"] = $_GET['displaycontact'] == 'true'?true:false;
}
if (isset($_REQUEST["searchtext"])) {
	$settings["searchtext"] = $_REQUEST['searchtext'];
}
if(isset($_GET['displaycontact']) || isset($_REQUEST["searchtext"])) {
	$_SESSION['blockedemailoptions'] = json_encode($settings);
	redirect();
}

// if csv download, else html
$settings["downloadcsv"] = isset($_GET['csv'])?true:false;
$_SESSION['blockedemailoptions'] = json_encode($settings);



$helpstepnum = 1;
$helpsteps = array("TODO");
$formdata = array();


$formdata["email"] = array(
	"label" => _L('Email'),
	"value" => '',
	"validators" => array(
		array("ValRequired"),
		array("ValLength","max" => 200),
		array("ValEmail"),
		array("ValBlockedEmailExists")
	),
	"control" => array("TextField","size"=>35),
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
		$query = "insert into blockeddestination(userid, description, destination, type, createdate, blockmethod)
						values 
						(?, ?, ?, 'email', now(), 'manual')
						 on duplicate key update userid = ?, description = ?, createdate = now(), failattempts = null, blockmethod = 'manual'";
		
		$result = QuickUpdate($query,false, array($USER->id, $postdata["reason"], $postdata["email"], $USER->id, $postdata["reason"]));
		if ($result) {
			notice(_L("Emails for %s are now blocked.", escapehtml($postdata["email"])));
		}
		
		Query("COMMIT");
		if ($ajax)
			$form->sendTo("blockedemail.php");
		else
			redirect("blockedemail.php");
	}
}

$titles = array(
			"4" => '#Email Address',
			"5" => '#Reason for Blocking',
			"6" => '#Blocked by',
			"11" => 'Blocked on'); // date sort does not work with paging

if ($ACCESS->getValue('callblockingperms') == 'editall' || $ACCESS->getValue('callblockingperms') == 'addonly') {
	$titles = $titles + array("7" => 'Actions');
}

$formatters = array(
			"4" => 'fmt_email',
			"6" => 'fmt_blockedby',
			"7" => 'fmt_blocking_actions',
			"1" => 'fmt_persontip');

$start = 0 + (isset($_GET['pagestart']) ? $_GET['pagestart'] : 0);
$limit = 500;
$extrasql = "";
$dataqueryargs = array();
if ($settings["searchtext"] != "") {
	$extrasql .= " and b.destination like ? or b.description like ?";
	$dataqueryargs[] = "%{$settings["searchtext"]}%";
	$dataqueryargs[] = "%{$settings["searchtext"]}%";
}



if ($settings["displaycontact"]) {
	$personfields = array(
		"1" => _L("ID #"),
		"2" => _L("First Name"),
		"3" => _L("Last Name"));
	$titles = $personfields + $titles; // prepend the person fields, keeping the indecies in place
	
	// must have pid index 0, pkey index 1, for fmt_persontip to work
	$dataquery = "select SQL_CALC_FOUND_ROWS p.id, p.pkey, p.f01, p.f02,
			b.destination, b.description, CONCAT(u.firstname, ' ', u.lastname) as fullname, b.id, b.userid, '" .
			$ACCESS->getValue('callblockingperms') . "' as permission, b.type, b.createdate, b.failattempts, b.blockmethod 
		from blockeddestination b
		left join user u on (u.id = b.userid)
		left join email e on (e.email = b.destination)
		left join person p on (p.id = e.personid)
		where b.type = 'email'
		and b.blockmethod in ('autoblock', 'manual')
		$extrasql
		order by createdate desc";
	
			
} else {
	// must stub in dummy contact details for pid and pkey index order, if we do the same query with person details we get duplicate rows when multiple people share a phone
	$dataquery = "select SQL_CALC_FOUND_ROWS 'pid', 'pkey', 'f01', 'f02', b.destination, b.description, CONCAT(u.firstname, ' ', u.lastname) as fullname, b.id, b.userid, '" .
			$ACCESS->getValue('callblockingperms') . "' as permission, b.type, b.createdate, b.failattempts, b.blockmethod
			from blockeddestination b
			left join user u on (b.userid = u.id)
			where b.type = 'email'
			and b.blockmethod in ('autoblock', 'manual')
			$extrasql
			order by createdate desc";
}
//$settings["searchtext"]

///////////////////////////////
// Functions
//////////////////////////////

function fmt_blocking_actions($row, $index) {
	global $USER;
	$id = $row[$index];
	$ownerid = $row[$index + 1];
	$perm = $row[$index + 2];

	// Only show the delete link in 'addonly' mode for blocked calls created by this user
	if ($perm == 'editall' ||
		($perm == 'addonly' && $USER->id == $ownerid)) {
		return action_links(action_link(_L("Delete"),"cross","blockedemail.php?delete=$id","return confirmDelete();"));
	} else {
		return '';
	}
}

function fmt_blockedby($row, $index) {
	if ($row[$index])
		return escapehtml($row[$index]);
	else if ($row[9] == "autoblock")
		return "Auto-Blocked";
	return "Recipient";
}

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
if ($settings["downloadcsv"]) {
	
	$titles = array(
			"4" => 'Email Address',
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
	header("Content-disposition: attachment; filename=blockedemail.csv");
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
			// [6] blocked by
			if ($row[6])
				$row[6] = $row[6];
			else if ($row[9] == "autoblock")
				$row[6] = "Auto-Blocked";
			else
				$row[6] = "Recipient";
		
			if ($settings["displaycontact"])
				$displaydata = array($row[1], $row[2], $row[3], $row[4], $row[5], $row[6], $row[11]);
			else
				$displaydata = array($row[4], $row[5], $row[6], $row[11]);
		
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
$TITLE = _L('Systemwide Blocked Emails');

include_once("nav.inc.php");
// Optional Load Custom Form Validators
?>
<script type="text/javascript">
<? Validator::load_validators(array("ValBlockedEmailExists")); ?>
</script>
<?


if ($ACCESS->getValue('callblockingperms') == 'addonly' || $ACCESS->getValue('callblockingperms') == 'editall') {
	startWindow(_L('Add Email Address') , 'padding: 3px;', false, true);
	echo $form->render();

	endWindow();
}

startWindow(_L('Blocked Emails') , 'padding: 3px;', false, true);
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
			<a href='blockedemail.php?csv'><?= _L("CSV Download"); ?></a>
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
	echo "<div class='destlabel'><img src='img/largeicons/information.jpg' align='middle'> " . _L("No blocked emails found") . "<div>";
}
endWindow();


?>
<script type="text/javascript">
	var searchBox = $('searchtext');
	blankFieldValue('searchtext', 'Search email and/or block reasons');
	searchBox.focus();
	searchBox.blur();
</script>
<?

include_once("navbottom.inc.php");

?>
