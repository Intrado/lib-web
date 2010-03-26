<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
require_once("inc/common.inc.php");
require_once("inc/securityhelper.inc.php");
require_once("obj/Job.obj.php");
require_once("obj/JobType.obj.php");
require_once("inc/table.inc.php");
require_once("inc/html.inc.php");
require_once("inc/form.inc.php");
require_once("inc/utils.inc.php");
require_once("inc/reportutils.inc.php");
require_once("inc/formatters.inc.php");
require_once("obj/FieldMap.obj.php");
require_once("obj/ReportGenerator.obj.php");
require_once("obj/ReportInstance.obj.php");
require_once("obj/ReportSubscription.obj.php");
require_once("obj/UserSetting.obj.php");
require_once("inc/date.inc.php");
require_once("obj/CallsReport.obj.php");
require_once("inc/reportgeneratorutils.inc.php");
require_once("obj/Phone.obj.php");
require_once("inc/rulesutils.inc.php");

////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$USER->authorize('createreport') && !$USER->authorize('viewsystemreports')) {
	redirect('unauthorized.php');
}

if (!$USER->authorize('viewsystemreports')) {
	$userjoin = " and rp.userid = $USER->id ";
} else {
	$userjoin = "";
}

////////////////////////////////////////////////////////////////////////////////
// Functions
////////////////////////////////////////////////////////////////////////////////

function drilldownOnId($row, $index){
	//index 3 is personid
	$url = "<a href='reportcallsperson.php?pid=" . $row[3] . "'/>" . escapehtml($row[$index]) . "</a>";
	return $url;

}



////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

$options = $_SESSION['report']['options'];
$personsql = "";
$phonesql = "";
$emailsql = "";
$rulesql = "";
$jobtypes = "";
$resultsql = "";
$jobquery = "";
$jobtypequery = "";

if(isset($options['personid']) && $options['personid'] != "")
	$personsql = " and rp.pkey = '" . DBSafe($options['personid']) . "'";

if(isset($options['phone']) && $options['phone'] != "")
	$phonesql = " and rc.phone like '%" . DBSafe($options['phone']) . "%'";

if(isset($options['email']) && $options['email'] != "")
	$emailsql = " and rc.email = '" . DBSafe($options['email']) . "'";

if(isset($options['rules']) && $options['rules'] != "")
	$rulesql = getRuleSql($options, "rp");

if(isset($options['jobtypes']) && $options['jobtypes'] != ""){
	$jobtypes = $options['jobtypes'];
	$jobtypequery = " and j.jobtypeid in ('". $jobtypes ."') ";
}
if(isset($options['results']) && $options['results'] != "")
	$resultsql = " and rc.result in ('" . $options['results'] . "')";

if(isset($options['reldate']) && $options['reldate'] != ""){
	list($startdate, $enddate) = getStartEndDate($options['reldate'], $options);
	$joblist = implode("','", getJobList($startdate, $enddate, $jobtypes));
	$jobquery = " and rp.jobid in ('" . $joblist . "')";
}

$query = "select rp.pkey,
			rp. " . FieldMap::getFirstNameField() . " as firstname,
			rp. " . FieldMap::getLastNameField() . " as lastname,
			rp.personid,
			max(rc.starttime)
			from reportperson rp
			left join reportcontact rc on (rc.personid = rp.personid and rc.type = rp.type and rc.jobid = rp.jobid)
			left join job j on (j.id = rp.jobid)
			where 1
			$userjoin
			$personsql
			$phonesql
			$emailsql
			$resultsql
			$jobquery
			$jobtypequery
			$rulesql
			group by rp.personid";

$result = Query($query);
$data = array();
while($row = DBGetRow($result)){
	$data[] = $row;
}

// if only one person(one row in data), redirect to person with person id.
if(count($data) == 1){
	$_SESSION['report']['singleperson'] = 1;
	redirect("reportcallsperson.php?pid=" . $data[0][3]);
}
unset($_SESSION['report']['singleperson']);


$titles = array("0" => "ID#",
				"1" => "First Name",
				"2" => "Last Name");

$formatters = array("0" => "drilldownOnId",
					"1" => "drilldownOnId",
					"2" => "drilldownOnId");

$searchrules = array();
if(isset($options['rules']) && $options['rules']){
	$searchrules = displayRules($options['rules']);
}

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

	$PAGE = "reports:reports";
	$TITLE = "Contact History";


	include_once("nav.inc.php");
	buttons(button('Back', null, 'reportcallssearch.php'));

	startWindow("Search Parameters");
?>
	<table>
<?
		if(isset($options['personid']) && $options['personid'] != ""){
?>
			<tr><td>ID#: <?=$options['personid']?></td></tr>
<?
		}
		if(isset($options['phone']) && $options['phone'] != ""){
?>
			<tr><td>Phone: <?=Phone::format($options['phone'])?></td></tr>
<?
		}
		if(isset($options['email']) && $options['email'] != ""){
?>
			<tr><td>Email: <?=$options['email']?></td></tr>
<?
		}
		if(isset($options['reldate'])){
?>
			<tr><td>From: <?=date("m/d/Y", $startdate)?> To: <?=date("m/d/Y", $enddate)?></td></tr>
<?
		}
		if(isset($options['jobtypes']) && $options['jobtypes'] != ""){
			$jobtypes = explode("','", $options['jobtypes']);
			$jobtypenames = array();
			foreach($jobtypes as $jobtype){
				$jobtypeobj = new JobType($jobtype);
				$jobtypenames[] = escapehtml($jobtypeobj->name);
			}
			$jobtypenames = implode(", ",$jobtypenames);
?>
			<tr><td>Job Type: <?=$jobtypenames?></td></tr>
<?
		}
		if(isset($options['results']) && $options['results'] != ""){
			$results = explode("','",$options['results']);
			$resultnames = array();
			foreach($results as $result)
				$resultnames[] = fmt_result(array($result), 0);
			$resultnames = implode(", ", $resultnames);
?>
			<tr><td>Result: <?=$resultnames?></td></tr>
<?
		}
		foreach($searchrules as $rule){
			?><tr><td><?=$rule?></td></tr><?
		}
?>
	</table>
<?
	endWindow();
?>
	<br>
<?
	startWindow("Search Results");
		if(count($data) > 0){
?>
			<div>Your search returned more than one result.
			<br>Please select one of the following:<div>
<?
		} else {
?>
			<div>Your search did not find any matching results. Click the back button and try modifying your search settings.<div>
<?
		}
?>
	<br>
	<table class="list" cellpadding="3" cellspacing="1" >

<?
		showTable($data, $titles, $formatters);
?>
	</table>
<?
	endWindow();
	buttons();
	include_once("navbottom.inc.php");
?>
