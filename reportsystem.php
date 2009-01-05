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
require_once("inc/date.inc.php");
require_once("inc/reportgeneratorutils.inc.php");


////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$USER->authorize('viewusagestats')) {
	redirect('unauthorized.php');
}

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

if(isset($_GET['clear'])){
	unset($_SESSION['usagestats']);
	redirect();
}

$groupby = isset($_SESSION['usagestats']['groupby']) ? $_SESSION['usagestats']['groupby'] : FieldMap::getSchoolField(); //defaults to school field
if(!$groupby)
	$groupby = ""; //but if school is not used, default to blank
$fields = DBFindMany("FieldMap", "from fieldmap where options like '%multisearch%' and (fieldnum like 'f%' or fieldnum like 'g%') order by fieldnum");

$showusers = isset($_SESSION['usagestats']['showusers']) ? $_SESSION['usagestats']['showusers'] : "0";
$type = isset($_SESSION['usagestats']['type']) ? $_SESSION['usagestats']['type'] : "phone";
$reldate = isset($_SESSION['usagestats']['reldate']) ? $_SESSION['usagestats']['reldate'] : "monthtodate";
$lastxdays = isset($_SESSION['usagestats']['lastxdays']) ? $_SESSION['usagestats']['lastxdays'] : "0";
$startdate = isset($_SESSION['usagestats']['startdate']) ? $_SESSION['usagestats']['startdate'] : "";
$enddate = isset($_SESSION['usagestats']['enddate']) ? $_SESSION['usagestats']['enddate'] : "";
$f = "system";
$s = "report";
$reload = 0;

if(CheckFormSubmit($f,$s))
{
	//check to see if formdata is valid
	if(CheckFormInvalid($f))
	{
		print '<div class="warning">Form was edited in another window, reloading data.</div>';
		$reload = 1;
	}
	else
	{
		MergeSectionFormData($f, $s);
		//do check
		$startdate = TrimFormData($f, $s, "startdate");
		$enddate = TrimFormData($f, $s, "enddate");

		if(GetFormData($f, $s, "relativedate") != "xdays") {
			PutFormData($f, $s, 'xdays',"", "number");
		} else {
			TrimFormData($f, $s,'xdays');
		}

		if( CheckFormSection($f, $s) ) {
			error('There was a problem trying to save your changes', 'Please verify that all required field information has been entered properly');
		} else if((GetFormData($f, $s, "relativedate") == "daterange") && !strtotime($startdate)){
			error('Beginning Date is not in a valid format.  February 1, 2007 would be 02/01/07');
		} else if((GetFormData($f, $s, "relativedate") == "daterange") && !strtotime($enddate)){
			error('Ending Date is not in a valid format.  February 1, 2007 would be 02/01/07');
		} else if((GetFormData($f, $s, "relativedate") == "xdays") && GetFormData($f, $s, "xdays") == ""){
			error('You must enter a number for X days');
		} else {
			$_SESSION['usagestats']['groupby'] = DBSafe(GetFormData($f, $s, "groupby"));
			$_SESSION['usagestats']['reldate'] = GetFormData($f, $s, "relativedate");
			$_SESSION['usagestats']['type'] = DBSafe(GetFormData($f, $s, "type"));
			$_SESSION['usagestats']['lastxdays'] = GetFormData($f, $s, "xdays");
			$_SESSION['usagestats']['startdate'] = $startdate;
			$_SESSION['usagestats']['enddate'] = $enddate;
			$_SESSION['usagestats']['showusers'] = GetFormData($f, $s, "showusers");
			redirect();
		}
	}
} else {
	$reload=1;
}

if($reload){
	ClearFormData($f);
	PutFormData($f, $s, "groupby", $groupby);
	PutFormData($f, $s, "showusers", $showusers, "bool", 0, 1);

	PutFormData($f, $s, "relativedate", $reldate);
	PutFormData($f, $s, 'xdays', $lastxdays, "number");
	PutFormData($f, $s, "startdate", $startdate, "text");
	PutFormData($f, $s, "enddate", $enddate, "text");
	PutFormData($f, $s, "type", $type);
}




////////////////////////////////////////////////////////////////////////////////
// Data Calculation
////////////////////////////////////////////////////////////////////////////////
	$joblistquery = "";
	$jobtypelist = QuickQueryList("select id, name from jobtype where not deleted", true);

	$paramdata = array("lastxdays" =>  GetFormData($f, $s, "xdays"), "startdate" => $startdate, "enddate" => $enddate);

	list($startdate, $enddate) = getStartEndDate($reldate, $paramdata);
	
	$joblist = getJobList($startdate, $enddate, implode("','", array_keys($jobtypelist)), "", $type);
	$joblistquery = " and rp.jobid in ('" . implode("','", $joblist) . "') ";
	$jobidtypelist = QuickQueryList("select id, jobtypeid from job j where j.id in ('" . implode("','",$joblist) ."') ", true);
	$groupbyquery = "";
	$groupbyorder = "";
	$rgroupdata = "";
	if($groupby != ""){
		if (strpos($groupby, "g") === 0) {
			$groupbyquery = "rgd.value"; // reportgroupdata
			$rgroupdata = "join reportgroupdata rgd on (rgd.personid=rp.personid and rgd.jobid=rp.jobid and rgd.fieldnum=".substr($groupby,1).")";
		} else {
			$groupbyquery = "rp." . $groupby; // reportperson
		}
		$groupbyorder = $groupbyquery . ", ";
	} else {
		$groupbyquery = "''";
		$groupbyorder = "";
	}

	$userlist = array();
	$userresult = Query("Select login, id from user");
	while($row = DBGetRow($userresult)){
		$userlist[$row[1]] = $row[0];
	}

	$query = "SELECT $groupbyquery as field,
				rp.userid,
				rp.jobid,
				count(*)
				from reportperson rp
				$rgroupdata
				where rp.status in ('fail', 'success')
				$joblistquery
				and type = '" . DBSafe($type) . "'
				group by $groupbyorder rp.jobid, rp.userid";
//echo $query;
	$result = Query($query);
	$data = array();
	$userlistarray = array();
	foreach($userlist as $userid => $name){
		$jobtypearray = array();
		foreach($jobtypelist as $jobtypeid => $jobtypename){
			$jobtypearray[$jobtypeid] = 0;
		}
		$userlistarray[$userid] = $jobtypearray;
	}
	$groupbyarray = array();
	while($row = DBGetRow($result)){
		if(!isset($groupbyarray[$row[0]]))
			$groupbyarray[$row[0]]= $userlistarray;
		$groupbyarray[$row[0]][$row[1]][$jobidtypelist[$row[2]]] += $row[3];
	}
	$schooltotals = array();
	$systemtotal = 0;
	foreach($groupbyarray as $school => $users){
		$schooltotals[$school] = array();
		foreach($users as $userid => $jobtypes){
			foreach($jobtypelist as $jobtypeid => $jobtypename){
				if(!isset($groupbyarray[$school][$userid][$jobtypeid]))
					$groupbyarray[$school][$userid][$jobtypeid]=0;
				if(!isset($schooltotals[$school][$jobtypeid]))
					$schooltotals[$school][$jobtypeid] = 0;
				if(!isset($schooltotals[$school]["total"]))
					$schooltotals[$school]["total"] = 0;
				$schooltotals[$school][$jobtypeid]+=$groupbyarray[$school][$userid][$jobtypeid];
				$schooltotals[$school]["total"] +=$groupbyarray[$school][$userid][$jobtypeid];
				$systemtotal +=$groupbyarray[$school][$userid][$jobtypeid];
			}
		}
		foreach($users as $userid => $jobtypes){
			if($schooltotals[$school]["total"] == 0)
				$groupbyarray[$school][$userid]["total"] = 0;
			else
				$groupbyarray[$school][$userid]["total"] = (array_sum($groupbyarray[$school][$userid])/$schooltotals[$school]["total"]) * 100;
		}

	}



////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$PAGE = "reports:system";
$TITLE = "Usage Statistics";
include_once("nav.inc.php");
NewForm($f);
buttons(submit($f, $s, "Refresh"));
startWindow("Display Options" . help("UsageStats_DisplayOptions"), "padding: 3px;");
?>
	<table border="0" cellpadding="3" cellspacing="0" width="100%">
		<tr>
			<th align="right" class="windowRowHeader bottomBorder">Delivery Method</th>
			<td class="bottomBorder">
				<?
					NewFormItem($f, $s, "type", "selectstart");
					NewFormItem($f, $s, "type", "selectoption", "Phone", "phone");
					NewFormItem($f, $s, "type", "selectoption", "Email", "email");
if(getSystemSetting('_hassms', false)){
					NewFormItem($f, $s, "type", "selectoption", "SMS", "sms");
}
					NewFormItem($f, $s, "type", "selectend");
				?>
			</td>
		</tr>
		<tr>
			<th align="right" class="windowRowHeader bottomBorder">Date</th>
			<td class="bottomBorder">
<?
				dateOptions($f, $s, "", true);
?>
			</td>
		</tr>

		<tr valign="top">
			<th align="right" class="bottomBorder windowRowHeader">Group By:</th>
			<td class="bottomBorder">
				<?
					NewFormItem($f, $s, "groupby", "selectstart");
					NewFormItem($f, $s, "groupby", "selectoption", " -- System -- ", "");
					foreach($fields as $field){
						NewFormItem($f, $s, "groupby", "selectoption", $field->name, $field->fieldnum);
					}
					NewFormItem($f, $s, "groupby", "selectend");
				?>
			</td>
		</tr>
		<tr valign="top">
			<th align="right" class="bottomBorder windowRowHeader">Show Users:</th>
			<td class="bottomBorder">
				<?
					NewFormItem($f, $s, "showusers", "checkbox");
				?>
				Show Users
			</td>
		</tr>
	</table>
<?
endWindow();

?><br><?
startWindow("Total Messages Delivered", "padding: 3px;");
?>
	<table border="0" cellpadding="2" cellspacing="1" class="list">
		<tr class="listHeader" >
			<td colspan="<?=count($jobtypelist)+2?>">System Total</td>
			<td><?=$systemtotal?></td>
		</tr>
		<tr class="listHeader" align="left" valign="bottom">
			<td>&nbsp;</td>
<?
			foreach($jobtypelist as $id => $name){
?>
			<td><?=escapehtml($name)?></td>
<?
			}
?>
			<td>Total</td>
			<td>Group %</td>
		</tr>
<?
		$alt=0;

		foreach($groupbyarray as $index => $groupbyfield){

			echo ++$alt % 2 ? '<tr>' : '<tr class="listAlt">';
			$name = FieldMap::getName($groupby);
			$display = $index;
			if($display == "")
				$display = escapehtml("<Not Assigned>");
			if(!$name)
				$name = "System";
			else
				$name .= ": " . $display;
?>
			<td><u><?=$name?><u></td>
<?
			$schooltotal = 0;
			foreach($jobtypelist as $jobtypeid => $jobtypename){
?>
				<td><?=$schooltotals[$index][$jobtypeid]?></td>
<?
			}
?>
				<td><?=$schooltotals[$index]["total"]?></td>
				<td>100.00%</td>
			</tr>
<?
			if($showusers){
				foreach($groupbyfield as $uindex => $user){
					if($user["total"] == 0) continue;
					echo $alt % 2 ? '<tr>' : '<tr class="listAlt">';
?>
						<td>&nbsp;&nbsp;&nbsp;User: <?=$userlist[$uindex]?></td>
<?
					$usertotal = 0;
					foreach($jobtypelist as $jobtypeid => $jobtypename){
						$usertotal += $user[$jobtypeid];
?>
						<td><?=$user[$jobtypeid]?></td>
<?
					}
?>
						<td><?=$usertotal?></td>
						<td><?=number_format($user["total"],2)?>%</td>
					</tr>
<?

				}
			}
		}
?>
	</table>
<?
endWindow();
buttons();
endForm();
?>
<script SRC="script/calendar.js"></script>
<? 
include_once("navbottom.inc.php");
?>
