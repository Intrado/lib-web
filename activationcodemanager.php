<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
include_once("inc/common.inc.php");
include_once("inc/form.inc.php");
include_once("inc/table.inc.php");
include_once("inc/html.inc.php");
include_once("inc/formatters.inc.php");
include_once("inc/date.inc.php");
include_once("obj/Person.obj.php");
include_once("obj/PeopleList.obj.php");
include_once("obj/ListEntry.obj.php");
include_once("obj/RenderedList.obj.php");
include_once("obj/Validator.obj.php");
require_once("obj/Form.obj.php");
include_once("obj/Rule.obj.php");
include_once("obj/FieldMap.obj.php");
require_once("obj/FormItem.obj.php");
require_once("obj/Phone.obj.php");
require_once("obj/Email.obj.php");
require_once("obj/Sms.obj.php");
require_once("inc/securityhelper.inc.php");
include_once("ruleeditform.inc.php");
require_once("inc/rulesutils.inc.php");
require_once("obj/FormRuleWidget.fi.php");
require_once("obj/SectionWidget.fi.php");
require_once("obj/ValSections.val.php");
require_once("inc/reportutils.inc.php");
require_once("list.inc.php");

include_once("obj/Address.obj.php");
include_once("obj/Language.obj.php");
include_once("obj/JobType.obj.php");

require_once("inc/reportgeneratorutils.inc.php");
require_once("obj/ReportGenerator.obj.php");
require_once("obj/ReportInstance.obj.php");
require_once("obj/UserSetting.obj.php");
require_once("inc/rulesutils.inc.php");
require_once("obj/PortalReport.obj.php");

////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////

if (!getSystemSetting("_hasportal", false) || !$USER->authorize('portalaccess')) {
	redirect('unauthorized.php');
}

////////////////////////////////////////////////////////////////////////////////
// Action/Request Processing
////////////////////////////////////////////////////////////////////////////////
$fields = FieldMap::getOptionalAuthorizedFieldMaps();// + FieldMap::getOptionalAuthorizedFieldMapsLike('g');
$generateBulkTokens = $USER->authorize('generatebulktokens');

if (isset($_GET['clear']) && $_GET['clear']) {
	activationcodemanager_clear_search_session();
}

if (isset($_GET['hideactivecodes'])) {
	$_SESSION['hideactivecodes'] = $_GET['hideactivecodes'] == "true" ? true : false;
}
if (isset($_GET['hideassociated'])) {
	$_SESSION['hideassociated'] = $_GET['hideassociated'] == "true" ? true : false;
}

$rulesjson = '';
if (isset($_GET['showall'])) {
	activationcodemanager_clear_search_session();
	$_SESSION['activationcodemanager_showall'] = true;
} else if (!empty($_SESSION['activationcodemanager_rules'])) {
	// NOTE: $_SESSION['activationcodemanager_rules'] may also contain array("fieldnum" => "organization", "val" => $organizationids)
	$rules = $_SESSION['activationcodemanager_rules'];
	if (is_array($rules))
		$rulesjson = json_encode(cleanObjects(array_values($rules)));
}

if ($generateBulkTokens && isset($_GET['generate'])) {
	$reportinstance = new ReportInstance();
	$reportinstance->setParameters(activationcodemanager_make_report_options());
	$reportgenerator = new PortalReport();
	$reportgenerator->reportinstance = $reportinstance;
	$reportgenerator->generateQuery();
	if ($reportgenerator->query) {
		$result = Query($reportgenerator->query);
		$data = array();
		$totalgenerated = 0;
		while ($row = DBGetRow($result)) {
			$data[] = $row[1];
			if (count($data) >= 10000) {
				$count = generatePersonTokens($data);
				if ($count)
					$totalgenerated += $count;
				else {
					$count = 0; // failure
					$data = array();
					break; // exit loop
				}
				$data = array();
			}
		}
		if (count($data) > 0) {
			$count = generatePersonTokens($data);
			if ($count)
				$totalgenerated += $count;
			else
				$count = 0; // failure
		}
		if ($totalgenerated > 0)
			notice(_L("%s activation codes have been generated.", number_format($totalgenerated)));
		else
			notice(_L("An unexpected error occurred.  Please try again."));
	}
	redirect();
}

//////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////
// APPLY REPORT GENERATOR SETTINGS
$reportinstance = new ReportInstance();
$reportinstance->setParameters(activationcodemanager_make_report_options());
$reportgenerator = new PortalReport();
$reportgenerator->reportinstance = $reportinstance;
$reportgenerator->userid = $USER->id;

if (isset($_GET['csv']) && $_GET['csv']) {
	$reportgenerator->format = "csv";
} else {
	$reportgenerator->format = "html";
}

// FORM DATA
$extrajs = '';
if ($generateBulkTokens) {
	$reportgenerator->generateQuery();
	$query = $reportgenerator->testquery;
	$result = ($query != "") ? QuickQuery($query) : false;
	$extrajs = ($result) ? "if(confirmGenerateActive())" : "if(confirmGenerate())";
}

$checkHideActiveCodes = (!empty($_SESSION['hideactivecodes'])) ? 'checked' : '';
$checkHideAssociated = (!empty($_SESSION['hideassociated'])) ? 'checked' : '';

$formdata = array();

if ($USER->hasSections()) {
	$formdata["toggles"] = array(
		"label" => _L('Search Options'),
		"control" => array("FormHtml", 'html' => "
			<input name='activationcodemanager_searchOption' id='searchByRules' type='radio' onclick=\"choose_search_by_rules();\"><label for='searchByRules'> Search by Rules </label>
			<input name='activationcodemanager_searchOption' id='searchBySections' type='radio' onclick=\"choose_search_by_sections();\"><label for='searchBySections'> Search by Sections </label>
		"),
		"helpstep" => 2
	);
}

$formdata["ruledata"] = array(
	"label" => _L('Search'),
	"value" => $rulesjson,
	"control" => array("FormRuleWidget"),
	"validators" => array(array('ValRules')),
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
		"control" => array("SectionWidget", "sectionids" => isset($_SESSION['activationcodemanager_sectionids']) ? $_SESSION['activationcodemanager_sectionids'] : array()),
		"helpstep" => 2
	);


	$formdata["sectionsearchbutton"] = array(
		"label" => _L(''),
		"control" => array("FormHtml", "html" => "<div id='sectionsearchButtonContainer'>" . submit_button(_L('Search'),'sectionsearch',"magnifier") . "</div>"),
		"helpstep" => 2
	);
}

$formdata["displayoptions"] = array(
	"label" => _L("Display Options"),
	"control" => array("FormHtml", "html" => "<div id='metadataDiv'></div>"),
	"helpstep" => 1
);

$formdata["filter"] = array(
	"label" => _L("Filter"),
	"control" => array("FormHtml", "html" => "
		<div><input type='checkbox' id='checkboxHideActiveCodes' onclick='location.href=\"?hideactivecodes=\" + this.checked' $checkHideActiveCodes><label for='checkboxHideActiveCodes'>"._L('Hide people with unexpired codes')."</label></div>
		<div><input type='checkbox' id='checkboxHideAssociated' onclick='location.href=\"?hideassociated=\" + this.checked' $checkHideAssociated><label for='checkboxHideAssociated'>"._L('Hide people with Contact Manager accounts')."</label></div>
	"),
	"helpstep" => 1
);

if (!empty($_SESSION['activationcodemanager_rules']) || !empty($_SESSION['activationcodemanager_sectionids']) || !empty($_SESSION['activationcodemanager_showall'])) {
	$formdata["outputformat"] = array(
		"label" => _L("Output Format"),
		"control" => array("FormHtml", "html" => "<a href='activationcodemanager.php/report.csv?csv=true'>CSV</a>"),
		"helpstep" => 1
	);
}

$buttons = array(
	icon_button(_L('Back'),"tick",null,"contacts.php"),
	submit_button(_L('Refresh'),"refresh","arrow_refresh"),
	icon_button(_L('Show All Contacts'),"tick",null,"?showall")
);
if ($generateBulkTokens)
	$buttons[] = icon_button("Generate Activation Codes", "tick", "$extrajs window.location='?generate=1'", "activationcodemanager.php");

$form = new Form('activationcodemanager',$formdata,array(),$buttons);
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
			switch ($button) {
				case 'addrule':
					activationcodemanager_clear_search_session('activationcodemanager_rules');
					$data = json_decode($postdata['ruledata']);
					if (isset($data->fieldnum, $data->logical, $data->op, $data->val)) {
						if ($data->fieldnum == 'organization') {
							$orgkeys = array();
							
							$organizations = $USER->organizations();
							foreach ($data->val as $id) {
								$id = $id + 0;
								$orgkeys[$id] = $organizations[$id]->orgkey;
							}
							
							if (!isset($_SESSION['activationcodemanager_rules']))
								$_SESSION['activationcodemanager_rules'] = array();
							
							$_SESSION['activationcodemanager_rules']["organization"] = array(
								"fieldnum" => "organization",
								"val" => $orgkeys
							);
						} else if ($type = Rule::getType($data->fieldnum)) {
							$data->val = prepareRuleVal($type, $data->op, $data->val);
							if ($rule = Rule::initFrom($data->fieldnum, $data->logical, $data->op, $data->val)) {
								if (!isset($_SESSION['activationcodemanager_rules']))
									$_SESSION['activationcodemanager_rules'] = array();
								$_SESSION['activationcodemanager_rules'][$data->fieldnum] = $rule;
							}
						}
					}
					$form->sendTo("activationcodemanager.php");
					break;

				case 'deleterule':
					activationcodemanager_clear_search_session('activationcodemanager_rules');
					if (!empty($_SESSION['activationcodemanager_rules'])) {
						$fieldnum = $postdata['ruledata'];
						unset($_SESSION['activationcodemanager_rules'][$fieldnum]);
					}
					$form->sendTo("activationcodemanager.php");
					break;

				case 'sectionsearch':
					if ($USER->hasSections()) {
						activationcodemanager_clear_search_session('activationcodemanager_sectionids');
						$_SESSION['activationcodemanager_sectionids'] = $postdata['sectionids'];
					}
					
					$form->sendTo("activationcodemanager.php");
					break;
					
				case 'refresh':
					$form->sendTo("activationcodemanager.php");
					break;

				case 'showall':
					activationcodemanager_clear_search_session();
					$_SESSION['activationcodemanager_showall'] = true;
					$form->sendTo("activationcodemanager.php");
					break;
			}
		} else {
			redirect("activationcodemanager.php");
		}
	}
}

////////////////////////////////////////////////////////////////////////////////
// Functions
////////////////////////////////////////////////////////////////////////////////
function activationcodemanager_clear_search_session($keep = false) {
	$_SESSION['saved_report'] = false;

	if ($keep != 'activationcodemanager_showall')
		$_SESSION['activationcodemanager_showall'] = false;

	if ($keep != 'activationcodemanager_rules')
		$_SESSION['activationcodemanager_rules'] = array();
		
	if ($keep != 'activationcodemanager_sectionids')
		$_SESSION['activationcodemanager_sectionids'] = array();
}


function activationcodemanager_make_report_options() {
	global $fields;

	$options = array("reporttype" => "portal");

	if (!empty($_SESSION['activationcodemanager_showall'])) {
		$options['showall'] = true;
	} else if (!empty($_SESSION['activationcodemanager_rules'])) {
		$rules = $_SESSION['activationcodemanager_rules'];
		$organizationids = isset($rules['organization']) ? array_keys($rules['organization']['val']) : array();
		unset($rules['organization']);
		$options['rules'] = $rules;
		$options['organizationids'] = $organizationids;
	} else if (!empty($_SESSION['activationcodemanager_sectionids'])) {
		$options['sectionids'] = $_SESSION['activationcodemanager_sectionids'];
	}

	$activefields = array();
	foreach ($fields as $field){
		// used in pdf
		if (isset($_SESSION['report']['fields'][$field->fieldnum]) && $_SESSION['report']['fields'][$field->fieldnum]){
			$activefields[] = $field->fieldnum;
		}
	}
	$options['activefields'] = implode(",",$activefields);

	$options['hideactivecodes'] = !empty($_SESSION['hideactivecodes']) ? true : false;
	$options['hideassociated'] = !empty($_SESSION['hideassociated']) ? true : false;

	$options['pagestart'] = isset($_GET['pagestart']) ? $_GET['pagestart'] : 0;
	return $options;
}

//index 4 is token
//index 5 is expiration date
function fmt_activation_code($row, $index){
	if($row[$index]){
		if(strtotime($row[5]) < strtotime("today")){
			return "Expired";
		}
	}
	return $row[$index];
}

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
if ($reportgenerator->format == "csv") {
	$reportgenerator->generate();
} else {
	$PAGE = "system:contacts";
	$TITLE = "Activation Code Manager";

	include_once("nav.inc.php");
	startWindow("Contact Search", "padding: 3px;");

	echo "<div id='metadataTempDiv' style='display:none'>";
		select_metadata("$('portalresults')", 5, $fields);
	echo "</div>";

	?>
		<script type="text/javascript">
			<? Validator::load_validators(array("ValSections", "ValRules")); ?>
		</script>
	<?

	echo $form->render();

	if (isset($formdata['outputformat'])) {
		$reportgenerator->generate();
	}

	endWindow();

	?>
		<script type="text/javascript">
			document.observe('dom:loaded', function() {
				ruleWidget.delayActions = true;
				ruleWidget.container.observe('RuleWidget:AddRule', rulewidget_add_rule);
				ruleWidget.container.observe('RuleWidget:DeleteRule', rulewidget_delete_rule);

				$('metadataDiv').update($('metadataTempDiv').innerHTML);
				
				<?
					if (!empty($_SESSION['activationcodemanager_rules']) || empty($_SESSION['activationcodemanager_sectionids']))
						echo 'choose_search_by_rules();';
					else if (!empty($_SESSION['activationcodemanager_sectionids']))
						echo 'choose_search_by_sections();';
				?>
			});

			function rulewidget_add_rule(event) {
				$('activationcodemanager_ruledata').value = event.memo.ruledata.toJSON();
				form_submit(event, 'addrule');
			}

			function rulewidget_delete_rule(event) {
				$('activationcodemanager_ruledata').value = event.memo.fieldnum;
				form_submit(event, 'deleterule');
			}

			function confirmGenerate () {
			<?
				if($reportgenerator->reporttotal > 0) {
					$str = addslashes(_L("Are you sure you want to generate activation codes for these people?"));
					echo "
						return confirm('$str');
					";
				} else {
					$str = addslashes(_L("There are no people in this list."));
					echo "
						window.alert('$str');
						return false;
					";
				}
			?>
			}

			function confirmGenerateActive () {
			<?
				$str = addslashes(_L("Some activation codes exist in this list.  Are you sure you want to overwrite them?"));
				echo "
					return confirm('$str');
				";
			?>
			}
			
			function choose_search_by_rules() {
				$('searchByRules').checked = true;
				$('ruleWidgetContainer').up('tr').show();
			<? if ($USER->hasSections()) { ?>
				$('searchBySections').checked = false;
				$('<?=$form->name?>_sectionids_fieldarea').hide();
				$('sectionsearchButtonContainer').up('tr').hide();
			<? } ?>
			}

			function choose_search_by_sections() {
				$('searchByRules').checked = false;
				$('ruleWidgetContainer').up('tr').hide();
			<? if ($USER->hasSections()) { ?>
				$('searchBySections').checked = true;
				$('<?=$form->name?>_sectionids_fieldarea').show();
				$('sectionsearchButtonContainer').up('tr').show();
			<? } ?>
			}
		</script>
	<?

	include_once("navbottom.inc.php");
}
?>
