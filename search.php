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

////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$USER->authorize('createlist')) {
	redirect('unauthorized.php');
}

////////////////////////////////////////////////////////////////////////////////
// Action/Request Processing
////////////////////////////////////////////////////////////////////////////////

if (isset($_SESSION['listsearchpreview']))
	$containerID = 'listPreviewContainer';
else
	$containerID = 'listSearchContainer';

$list = new PeopleList(isset($_SESSION['listid']) ? $_SESSION['listid'] : null);
if (!userOwns('list', $list->id)) {
	redirect('lists.php');
}
if (!$renderedlist = new RenderedList($list)) {
	redirect('list.php');
}

$rulewidgetvaluejson = '';

if (isset($_GET['showall'])) {
	list_clear_search_session();
	$_SESSION['listsearchshowall'] = true;
} else {
	// NOTE: $_SESSION['listsearchrules'] may also contain array("fieldnum" => "organization", "val" =>  $organizationids)
	$rulewidgetdata = array();
	
	if (isset($_SESSION['listsearchrules']) && count($_SESSION['listsearchrules']) > 0) {
		$rules = $_SESSION['listsearchrules'];
		$rulewidgetdata = cleanObjects(array_values($rules));
	}
	
	if (count($rulewidgetdata) > 0)
		$rulewidgetvaluejson = json_encode($rulewidgetdata);
}

list_handle_ajax_table($renderedlist, array($containerID));

////////////////////////////////////////////////////////////////////////////////
// Form Data
////////////////////////////////////////////////////////////////////////////////

$formdata = array();

if (!isset($_SESSION['listsearchpreview'])) {
	$formdata["toggles"] = array(
		"label" => _L('Search Options'),
		"control" => array("FormHtml", 'html' => "
			<input id='searchByRules' type='radio' onclick=\"choose_search_by_rules();\"><label for='searchByRules'> Search by Rules </label>" .
			($USER->hasSections() ?
				"<input id='searchBySections' type='radio' onclick=\"choose_search_by_sections();\"><label for='searchBySections'> Search by Sections </label>" :
				""
			) .
			"<input id='searchByPerson' type='radio' onclick=\"choose_search_by_person();\"><label for='searchByPerson'> Search for Person </label>
		"),
		"helpstep" => 2
	);
	
	$formdata["ruledata"] = array(
		"label" => _L('Rules'),
		"value" => $rulewidgetvaluejson,
		"control" => array("FormRuleWidget"),
		"validators" => array(array('ValRules')),
		"helpstep" => 2
	);
	
	if ($USER->hasSections()) {
		$formdata["sectionids"] = array(
			"label" => _L('Sections'),
			"fieldhelp" => _L('Select sections from an organization.'),
			"value" => "",
			"validators" => array(
				array("ValSections")
			),
			"control" => array("SectionWidget", "sectionids" => isset($_SESSION['listsearchsectionids']) ? $_SESSION['listsearchsectionids'] : array()),
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
		"value" => !empty($_SESSION['listsearchpkey']) ? $_SESSION['listsearchpkey'] : '',
		"validators" => array(
		),
		"control" => array("TextField"),
		"helpstep" => 2
	);
	$formdata["phone"] = array(
		"label" => _L('Phone or SMS Number'),
		"value" => !empty($_SESSION['listsearchphone']) ? $_SESSION['listsearchphone'] : '',
		"validators" => array(array("ValPhone")),
		"control" => array("TextField"),
		"helpstep" => 2
	);
	$formdata["email"] = array(
		"label" => _L('Email Address'),
		"value" => !empty($_SESSION['listsearchemail']) ? $_SESSION['listsearchemail'] : '',
		"validators" => array(array("ValEmail")),
		"control" => array("TextField"),
		"helpstep" => 2
	);

	$formdata["personsearchbutton"] = array(
		"label" => _L(''),
		"control" => array("FormHtml", "html" => "<div id='personsearchButtonContainer'>" . submit_button(_L('Search'),'personsearch',"magnifier") . "</div>"),
		"helpstep" => 2
	);
}

$buttons = array(
	submit_button(_L('Refresh'),"refresh","arrow_refresh"),
);
if (!isset($_SESSION['listsearchpreview']))
	$buttons[] = icon_button(_L('Show All Contacts'),"tick",null,"search.php?showall");
$buttons[] = icon_button(_L('Done'),"tick",null, isset($_SESSION['previewfrom']) ? $_SESSION['previewfrom'] : "list.php");

$form = new Form('listsearch',$formdata,array(),$buttons);
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

		if ($ajax) {
			switch ($button) {
				case 'addrule':
					list_clear_search_session('listsearchrules');
					$data = json_decode($postdata['ruledata']);
					if (isset($data->fieldnum, $data->logical, $data->op, $data->val)) {
						if ($data->fieldnum == 'organization') {
							$orgkeys = array();
							
							$organizations = $USER->organizations();
							foreach ($data->val as $id) {
								$id = $id + 0;
								$orgkeys[$id] = $organizations[$id]->orgkey;
							}
							
							if (!isset($_SESSION['listsearchrules']))
								$_SESSION['listsearchrules'] = array();
							
							$_SESSION['listsearchrules']['organization'] = array(
								"fieldnum" => "organization",
								"val" => $orgkeys
							);
						} else if ($type = Rule::getType($data->fieldnum)) {
							$data->val = prepareRuleVal($type, $data->op, $data->val);
							
							if ($rule = Rule::initFrom($data->fieldnum, $data->logical, $data->op, $data->val)) {
								if (!isset($_SESSION['listsearchrules']))
									$_SESSION['listsearchrules'] = array();
								$_SESSION['listsearchrules'][$data->fieldnum] = $rule;
							}
						}
					}
					$form->sendTo("search.php");
					break;
					
				case 'deleterule':
					list_clear_search_session('listsearchrules');
					if (!empty($_SESSION['listsearchrules'])) {
						$fieldnum = $postdata['ruledata'];
						unset($_SESSION['listsearchrules'][$fieldnum]);
					}
					$form->sendTo("search.php");
					break;
					
				case 'sectionsearch':
					if ($USER->hasSections()) {
						list_clear_search_session('listsearchsectionids');
						$_SESSION['listsearchsectionids'] = $postdata['sectionids'];
					}
					$form->sendTo("search.php");
					break;
					
				case 'personsearch':
					list_clear_search_session();
					$_SESSION['listsearchperson'] = true;
					$_SESSION['listsearchpkey'] = isset($postdata['pkey']) ? $postdata['pkey'] : false;
					$_SESSION['listsearchphone'] = isset($postdata['phone']) ? Phone::parse($postdata['phone']) : false;
					$_SESSION['listsearchemail'] = isset($postdata['email']) ? $postdata['email'] : false;	
					$form->sendTo("search.php");
					break;
					
				case 'refresh':
					$form->sendTo("search.php");
					break;
			}
		} else {
			redirect("list.php");
		}
	}
}

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$PAGE = "notifications:lists";
$TITLE = ($containerID == 'listSearchContainer' ? "List Search" : "List Preview" ) . ": " . escapehtml($list->name);
include_once("nav.inc.php");
?>

<script type="text/javascript">
	var notpreview = <?= !isset($_SESSION['listsearchpreview']) ? "true;" : "false;" ?>

	<? if (!isset($_SESSION['listsearchpreview'])) {
		Validator::load_validators(array("ValSections", "ValRules"));
	} ?>

	function choose_search_by_rules() {
		$('searchByRules').checked = true;
		$('ruleWidgetContainer').up('tr').show();
	<? if ($USER->hasSections()) { ?>
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
	<? if ($USER->hasSections()) { ?>
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
	<? if ($USER->hasSections()) { ?>
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

	function list_clear_person() {
		if (notpreview) {
			$('listsearch_pkey').value = '';
			$('listsearch_phone').value = '';
			$('listsearch_email').value = '';
		}
	}
	
	function rulewidget_add_rule(event) {
		if (notpreview) {
			$('listsearch_ruledata').value = event.memo.ruledata.toJSON();
			list_clear_person();
			form_submit(event, 'addrule');
		}
	}

	function rulewidget_delete_rule(event) {
		if (notpreview) {
			$('listsearch_ruledata').value = event.memo.fieldnum;
			list_clear_person();
			form_submit(event, 'deleterule');
		}
	}
	
	document.observe('dom:loaded', function() {
		if (notpreview) {
			ruleWidget.delayActions = true;
			ruleWidget.container.observe('RuleWidget:AddRule', rulewidget_add_rule);
			ruleWidget.container.observe('RuleWidget:DeleteRule', rulewidget_delete_rule);
			
			<?
				if (!empty($_SESSION['listsearchrules']) || (empty($_SESSION['listsearchperson']) && empty($_SESSION['listsearchsectionids'])))
					echo 'choose_search_by_rules();';
				else if (!empty($_SESSION['listsearchsectionids']))
					echo 'choose_search_by_sections();';
				else
					echo 'choose_search_by_person();';
			?>
		}
		
		$('<?=$containerID?>').update('<?=addslashes(list_get_results_html($containerID, $renderedlist))?>');
	});
</script>
<?
if (!isset($_SESSION['listsearchpreview']))
	startWindow("Search Options");
	
echo $form->render();

if (!isset($_SESSION['listsearchpreview']))
	endWindow();

startWindow("Search Results");
	echo "<div id='$containerID'></div>";
endWindow();

include_once("navbottom.inc.php");
?>
