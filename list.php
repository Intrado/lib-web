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
require_once("obj/FormRuleWidget.obj.php");
require_once("inc/rulesutils.inc.php");

////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$USER->authorize('createlist')) {
	redirect('unauthorized.php');
}

////////////////////////////////////////////////////////////////////////////////
// Action/Request Processing
////////////////////////////////////////////////////////////////////////////////

if (isset($_GET['origin'])) {
	$_SESSION['origin'] = trim($_GET['origin']);
}

if (isset($_GET['id'])) {
	setCurrentList($_GET['id']);
	redirect();
}

////////////////////////////////////////////////////////////////////////////////
// Optional Form Items And Validators
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

$list = new PeopleList(isset($_SESSION['listid']) ? $_SESSION['listid'] : null);
$rulesjson = '[]';
if ($list->id) {
	$rules = $list->getListRules();
	if (is_array($rules)) {
		$rulesjson = json_encode(cleanObjects(array_values($rules)));
	} else {
		unset($rules);
	}
	
	$renderedlist = new RenderedList($list);
	$renderedlist->calcStats();
}

$formdata = array(
	"name" => array(
		"label" => _L('List Name'),
		"value" => $list->name,
		"validators" => array(
			array("ValRequired"),
			array("ValLength","min" => 3,"max" => 50),
			array("ValListName")
		),
		"control" => array("TextField","size" => 30, "maxlength" => 51),
		"helpstep" => 1
	),
	"description" => array(
		"label" => _L('Description'),
		"value" => $list->description,
		"validators" => array(
			array("ValLength","min" => 3,"max" => 50)
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

$formdata[] = _L('List Rules');
$formdata["ruledata"] = array(
	"label" => _L('List Rules'),
	"value" => $rulesjson,
	"validators" => array(
		array("ValRules")
	),
	"control" => array("FormRuleWidget"),
	"helpstep" => 2
);

if (isset($renderedlist) && $renderedlist->totaladded > 0) {
	$formdata[] = _L('Additions');
	$formdata["additions"] = array(
		"label" => '',
		"control" => array("FormHtml", 'html' => 'TODO: Show additions'),
		"helpstep" => 2
	);
}

if (isset($renderedlist) && $renderedlist->totalremoved > 0) {
	$formdata[] = _L('Skips');
	$formdata["skips"] = array(
		"label" => '',
		"control" => array("FormHtml", 'html' => 'TODO: Show skips'),
		"helpstep" => 2
	);
}

$advancedtools = '';
$advancedtools .= '<tr><td>'.submit_button(_L('Search Contacts'),'search','tick').'</td><td>'._L('Search for contacts in the database').'</td></tr>';
$advancedtools .= '<tr><td>'.submit_button(_L('Enter Contacts'),'manualAdd','tick').'</td><td>'._L('Manually add new contacts').'</td></tr>';
$advancedtools .= '<tr><td>'.submit_button(_L('Open Address Book'),'addressBookAdd','tick').'</td><td>'._L('Choose contacts from your address book').'</td></tr>';
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

$buttons = array(submit_button(_L('Save'),"submit","tick"),
				icon_button(_L('Cancel'),"cross",null,"start.php"));
				
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
			$ruledata = json_decode($postdata['ruledata']);
			if (is_array($ruledata)) {
				QuickUpdate('BEGIN');
					if (isset($rules)) {
						foreach ($rules as $existingrule) {
							if (!$USER->authorizeField($existingrule->fieldnum))
								continue;
							QuickUpdate("DELETE le.*, r.* FROM listentry le, rule r WHERE le.ruleid=r.id AND le.listid=? AND r.id=?", false, array($list->id, $existingrule->id));
						}
					}
					foreach ($ruledata as $data) {
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
					}
				QuickUpdate('COMMIT');
			}
			
			if ($ajax) {
				switch ($button) {
					case 'save':
						if (isset($_SESSION['origin']) && ($_SESSION['origin'] == 'start')) {
							unset($_SESSION['origin']);
							$form->sendTo('start.php');
						} else {
							unset($_SESSION['origin']);
							$form->sendTo('lists.php');
						}
						break;
				
					case 'preview':
						$form->sendTo("showlist.php?id=" . $list->id);
						break;
						
					case 'search':
						$form->sendTo("search.php");
						break;
						
					case 'manualAdd':
						$form->sendTo("addressmanualadd.php?id=new");
						break;
						
					case 'addressBookAdd':
						$form->sendTo("addressesmanualadd.php?");
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
// Display Functions
////////////////////////////////////////////////////////////////////////////////


////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
$PAGE = "notifications:lists";
$TITLE = _L('List Editor: ') . 'TODO';

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
	function update_list_total() {
	}
	ruleWidget.container.observe('RuleWidget:AddRule', update_list_total);
	ruleWidget.container.observe('RuleWidget:DeleteRule', update_list_total);
</script>
<?
include_once("navbottom.inc.php");
?>
