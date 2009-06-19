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
require_once("inc/reportgeneratorutils.inc.php");
require_once("inc/formatters.inc.php");
require_once("obj/Phone.obj.php");
require_once("obj/ReportInstance.obj.php");
require_once("obj/ReportSubscription.obj.php");
require_once("obj/UserSetting.obj.php");
require_once("obj/FieldMap.obj.php");
require_once("inc/date.inc.php");
require_once("obj/ReportGenerator.obj.php");
require_once("obj/CallsReport.obj.php");
require_once("inc/rulesutils.inc.php");
require_once("ruleeditform.inc.php");


////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$USER->authorize('createreport') && !$USER->authorize('viewsystemreports')) {
	redirect('unauthorized.php');
}

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////


$fields = FieldMap::getOptionalAuthorizedFieldMaps() + FieldMap::getOptionalAuthorizedFieldMapsLike('g');

if(isset($_GET['clear']) && $_GET['clear']){
	unset($_SESSION['reportid']);
	unset($_SESSION['report']['options']);
	redirect();
}

$options= isset($_SESSION['report']['options']) ? $_SESSION['report']['options'] : array();

if(isset($_GET['deleterule'])) {
	if(isset($options['rules'])){
		unset($options['rules'][$_GET['deleterule']]);
		if(!count($options['rules']))
			unset($options['rules']);
	}
	$_SESSION['report']['options'] = $options;
	redirect();
}

$RULES = false;
if(isset($options['rules']) && $options['rules']){
	$RULES = $options['rules'];
}

$activefields = array();
foreach($fields as $field){
	// used in pdf,csv
	if(isset($_SESSION['report']['fields'][$field->fieldnum]) && $_SESSION['report']['fields'][$field->fieldnum]){
		$activefields[] = $field->fieldnum;
	}
}
$options['activefields'] = implode(",",$activefields);

$_SESSION['report']['options'] = $options;

$jobtypeobjs = DBFindMany("JobType", "from jobtype where deleted = '0'");
$jobtypes = array();
foreach($jobtypeobjs as $jobtype){
	$jobtypes[$jobtype->id] = $jobtype->name;
}

$results = array("A" => "Answered",
					"M" => "Machine",
					"N" => "No Answer",
					"B" => "Busy",
					"F" => "Unknown",
					"X" => "Disconnected",
					"duplicate" => "Duplicate",
					"blocked" => "Blocked",
					"notattempted" => "Not Attempted",
					"sent" => "Sent",
					"unsent" => "Unsent",
					"declined" => "No Destination Selected");

$f = "report";
$s = "personnotify";
$reload = 0;

if(CheckFormSubmit($f,$s) || CheckFormSubmit($f,"view")){
	//check to see if formdata is valid
	if(CheckFormInvalid($f))
	{
		error('Form was edited in another window, reloading data');
		$reload = 1;
	}
	else
	{
		MergeSectionFormData($f, $s);

		if(GetFormData($f, $s, "relativedate") != "xdays") {
			PutFormData($f, $s, 'xdays',"", "number");
		} else {
			TrimFormData($f, $s,'xdays');
		}
		TrimFormData($f, $s,'personid');
		TrimFormData($f, $s,'phone');
		TrimFormData($f, $s,'email');
		TrimFormData($f, $s,'startdate');
		TrimFormData($f, $s,'enddate');
			
		if( CheckFormSection($f, $s) ) {
			error('There was a problem trying to save your changes', 'Please verify that all required field information has been entered properly');
		} else if((GetFormData($f, $s, "relativedate") == "daterange") && !strtotime(GetFormData($f, $s, 'startdate'))){
			error("The start date is in an invalid format");
		} else if((GetFormData($f, $s, "relativedate") == "daterange") && !strtotime(GetFormData($f, $s, 'enddate'))){
			error("The end date is in an invalid format");
		} else if((GetFormData($f, $s, "relativedate") == "daterange") && (strtotime(GetFormData($f, $s, 'startdate')) > strtotime(GetFormData($f, $s, 'enddate')))){
			error("The end date must be before the start date");
		} else if((GetFormData($f, $s, "relativedate") == "xdays") && GetFormData($f, $s, "xdays") == ""){
			error('You must enter a number for X days');
		} else if(GetFormData($f, $s, 'personid') == "" && GetFormData($f, $s, 'phone') == "" &&  GetFormData($f, $s, 'email')== ""){
			error("At least one search criteria must have input");
		} else {
			$options['reporttype'] = "contacthistory";
			$options['personid'] = GetFormData($f, $s, 'personid');
			$options['phone'] = Phone::parse(GetFormData($f, $s, 'phone'));
			$options['email'] = GetFormData($f, $s, 'email');

			$options['reldate'] = GetFormData($f, $s, "relativedate");

			if($options['reldate'] == "xdays"){
				$options['lastxdays'] = GetFormData($f, $s, "xdays");
			} else if($options['reldate'] == "daterange"){
				$options['startdate'] = GetFormData($f, $s, 'startdate');
				$options['enddate'] = GetFormData($f, $s, 'enddate');
			}

			$savedjobtypes = GetFormData($f, $s, 'jobtypes');
			if($savedjobtypes){
				$temp = array();
				foreach($savedjobtypes as $savedjobtype)
					$temp[] = DBSafe($savedjobtype);
				$options['jobtypes'] = implode("','", $temp);
			}else
				$options['jobtypes'] = "";

			$savedresults = GetFormData($f, $s, "results");
			if($savedresults){
				$temp = array();
				foreach($savedresults as $savedresult)
					$temp[] = DBSafe($savedresult);
				$options['results'] = implode("','", $temp);
			}else
				$options['results'] = "";

			if($rule = getRuleFromForm($f, $s)){
				if(!isset($options['rules']))
					$options['rules'] = array();
				$options['rules'][] = $rule;
				$rule->id = array_search($rule, $options['rules']);
				$options['rules'][$rule->id] = $rule;
			}

			foreach($options as $index => $option){
				if($option == "")
					unset($options[$index]);
			}
			$_SESSION['report']['options'] = $options;

			if(CheckFormSubmit($f, "save")){
				ClearFormData($f);
				redirect("reportedit.php");
			}
			if(CheckFormSubmit($f,"view")){
				ClearFormData($f);
				redirect("reportcallsresult.php");
			}
			redirect();
		}
	}
} else {
	$reload = 1;
}


if($reload){
	ClearFormData($f);
	$options = isset($_SESSION['report']['options']) ? $_SESSION['report']['options'] : array();
	PutFormData($f, $s, "relativedate", isset($options['reldate']) ? $options['reldate'] : "");
	PutFormData($f, $s, 'xdays', isset($options['lastxdays']) ? $options['lastxdays'] : "", "number");
	PutFormData($f, $s, 'personid', isset($options['personid']) ? $options['personid'] : "", 'text');
	PutFormData($f, $s, 'phone', isset($options['phone']) ? Phone::format($options['phone']) : "", 'phone', "7", "10");
	PutFormData($f, $s, 'email', isset($options['email']) ? $options['email'] : "", 'email');

	PutFormData($f, $s, 'startdate', isset($options['startdate']) ? $options['startdate'] : "");
	PutFormData($f, $s, 'enddate', isset($options['enddate']) ? $options['enddate'] : "");

	$savedjobtypes = array();
	if(isset($options['jobtypes'])){
		$savedjobtypes = explode("','", $options['jobtypes']);
	}
	PutFormData($f, $s, 'jobtype', isset($options['jobtypes']) && $options['jobtypes'] !="" ? 1 : 0, "bool", 0, 1);
	PutFormData($f, $s, 'jobtypes', $savedjobtypes, "array", array_keys($jobtypes));

	$savedresults = array();
	if(isset($options['results'])){
		$savedresults = explode("','", $options['results']);
	}
	PutFormData($f, $s, 'result', isset($options['results']) && $options['results'] !="" ? 1 : 0, "bool", 0, 1);
	PutFormData($f, $s, 'results', $savedresults, "array", array_keys($results));

	putRuleFormData($f, $s);


}



////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$PAGE = "reports:reports";
$TITLE = "Contact History";


if(isset($_SESSION['reportid'])){
	$subscription = new ReportSubscription($_SESSION['reportid']);
	$TITLE .= ": " . escapehtml($subscription->name);
}

include_once("nav.inc.php");

NewForm($f);
buttons(button('Back', null, 'reports.php'), submit($f, "view", "View Report"));
startWindow("Person Notification Search", "padding: 3px;");

?>
<table border="0" cellpadding="3" cellspacing="0" width="100%">
	<tr valign="top"><th align="right" class="windowRowHeader bottomBorder">Search on:<br><?=help('ContactHistory_Search',NULL,'small')?></th>
	<td class="bottomBorder">
		<table>
			<tr><td>ID#: </td><td><? NewFormItem($f, $s, 'personid', 'text', '20'); ?></td></tr>
			<tr><td>Phone Number: </td><td><? NewFormItem($f, $s, 'phone', 'text', '15'); ?></td></tr>
			<tr><td>Email Address: </td><td><? NewFormItem($f, $s, 'email', 'text', '20', '100'); ?></td></tr>
		</table>

	</td>
	<tr valign="top"><th align="right" class="windowRowHeader bottomBorder">Filter by:<br><?=help("ContactHistory_Filter",NULL,'small')?></th>
	<td class="bottomBorder">
		<table border="0" cellpadding="3" cellspacing="0" width="100%">
			<tr>
				<td><br>
					<?
						if(!isset($_SESSION['reportrules']) || is_null($_SESSION['reportrules']))
							$_SESSION['reportrules'] = false;

						//$RULES declared above
						$RULEMODE = array('multisearch' => true, 'text' => true, 'reldate' => true, 'numeric' => true);

						drawRuleTable($f, $s, false, true, true, false);
					?>
				<br></td>
			</tr>
		</table>
		<table>
			<tr>
				<td>
<?
					dateOptions($f, $s, "", true);
?>
				</td>
			</tr>
			<tr>
				<td>
					<table>
						<tr valign="top">
							<td><? NewFormItem($f,$s,"jobtype","checkbox",NULL,NULL,'id="jobtype" onclick="clearAllIfNotChecked(this,\'jobtypeselect\');"'); ?></td>
							<td>Job Type: </td>
							<td>
								<?
								NewFormItem($f, $s, 'jobtypes', 'selectmultiple', count($jobtypes), $jobtypes, 'id="jobtypeselect" onmousedown="setChecked(\'jobtype\');"');
								?>
							</td>
						</tr>
						<tr valign="top">
							<td><? NewFormItem($f,$s,"result","checkbox",NULL,NULL,'id="result" onclick="clearAllIfNotChecked(this,\'resultselect\');"'); ?></td>
							<td>Result:</td>
							<td>
								<?
								NewFormItem($f, $s, 'results', 'selectmultiple',  "6", $results, 'id="resultselect" onmousedown="setChecked(\'result\');"');
								?>
							</td>
						</tr>
					</table>
				</td>
			</tr>
		</table>
	</td>
	<tr valign="top"><th align="right" class="windowRowHeader bottomBorder">Display Fields:<br><?=help('ContactHistory_DisplayFields',NULL,'small')?></th>
		<td class="bottomBorder">
<?
			select_metadata(null, null, $fields);
?>
		</td>
	</tr>
</table>
<?
endWindow();
buttons();
EndForm();

?>
<script SRC="script/calendar.js"></script>
<?

include_once("navbottom.inc.php");
?>
