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
require_once("obj/ValRules.val.php");
require_once("inc/reportutils.inc.php");
include_once("obj/Address.obj.php");
include_once("obj/Language.obj.php");
include_once("obj/JobType.obj.php");
require_once("inc/utils.inc.php");
require_once("inc/reportgeneratorutils.inc.php");
require_once("obj/FieldMap.obj.php");
require_once("obj/ReportGenerator.obj.php");
require_once("obj/ReportInstance.obj.php");
require_once("obj/UserSetting.obj.php");
require_once("obj/ContactsReport.obj.php");

////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////

if (!$USER->authorize('viewcontacts')) {
	redirect('unauthorized.php');
}

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

if (!empty($_GET['clear'])) {
	$_SESSION['systemcontact_orderby'] = array();
	systemcontact_clear_search_session();
}

////////////////////////////////////////////////////////////////////////////////
// Action/Request Processing
////////////////////////////////////////////////////////////////////////////////

$rulesjson = '';
if (isset($_GET['showall'])) {
	systemcontact_clear_search_session();
	$_SESSION['systemcontact_showall'] = true;
} else if (!empty($_SESSION['systemcontact_rules'])) {
	// NOTE: $_SESSION['systemcontact_rules'] may also contain array("fieldnum" => "organization", "val" => $organizationids)
	$rules = $_SESSION['systemcontact_rules'];
	if (is_array($rules))
		$rulesjson = json_encode(cleanObjects(array_values($rules)));
}

////////////////////////////////////////////////////////////////////////////////
// Form Data
////////////////////////////////////////////////////////////////////////////////

$fields = FieldMap::getOptionalAuthorizedFieldMapsLike('f') + FieldMap::getOptionalAuthorizedFieldMapsLike('g');
$ordering = ContactsReport::getOrdering();
$manageActivationCodes = getSystemSetting("_hasportal", false) && $USER->authorize('portalaccess');

$formdata = array();

$formdata["toggles"] = array(
	"label" => _L('Search Options'),
	"control" => array("FormHtml", 'html' => "
		<input name='systemcontact_searchOption' id='searchByRules' type='radio' onclick=\"choose_search_by_rules();\"><label for='searchByRules'> Search by Rules </label>" .
		(getSystemSetting('_hasenrollment') ?
			"<input name='systemcontact_searchOption' id='searchBySections' type='radio' onclick=\"choose_search_by_sections();\"><label for='searchBySections'> Search by Sections </label>" :
			""
		) .
		"<input name='systemcontact_searchOption' id='searchByPerson' type='radio' onclick=\"choose_search_by_person();\"><label for='searchByPerson'> Search for Person </label>
	"),
	"helpstep" => 2
);

$formdata["ruledata"] = array(
	"label" => _L('Criteria'),
	"value" => $rulesjson,
	"control" => array("FormRuleWidget"),
	"validators" => array(array('ValRules')),
	"helpstep" => 2
);

if (getSystemSetting('_hasenrollment')) {
	$formdata["sectionids"] = array(
		"label" => _L('Sections'),
		"fieldhelp" => _L('Select sections from an organization.'),
		"value" => isset($_SESSION['systemcontact_sectionids']) && count($_SESSION['systemcontact_sectionids']) > 0 ?
			QuickQueryList("select id, skey from section where id in (" .implode(",", $_SESSION['systemcontact_sectionids']) . ")", true, false) :
			array(),
		"validators" => array(
			array("ValSections")
		),
		"control" => array("SectionWidget"),
		"helpstep" => 2
	);

	$formdata["sectionsearchbutton"] = array(
		"label" => _L(''),
		"control" => array("FormHtml", "html" => "<div id='sectionsearchButtonContainer'>" . submit_button(_L('Search'),'sectionsearch',"magnifier") . "</div>"),
		"helpstep" => 2
	);
}
	
$formdata["pkey"] = array(
	"label" => _L('Person ID'),
	"value" => !empty($_SESSION['systemcontact_pkey']) ? $_SESSION['systemcontact_pkey'] : '',
	"validators" => array(),
	"control" => array("TextField"),
	"helpstep" => 3
);
$formdata["phone"] = array(
	"label" => _L('Phone Number'),
	"value" => !empty($_SESSION['systemcontact_phone']) ? $_SESSION['systemcontact_phone'] : '',
	"validators" => array(array("ValPhone")),
	"control" => array("TextField"),
	"helpstep" => 3
);
$formdata["email"] = array(
	"label" => _L('Email Address'),
	"value" => !empty($_SESSION['systemcontact_email']) ? $_SESSION['systemcontact_email'] : '',
	"validators" => array(array("ValEmail")),
	"control" => array("TextField"),
	"helpstep" => 3
);

$formdata["personsearchbutton"] = array(
	"label" => _L(''),
	"control" => array("FormHtml", "html" => "<div id='personsearchButtonContainer'>" . submit_button(_L('Search'),'personsearch',"magnifier") . "</div>"),
	"helpstep" => 3
);

$formdata["displayoptions"] = array(
	"label" => _L("Display Fields"),
	"control" => array("FormHtml", "html" => "<div id='metadataDiv'></div>"),
	"helpstep" => 1
);

$formdata["multipleorderby"] = array(
	"label" => _L('Sort By'),
	"value" => !empty($_SESSION['systemcontact_orderby']) ? $_SESSION['systemcontact_orderby'] : '',
	"control" => array("MultipleOrderBy", "count" => 3, "values" => $ordering),
	"validators" => array(),
	"helpstep" => 1
);

$buttons = array(
	submit_button(_L('Refresh'),"refresh","arrow_refresh"),
	icon_button(_L('Show All Contacts'),"tick",null,"contacts.php?showall")
);
if ($manageActivationCodes)
	$buttons[] = icon_button("Manage Activation Codes", "tick", null, "activationcodemanager.php");
$form = new Form('systemcontact',$formdata,array(),$buttons);
$form->ajaxsubmit = true;

////////////////////////////////////////////////////////////////////////////////
// Form Data Handling
////////////////////////////////////////////////////////////////////////////////
$form->handleRequest();

$datachange = false;
$errors = false;

//check for form submission
if ($button = $form->getSubmit()) { //checks for submit and merges in post data
	$ajax = $form->isAjaxSubmit(); //whether or not this requires an ajax response	
	
	if (($errors = $form->validate()) === false) { //checks all of the items in this form
		$postdata = $form->getData(); //gets assoc array of all values {name:value,...}

		if (isset($postdata['multipleorderby'])) {
			$multipleorderby = $postdata['multipleorderby'];
			if (is_array($multipleorderby)) {
				$_SESSION['systemcontact_orderby'] = array();
				foreach ($multipleorderby as $i=>$orderby) {
					if (in_array($orderby, $ordering))
						$_SESSION['systemcontact_orderby'][] = $orderby;
				}
			}
		}
		
		if ($ajax) {
			switch ($button) {
				case 'addrule':
					systemcontact_clear_search_session('systemcontact_rules');
					$data = json_decode($postdata['ruledata']);
					if (isset($data->fieldnum, $data->logical, $data->op, $data->val)) {
						if ($data->fieldnum == 'organization') {
							$orgkeys = array();
							
							$organizations = Organization::getAuthorizedOrgKeys();
							foreach ($data->val as $id) {
								$id = $id + 0;
								$orgkeys[$id] = $organizations[$id];
							}
							
							if (!isset($_SESSION['systemcontact_rules']))
								$_SESSION['systemcontact_rules'] = array();
							
							$_SESSION['systemcontact_rules']["organization"] = array(
								"fieldnum" => "organization",
								"val" => $orgkeys
							);
						} else if ($type = Rule::getType($data->fieldnum)) {
							$data->val = prepareRuleVal($type, $data->op, $data->val);
							if ($rule = Rule::initFrom($data->fieldnum, $data->logical, $data->op, $data->val)) {
								if (!isset($_SESSION['systemcontact_rules']))
									$_SESSION['systemcontact_rules'] = array();
								$_SESSION['systemcontact_rules'][$data->fieldnum] = $rule;
							}
						}
					}
					$form->sendTo("contacts.php");
					break;
					
				case 'deleterule':
					systemcontact_clear_search_session('systemcontact_rules');
					if (!empty($_SESSION['systemcontact_rules'])) {
						$fieldnum = $postdata['ruledata'];
						unset($_SESSION['systemcontact_rules'][$fieldnum]);
					}
					$form->sendTo("contacts.php");
					break;
					
				case 'sectionsearch':
					if (getSystemSetting('_hasenrollment')) {
						systemcontact_clear_search_session('systemcontact_sectionids');
						$_SESSION['systemcontact_sectionids'] = explode(",", $postdata['sectionids']);
					}
					$form->sendTo("contacts.php");
					break;
					
				case 'personsearch':
					systemcontact_clear_search_session();
					$_SESSION['systemcontact_person'] = true;
					$_SESSION['systemcontact_pkey'] = isset($postdata['pkey']) ? $postdata['pkey'] : false;
					$_SESSION['systemcontact_phone'] = isset($postdata['phone']) ? Phone::parse($postdata['phone']) : false;
					$_SESSION['systemcontact_email'] = isset($postdata['email']) ? $postdata['email'] : false;	
					$form->sendTo("contacts.php");
					break;
					
				case 'refresh':
					$form->sendTo("contacts.php");
					break;

				case 'showAll':			
					systemcontact_clear_search_session();
					$_SESSION['systemcontact_showall'] = true;
					$form->sendTo("contacts.php");
					break;
			}
		} else {
			redirect("contacts.php");
		}
	}
}

////////////////////////////////////////////////////////////////////////////////
// Functions
////////////////////////////////////////////////////////////////////////////////

function systemcontact_clear_search_session($keep = false) {
	$_SESSION['saved_report'] = false;
		
	if ($keep != 'systemcontact_showall')
		$_SESSION['systemcontact_showall'] = false;
	
	if ($keep != 'systemcontact_sectionids') {
		$_SESSION['systemcontact_sectionids'] = array();
	}
	
	if ($keep != 'systemcontact_person') {
		$_SESSION['systemcontact_person'] = false;
		$_SESSION['systemcontact_pkey'] = false;
		$_SESSION['systemcontact_phone'] = false;
		$_SESSION['systemcontact_email'] = false;
	}
	
	if ($keep != 'systemcontact_rules')
		$_SESSION['systemcontact_rules'] = array();
}

function systemcontact_make_report_options() {
	global $fields;
	
	$options = array("reporttype" => "contacts");
	
	if (!empty($_SESSION['systemcontact_showall'])) {
		$options['showall'] = true;
	} else if (!empty($_SESSION['systemcontact_person'])) {
		if (!empty($_SESSION['systemcontact_pkey']))
			$options['personid'] = $_SESSION['systemcontact_pkey'];
		if (!empty($_SESSION['systemcontact_phone']))
			$options['phone'] = $_SESSION['systemcontact_phone'];
		if (!empty($_SESSION['systemcontact_email']))
			$options['email'] = $_SESSION['systemcontact_email'];
	} else if (!empty($_SESSION['systemcontact_rules'])) {
		$rules = $_SESSION['systemcontact_rules'];
		$organizationids = isset($rules['organization']) ? array_keys($rules['organization']['val']) : array();
		unset($rules['organization']);
		
		$options['rules'] = $rules;
		$options['organizationids'] = $organizationids;
	} else if (!empty($_SESSION['systemcontact_sectionids'])) {
		$options['sectionids'] = $_SESSION['systemcontact_sectionids'];
	}
	
	if (!empty($_SESSION['systemcontact_orderby'])) {
		foreach ($_SESSION['systemcontact_orderby'] as $i => $orderby) {
			$options["order" . ($i+1)] = $orderby;
		}
	}
	
	$activefields = array();
	foreach ($fields as $field){
		// used in pdf
		if (isset($_SESSION['report']['fields'][$field->fieldnum]) && $_SESSION['report']['fields'][$field->fieldnum]){
			$activefields[] = $field->fieldnum;
		}
	}
	$options['activefields'] = implode(",",$activefields);
	
	$options['pagestart'] = isset($_GET['pagestart']) ? $_GET['pagestart'] : 0;
	
	return $options;
}

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////


$PAGE = "system:contacts";
$TITLE = "Contact Database";

include_once("nav.inc.php");
?>

<script type="text/javascript">
	<? Validator::load_validators(array("ValSections", "ValRules")); ?>

	document.observe('dom:loaded', function() {
		ruleWidget.delayActions = true;
		ruleWidget.container.observe('RuleWidget:AddRule', rulewidget_add_rule);
		ruleWidget.container.observe('RuleWidget:DeleteRule', rulewidget_delete_rule);
		
		if ($('metadataTempDiv'))
			$('metadataDiv').update($('metadataTempDiv').innerHTML);
		else 
			$('metadataDiv').up('tr').hide();
			
		<?
			if (!empty($_SESSION['systemcontact_rules']) || (empty($_SESSION['systemcontact_sectionids']) && empty($_SESSION['systemcontact_person'])))
				echo 'choose_search_by_rules();';
			else if (!empty($_SESSION['systemcontact_sectionids']))
				echo 'choose_search_by_sections();';
			else
				echo 'choose_search_by_person();';
		?>
	});

	function choose_search_by_rules() {
		$('searchByRules').checked = true;
		$('ruleWidgetContainer').up('tr').show();
	<? if (getSystemSetting('_hasenrollment')) { ?>
		$('searchBySections').checked = false;
		$('<?=$form->name?>_sectionids_fieldarea').hide();
		$('sectionsearchButtonContainer').up('tr').hide();
	<? } ?>
		$('searchByPerson').checked = false;
		$('<?=$form->name?>_pkey').up('tr').hide();
		$('<?=$form->name?>_phone').up('tr').hide();
		$('<?=$form->name?>_email').up('tr').hide();
		$('personsearchButtonContainer').up('tr').hide();
	}

	function choose_search_by_sections() {
		$('searchByRules').checked = false;
		$('ruleWidgetContainer').up('tr').hide();
	<? if (getSystemSetting('_hasenrollment')) { ?>
		$('searchBySections').checked = true;
		$('<?=$form->name?>_sectionids_fieldarea').show();
		$('sectionsearchButtonContainer').up('tr').show();
	<? } ?>
		$('searchByPerson').checked = false;
		$('<?=$form->name?>_pkey').up('tr').hide();
		$('<?=$form->name?>_phone').up('tr').hide();
		$('<?=$form->name?>_email').up('tr').hide();
		$('personsearchButtonContainer').up('tr').hide();
	}
	
	function choose_search_by_person() {
		$('searchByRules').checked = false;
		$('ruleWidgetContainer').up('tr').hide();
	<? if (getSystemSetting('_hasenrollment')) { ?>
		$('searchBySections').checked = false;
		$('<?=$form->name?>_sectionids_fieldarea').hide();
		$('sectionsearchButtonContainer').up('tr').hide();
	<? } ?>
		$('searchByPerson').checked = true;
		$('<?=$form->name?>_pkey').up('tr').show();
		$('<?=$form->name?>_phone').up('tr').show();
		$('<?=$form->name?>_email').up('tr').show();
		$('personsearchButtonContainer').up('tr').show();
	}
	
	function systemcontact_clear_person() {
		$('systemcontact_pkey').value = '';
		$('systemcontact_phone').value = '';
		$('systemcontact_email').value = '';
	}
	
	function rulewidget_add_rule(event) {
		$('systemcontact_ruledata').value = event.memo.ruledata.toJSON();
		systemcontact_clear_person();
		form_submit(event, 'addrule');
	}

	function rulewidget_delete_rule(event) {
		$('systemcontact_ruledata').value = event.memo.fieldnum;
		systemcontact_clear_person();
		form_submit(event, 'deleterule');
	}
</script>
<?
startWindow("Options");
	echo $form->render();
endWindow();

if (!empty($_SESSION['systemcontact_showall']) || !empty($_SESSION['systemcontact_sectionids']) || !empty($_SESSION['systemcontact_person']) || !empty($_SESSION['systemcontact_rules'])) {
	echo "<div id='metadataTempDiv' style='display:none'>";
		select_metadata("$('searchresults')", 6, $fields);
	echo "</div>";
	$reportinstance = new ReportInstance();
	$reportinstance->setParameters(systemcontact_make_report_options());
	$reportgenerator = new ContactsReport();
	$reportgenerator->reportinstance = $reportinstance;
	$reportgenerator->userid = $USER->id;
	$reportgenerator->format = "html";
	$reportgenerator->generate();
}

include_once("navbottom.inc.php");
?>
