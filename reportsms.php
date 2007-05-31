<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
require_once("inc/common.inc.php");
include_once("inc/securityhelper.inc.php");
require_once("obj/Job.obj.php");
require_once("obj/Phone.obj.php");
require_once("obj/SmsJob.obj.php");
require_once("obj/FieldMap.obj.php");
require_once("inc/html.inc.php");
require_once("inc/table.inc.php");
require_once("inc/utils.inc.php");
require_once("inc/formatters.inc.php");

////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$USER->authorize('createreport') && !$USER->authorize('viewsystemreports')) {
	redirect('unauthorized.php');
}

if (!isset($_GET['smsjobid'])) {
	if (isset($_SESSION['smsjobid']))
		$smsjobid = $_SESSION['smsjobid'];
	else
		$smsjobid = false;
} else {
	$smsjobid = $_GET['smsjobid'] + 0;
	//check userowns or customerowns and viewsystemreports
	if ($smsjobid != 0 && !userOwns("smsjob",$smsjobid) && !($USER->authorize('viewsystemreports') && customerOwns("smsjob",$smsjobid))) {
		redirect('unauthorized.php');
	} else if ($smsjobid != 0) {
		$_SESSION['smsjobid'] = $smsjobid;
//		$_SESSION['reportsmsreferer'] = $_SERVER['HTTP_REFERER'];
		redirect();
	} else {
		$smsjobid = false;
	}
}
////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

if ($smsjobid)
	$smsjob = new SmsJob($smsjobid);

$start = 0 + (isset($_GET['pagestart']) ? $_GET['pagestart'] : 0);
$limit = 100;


function fmt_smsstatus ($row,$index) {
	global $smsjob;
	return ucfirst($smsjob->status);
}

function fmt_smsdate ($row,$index) {
	global $smsjob;
	return date("M j, g:i a",strtotime($smsjob->sentdate));
}

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$PAGE = "reports:sms";
$TITLE = "SMS Job Report" . ($smsjobid ? " - " . $smsjob->name : "");

include_once("nav.inc.php");
//TODO buttons for notification log: download csv, view call details
if ($smsjobid)
	echo buttons(button('refresh', 'window.location.reload()'), button('done', 'window.history.go(-1)'));
else
	buttons();


//--------------- Select window ---------------
startWindow("Select", NULL, false);
?>
<table border="0" cellpadding="3" cellspacing="0" width="100%">
<tr>
	<th align="right" class="windowRowHeader" valign="top" style="padding-top: 6px;">SMS Job:</th>
	<td><select name="jobid" id="jobid" onchange="location.href='?smsjobid=' + this.value">
			<option value='0'>-- Select a Job --</option>
<?
$smsjobs = DBFindMany("SmsJob","from smsjob where userid=$USER->id and deleted = 0 order by sentdate desc");



foreach ($smsjobs as $s) {
echo '<option value="' . $s->id . '">' . htmlentities($s->name) . '</option>';
}
?>
	</td>
</tr>
</table>

<?
endWindow();

echo "<br>";

if ($smsjobid) {

	//--------------- Summary ---------------
	startWindow("SMS Job", NULL, false);
?>

<table border="0" cellpadding="3" cellspacing="0" width="100%">
<tr>
	<th align="right" class="windowRowHeader" valign="top" style="padding-top: 6px;">Name:</th>
	<td><?= htmlentities($smsjob->name) ?></td>
</tr>
<tr>
	<th align="right" class="windowRowHeader" valign="top" style="padding-top: 6px;">Description:</th>
	<td><?= htmlentities($smsjob->description) ?></td>
</tr>
<tr>
	<th align="right" class="windowRowHeader" valign="top" style="padding-top: 6px;">Text:</th>
	<td><div style="width: 250px;"><?= htmlentities($smsjob->txt) ?></div></td>
</tr>
</table>

<?

	endWindow();
	echo "<br>";
	startWindow("SMS Report", NULL, false);


	$fnf = FieldMap::getFirstNameField();
	$lnf = FieldMap::getLastNameField();
	$langf = FieldMap::getLanguageField();


	$query = "select SQL_CALC_FOUND_ROWS p.pkey, pd.$fnf, pd.$lnf, pd.$langf, s.phone from smsmsg s left join person p on (p.id = s.personid) left join persondata pd on (pd.personid = s.personid) where s.smsjobid='$smsjobid' order by pd.f02, pd.f01 limit $start, $limit";
	$data = array();
	$res = Query($query);
	while ($row = DBGetRow($res)) {
		$data[] = $row;
	}

	$total = QuickQuery("select FOUND_ROWS()");

	$titles = array(0 => "ID#",
					1 => "First Name",
					2 => "Last Name",
				/*	3 => "Language", */
					4 => "Phone",
					5 => "Status",
					6 => "Date");
	$formatters = array(4 => "fmt_phone",
						5 => "fmt_smsstatus",
						6 => "fmt_smsdate");


	showPageMenu($total, $start, $limit);
?>
	<table width="100%" cellpadding="3" cellspacing="1" class="list">
<?
	showTable($data,$titles,$formatters);
?>
	</table>
<?
	showPageMenu($total, $start, $limit);



	endWindow();

}

echo buttons();

include_once("navbottom.inc.php");
?>