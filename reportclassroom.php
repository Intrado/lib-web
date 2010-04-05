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

	$person = new Person($pid);


	$TITLE = _L('Classroom Comment Report: %s ID: %s (From: %s To: %s)',escapehtml(Person::getFullName($person)),escapehtml($person->pkey),$startdate,$enddate);

	$result = Query("SELECT s.skey,tm.id,tm.overridemessagegroupid,tm.messagekey,a.date,a.time,CONCAT(u.firstname,' ',u.lastname),e.notes
					FROM person p
					INNER JOIN personassociation pa ON ( p.id = pa.personid )
					INNER JOIN event e ON ( pa.eventid = e.id )
					INNER JOIN targetedmessage tm ON ( e.targetedmessageid = tm.id )
					INNER JOIN alert a ON ( e.id = a.eventid )
					INNER JOIN user u ON ( e.userid = u.id )
					INNER JOIN section s ON ( e.sectionid = s.id )
					WHERE pa.type = 'event' and p.id = ?
					$datesql", false, array($pid));
	$overrideids = array();
	while($row = DBGetRow($result)){
		$data[] = $row;
		if($row[2]) {
			$overrideids[] = $row[2];
		}
	}


	$titles = array("3" => _L("Classroom Comment"),
				"4" => _L("Date Sent"),
				"6" => _L("User"),
				"0" => _L("Section"));

	$formatters = array("3" => "frm_classroommessage",
					"4" => "fmt_null",
					"5" => "fmt_null",
					"0" => "fmt_null");
} else if($options['classroomreporttype'] == 'organization') {
	$TITLE = _L('Classroom Comment Report (From: %s To: %s)',$startdate,$enddate);
	$result = Query("SELECT o.orgkey,tm.id,tm.overridemessagegroupid, tm.messagekey, count( tm.messagekey )
						FROM organization o
						INNER JOIN event e ON ( e.organizationid = o.id )
						INNER JOIN targetedmessage tm ON ( e.targetedmessageid = tm.id )
						INNER JOIN alert a ON ( a.eventid = e.id )
						WHERE 1
						$datesql
						GROUP BY tm.messagekey
						");
	$overrideids = array();
	$org = false;
	$data = array();

	while($row = DBGetRow($result)){
		if($row[0] != $org) {
			$org = $row[0];
			$data[$org] = array();
		}
		$data[$org][] = $row;
		if($row[2]) {
			$overrideids[] = $row[2];
		}
	}
	$titles = array("3" => _L("Comments"),
					"4" => _L("Total Sent"));
	$formatters = array("3" => "frm_classroommessage",
					"4" => "fmt_null");
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





function frm_classroommessage($row, $index) {
		global $messagedatacache,$customtxt;
		if(isset($row[2]) && isset($customtxt[$row[1]])) {
			$title = $customtxt[$row[1]];
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
		<?
		if($options['classroomreporttype'] == 'person') {
			echo '<table class="list" cellpadding="3" cellspacing="1" >';
			showTable($data, $titles, $formatters);
			echo '</table>';
		} else if($options['classroomreporttype'] == 'organization') {
			foreach($data as $org => $comments) {
				echo "<h3>$org</h3>";
				echo '<table class="list" cellpadding="3" cellspacing="1" >';
				showTable($comments, $titles, $formatters);
				echo '</table>';
			}
		}
		?>

<?
	buttons($back,$donebutton);
} else {
?>
	<div><?= _L('Your search did not find any matching results.'); ?></div>
<?
	buttons($back,$donebutton);
}
endWindow();

include_once("navbottom.inc.php");
?>
