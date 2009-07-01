<?
// TODO
//+ ajax validator for checking if list name already exists
//+ fix rulewidget.js.php, onchange for the fieldnum should clear the value column.

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

////////////////////////////////////////////////////////////////////////////////
// Form Data
////////////////////////////////////////////////////////////////////////////////

$list = new PeopleList(isset($_SESSION['listid']) ? $_SESSION['listid'] : null);
$rulesjson = '[]';
if ($list->id) {
	$rules = $list->getListRules();
	if (is_array($rules)) {
		$fieldmaps = FieldMap::getAllAuthorizedFieldMaps();
		foreach ($rules as $ruleid => $rule) {
			$rules[$ruleid] = cleanObjects($rule);
			$rules[$ruleid]['ruleid'] = $ruleid;
			$rules[$ruleid]['type'] = 'multisearch';
			if ($fieldmaps[$rule->fieldnum]->isOptionEnabled('text'))
				$rules[$ruleid]['type'] = 'text';
			else if ($fieldmaps[$rule->fieldnum]->isOptionEnabled('reldate'))
				$rules[$ruleid]['type'] = 'reldate';
			else if ($fieldmaps[$rule->fieldnum]->isOptionEnabled('numeric'))
				$rules[$ruleid]['type'] = 'numeric';
		}
		$rulesjson = json_encode(array_values($rules));
	} else {
		unset($rules);
	}
}

$advancedtools = '';
$advancedtools .= '<tr><td>'.submit_button(_L('Search Contacts'),'search','tick').'</td><td>'._L('Search for contacts in the database').'</td></tr>';
$advancedtools .= '<tr><td>'.submit_button(_L('Enter Contacts'),'manualAdd','tick').'</td><td>'._L('Manually add new contacts').'</td></tr>';
$advancedtools .= '<tr><td>'.submit_button(_L('Open Address Book'),'addressBookAdd','tick').'</td><td>'._L('Choose contacts from your address book').'</td></tr>';
$advancedtools .= '<tr><td>'.submit_button(_L('Upload List'),'uploadList','tick').'</td><td>'._L('Upload a list of contacts using a CSV file').'</td></tr>';

$formdata = array(
	"name" => array(
		"label" => _L('List Name'),
		"value" => $list->name,
		"validators" => array(
			array("ValLength","min" => 3,"max" => 50)
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
	"ruledata" => array(
		"label" => _L('List Rules'),
		"value" => $rulesjson,
		"validators" => array(
			array("ValRules")
		),
		"control" => array("FormRuleWidget"),
		"helpstep" => 2
	),
	"advancedtools" => array(
		"label" => _L('Advanced Tools'),
		"value" => null,
		"validators" => array(),
		"control" => array("FormHtml", 'html' => "<table>$advancedtools</table>"
		),
		"helpstep" => 3
	)
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
			error_log('Rule data ------------------------');
			error_log($postdata['ruledata']);
			$ruledata = json_decode($postdata['ruledata']);
			if (is_array($ruledata)) {
				QuickUpdate('BEGIN');
					foreach ($ruledata as $data) {
						// Existing Rule to Keep
						if (isset($data->ruleid)) {
							if (isset($rules, $rules[$data->ruleid]))
								unset($rules[$data->ruleid]); // Remove from $rules
							continue;
						}
						
						// CREATE rule.
						if (!isset($data->fieldnum, $data->type, $data->logical, $data->op, $data->val))
							continue;
						if (!$rule = Rule::initFrom($data->fieldnum, $data->type, $data->logical, $data->op, $data->val))
							continue;
						$rule->create();
						$le = new ListEntry();
						$le->listid = $list->id;
						$le->type = "R";
						$le->ruleid = $rule->id;
						$le->create();
					}
					
					// Existing Rules to Remove
					if (isset($rules)) {
						foreach ($rules as $rule)
							QuickUpdate("DELETE le.*, r.* FROM listentry le, rule r WHERE le.ruleid=r.id AND le.listid=? AND r.fieldnum=?", false, array($list->id, $rule->fieldnum));
					}
				QuickUpdate('COMMIT');
			}
			error_log('--Rule data ------------------------');
			
			if ($ajax)
				$form->sendTo("lists.php");
			else
				redirect("lists.php");
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
	<? Validator::load_validators(array("ValRules")); ?>
</script>

<?
startWindow(_L('List Editor'));

echo $form->render();
endWindow();
include_once("navbottom.inc.php");
?>