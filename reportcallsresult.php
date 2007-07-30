<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
require_once("inc/common.inc.php");
include_once("inc/securityhelper.inc.php");
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

////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$USER->authorize('createreport')) {
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
	$url = "<a href='reportcallsperson.php?pid=" . $row[3] . "'/>" . $row[$index] . "</a>";
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

$usersql = $USER->userSQL("rp");

if(isset($options['personid']) && $options['personid'] != "")
	$personsql = " and rp.pkey like '%" . DBSafe($options['personid']) . "%'";
	
if(isset($options['phone']) && $options['phone'] != "")
	$phonesql = " and rp.pkey like '%" . DBSafe($options['phone']) . "%'";

if(isset($options['email']) && $options['email'] != "")
	$emailsql = " and rp.pkey like '%" . DBSafe($options['email']) . "%'";

if(isset($options['rules']) && $options['rules'] != "")
	$rulesql = getRuleSql($options, "rp");

if(isset($options['jobtypes']) && $options['jobtypes'] != "")
	$jobtypes = $options['jobtypes'];

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
			where 1
			$userjoin
			$usersql
			$personsql
			$phonesql
			$emailsql
			$resultsql
			$jobquery
			$rulesql
			group by rp.personid";

$result = Query($query);
$data = array();
while($row = DBGetRow($result)){
	$data[] = $row;
}

$titles = array("0" => "ID#",
				"1" => "First Name",
				"2" => "Last Name");
				
$formatters = array("0" => "drilldownOnId");

$searchrules = array();
if(isset($options['rules']) && $options['rules']){
	$rules = explode("||", $options['rules']);
	foreach($rules as $rule){
		if($rule) {
			$rule = explode(";", $rule);
			$newrule = new Rule();
			$newrule->logical = $rule[0];
			$newrule->op = $rule[1];
			$newrule->fieldnum = $rule[2];
			$newrule->val = $rule[3];
			$fieldname = QuickQuery("select name from fieldmap where fieldnum = '$newrule->fieldnum'");
			$searchrules[] = $fieldname . " : " . preg_replace("{\|}", ", ", $newrule->val);
		}
	}
}

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

	$PAGE = "reports:reports";
	$TITLE = "Contact History";

	
	include_once("nav.inc.php");
	buttons(button('back', 'window.history.go(-1)'));
	
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
			<tr><td>Phone: <?=$options['phone']?></td></tr>
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
				$jobtypenames[] = $jobtypeobj->name;
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
			<tr><td>Results: <?=$resultnames?></td></tr>
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
?>
	<div>Your search returned multiple persons.  Please choose one<div>
	<table>
		
<?
		showTable($data, $titles, $formatters);
?>
	</table>
<?
	endWindow();
	buttons();
	include_once("navbottom.inc.php");
?>
