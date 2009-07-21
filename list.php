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
include_once("inc/formatters.inc.php");

////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$USER->authorize('createlist')) {
	redirect('unauthorized.php');
}

////////////////////////////////////////////////////////////////////////////////
// Action/Request Processing
////////////////////////////////////////////////////////////////////////////////
if (!isset($_SESSION['ajaxtablepagestart']))
	$_SESSION['ajaxtablepagestart'] = array();

function prepare_table($containerID, $renderedlist, $destinations = array()) {
	global $USER;
	switch ($containerID) {
		case 'additionsContainer':
			$renderedlist->mode = 'add';
			$renderedlist->pagelimit = 500;
			break;
		default:
			return false;
	}
	$renderedlist->hasstats = false;
	$data = $renderedlist->getPage(isset($_SESSION['ajaxtablepagestart'][$containerID]) ? $_SESSION['ajaxtablepagestart'][$containerID] : 0, $renderedlist->pagelimit);
	$titles = array(//"0" => "In List",
		2 => "",
		3 => "First Name",
		4 => "Last Name",
		5 => "Language");
	$formatters = array(//"0" => "fmt_checkbox",
		2 => "fmt_idmagnify",
		6 => "fmt_phone",
		7 => "fmt_email",
		8 => "fmt_phone",
		9 => "fmt_null");
	$sorting = array(
		3 => $renderedlist->firstname,
		4 => $renderedlist->lastname,
		5 => $renderedlist->language
	);
	if (isset($destinations['phone'])) {
		$sorting[6] = 'phone';
		$titles[6] = destination_label("phone", 0);
	}
	if (isset($destinations['email'])) {
		$sorting[7] = 'email';
		$titles[7] = destination_label("email", 0);
	}
	if (isset($destinations['sms'])) {
		$sorting[8] = 'sms';
		$titles[8] = destination_label("sms", 0);
	}
	$sorting[9] = 'address';
	$titles[9] = "Address";
	
	return ajax_table_show_menu($containerID, $renderedlist->total, $renderedlist->pageoffset, $renderedlist->pagelimit) . ajax_show_table($containerID, $data, $titles, $formatters, $sorting, true);
}

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

$destinations = array();
if ($USER->authorize('sendphone'))
	$destinations['phone'] = true;
if ($USER->authorize('sendemail'))
	$destinations['email'] = true;
if (getSystemSetting("_hassms") && $USER->authorize('sendsms'))
	$destinations['sms'] = true;

if (!empty($_GET['ajax']) && isset($renderedlist) && isset($_GET['containerID']) && in_array($_GET['containerID'], array('additionsContainer'))) {
	$ajaxdata = false;
	switch ($_GET['ajax']) {
		case 'orderby': // Handled same as case 'page'.
		case 'page': // Order by what's in the user's setting.
			if (isset($_GET['start']))
				$_SESSION['ajaxtablepagestart'][$_GET['containerID']] = $_GET['start'] + 0;
			$orderbySQL = ajax_table_get_orderby($_GET['containerID'], array_merge($destinations, array('address')));
			if (!empty($orderbySQL))
				$renderedlist->orderby = $orderbySQL;
			$ajaxdata = array('html' => prepare_table($_GET['containerID'], $renderedlist, $destinations));
			break;
	}
	header('Content-Type: application/json');
	exit(json_encode($ajaxdata));
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

// Reset ajax tables' page menus.
$_SESSION['ajaxtablepagestart']['additionsContainer'] = 0;

$rulesjson = '[]';
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
		"fieldhelp" => _L('This is the name of your list. The best names describe the list contents.'),
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
		"fieldhelp" => _L('This field is for an optional description of your list, viewable in the List Builder screen.'),
		"value" => $list->description,
		"validators" => array(
			array("ValLength","min" => 3,"max" => 50)
		),
		"control" => array("TextField","size" => 30, "maxlength" => 51),
		"helpstep" => 1
	),
	"preview" => array(
		"label" => 'Total',
		"fieldhelp" => _L('This number indicates how many people are currently in your list. Click the preview button to view contact information.'),
		"control" => array("FormHtml", 'html' => '<div id="listTotal" style="float:left; padding:5px; margin-right: 10px;">' . (isset($renderedlist) ? $renderedlist->total : '0') . '</div>' . submit_button(_L('Preview'), 'preview', 'tick')),
		"helpstep" => 1
	)
);

$formdata[] = _L('List Rules');
$formdata["ruledata"] = array(
	"label" => _L('List Rules'),
	"fieldhelp" => _L('Use rules to select groups of contacts from the data available to your account.'),
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
		"control" => array("FormHtml", 'html' => "<div id='additionsContainer'>" . prepare_table('additionsContainer', $renderedlist, $destinations) . "</div>"),
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
if ($USER->authorize('listuploadids') || $USER->authorize('listuploadcontacts'))
	$advancedtools .= '<tr><td>'.submit_button(_L('Upload List'),'uploadList','tick').'</td><td>'._L('Upload a list of contacts using a CSV file').'</td></tr>';
$formdata[] = _L('Advanced List Tools');
$formdata["advancedtools"] = array(
	"label" => '',
	"control" => array("FormHtml", 'html' => "<table>$advancedtools</table>"),
	"helpstep" => 3
);

$helpsteps = array (
	_L('Enter a name for your list. The best names describe the list\'s content, making the list easy to reuse.'), // 1
	_L('Rules are used to select groups of contacts from the data available to your account. For example, if you wanted to make a list of 6th graders from Springfield Elementary, you would create two rules: "Grade equals 6" and "School equals Springfield Elementary".'), // 2
	_L('This section contains tools to add specific individuals and add contacts that are not part of your regular database of contacts. '), // 3
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
		$list->modifydate = QuickQuery("select now()");
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