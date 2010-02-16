<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
require_once("inc/common.inc.php");
require_once("inc/form.inc.php");
require_once("inc/table.inc.php");
require_once("inc/html.inc.php");
require_once("inc/formatters.inc.php");
require_once("inc/date.inc.php");
require_once("obj/Person.obj.php");
require_once("obj/Validator.obj.php");
require_once("obj/Form.obj.php");
require_once("obj/Rule.obj.php");
require_once("obj/FieldMap.obj.php");
require_once("obj/FormItem.obj.php");
require_once("obj/Phone.obj.php");
require_once("obj/Email.obj.php");
require_once("obj/Sms.obj.php");
require_once("inc/securityhelper.inc.php");
require_once("inc/rulesutils.inc.php");
require_once("obj/FormRuleWidget.fi.php");
require_once("obj/SectionWidget.fi.php");
require_once("obj/ValSections.val.php");
require_once("obj/Job.obj.php");
require_once("inc/reportutils.inc.php");

require_once("obj/Address.obj.php");
require_once("obj/Language.obj.php");
require_once("obj/JobType.obj.php");

require_once("inc/reportgeneratorutils.inc.php");
require_once("obj/ReportGenerator.obj.php");
require_once("obj/CallsReport.obj.php");
require_once("obj/ReportInstance.obj.php");
require_once("obj/ReportSubscription.obj.php");
require_once("obj/JobDetailReport.obj.php");
require_once("obj/UserSetting.obj.php");
require_once("obj/PortalReport.obj.php");


////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$USER->authorize('createreport') && !$USER->authorize('viewsystemreports')) {
	redirect('unauthorized.php');
}

////////////////////////////////////////////////////////////////////////////////
// Action/Request Processing
////////////////////////////////////////////////////////////////////////////////
$fields = FieldMap::getOptionalAuthorizedFieldMaps() + FieldMap::getOptionalAuthorizedFieldMapsLike('g');

if (isset($_GET['clear']) && $_GET['clear']) {
	unset($_SESSION['reportid']);
	unset($_SESSION['report']['options']);
}

if (isset($_GET['type'])) {
	$_SESSION['report']['type'] = $_GET['type'];
}

if (isset($_GET['reportid'])) {
	if (!userOwns("reportsubscription", $_GET['reportid']+0)) {
		redirect('unauthorized.php');
	}

	$_SESSION['reportid'] = $_GET['reportid']+0;

	$subscription = new ReportSubscription($_SESSION['reportid']);
	$instance = new ReportInstance($subscription->reportinstanceid);
	$options = $instance->getParameters();

	$_SESSION['report']['type']="phone";
	if(isset($options['reporttype'])){
		if($options['reporttype']=="emaildetail")
			$_SESSION['report']['type']="email";
		else if($options['reporttype'] == "notcontacted")
			$_SESSION['report']['type']="notcontacted";
		else if($options['reporttype'] == "smsdetail")
			$_SESSION['report']['type']="sms";
	}
	$activefields = array();
	if(isset($options['activefields'])){
		$activefields = explode(",", $options['activefields']) ;
	}
	foreach($fields as $field){
		if(in_array($field->fieldnum, $activefields)){
			$_SESSION['report']['fields'][$field->fieldnum] = true;
		} else {
			$_SESSION['report']['fields'][$field->fieldnum] = false;
		}
	}
	$_SESSION['saved_report'] = true;
	$_SESSION['report']['options'] = $options;
}

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////
$validOrdering = JobDetailReport::getOrdering();

if (!isset($_SESSION['report']['options']))
	$_SESSION['report']['options'] = array('reporttype' => 'phonedetail');

if (isset($_SESSION['reportid'])) {
	$subscription = new ReportSubscription($_SESSION['reportid']+0);
	$_SESSION['saved_report'] = true;
} else {
	$_SESSION['saved_report'] = false;
}

set_session_options_reporttype();
set_session_options_activefields();
set_session_options_orderby();

////////////////////////////////////////////////////////////////////////////////
// FORM DATA
////////////////////////////////////////////////////////////////////////////////
$jobtypeobjs = DBFindMany("JobType", "from jobtype where deleted = '0' and not issurvey order by systempriority, name");
$jobtypenames = array();
foreach($jobtypeobjs as $jobtype){
	$jobtypenames[$jobtype->id] = $jobtype->name;
}



switch($_SESSION['report']['type']){
	case "phone":
		$possibleresults = array("A" => "Answered",
			"M" => "Machine",
			"N" => "No Answer",
			"B" => "Busy",
			"F" => "Unknown",
			"X" => "Disconnected",
			"duplicate" => "Duplicate",
			"blocked" => "Blocked",
			"notattempted" => "Not Attempted",
			"declined" => "No Phone Selected",
			"confirmed" => "Confirmed",
			"notconfirmed" => "Not Confirmed",
			"noconfirmation" => "No Confirmation Response");
		break;

	case "notcontacted":
		$possibleresults = array("N" => "No Answer",
			"B" => "Busy",
			"F" => "Unknown",
			"X" => "Disconnected",
			"blocked" => "Blocked",
			"notattempted" => "Not Attempted",
			"unsent" => "Unsent",
			"declined" => "No Destination Selected");
		break;

	case "email":
		$possibleresults = array("sent" => "Sent",
			"unsent" => "Unsent",
			"duplicate" => "Duplicate",
			"declined" => "No Email Selected");

		break;

	case "sms":
		$possibleresults = array("sent" => "Sent",
			"unsent" => "Unsent",
			"duplicate" => "Duplicate",
			"declined" => "No SMS Selected");
		break;

	default:
		$possibleresults = array("A" => "Answered",
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
			"duplicate" => "Duplicate",
			"declined" => "No Destination Selected");
		break;
}

$options = $_SESSION['report']['options'];

$rulewidgetvaluejson = '';
$rulewidgetdata = array();
if (isset($options['rules']) && count($options['rules']) > 0) {
	$rulewidgetdata = cleanObjects(array_values($options['rules']));
}
if (isset($options['organizationids']) && count($options['organizationids']) > 0) {
	$organizations = $USER->organizations();
	
	if (count($organizations) > 0) {
		$orgkeys = array(); // An array of value=>title pairs.
		
		foreach ($options['organizationids'] as $id) {
			if (isset($organizations[$id]))
				$orgkeys[$id] = $organizations[$id]->orgkey;
		}
		
		if (count($orgkeys) > 0) {
			$rulewidgetdata[] = array(
				'fieldnum' => 'organization',
				'val' => $orgkeys
			);
		}
	}
}
if (count($rulewidgetdata) > 0)
	$rulewidgetvaluejson = json_encode($rulewidgetdata);

$savedjobtypes = array();
if(isset($options['jobtypes'])){
	$savedjobtypes = explode("','", $options['jobtypes']);
}

$savedresults = array();
if(isset($options['result'])) {
	if ($options['result'] == "undelivered")
		$savedresults = array("F", "B", "N", "X", "notattempted", "declined", "blocked", "unsent");
	else
		$savedresults = explode("','", $options['result']);
}

$jobid = isset($options['jobid']) ? $options['jobid']: '';
$jobtypefilter = "";
if (isset($_SESSION['report']['type'])) {
	$type = $_SESSION['report']['type'];
	if (in_array($type, array('phone', 'email', 'sms')))
		$jobtypefilter = " and exists (select * from message m where m.type='$type' and m.messagegroupid=j.messagegroupid) ";
}

//if this user can see systemwide reports, then lock them to the customerid
//otherwise lock them to jobs that they own
$userJoin = "";
if (!$USER->authorize('viewsystemreports')) {
	$userJoin = " and userid = $USER->id ";
}
$jobs = DBFindMany("Job","from job j where deleted = 0 and status in ('active','complete','cancelled','cancelling') and j.questionnaireid is null $userJoin $jobtypefilter order by id desc limit 500");
$jobids = array();
foreach ($jobs as $job) {
	$jobids[$job->id] = $job->name;
}
$jobidsarchived = array();
$jobsarchived = DBFindMany("Job","from job j where deleted = 2 and status!='repeating' and j.questionnaireid is null $userJoin $jobtypefilter order by id desc limit 500");
foreach ($jobsarchived as $job) {
	$jobidsarchived[$job->id] = $job->name;
}

$formdata = array();
$formdata["radioselect"] = array(
	"label" => _L("Search on job or date"),
	"value" => isset($options['jobid']) ? 'job' : 'date',
	"control" => array("RadioButton", "values" => array("job" => _L("Job"), "date" => _L("Date"))),
	"validators" => array(array("ValRequired"), array("ValInArray", "values" => array('job', 'date'))),
	"helpstep" => 1
);

$formdata["jobid"] = array(
	"label" => _L("Jobs"),
	"value" => !empty($options['archived']) ? '' : $jobid,
	"control" => array("SelectMenu", "values" => $jobids),
	"validators" => array(array("ValInArray", "values" => array_keys($jobids))),
	"helpstep" => 1
);

$formdata["jobidarchived"] = array(
	"label" => _L("Archived Jobs"),
	"value" => !empty($options['archived']) ? $jobid : '',
	"control" => array("SelectMenu", "values" => $jobidsarchived),
	"validators" => array(array("ValInArray", "values" => array_keys($jobidsarchived))),
	"helpstep" => 1
);

$formdata["checkarchived"] = array(
	"label" => _L("Show archived jobs"),
	"value" => !empty($options['archived']) ? 1 : 0,
	"control" => array("CheckBox"),
	"validators" => array(),
	"helpstep" => 1
);

$formdata["dateoptions"] = array(
	"label" => _L("Date Options"),
	"value" => json_encode(array(
		"reldate" => isset($options['reldate']) ? $options['reldate'] : 'today',
		"xdays" => isset($options['lastxdays']) ? $options['lastxdays'] : '',
		"startdate" => isset($options['startdate']) ? $options['startdate'] : '',
		"enddate" => isset($options['enddate']) ? $options['enddate'] : ''
	)),
	"control" => array("ReldateOptions"),
	"validators" => array(array("ValReldate")),
	"helpstep" => 1
);

$formdata[] = _L("Filter By");
$allowedFields = array('f','g');
$formdata["ruledata"] = array(
	"label" => _L('Criteria'),
	"value" => $rulewidgetvaluejson,
	"control" => array("FormRuleWidget", "allowedFields" => $allowedFields),
	"validators" => array(array('ValRules', "allowedFields" => $allowedFields)),
	"helpstep" => 1
);

if ($USER->hasSections()) {
	$formdata["sectionids"] = array(
		"label" => _L('Sections'),
		"fieldhelp" => _L('Select sections from an organization.'),
		"value" => "",
		"validators" => array(
			array("ValSections")
		),
		"control" => array("SectionWidget",
			"sectionids" => isset($options['sectionids']) && count($options['sectionids']) > 0 ?
				$options['sectionids'] :
				array()
		),
		"helpstep" => 2
	);
}

$formdata["jobtype"] = array(
	"label" => _L("Filter by job type"),
	"value" => isset($options['jobtypes']) ? 1 : 0,
	"control" => array("CheckBox"),
	"validators" => array(),
	"helpstep" => 1
);

$formdata["jobtypes"] = array(
	"label" => _L("Job Types"),
	"value" => $savedjobtypes,
	"control" => array("MultiCheckBox", "values" => $jobtypenames),
	"validators" => array(array("ValInArray", "values" => array_keys($jobtypenames))),
	"helpstep" => 1
);

$formdata["result"] = array(
	"label" => _L("Filter by result"),
	"value" => isset($options['result']) ? 1 : 0,
	"control" => array("CheckBox"),
	"validators" => array(),
	"helpstep" => 1
);

$formdata["results"] = array(
	"label" => _L("Results"),
	"value" => $savedresults,
	"control" => array("MultiCheckBox", "values" => $possibleresults),
	"validators" => array(array("ValInArray", "values" => array_keys($possibleresults))),
	"helpstep" => 1
);

$formdata[] = _L("Report Options");

$formdata["displayoptions"] = array(
	"label" => _L("Display Fields"),
	"control" => array("FormHtml", "html" => "<div id='metadataDiv'></div>"),
	"helpstep" => 1
);

$formdata["multipleorderby"] = array(
	"label" => _L('Sort By'),
	"value" => !empty($validOrdering) ? $validOrdering : '',
	"control" => array("MultipleOrderBy", "count" => 3, "values" => $validOrdering),
	"validators" => array(),
	"helpstep" => 1
);

$buttons = array(
	icon_button(_L('Back'), 'tick', null, 'reports.php'),
	submit_button(_L("View Report"),"view","arrow_refresh"),
	submit_button(_L("Save/Schedule"),"save","arrow_refresh")
);

$form = new Form('reportcallssearch',$formdata,array(),$buttons);
$form->ajaxsubmit = true;
///////////////////////////////////////////////////////////
// FORM HANDLING
$form->handleRequest();

$datachange = false;
$errors = false;

//check for form submission
if ($button = $form->getSubmit()) { //checks for submit and merges in post data
	$ajax = $form->isAjaxSubmit(); //whether or not this requires an ajax response

	if ($form->checkForDataChange()) {
		$datachange = true;
	} else if (($errors = $form->validate()) === false) { //checks all of the items in this form
		$postdata = $form->getData(); //gets assoc array of all values {name:value,...}

		if ($ajax) {
			if (in_array($button,array('addrule','deleterule','view', 'save'))) {
				// Clear report options except for rules and organizations.
				if (isset($_SESSION['report']['options']['rules']))
					$rules = $_SESSION['report']['options']['rules'];
				if (isset($_SESSION['report']['options']['organizationids']))
					$organizationids = $_SESSION['report']['options']['organizationids'];
				
				$_SESSION['report']['options'] = array(
					'reporttype' => 'phonedetail',
					'rules' => isset($rules) ? $rules : array(),
					'organizationids' => isset($organizationids) ? $organizationids : array()
				);
				
				if ($USER->hasSections()) {
					$_SESSION['report']['options']['sectionids'] = isset($postdata['sectionids']) ? $postdata['sectionids'] : array();
				}
				
				set_session_options_reporttype();

				switch($postdata['radioselect']){
					case "job":
						if ($postdata['checkarchived'])
							$_SESSION['report']['options']['jobid'] = $postdata["jobidarchived"];
						else
							$_SESSION['report']['options']['jobid'] = $postdata["jobid"];
						$_SESSION['report']['options']['archived'] = $postdata['checkarchived'];
						break;
					case "date":
						$dateOptions = json_decode($postdata['dateoptions'], true);
						if (!empty($dateOptions['reldate'])) {
							$_SESSION['report']['options']['reldate'] = $dateOptions['reldate'];

							if ($dateOptions['reldate'] == 'xdays' && isset($dateOptions['xdays'])) {
								$_SESSION['report']['options']['lastxdays'] = $dateOptions['xdays'] + 0;
							} else if ($dateOptions['reldate'] == 'daterange') {
								if (!empty($dateOptions['startdate']))
									$_SESSION['report']['options']['startdate'] = $dateOptions['startdate'];
								if (!empty($dateOptions['enddate']))
									$_SESSION['report']['options']['enddate'] = $dateOptions['enddate'];
							}
						}
						break;
				}

				if (isset($postdata['multipleorderby'])) {
					$multipleorderby = $postdata['multipleorderby'];
					if (is_array($multipleorderby)) {
						$_SESSION['reportjobdetailsearch_orderby'] = array();
						foreach ($multipleorderby as $i=>$orderby) {
							if (in_array($orderby, $validOrdering)) {
								$_SESSION['reportjobdetailsearch_orderby'][] = $orderby;
							}
						}
						set_session_options_orderby();
					}
				}

				if (!empty($postdata['jobtypes'])) {
					$temp = array();
					foreach($postdata['jobtypes'] as $savedjobtype) {
						$temp[] = DBSafe($savedjobtype);
					}
					$_SESSION['report']['options']['jobtypes'] = implode("','", $temp);
				}
				if (!empty($postdata["results"])) {
					$temp = array();
					foreach($postdata["results"] as $savedresult)
						$temp[] = DBSafe($savedresult);
					$_SESSION['report']['options']['result'] = implode("','", $temp);
				}

				switch ($button) {
					case 'addrule':
						$data = json_decode($postdata['ruledata']);
						if (isset($data->fieldnum, $data->logical, $data->op, $data->val)) {
							if ($data->fieldnum == 'organization') {
								$_SESSION['report']['options']['organizationids'] = $data->val;
							} else if ($type = Rule::getType($data->fieldnum)) {
								$data->val = prepareRuleVal($type, $data->op, $data->val);
								if ($rule = Rule::initFrom($data->fieldnum, $data->logical, $data->op, $data->val)) {
									if (!isset($_SESSION['report']['options']['rules']))
										$_SESSION['report']['options']['rules'] = array();
									$_SESSION['report']['options']['rules'][$data->fieldnum] = $rule;
								}
							}
						}
						$form->sendTo("reportjobdetailsearch.php");
						break;

					case 'deleterule':
						$fieldnum = $postdata['ruledata'];
						
						if ($fieldnum == 'organization') {
							unset($_SESSION['report']['options']['organizationids']);
						} else if (!empty($_SESSION['report']['options']['rules'])) {
							unset($_SESSION['report']['options']['rules'][$fieldnum]);
						}
						
						$form->sendTo("reportjobdetailsearch.php");
						break;

					case 'view':
						$form->sendTo("reportjobdetails.php");
						break;

					case 'save':
						set_session_options_activefields();
						$form->sendTo("reportedit.php");
						break;
				}
			}
		} else {
			redirect("reportjobdetailsearch.php");
		}
	}
}//NEW

////////////////////////////////////////////////////////////////////////////////
// FUNCTIONS
////////////////////////////////////////////////////////////////////////////////
function set_session_options_reporttype() {
	if (isset($_SESSION['report']['type'])) {
		if ($_SESSION['report']['type'] == "phone") {
			$_SESSION['report']['options']['reporttype'] = "phonedetail";
		} else if ($_SESSION['report']['type'] == "email"){
			$_SESSION['report']['options']['reporttype'] = "emaildetail";
		} else if ($_SESSION['report']['type'] == "sms"){
			$_SESSION['report']['options']['reporttype'] = "smsdetail";
		} else if ($_SESSION['report']['type'] == "notcontacted"){
			$_SESSION['report']['options']['reporttype'] = "notcontacted";
		}
	}
}

function set_session_options_activefields() {
	global $fields;

	$activefields = array();
	foreach($fields as $field){
		if(isset($_SESSION['report']['fields'][$field->fieldnum]) && $_SESSION['report']['fields'][$field->fieldnum]){
			$activefields[] = $field->fieldnum;
		}
	}
	$_SESSION['report']['options']['activefields'] = implode(",",$activefields);
}

function set_session_options_orderby() {
	if (!empty($_SESSION['reportjobdetailsearch_orderby'])) {
		foreach ($_SESSION['reportjobdetailsearch_orderby'] as $i => $orderby) {
			$_SESSION['report']['options']["order" . ($i+1)] = $orderby;
		}
	}
}

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
$PAGE = "reports:reports";
$TITLE = "Phone Log";

if(isset($_SESSION['report']['type'])){
	if($_SESSION['report']['type'] == 'email'){
		$TITLE = "Email Log";
	} else if($_SESSION['report']['type'] == 'phone'){
		$TITLE = "Phone Log";
	} else if($_SESSION['report']['type'] == 'sms'){
		$TITLE = "SMS Log";
	} else if($_SESSION['report']['type'] == 'notcontacted'){
		$TITLE = "Recipients Not Contacted";
	}
}

if(isset($_SESSION['reportid']))
	$TITLE .= " - " . escapehtml($subscription->name);

require_once("nav.inc.php");

startWindow(_L("Select"), "padding: 3px;");

	echo "<div id='metadataTempDiv' style='display:none'>";
		select_metadata(null, null, $fields);
	echo "</div>";

	?>
		<script type="text/javascript">
			<? Validator::load_validators(array("ValSections", "ValRules", "ValReldate")); ?>
		</script>
	<?
	echo $form->render();
endWindow();
?>
	<script type="text/javascript">
		document.observe('dom:loaded', function() {
			ruleWidget.delayActions = true;
			ruleWidget.container.observe('RuleWidget:AddRule', rulewidget_add_rule);
			ruleWidget.container.observe('RuleWidget:DeleteRule', rulewidget_delete_rule);

			$('<?=$form->name?>_radioselect').select('input').invoke('observe', 'click', function(event) {
				var radio = event.element();
				if (radio.value == 'job') {
					$('<?=$form->name?>_dateoptions').up('tr').hide();
					$('<?=$form->name?>_checkarchived').up('tr').show();

					if ($('<?=$form->name?>_checkarchived').checked) {
						$('<?=$form->name?>_jobid').up('tr').hide();
						$('<?=$form->name?>_jobidarchived').up('tr').show();
					} else {
						$('<?=$form->name?>_jobid').up('tr').show();
						$('<?=$form->name?>_jobidarchived').up('tr').hide();
					}
				} else if (radio.value == 'date') {
					$('<?=$form->name?>_dateoptions').up('tr').show();
					$('<?=$form->name?>_jobid').up('tr').hide();
					$('<?=$form->name?>_jobidarchived').up('tr').hide();
					$('<?=$form->name?>_checkarchived').up('tr').hide();
				}
			});

			$('<?=$form->name?>_checkarchived').observe('click', function(event) {
				if (event.element().checked) {
					$('<?=$form->name?>_jobid').up('tr').hide();
					$('<?=$form->name?>_jobidarchived').up('tr').show();
				} else {
					$('<?=$form->name?>_jobid').up('tr').show();
					$('<?=$form->name?>_jobidarchived').up('tr').hide();
				}
			});

			var jobtypesCheckboxes = $('<?=$form->name?>_jobtypes').select('input');
			$('<?=$form->name?>_jobtype').observe('click', function(event, jobtypesCheckboxes) {
				if (!this.checked) {
					jobtypesCheckboxes.each(function(checkbox){
						checkbox.checked = false;
					});
				}
			}.bindAsEventListener($('jobtype'), jobtypesCheckboxes));
			jobtypesCheckboxes.invoke('observe', 'click', function(event) {
				$('<?=$form->name?>_jobtype').checked = true;
			});

			var resultsCheckboxes = $('<?=$form->name?>_results').select('input');
			$('<?=$form->name?>_result').observe('click', function(event, resultsCheckboxes) {
				if (!this.checked) {
					resultsCheckboxes.each(function(checkbox){
						checkbox.checked = false;
					});
				}
			}.bindAsEventListener($('result'), resultsCheckboxes));
			resultsCheckboxes.invoke('observe', 'click', function(event) {
				$('<?=$form->name?>_result').checked = true;
			});

			var radioselectchoice = $('<?=$form->name?>_radioselect').down('input:checked');
			if (radioselectchoice.value == 'job') {
				$('<?=$form->name?>_dateoptions').up('tr').hide();
				$('<?=$form->name?>_checkarchived').up('tr').show();

				if ($('<?=$form->name?>_checkarchived').checked) {
					$('<?=$form->name?>_jobid').up('tr').hide();
					$('<?=$form->name?>_jobidarchived').up('tr').show();
				} else {
					$('<?=$form->name?>_jobid').up('tr').show();
					$('<?=$form->name?>_jobidarchived').up('tr').hide();
				}
			} else if (radioselectchoice.value == 'date') {
				$('<?=$form->name?>_dateoptions').up('tr').show();
				$('<?=$form->name?>_checkarchived').up('tr').hide();
				$('<?=$form->name?>_jobid').up('tr').hide();
				$('<?=$form->name?>_jobidarchived').up('tr').hide();
				$('<?=$form->name?>_checkarchived').up('tr').hide();
			}

			$('metadataDiv').update($('metadataTempDiv').innerHTML);
		});

		function rulewidget_add_rule(event) {
			$('<?=$form->name?>_ruledata').value = event.memo.ruledata.toJSON();
			form_submit(event, 'addrule');
		}

		function rulewidget_delete_rule(event) {
			$('<?=$form->name?>_ruledata').value = event.memo.fieldnum;
			form_submit(event, 'deleterule');
		}
	</script>
<?
	require_once("navbottom.inc.php");
?>
