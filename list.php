<?
// TODO
//+ fix rulewidget.js.php, onchange for the fieldnum should clear the value column.
//+ refactor ajaxlistform.php and list.php to use common functions for add a new list, rules, etc..

////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
require_once("inc/common.inc.php");
require_once("inc/securityhelper.inc.php");
require_once("inc/table.inc.php");
require_once("inc/html.inc.php");
require_once("inc/form.inc.php");
require_once("inc/date.inc.php");
require_once("obj/Language.obj.php");
require_once("obj/Person.obj.php");
require_once("obj/Address.obj.php");
require_once("obj/Phone.obj.php");
require_once("obj/Email.obj.php");
require_once("obj/Sms.obj.php");
require_once("obj/PeopleList.obj.php");
require_once("obj/AudioFile.obj.php");
require_once("obj/FieldMap.obj.php");
require_once("obj/Rule.obj.php");
require_once("obj/ListEntry.obj.php");
require_once("obj/RenderedList.obj.php");
require_once("ruleeditform.inc.php");
require_once("inc/rulesutils.inc.php");
require_once("obj/Validator.obj.php");
require_once("obj/Form.obj.php");
require_once("obj/FormItem.obj.php");
require_once("obj/FormRuleWidget.fi.php");
require_once("inc/rulesutils.inc.php");
require_once("inc/formatters.inc.php");
require_once("obj/JobType.obj.php");
require_once('list.inc.php');

////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$USER->authorize('createlist')) {
	redirect('unauthorized.php');
}


////////////////////////////////////////////////////////////////////////////////
// Action/Request Processing
////////////////////////////////////////////////////////////////////////////////

unset($_SESSION['listsearchpreview']);
list_clear_search_session();

if (isset($_GET['origin'])) {
	$_SESSION['origin'] = trim($_GET['origin']);
}

if (isset($_GET['id'])) {
	setCurrentList($_GET['id']);
	redirect();
}

$list = new PeopleList(isset($_SESSION['listid']) ? $_SESSION['listid'] : null);
if ($list->id) {
	$renderedlist = new RenderedList($list);
	$renderedlist->calcStats();
}

if (isset($renderedlist))
	list_handle_ajax_table($renderedlist, array('listAdditionsContainer','listSkipsContainer'));

////////////////////////////////////////////////////////////////////////////////
// Validators
////////////////////////////////////////////////////////////////////////////////

class ValListName extends Validator {
	var $onlyserverside = true;
	function validate($value) {
		global $USER;
		if (QuickQuery('select id from list where deleted=0 and id!=? and name=? and userid=?', false, array(!empty($_SESSION['listid']) ? $_SESSION['listid'] : 0, $value, $USER->id)))
			return _L('There is already a list with this name');
		return true;
	}
}

////////////////////////////////////////////////////////////////////////////////
// Form Data
////////////////////////////////////////////////////////////////////////////////

$rulesjson = '';
if ($list->id) {
	$rules = $list->getListRules();
	if (is_array($rules))
		$rulesjson = json_encode(cleanObjects(array_values($rules)));
	else
		unset($rules);
}

$formdata = array(
	"name" => array(
		"label" => _L('List Name'),
		"value" => $list->name,
		"validators" => array(
			array("ValRequired"),
			array("ValLength","max" => 50),
			array("ValListName")
		),
		"control" => array("TextField","size" => 30, "maxlength" => 51),
		"helpstep" => 1
	),
	"description" => array(
		"label" => _L('Description'),
		"value" => $list->description,
		"validators" => array(
			array("ValLength","max" => 50)
		),
		"control" => array("TextField","size" => 30, "maxlength" => 51),
		"helpstep" => 1
	),
	"preview" => array(
		"label" => 'Total',
		"control" => array("FormHtml", 'html' => '<div id="listTotal" style="float:left; padding:5px; margin-right: 10px;">' . (isset($renderedlist) ? $renderedlist->total : '0') . '</div>' . submit_button(_L('Preview'), 'preview', 'tick')),
		"helpstep" => 1
	)
);

$formdata[] = _L('List Content');
$formdata["ruledelete"] = array(
	"value" => "",
	"control" => array("HiddenField")
);
$formdata["newrule"] = array(
	"label" => _L('List Rules'),
	"value" => $rulesjson,
	"validators" => array(
		array("ValRules")
	),
	"control" => array("FormRuleWidget"),
	"helpstep" => 2
);

if (isset($renderedlist) && $renderedlist->totaladded > 0) {
	$formdata["additions"] = array(
		"label" => _L('Additions'),
		"control" => array("FormHtml", 'html' => submit_button(_L('Clear Additions'),'clearadditions','tick') .  "<div id='listAdditionsContainer' style='clear:both;margin:0; padding:0'></div>"),
		"helpstep" => 2
	);
}

if (isset($renderedlist) && $renderedlist->totalremoved > 0) {
	$formdata["skips"] = array(
		"label" => _L('Skips'),
		"control" => array("FormHtml", 'html' => submit_button(_L('Clear Skips'),'clearskips','tick') . "<div id='listSkipsContainer' style='clear:both;margin:0;padding:0'></div>"),
		"helpstep" => 2
	);
}

$advancedtools = '';
$advancedtools .= '<tr><td>'.submit_button(_L('Search Contacts'),'search','tick').'</td><td>'._L('Search for contacts in the database').'</td></tr>';
$advancedtools .= '<tr><td>'.submit_button(_L('Enter Contacts'),'manualAdd','tick').'</td><td>'._L('Manually add new contacts').'</td></tr>';
$advancedtools .= '<tr><td>'.submit_button(_L('Open Address Book'),'addressBookAdd','tick').'</td><td>'._L('Choose contacts from your address book').'</td></tr>';
if ($USER->authorize('listuploadids') || $USER->authorize('listuploadcontacts'))
	$advancedtools .= '<tr><td>'.submit_button(_L('Upload List'),'uploadList','tick').'</td><td>'._L('Upload a list of contacts using a CSV file').'</td></tr>';
$formdata[] = _L('Advanced List Tools');
$formdata["advancedtools"] = array(
	"label" => '',
	"control" => array("FormHtml", 'html' => "<table>$advancedtools</table>"),
	"helpstep" => 3
);

$helpsteps = array (
	_L('Please enter descriptive information about this list.'), // 1
	_L('You may enter some rules for this list.'), // 2
	_L('These are advanced list tools.'), // 3
);

$buttons = array(submit_button(_L('Refresh'),"refresh","arrow_refresh"),
	submit_button(_L('Done'),"done","tick"));
				
$form = new Form("list",$formdata,$helpsteps,$buttons);
$form->ajaxsubmit = true;

////////////////////////////////////////////////////////////////////////////////
// Form Data Handling
////////////////////////////////////////////////////////////////////////////////

//check and handle an ajax request (will exit early)
//or merge in related post data
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
		
		$list->name = $postdata['name'];
		$list->description = $postdata['description'];
		$list->userid = $USER->id;
		$list->deleted = 0;
		$list->update();
		$_SESSION['listid'] = $list->id;
		
		// Save
		if ($list->id) {
			if ($ajax) {
				switch ($button) {
					case 'addrule':
						QuickUpdate('BEGIN');
							$ruledata = json_decode($postdata['newrule']);
							$data = $ruledata[0];
							// CREATE rule.
							if (!isset($data->fieldnum, $data->logical, $data->op, $data->val))
								continue;
							if (!$type = Rule::getType($data->fieldnum))
								continue;
							$data->val = prepareRuleVal($type, $data->op, $data->val);
							if (!$rule = Rule::initFrom($data->fieldnum, $data->logical, $data->op, $data->val))
								continue;
							$rule->create();
							$le = new ListEntry();
							$le->listid = $list->id;
							$le->type = "R";
							$le->ruleid = $rule->id;
							$le->create();
						QuickUpdate('COMMIT');
						$form->sendTo('list.php');
						break;
						
					case 'deleterule':
						$fieldnum = $postdata['ruledelete'];
						if ($USER->authorizeField($fieldnum))
							QuickUpdate("DELETE le.*, r.* FROM listentry le, rule r WHERE le.ruleid=r.id AND le.listid=? AND r.fieldnum=?", false, array($list->id, $fieldnum));
						$form->sendTo('list.php');
						break;
						
					case 'clearadditions':
						QuickUpdate("DELETE le.* FROM listentry le WHERE le.type='A' AND le.listid=?", false, array($list->id));
						$form->sendTo('list.php');
						break;
						
					case 'clearskips':
						QuickUpdate("DELETE le.* FROM listentry le WHERE le.type='N' AND le.listid=?", false, array($list->id));
						$form->sendTo('list.php');
						break;
						
					case 'refresh': // handled same as case 'done'.
					case 'done':
						if (isset($_SESSION['origin']) && ($_SESSION['origin'] == 'start')) {
							unset($_SESSION['origin']);
							$form->sendTo('start.php');
						} else {
							unset($_SESSION['origin']);
							if ($button == 'refresh')
								$form->sendTo('list.php');
							$form->sendTo('lists.php');
						}
						break;
				
					case 'preview':
						$_SESSION['listsearchpreview'] = true;
						$form->sendTo("showlist.php?id=" . $list->id);
						break;
						
					case 'search':
						unset($_SESSION['listsearchpreview']);
						$form->sendTo("search.php");
						break;
						
					case 'manualAdd':
						$form->sendTo("addressedit.php?id=new&origin=manualadd");
						break;
						
					case 'addressBookAdd':
						$form->sendTo("addresses.php?origin=manualadd");
						break;
						
					case 'uploadList':
						$form->sendTo("uploadlist.php");
						break;
						
					default:
						$form->sendTo("lists.php");
				}
			} else {
				redirect("lists.php");
			}
		}
	}
}

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
$PAGE = "notifications:lists";
$TITLE = _L('List Editor: ') . escapehtml($list->name);

include_once("nav.inc.php");

// Next: Optional, Load Custom Form Validators
?>
<script type="text/javascript">
	<? Validator::load_validators(array("ValRules", "ValListName")); ?>
</script>
<?
startWindow(_L('List Editor'));

echo $form->render();
endWindow();
?>
<script type='text/javascript'>
	document.observe('dom:loaded', function() {
		ruleWidget.delayActions = true;
		ruleWidget.container.observe('RuleWidget:AddRule', list_add_rule);
		ruleWidget.container.observe('RuleWidget:DeleteRule', list_delete_rule);
		
		<?php if (isset($renderedlist)) { ?>
			if ($('listAdditionsContainer'))
				$('listAdditionsContainer').update('<?=addslashes(list_get_results_html('listAdditionsContainer', $renderedlist))?>');
			if ($('listSkipsContainer'))
				$('listSkipsContainer').update('<?=addslashes(list_get_results_html('listSkipsContainer', $renderedlist))?>');
		<?php } ?>
	});

	function list_add_rule(event) {
		$('list_newrule').value = [event.memo.ruledata].toJSON();
		form_submit(event, 'addrule');
	}
	function list_delete_rule(event) {
		$('list_ruledelete').value = event.memo.fieldnum;
		form_submit(event, 'deleterule');
	}
</script>
<?
include_once("navbottom.inc.php");
?>