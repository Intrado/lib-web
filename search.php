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
require_once("inc/reportutils.inc.php");
require_once('list.inc.php');

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

$rulesjson = '';

if (isset($_GET['showall'])) {
	list_clear_search_session();
	$_SESSION['listsearchshowall'] = true;
} else if (!empty($_SESSION['listsearchrules'])) {
	$rules = $_SESSION['listsearchrules'];
	if (is_array($rules))
		$rulesjson = json_encode(cleanObjects(array_values($rules)));
}

list_handle_ajax_table($renderedlist, array($containerID));

////////////////////////////////////////////////////////////////////////////////
// Form Data
////////////////////////////////////////////////////////////////////////////////

$formdata = array();

$formdata["miscbuttons"] = array(
	"label" => _L(""),
	"control" => array("FormHtml", "html"=>
		submit_button(_L('Refresh'),"refresh","arrow_refresh")
		. (!isset($_SESSION['listsearchpreview']) ? icon_button(_L('Show All Contacts'),"tick",null,"search.php?showall") : '')
		. icon_button(_L('Done'),"tick",null,"list.php")
	),
	"helpstep" => 1
);

if (!isset($_SESSION['listsearchpreview'])) {
	$formdata[] = _L("Search by Rules");
	$formdata["ruledata"] = array(
		"label" => _L('Rules'),
		"value" => $rulesjson,
		"control" => array("FormRuleWidget"),
		"validators" => array(array('ValRules')),
		"helpstep" => 2
	);

	$formdata[] = _L("Search by Person");
	$formdata["pkey"] = array(
		"label" => _L('Person ID'),
		"value" => !empty($_SESSION['listsearchpkey']) ? $_SESSION['listsearchpkey'] : '',
		"validators" => array(
		),
		"control" => array("TextField"),
		"helpstep" => 2
	);
	$formdata["phone"] = array(
		"label" => _L('Phone Number'),
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
	$formdata["sms"] = array(
		"label" => _L('SMS Number'),
		"value" => !empty($_SESSION['listsearchsms']) ? $_SESSION['listsearchsms'] : '',
		"validators" => array(array("ValPhone")),
		"control" => array("TextField"),
		"helpstep" => 2
	);

	$formdata["searchbutton"] = array(
		"label" => _L(''),
		"control" => array("FormHtml", "html" => "<div id='searchButtonContainer'>" . submit_button(_L('Search by Person'),"search","magnifier") . "</div>"),
		"helpstep" => 2
	);
	
	$formdata[] = _L("Results");
}

$formdata["results"] = array(
	"label" => _L(''),
	"control" => array("FormHtml", 'html' => "<div id='$containerID'>"."</div>"),
	"helpstep" => 2
);

$helpsteps = array (
	_L('Please enter descriptive information about this list.'), // 1
	_L('You may enter some rules for this list.'), // 2
	_L('These are advanced list tools.'), // 3
);

$form = new Form('listsearch',$formdata,$helpsteps,array());
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
					if (isset($data->fieldnum, $data->logical, $data->op, $data->val) && $type = Rule::getType($data->fieldnum)) {
						$data->val = prepareRuleVal($type, $data->op, $data->val);
						if ($rule = Rule::initFrom($data->fieldnum, $data->logical, $data->op, $data->val)) {
							if (!isset($_SESSION['listsearchrules']))
								$_SESSION['listsearchrules'] = array();
							$_SESSION['listsearchrules'][$data->fieldnum] = $rule;
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
					
				case 'search':
					list_clear_search_session();
					$_SESSION['listsearchperson'] = true;
					$_SESSION['listsearchpkey'] = isset($postdata['pkey']) ? $postdata['pkey'] : false;
					$_SESSION['listsearchphone'] = isset($postdata['phone']) ? Phone::parse($postdata['phone']) : false;
					$_SESSION['listsearchsms'] = isset($postdata['sms']) ? Phone::parse($postdata['sms']) : false;
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
		Validator::load_validators(array("ValRules"));
	} ?>


	document.observe('dom:loaded', function() {
		if (notpreview) {
			ruleWidget.delayActions = true;
			ruleWidget.container.observe('RuleWidget:AddRule', rulewidget_add_rule);
			ruleWidget.container.observe('RuleWidget:DeleteRule', rulewidget_delete_rule);
		}
		
		$('<?=$containerID?>').update('<?=addslashes(list_get_results_html($containerID, $renderedlist))?>');
	});

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
</script>
<?

echo $form->render();

include_once("navbottom.inc.php");
?>