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

$options = $_SESSION['report']['options'];

// ====== Note: Same date SQL is used for person and org report below ================
$datesql = $startdate = $enddate = '';
if(isset($options['reldate']) && $options['reldate'] != ""){
	list($startdate, $enddate) = getStartEndDate($options['reldate'], $options);
	$startdate = date("Y-m-d", $startdate);
	$enddate = date("Y-m-d", $enddate);
	$datesql = " AND (a.date >= '$startdate' and a.date < date_add('$enddate',interval 1 day) )";
} else {
	$datesql = " AND Date(e.occurence) = CURDATE()";
	$enddate = $startdate = date("Y-m-d", time());
}
// ===================================================================================

if (isset($options['organizationid']) && count($options['organizationid'])) {
	$orglist = join("','", $options['organizationid']);
	$orgsql = "AND o.id in ('{$orglist}')";
}
else $orgsql = '';



////////////////////////////////////////////////////////////////////////////////
// CSV Report Handling
////////////////////////////////////////////////////////////////////////////////

if (isset($_GET['download'])) {

        $query = Query("
		select
			a.id,
			rc.jobid,
			u.login,
			concat(u.firstname, ' ', u.lastname) as teacher,
			o.orgkey,
			s.skey, 
			if(rp.pkey is null, p.pkey, rp.pkey) as studentid, 
			concat(if(rp.f01 is null, p.f01, rp.f01), ' ', if(rp.f02 is null, p.f02, rp.f02)) as student, 
			tg.messagekey,
			e.notes,
			e.occurence, 
			from_unixtime(if(rc.type = 'email', (select timestamp from reportemaildelivery where jobid = rc.jobid and personid = rc.personid and sequence = rc.sequence order by timestamp limit 1), rc.starttime/1000)) as lastattempt,
			rc.type,
			if(rc.type = 'email', rc.email, rc.phone) as destination, 
			if(rc.type = 'email', (select statuscode from reportemaildelivery where jobid = rc.jobid and personid = rc.personid and sequence = rc.sequence order by timestamp limit 1), rc.result) as result, 
			rp.status
		from alert a
			inner join event e on (e.id = a.eventid)
			inner join organization o on (o.id = e.organizationid)
			inner join section s on (s.id = e.sectionid)
			inner join user u on (u.id = e.userid)
			inner join person p on (p.id = a.personid)
			inner join targetedmessage tg on (tg.id = e.targetedmessageid)
			left join job j on (j.startdate = a.date and j.type = 'alert')
			left join reportperson rp on (rp.jobid = j.id and rp.type in ('email', 'phone') and rp.personid = a.personid)
			left join reportcontact rc on (rc.jobid = rp.jobid and rc.type = rp.type and rc.personid = rp.personid)
		where
			1
			{$orgsql}
			{$datesql};
	");

	// set header
	header("Pragma: private");
	header("Cache-Control: private");
	header("Content-disposition: attachment; filename=classroom_messaging_report.csv");
	header("Content-type: application/vnd.ms-excel");

	// echo out the data
	echo '"alert id", "job id", "login", "teacher", "school", "section", "student id", "student", "messagekey", "notes", "occurence", "lastattempt", "type", "destination", "result", "status"' . "\n";

	// For every row in the result data
	while ($row = $query->fetch(PDO::FETCH_ASSOC)) {

		// Translate some of the raw values into something human readable
		switch ($row['type']) {
			case 'email':
				// TODO - translate rp.status for email status significance
				break;

			case 'phone':
				// TODO - translate rc.result for phone result significance
				break;
		}
		// TODO - translate messagekey


		// Then spit the row out to STDOUT as CSV data
		echo array_to_csv($row) . "\n";
	}
	exit;
}

$contentfile = "messagedata/en/targetedmessage.php";
if(file_exists($contentfile))
	include_once($contentfile);

$data = array();
$titles = array();
$formatters = array();
$customxt = array();

if($options['classroomreporttype'] == 'person') {
	$pid = $_SESSION['report']['options']['pid'];

	$person = new Person($pid);


	$TITLE = _L('Classroom Comment Report: %s ID: %s (From: %s To: %s)',escapehtml(Person::getFullName($person)),escapehtml($person->pkey),$startdate,$enddate);

	$result = Query("
		SELECT
			s.skey,tm.id,tm.overridemessagegroupid,tm.messagekey,a.date,a.time,CONCAT(u.firstname,' ',u.lastname),e.notes
		FROM
			person p
			INNER JOIN personassociation pa ON ( p.id = pa.personid )
			INNER JOIN event e ON ( pa.eventid = e.id )
			INNER JOIN targetedmessage tm ON ( e.targetedmessageid = tm.id )
			INNER JOIN alert a ON ( e.id = a.eventid )
			INNER JOIN user u ON ( e.userid = u.id )
			INNER JOIN section s ON ( e.sectionid = s.id )
		WHERE
			pa.type = 'event' and p.id = ?
			$datesql
	", false, array($pid));
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
	//$orgsql = $options['organizationid'] > 0 ? " AND o.id = ". $options['organizationid'] ." " : "";
	$result = Query("
		SELECT
			o.orgkey,tm.id,tm.overridemessagegroupid, tm.messagekey, count( tm.messagekey )
		FROM
			organization o
			INNER JOIN event e ON ( e.organizationid = o.id )
			INNER JOIN targetedmessage tm ON ( e.targetedmessageid = tm.id )
			INNER JOIN alert a ON ( a.eventid = e.id )
		WHERE
			1
			$orgsql
			$datesql
		GROUP BY tm.messagekey
	");

	$overrideids = array();
	$data = array();

	while($row = DBGetRow($result)){
		$orgkey = $row[0];
		if (!isset($data[$orgkey])) {
			$data[$orgkey] = array();
		}
		$data[$orgkey][] = $row;
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
	$customtxt = QuickQueryList("
		select
			t.id, p.txt
		from
			targetedmessage t, message m, messagepart p
		where
			t.deleted = 0
			and t.overridemessagegroupid = m.messagegroupid
			and m.languagecode = 'en'
			and	p.messageid = m.id
			and p.sequence = 0
			and t.overridemessagegroupid in (" . implode(",",$overrideids). ")
	",true);
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
<?= buttons($back,$donebutton);?>
		<?
		echo '<a href="?download" target="_blank" class="" style="float:right; margin:10px 0;"><img src="img/icons/document_excel_csv.png" style="margin-right:5px;">Open full detail report in Excel</a>';
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
