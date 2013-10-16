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

require_once('obj/Headers.obj.php');
require_once('obj/ReportGenerator.obj.php');
require_once('obj/ReportClassroomMessaging.obj.php');
require_once('obj/Formatters.obj.php');
require_once('inc/formatters.inc.php');
require_once('messagedata/en/targetedmessage.php');

//require_once("inc/rulesutils.inc.php");

////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////

if(!(getSystemSetting('_hastargetedmessage', false) && $USER->authorize('viewsystemreports'))){
	redirect('unauthorized.php');
}

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

$options = $_SESSION['report']['options'];

////////////////////////////////////////////////////////////////////////////////
//// CSV Report Handling
//////////////////////////////////////////////////////////////////////////////////

if (isset($_GET['download'])) {
	$headers = new Headers();
	$headers->send_csv_headers('classroom_messaging_report.csv');

	$rcm = new ReportClassroomMessaging($options);
	$rcm->set_format('csv');
	$rcm->generate();
	exit;
}


$personsql = "";
$emailtable = "";
$emailtableGuardianauto = "";
$emailsql = "";
$datesql = "";

if(isset($options['personid']) && $options['personid'] != "")
	$personsql = " AND p.pkey = '" . DBSafe($options['personid']) . "'";
else if(isset($options['email']) && $options['email'] != "") {
	$emailtable = " LEFT JOIN email e ON ( e.personid = p.id )";
	$emailtableGuardianauto = " LEFT JOIN email e ON ( e.personid = pg.guardianpersonid )";
	$emailsql = "AND e.email = '" . DBSafe($options['email']) . "'";
}

if(isset($options['reldate']) && $options['reldate'] != ""){
	list($startdate, $enddate) = getStartEndDate($options['reldate'], $options);
	$startdate = date("Y-m-d", $startdate);
	$enddate = date("Y-m-d", $enddate);
	$datesql = " AND (a.date >= '$startdate' and a.date < date_add('$enddate',interval 1 day) )";
} else {
	$datesql = " AND Date(e.occurence) = CURDATE()";
}

$query = "(SELECT p.pkey,
			p." . FieldMap::getFirstNameField() . " as firstname,
			p." . FieldMap::getLastNameField() . " as lastname,
			p.id
				FROM person p
				LEFT JOIN personassociation pa ON ( p.id = pa.personid )
				LEFT JOIN alert a ON ( a.eventid = pa.eventid )
				$emailtable
				where 1
				$personsql
				$emailsql
				$datesql
				group by p.pkey)
			UNION
				(SELECT p.pkey,
				p." . FieldMap::getFirstNameField() . " as firstname,
				p." . FieldMap::getLastNameField() . " as lastname,
				p.id
				FROM personguardian pg
				left join person g on (g.id = pg.guardianpersonid)
				left join person p on (p.id = pg.personid)
				LEFT JOIN personassociation pa ON ( p.id = pa.personid )
				LEFT JOIN alert a ON ( a.eventid = pa.eventid )
				$emailtableGuardianauto
				where not p.deleted and not g.deleted and g.type = 'guardianauto'
				and not exists (select * from personguardian pg2 where pg2.importid is null and pg2.personid = pg.personid)
				$personsql
				$emailsql
				$datesql
				group by p.pkey)
			UNION
				(SELECT p.pkey,
				p." . FieldMap::getFirstNameField() . " as firstname,
				p." . FieldMap::getLastNameField() . " as lastname,
				p.id
				FROM personguardian pg
				left join person g on (g.id = pg.guardianpersonid)
				left join person p on (p.id = pg.personid)
				LEFT JOIN personassociation pa ON ( p.id = pa.personid )
				LEFT JOIN alert a ON ( a.eventid = pa.eventid )
				$emailtableGuardianauto
				where not p.deleted and not g.deleted and g.type = 'guardiancm'
				$personsql
				$emailsql
				$datesql
				group by p.pkey)
				";
$result = Query($query);


$data = array();
while($row = DBGetRow($result)){
	$data[] = $row;
}



$result = Query($query);
$data = array();
while($row = DBGetRow($result)){
	$data[] = $row;
}

// if only one person(one row in data), redirect to person with person id.
if(count($data) == 1){
	$_SESSION['report']['singleperson'] = 1;
	redirect("reportclassroom.php?pid=" . $data[0][3]);
}
unset($_SESSION['report']['singleperson']);


$titles = array("0" => "ID#",
				"1" => "First Name",
				"2" => "Last Name");

$formatters = array("0" => "drilldownOnId",
					"1" => "drilldownOnId",
					"2" => "drilldownOnId");


function drilldownOnId($row, $index){
	//index 3 is personid
	$url = "<a href='reportclassroom.php?pid=" . $row[3] . "'/>" . escapehtml($row[$index]) . "</a>";
	return $url;
}

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
$PAGE = "reports:reports";
$TITLE = _L('Classroom Comment Report');

include_once("nav.inc.php");



startWindow("Search Results");

// TODO - something looks wrong with the language below. "Your search returned
// more than one result" would be true if count(data) > 1 was being checked, but
// we're checking >0, so a count of 1 will also result in the same text being shown...

if(count($data) > 0){
?>
	<a href="?download" target="_blank" class="" style="float:right; margin:10px 0;"><img src="img/icons/document_excel_csv.png" style="margin-right:5px;">Open full detail report in Excel</a>
	<div><?= _L('Your search returned more than one result.<br />
	<br>Please select one of the following:') ?></div>
	<br>
	<table class="list" cellpadding="3" cellspacing="1" >
	<?= showTable($data, $titles, $formatters); ?>
</table>
<?
} else {
?>
	<div><?= _L('Your search did not find any matching results.'); ?></div>
<?
}
buttons(icon_button(_L('Modify Search'), 'fugue/arrow_180', null, 'reportclassroomsearch.php'),
					icon_button(_L('Done'), 'tick', null, 'reports.php'));

endWindow();

include_once("navbottom.inc.php");
?>
