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
require_once("obj/Language.obj.php");

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

// default to 'Organization' only if they have any organizations in their database
$defaultgroupby = (QuickQuery("select count(*) from organization") > 0) ? "org" : "";
$requestedgroupby = isset($_SESSION['usagestats']['groupby']) ? $_SESSION['usagestats']['groupby'] : $defaultgroupby;

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
		error('Form was edited in another window, reloading data');
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
	PutFormData($f, $s, "groupby", $requestedgroupby);
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
$languageField = FieldMap::getLanguageField();

// get job types, don't show survey if they don't have it
$surveysql = getSystemSetting('_hassurvey', true) ? '' : 'and issurvey=0';
$jobtypelist = QuickQueryList("select id, name from jobtype where not deleted $surveysql", true);

$paramdata = array("lastxdays" =>  GetFormData($f, $s, "xdays"), "startdate" => $startdate, "enddate" => $enddate);

list($startdate, $enddate) = getStartEndDate($reldate, $paramdata);

// get the list of jobids for the requested date or range
$joblist = getJobList($startdate, $enddate, implode("','", array_keys($jobtypelist)), "", $type);
$joblistquery = " and rp.jobid in ('" . implode("','", $joblist) . "') ";

// query data based on which field the user requesed us to group by
$field = "''";
$fieldname = "";
$groupby = "";
$join = "";
if ($requestedgroupby == "") {
	// --System--
} else if ($requestedgroupby == "org") {
	// organization
	$field = "o.orgkey";
	$groupby = "ro.organizationid, ";
	$join = "left join reportorganization ro on (ro.jobid = rp.jobid and ro.personid = rp.personid)
			left join organization o on (o.id = ro.organizationid)";
	
	// get organization field name
	$fieldname = getSystemSetting("organizationfieldname","Organization");
} else {
	// F or G field
	if (strpos($requestedgroupby, "g") === 0) {
		// Gfield
		$field = "rgd.value";
		$groupby = "rgd.value, ";
		$join = "left join reportgroupdata rgd on (rgd.personid=rp.personid and rgd.jobid=rp.jobid and rgd.fieldnum=".DBSafe(substr($requestedgroupby,1)).")";
	} else {
		// Ffield
		$field = "rp." . DBSafe($requestedgroupby);
		$groupby = "rp." . DBSafe($requestedgroupby) . ", ";
	}
	
	// get fieldmap name value
	$fieldname = QuickQuery("select name from fieldmap where fieldnum = ?", false, array($requestedgroupby));
}

// if show users requested, query the user info
$userfield = "''";
if ($showusers) {
	$userfield = "u.login";
	$groupby .= " rp.userid, ";
	$join .= " inner join user u on (u.id = rp.userid) ";
}

$query = "SELECT $field as field,
			$userfield as userlogin,
			jt.id as jobtypeid,
			count(*) as contactcount
		from reportperson rp
		inner join job j on 
			(j.id = rp.jobid)
		inner join jobtype jt on 
			(jt.id = j.jobtypeid)
		$join
		where rp.status in ('fail', 'success')
			$joblistquery
			and rp.type = '" . DBSafe($type) . "'
		group by $groupby jobtypeid
		order by $groupby jobtypeid";

$results = QuickQueryMultiRow($query, true);

// add up all the job type totals
$fieldtotals = array();
$systemtotal = 0;
foreach($results as $result){
	$jobtype = $result['jobtypeid'];
	$field = $result['field'];
	$contacts = $result['contactcount'];
	
	if (!isset($fieldtotals[$field])) {
		$fieldtotals[$field] = array();
		foreach($jobtypelist as $id => $name)
			$fieldtotals[$field][$id] = 0;
	}
	
	$fieldtotals[$field][$jobtype] = $fieldtotals[$field][$jobtype] + $contacts;
	$systemtotal = $systemtotal + $contacts;
}

function outputUserData($userdata, $jobtypelist, $systemtotal) {
	// output the previous user's data
	echo "<tr><td>" . $userdata[0] . "</td>";
	
	// output and clear user jobtype data
	$total = 0;
	foreach($jobtypelist as $id => $name) {
		echo "<td>" . $userdata[$id] . "</td>";
		$total += $userdata[$id];
	}
	
	// output the total and percentage
	echo "<td>" . $total . "</td><td>" . number_format(($total/$systemtotal)*100,2) . "%</td></tr>";
	
	// clear jobtype data
	foreach($jobtypelist as $id => $name)
		$userdata[$id] = 0;
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
					NewFormItem($f, $s, "groupby", "selectoption", getSystemSetting('organizationfieldname', 'School'), "org");
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

$lastfield = 0;
$lastuser = 0;
$userdata = array();
$bgtoggle = 0;
foreach ($results as $result) {
	// results are ordered by requested groupby field then userid then jobtypeid
	$userlogin = $result['userlogin'];
	$jobtypeid = $result['jobtypeid'];
	$field = $result['field'];
	$contacts = $result['contactcount'];
	$wroteheader = false;

	// if field value changes, output field header
	if ($field !== $lastfield) {
		
		// output the user data so we can continue collecting for the next field value
		if ($lastfield !== 0) {
			if ($showusers)
				outputUserData($userdata, $jobtypelist, $systemtotal);
			$wroteheader = true;
		}
		
		// output field headers
		echo "<tr " . ($showusers?"class='listAlt'":"") . "><td>";
		if ($fieldname == "") {
			echo escapehtml(_L("System"));
		} else if ($field == "") {
			echo $fieldname . ": " . escapehtml(_L("Undefined"));
		} else {
			echo $fieldname . ": " . escapehtml($field);
		}
		echo "</td>";
		
		// output the totals for each job type
		$total = 0;
		foreach($jobtypelist as $id => $name) {
			echo "<td>" . $fieldtotals[$field][$id] . "</td>";
			$total += $fieldtotals[$field][$id];
		}
		
		// output the total and percentage
		echo "<td>" . $total . "</td><td>" . number_format(($total/$systemtotal)*100,2) . "%</td></tr>";
		
		$lastfield = $field;
	}
	
	if ($showusers) {
		// if initial value for last user then init it to the first user
		if ($lastuser === 0) {
			$userdata[0] = "&nbsp;&nbsp;" . escapehtml(_L("User")) . ": " . escapehtml($userlogin);
			foreach($jobtypelist as $id => $name) 
				$userdata[$id] = 0;
			$lastuser = $userlogin;
		}
		
		// if the user changes, output the last user's collected data and start collecting on a new one
		if ($userlogin != $lastuser) {
			
			// write out the userdata for the previous user, if the header changed... it already wrote it out
			if (!$wroteheader)
				outputUserData($userdata, $jobtypelist, $systemtotal);
			
			// set next user's label
			$userdata[0] = "&nbsp;&nbsp;" . escapehtml(_L("User")) . ": " . escapehtml($userlogin);
			$lastuser = $userlogin;
		}
		
		// collect user data
		$userdata[$jobtypeid] = $contacts;
	}
	
}

if ($results && $showusers) {
	
	// output last user's data
	echo "<tr><td>" . $userdata[0] . "</td>";
	
	// output and clear user jobtype data
	$total = 0;
	foreach($jobtypelist as $id => $name) {
		echo "<td>" . $userdata[$id] . "</td>";
		$total += $userdata[$id];
	}
	
	// output the total and percentage
	echo "<td>" . $total . "</td><td>" . number_format(($total/$systemtotal)*100,2) . "%</td></tr>";
			
		
}

?>
	</table>
<?
endWindow();
buttons();
endForm();
?>
<script type="text/javascript" src="script/datepicker.js"></script>
<? 
include_once("navbottom.inc.php");
?>
