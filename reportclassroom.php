<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
require_once("inc/common.inc.php");
require_once("inc/securityhelper.inc.php");
require_once("inc/table.inc.php");
require_once("inc/html.inc.php");
require_once("inc/utils.inc.php");
require_once("obj/Validator.obj.php");
require_once("obj/Form.obj.php");
require_once("obj/FormItem.obj.php");
require_once("inc/date.inc.php");
require_once("obj/FieldMap.obj.php");
require_once("inc/formatters.inc.php");
require_once("obj/Person.obj.php");


////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////

if(!(getSystemSetting('_hastargetedmessage', false) && $USER->authorize('viewsystemreports'))){
	redirect('unauthorized.php');
}

////////////////////////////////////////////////////////////////////////////////
// Action/Request Processing
////////////////////////////////////////////////////////////////////////////////

if(isset($_GET['pid'])){
	$_SESSION['report']['options']['pid'] = $_GET['pid'];
	redirect();
}

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////
$contentfile = "messagedata/en/targetedmessage.php";
if(file_exists($contentfile))
	include_once($contentfile);
	
$options = $_SESSION['report']['options'];
$datesql = "";


$data = array();
$titles = array();
$formatters = array();
$customxt = array();
$displaydate = '';
$startdate = '';
$enddate = '';

// ====== Note: Same date SQL is used for person and org report below ================
if(isset($options['reldate']) && $options['reldate'] != ""){
	list($startdate, $enddate) = getStartEndDate($options['reldate'], $options);
	$startdate = date("Y-m-d", $startdate);
	$enddate = date("Y-m-d", $enddate);
	$datesql = " AND (a.date >= '$startdate' and a.date < date_add('$enddate',interval 1 day) )";
} else {
	$datesql = " AND Date(a.occurence) = CURDATE()";
	$enddate = $startdate = date("Y-m-d", time());
}
// ===================================================================================

if($options['classroomreporttype'] == 'person') {
	$pid = $_SESSION['report']['options']['pid'];
	$TITLE = _L('Classroom Comment Report: %s (From: %s To: %s)',Person::getFullName($pid),$startdate,$enddate);


	$result = Query("SELECT tm.id,tm.messagekey,e.notes,a.date,a.time,CONCAT(u.firstname,' ',u.lastname),s.skey,tm.overridemessagegroupid
					FROM person p
					LEFT JOIN personassociation pa ON ( p.id = pa.personid )
					LEFT JOIN event e ON ( pa.eventid = e.id )
					LEFT JOIN targetedmessage tm ON ( e.targetedmessageid = tm.id )
					LEFT JOIN alert a ON ( e.id = a.eventid )
					LEFT JOIN user u ON ( e.userid = u.id)
					LEFT JOIN section s ON (e.sectionid = s.id)
					WHERE pa.type = 'event' and p.id = ?
					$datesql", false, array($pid));
	$overrideids = array();
	while($row = DBGetRow($result)){
		$data[] = $row;
		if($row[7]) {
			$overrideids[] = $row[7];
		}
	}

	if(!empty($overrideids)) {
		$customtxt = QuickQueryList("select t.id, p.txt from targetedmessage t, message m, messagepart p
											where t.deleted = 0
											and t.overridemessagegroupid = m.messagegroupid
											and m.languagecode = 'en'
											and	p.messageid = m.id
											and p.sequence = 0
											and t.overridemessagegroupid in (" . implode(",",$overrideids). ")",true);
	}

	$titles = array("1" => _L("Classroom Comment"),
				"3" => _L("Date Sent"),
				"5" => _L("User"),
				"6" => _L("Section"));

	$formatters = array("1" => "frm_classroommessage",
					"3" => "fmt_null",
					"5" => "fmt_null",
					"6" => "fmt_null");
} else if($options['classroomreporttype'] == 'organization') {
	$TITLE = _L('Classroom Comment Report (From: %s To: %s)',$startdate,$enddate);
	$data = QuickQueryMultiRow("SELECT o.orgkey, count(a.id)
					FROM alert a
					LEFT JOIN event e ON ( a.eventid = e.id )
					LEFT JOIN organization o ON ( e.organizationid = o.id )
					WHERE 1
					$datesql
					group by o.id
					");
	$titles = array("0" => _L("Current Org"),
					"1" => _L("Comments Sent"));
	$formatters = array("0" => "fmt_null",
					"1" => "fmt_null");
}




function frm_classroommessage($row, $index) {
		global $messagedatacache,$customtxt;
		if(isset($row[7]) && isset($customtxt[$row[0]])) {
			$title = $customtxt[$row[0]];
		} else
		if(isset($messagedatacache["en"]) && isset($messagedatacache["en"][$row[$index]])) {
			$title = $messagedatacache["en"][$row[$index]];
		} else {
			$title = ""; // Could not find message for this message key.
		}


	return escapehtml($title);
}


////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
$PAGE = "reports:reports";
//$TITLE = _L('Classroom Comment Report);  // This is set above

include_once("nav.inc.php");
$fallbackUrl = "reportclassroomsearch.php";
if(isset($_SESSION['report']['singleperson']))
	$back = icon_button(_L('Back'), 'fugue/arrow_180', null, 'reportclassroomsearch.php');
else
	$back = icon_button(_L('Back'), 'fugue/arrow_180', "location.href='" . (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $fallbackUrl) . "'");
$donebutton = icon_button(_L('Done'), 'tick', null, 'reports.php');

startWindow("Search Results");

if(count($data) > 0){
?>
	<br />
<?= buttons($back,$donebutton);?>
	<table class="list" cellpadding="3" cellspacing="1" >
		<?= showTable($data, $titles, $formatters); ?>
	</table>
<?
	buttons($back,$donebutton);
} else {
?>
	<div>Your search did not find any matching results. Click the back button and try modifying your search settings.<div>
<?
	buttons($back,$donebutton);
}
endWindow();



include_once("navbottom.inc.php");
?>
