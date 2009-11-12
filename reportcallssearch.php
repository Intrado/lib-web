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
require_once("obj/PeopleList.obj.php");
require_once("obj/ListEntry.obj.php");
require_once("obj/RenderedList.obj.php");
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
require_once("obj/Job.obj.php");
require_once("inc/reportutils.inc.php");
require_once('list.inc.php');

require_once("obj/Address.obj.php");
require_once("obj/Language.obj.php");
require_once("obj/JobType.obj.php");

require_once("inc/reportgeneratorutils.inc.php");
require_once("obj/ReportGenerator.obj.php");
require_once("obj/CallsReport.obj.php");
require_once("obj/ReportInstance.obj.php");
require_once("obj/ReportSubscription.obj.php");
require_once("obj/UserSetting.obj.php");
require_once("inc/rulesutils.inc.php");
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
if (isset($_GET['clear']) && $_GET['clear']) {
	unset($_SESSION['reportid']);
	unset($_SESSION['report']['options']);
}

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////
if (!isset($_SESSION['report']['options']))
	$_SESSION['report']['options'] = array('reporttype' => 'contacthistory');

$fields = FieldMap::getOptionalAuthorizedFieldMaps() + FieldMap::getOptionalAuthorizedFieldMapsLike('g');
$activefields = array();
foreach ($fields as $field) {
	// used in pdf,csv
	if(isset($_SESSION['report']['fields'][$field->fieldnum]) && $_SESSION['report']['fields'][$field->fieldnum]){
		$activefields[] = $field->fieldnum;
	}
}
$_SESSION['report']['options']['activefields'] = implode(",",$activefields);

////////////////////////////////////////////////////////////////////////////////
// FORM DATA
////////////////////////////////////////////////////////////////////////////////
if (getSystemSetting('_hassurvey', true))
	$jobtypeobjs = DBFindMany("JobType", "from jobtype where deleted = '0'");
else
	$jobtypeobjs = DBFindMany("JobType", "from jobtype where not issurvey and deleted = '0'");
$jobtypenames = array();
foreach($jobtypeobjs as $jobtype){
	$jobtypenames[$jobtype->id] = $jobtype->name;
}

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
	"declined" => "No Destination Selected");

$options = $_SESSION['report']['options'];

if (!empty($options['personid'])) {
	$searchmethod = 'personid';
	$searchvalue = $options['personid'];
} else if (!empty($options['phone'])) {
	$searchmethod = 'phone';
	$searchvalue = $options['phone'];
} else if (!empty($options['email'])) {
	$searchmethod = 'email';
	$searchvalue = $options['email'];
} else {
	$searchmethod = 'personid';
	$searchvalue = '';
}

$rulesjson = '';
if (!empty($options['rules'])) {
	$rules = $options['rules'];
	if (is_array($rules))
		$rulesjson = json_encode(cleanObjects(array_values($rules)));
}

$savedjobtypes = array();
if(isset($options['jobtypes'])){
	$savedjobtypes = explode("','", $options['jobtypes']);
}

$savedresults = array();
if(isset($options['results'])){
	$savedresults = explode("','", $options['results']);
}


$formdata = array();

$formdata["searchmethod"] = array(
	"label" => _L("Search By"),
	"value" => $searchmethod,
	"control" => array("RadioButton", "values" => array(
			"personid" => _L("Person ID"), // pkey
			"phone" => _L("Phone"),
			"email" => _L("Email")
		)
	),
	"validators" => array(array("ValRequired"), array("ValInArray", "values" => array(
			"personid", // pkey
			"phone",
			"email"
		)
	)),
	"helpstep" => 1
);

$formdata["searchvalue"] = array(
	"label" => _L("Search Value"),
	"value" => $searchvalue,
	"control" => array("TextField"),
	"validators" => array(
		array("ValRequired"),
		array("ValPhone", "field" => "searchmethod", "requirefieldvalue" => "phone"),
		array("ValEmail", "field" => "searchmethod", "requirefieldvalue" => "email")
	),
	"requires" => array("searchmethod"),
	"helpstep" => 1
);

$formdata[] = _L("Filter By");
$formdata["ruledata"] = array(
	"label" => _L('Criteria'),
	"value" => $rulesjson,
	"control" => array("FormRuleWidget", "allowedFields" => array('f','g')),
	"validators" => array(array('ValRules', "allowedFields" => array('f','g'))),
	"helpstep" => 1
);

$formdata["dateoptions"] = array(
	"label" => _L("Date Options"),
	"value" => json_encode(array(
		"reldate" => isset($options['reldate']) ? $options['reldate'] : 'xdays',
		"xdays" => isset($options['lastxdays']) ? $options['lastxdays'] : '7',
		"startdate" => isset($options['startdate']) ? $options['startdate'] : '',
		"enddate" => isset($options['enddate']) ? $options['enddate'] : ''
	)),
	"control" => array("ReldateOptions", "rangedonly" => true),
	"validators" => array(array("ValRequired"), array("ValReldate", "rangedonly" => true, "defaultxdays" => 7)),
	"helpstep" => 1
);

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
	"value" => isset($options['results']) ? 1 : 0,
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

$buttons = array(
	icon_button(_L('Back'), 'tick', null, 'reports.php'),
	submit_button(_L("View Report"),"view","arrow_refresh")
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

	if (($errors = $form->validate()) === false) { //checks all of the items in this form
		$postdata = $form->getData(); //gets assoc array of all values {name:value,...}

		if ($ajax) {
			if (in_array($button,array('addrule','deleterule','view'))) {
				if (isset($_SESSION['report']['options']['rules']))
					$rules = $_SESSION['report']['options']['rules'];
				$_SESSION['report']['options'] = array(
					'reporttype' => 'contacthistory',
					'rules' => isset($rules) ? $rules : array()
				);

				if ($postdata['searchmethod'] == 'personid')
					$_SESSION['report']['options']['personid'] = $postdata['searchvalue'];
				if ($postdata['searchmethod'] == 'phone')
					$_SESSION['report']['options']['phone'] = Phone::parse($postdata['searchvalue']);
				if ($postdata['searchmethod'] == 'email')
					$_SESSION['report']['options']['email'] = $postdata['searchvalue'];

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
					$_SESSION['report']['options']['results'] = implode("','", $temp);
				}

				switch ($button) {
					case 'addrule':
						$data = json_decode($postdata['ruledata']);
						if (isset($data->fieldnum, $data->logical, $data->op, $data->val) && $type = Rule::getType($data->fieldnum)) {
							$data->val = prepareRuleVal($type, $data->op, $data->val);
							if ($rule = Rule::initFrom($data->fieldnum, $data->logical, $data->op, $data->val)) {
								if (!isset($_SESSION['report']['options']['rules']))
									$_SESSION['report']['options']['rules'] = array();
								$_SESSION['report']['options']['rules'][$data->fieldnum] = $rule;
							}
						}
						$form->sendTo("reportcallssearch.php");
						break;

					case 'deleterule':
						if (!empty($_SESSION['report']['options']['rules'])) {
							$fieldnum = $postdata['ruledata'];
							unset($_SESSION['report']['options']['rules'][$fieldnum]);
						}
						$form->sendTo("reportcallssearch.php");
						break;

					case 'view':
						$form->sendTo("reportcallsresult.php");
						break;
				}
			}
		} else {
			redirect("reportcallssearch.php");
		}
	}
}//NEW

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$PAGE = "reports:reports";
$TITLE = "Contact History";
if(isset($_SESSION['reportid'])){
	$subscription = new ReportSubscription($_SESSION['reportid']);
	$TITLE .= ": " . escapehtml($subscription->name);
}

require_once("nav.inc.php");

startWindow(_L("Person Notification Search"), "padding: 3px;");

	echo "<div id='metadataTempDiv' style='display:none'>";
		select_metadata(null, null, $fields);
	echo "</div>";

	?>
		<script type="text/javascript">
			<? Validator::load_validators(array("ValRules", "ValReldate")); ?>
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

