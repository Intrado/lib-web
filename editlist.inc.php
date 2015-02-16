<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
require_once("inc/common.inc.php");
require_once("inc/securityhelper.inc.php");
require_once("inc/table.inc.php");
require_once("inc/html.inc.php");
require_once("inc/utils.inc.php");
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
require_once("obj/ValSections.val.php");
require_once("obj/ValRules.val.php");
require_once("obj/Form.obj.php");
require_once("obj/FormItem.obj.php");
require_once("obj/FormRuleWidget.fi.php");
require_once("obj/SectionWidget.fi.php");
require_once("inc/formatters.inc.php");
require_once("obj/JobType.obj.php");
require_once("inc/list.inc.php");
require_once("obj/RestrictedValues.fi.php");
require_once("obj/ListGuardianCategory.obj.php");

////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$USER->authorize('createlist')) {
	redirect('unauthorized.php');
}

if (isset($_GET['origin'])) {
	$_SESSION['origin'] = trim($_GET['origin']);
}

if (isset($_GET['id'])) {
	setCurrentList($_GET['id']);
	redirect();
}

if (isset($_GET['removealladds'])) {
	$id = $_GET['removealladds'] + 0;
	if (userOwns("list",$id)) {
		QuickUpdate("DELETE le.* FROM listentry le WHERE le.type='add' AND le.listid=?", false, array($id));
		notice(_L('All additions are now removed.'));
	}
	redirect();
}

if (isset($_GET['removeallskips'])) {
	$id = $_GET['removeallskips'] + 0;
	if (userOwns("list",$id)) {
		QuickUpdate("DELETE le.* FROM listentry le WHERE le.type='negate' AND le.listid=?", false, array($id));
		notice(_L('All skips are now removed.'));
	}
	redirect();
}

$list = new PeopleList(isset($_SESSION['listid']) ? $_SESSION['listid'] : null);
if ($list->id) {
	if($list->type == 'alert')
		redirect('unauthorized.php');

	$method = ($list->type === 'section') ? 'sections' : 'rules';
	
	$renderedlist = new RenderedList2();
	$renderedlist->initWithList($list);
}

if (!isset($method) || !in_array($method, array('rules', 'sections')))
	$method = 'rules';
$methodlink = $method == 'sections' ? 'editlistsections.php' : 'editlistrules.php';

handle_list_checkbox_ajax(); //for handling check/uncheck from the list

////////////////////////////////////////////////////////////////////////////////
// Validators
////////////////////////////////////////////////////////////////////////////////

class ValListName extends Validator {
	var $onlyserverside = true;
	function validate($value) {
		global $USER;
		$listid = isset($_SESSION['listid']) ? $_SESSION['listid'] : 0;
		if (QuickQuery('select id from list where deleted=0 and id!=? and name=? and userid=?', false, array($listid, $value, $USER->id)))
			return _L('There is already a list with this name');
		return true;
	}
}

////////////////////////////////////////////////////////////////////////////////
// Form Data
////////////////////////////////////////////////////////////////////////////////

if ($method === 'rules') {
	$rulewidgetvaluejson = '';
	if ($list->id) {
		$rulewidgetdata = array();
		
		$rules = $list->getListRules();
		
		if (count($rules) > 0)
			$rulewidgetdata = cleanObjects(array_values($rules));
		
		$organizations = $list->getOrganizations();
		
		if (count($organizations) > 0) {
			$orgkeys = array(); // An array of value=>title pairs.
			
			foreach ($organizations as $organization) {
				$orgkeys[$organization->id] = $organization->orgkey;
			}
			
			$rulewidgetdata[] = array(
				'fieldnum' => 'organization',
				'val' => $orgkeys
			);
		}
		
		if (count($rulewidgetdata) > 0)
			$rulewidgetvaluejson = json_encode($rulewidgetdata);
	}
}

$total = isset($renderedlist) ? $renderedlist->getTotal() : 0;
$showAdditions = $list->countAdded() > 0;
$showSkips = $list->countRemoved() > 0;

$maxguardians = getSystemSetting("maxguardians", 0);
if ($maxguardians) {
//get guardian categories
	$categoryList = $csApi->getGuardianCategoryList();
	$categories = array();
	foreach ($categoryList as $c) {
		$categories[$c->id] = $c->name;
	}
	$selectedCategories = array();
	if ($list->id) {
		$selectedCategories = ListGuardianCategory::getGuardiansForList($list->id);
	}
}

$formdata = array(
	// A hidden submit button is needed because otherwise pressing ENTER would take you to the Preview page.
	"hiddendone" => array(
		"label" => _L(''),
		"control" => array("FormHtml", "html" => hidden_submit_button('done')),
		"helpstep" => 1
	),
	"name" => array(
		"label" => _L('List Name'),
		"fieldhelp" => _L('This is the name of your list. The best names describe the list contents.'),
		"value" => $list->name,
		"validators" => array(
			array("ValRequired"),
			array("ValLength", "max" => 50),
			array("ValListName")
		),
		"control" => array("TextField", "size" => 30, "maxlength" => 50),
		"helpstep" => 1
	),
	"description" => array(
		"label" => _L('Description'),
		"fieldhelp" => _L('This field is for an optional description of your list, viewable in the List Builder screen.'),
		"value" => $list->description,
		"validators" => array(
			array("ValLength", "max" => 50)
		),
		"control" => array("TextField", "size" => 30, "maxlength" => 50),
		"helpstep" => 1
	),
	"preview" => array(
		"label" => 'Total',
		"fieldhelp" => _L('This number indicates how many people are currently in your list. Click the preview button to view contact information.'),
		"control" => array("FormHtml", 'html' => '<div id="listTotal" style="float:left; padding:5px; margin-right: 10px;">' . $total . '</div>' . submit_button(_L('Preview'), 'preview', 'diagona/16/049')),
		"helpstep" => 1
	)
);


$formdata[] = _L('List Content');

if ($method === 'rules') {
	$formdata["ruledelete"] = array(
		"value" => "",
		"control" => array("HiddenField")
	);
	$formdata["newrule"] = array(
		"label" => _L('List Rules'),
		"fieldhelp" => _L('Use rules to select groups of contacts from the data available to your account.'),
		"value" => $rulewidgetvaluejson,
		"validators" => array(
			array("ValRules")
		),
		"control" => array("FormRuleWidget", "showRemoveAllButton" => $rulewidgetvaluejson != ''),
		"helpstep" => 2
	);
}

if ($method === 'sections') {
	$formdata["sectionids"] = array(
		"label" => _L('Sections'),
		"fieldhelp" => _L('Select sections from an organization.'),
		"value" => "",
		"validators" => array(
			array("ValRequired"),
			array("ValSections")
		),
		"control" => array("SectionWidget",
			"sectionids" => QuickQueryList("select sectionid from listentry where listid=? and type='section'", false, false, array($list->id))
		),
		"helpstep" => 2
	);
}


if ($maxguardians) {
	$formdata[] = _L('Recipient Mode');
	$formdata["recipientmode"] = array(
		"label" => _L("Recipient Mode"),
		"fieldhelp" => _L('Select the recipients that will be contacted on behalf of this list'),
		"value" => $list->recipientmode,
		"validators" => array(), "control" => array("RadioButton", "values" => array(
				PeopleList::$RECIPIENTMODE_MAP[1] => _L("Self"),
				PeopleList::$RECIPIENTMODE_MAP[2] => _L("Guardian"),
				PeopleList::$RECIPIENTMODE_MAP[3] => _L("Self and Guardian"))),
		"helpstep" => 4
	);
	$formdata["category"] = array(
		"label" => _L("Guardian Category Restriction"),
		"fieldhelp" => _L('Select categories to restrict by'),
		"value" => $selectedCategories,
		"validators" => array(
			array("ValInArray", "values" => array_keys($categories))
		),
		"control" => array("RestrictedValues", "values" => $categories, "label" => _L("Restrict to these categories:")),
		"helpstep" => 4
	);
}

if ($showAdditions) {
	$pagestart = (isset($_GET['pagestart']) ? $_GET['pagestart'] + 0 : 0);
	$pagelimit = 100;
	
	$query = "select SQL_CALC_FOUND_ROWS p.id, p.pkey, p.f01, p.f02 
			from person p inner join listentry le 
				on (le.personid=p.id and le.type='add' and le.listid=?) 
			order by f02, f01
			limit $pagestart, $pagelimit";
	$data = QuickQueryMultiRow($query,false,false,array($list->id));
	
	//add all found IDs to $PAGEINLISTMAP
	$PAGEINLISTMAP = array();
	foreach ($data as $row)
		$PAGEINLISTMAP[$row[0]] = true;
	
	$total = QuickQuery("select found_rows()");
	$titles = array(
		0 => "In List",
		1 => "Unique ID",
		2 => FieldMap::getName(FieldMap::getFirstNameField()),
		3 => FieldMap::getName(FieldMap::getLastNameField())
	);
	
	$formatters = array (
		0 => "fmt_checkbox",
		1 => "fmt_persontip"
	);
	
	ob_start();
	echo '<div style="float: left; margin-top: 5px"><a href="?removealladds='. $list->id. '">'. escapehtml(_L("Remove all adds")). '</a></div>';
	showPageMenu($total,$pagestart,$pagelimit);
	echo '<div style="clear:both"></div><div style="margin-bottom: 10px;">';
	if(count($data) > 8)
		echo '<div class="scrollTableContainer">';
	echo '<table id="listadds" width="100%" cellpadding="3" cellspacing="1" class="list">';
	showTable($data, $titles, $formatters);
	echo "\n</table>";
	if(count($data) > 8)
		echo '</div>';
	echo '</div>';
	//second page menu confusing
	//showPageMenu($total,$pagestart,$pagelimit);
	$additionshtml = ob_get_clean();
	
	$formdata["additions"] = array(
		"label" => _L('Additions'),
		"control" => array("FormHtml", 'html' => $additionshtml),
		"helpstep" => 2
	);
}


if ($showSkips) {
	$query = "select p.id, p.pkey, p.f01, p.f02 
			from person p inner join listentry le 
				on (le.personid=p.id and le.type='negate' and le.listid=?)
			order by f02, f01";
	$data = QuickQueryMultiRow($query,false,false,array($list->id));
	
	//add all found IDs to $PAGEINLISTMAP
	$PAGEINLISTMAP = array();
	
	$total = count($data);
	$titles = array(
		0 => "In List",
		1 => "Unique ID",
		2 => FieldMap::getName(FieldMap::getFirstNameField()),
		3 => FieldMap::getName(FieldMap::getLastNameField())
	);
	
	$formatters = array (
		0 => "fmt_checkbox",
		1 => "fmt_persontip"
	);
	ob_start();
	echo '<div style="float: left; margin-top: 5px"><a href="?removeallskips='. $list->id. '">'. escapehtml(_L("Remove all skips")). '</a></div>';
	echo '<div class="pagenav" style="text-align:right;"> Showing '.$total.' records.</div>';
	if(count($data) > 8)
		echo '<div class="scrollTableContainer">';
	echo '<table id="listadds" width="100%" cellpadding="3" cellspacing="1" class="list">';
	showTable($data, $titles, $formatters);
	echo "\n</table>";
	if(count($data) > 8)
		echo '</div>';
	$skipshtml = ob_get_clean();
	
	$formdata["skips"] = array(
		"label" => _L('Skips'),
		"control" => array("FormHtml", 'html' => $skipshtml),
		"helpstep" => 2
	);
}
$advancedtools = '<tr class="listHeader"><th style="text-align:left">' . _L("Tool") . '</th><th style="text-align:left">' . _L("Description") . '</th></tr>';
$advancedtools .= '<tr><td>'.submit_button(_L('Enter Contacts'),'manualAdd','add').'</td><td>'._L('Manually type in new contacts').'</td></tr>';
$advancedtools .= '<tr class="listAlt"><td>'.submit_button(_L('Open Address Book'),'addressBookAdd', 'book_addresses').'</td><td>'._L('Choose from contacts you manually typed into your personal address book').'</td></tr>';
$advancedtools .= '<tr><td>'.submit_button(_L('Quick Pick'),'quickadd','find').'</td><td>'._L('Search for people by name, ID#, email, or phone number').'</td></tr>';
$advancedtools .= '<tr class="listAlt"><td>'.submit_button(_L('Search by Rules'),'search','application_form_add').'</td><td>'._L('Search the shared system contact database using rules').'</td></tr>';

if ($USER->authorize('listuploadids') || $USER->authorize('listuploadcontacts'))
	$advancedtools .= '<tr><td>'.submit_button(_L('Upload List'),'uploadList','folder').'</td><td>'._L('Upload a list of contacts using a CSV file').'</td></tr>';
$formdata[] = _L('Additional List Tools');
$formdata["advancedtools"] = array(
	"label" => '',
	"control" => array("FormHtml", 'html' => "<table  class='list' cellspacing='1' cellpadding='3' style='margin-bottom:10px;'>$advancedtools</table>"),
	"helpstep" => 3
);

if ($method == 'rules'){
	$helpsteps = array (
	_L('Enter a name for your list. The best names describe the list\'s content, making the list easy to reuse.'), // 1
	_L('Rules are used to select groups of contacts from the data available to your account. For example, if you wanted to make a list of 6th graders from Springfield Elementary, you would create two rules: "Grade equals 6" and "School equals Springfield Elementary".'), // 2
	_L('This section contains tools to add specific individuals and add contacts that are not part of your regular database of contacts. '), // 3
	_L('Select the recipients that will be contacted on behalf of this list and select categories to restrict by') // 4
	);

} else {
	$helpsteps = array (
	_L('Enter a name for your list. The best names describe the list\'s content, making the list easy to reuse.'), // 1
	_L('Select a school then select the sections you wish to include in the list.'), // 2
	_L('This section contains tools to add specific individuals and add contacts that are not part of your regular database of contacts. '), // 3
	_L('Select the recipients that will be contacted on behalf of this list and select categories to restrict by') // 4
	);
}


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
		$mode = PeopleList::$RECIPIENTMODE_MAP[1]; //self
		if ($maxguardians) {
			//1=> self, 2=>guardian 3=> selfAndGuardian
			if ($postdata['recipientmode']) {
				$mode = $postdata['recipientmode'];
			}
			$categories = $mode === 'self' ? array() : $postdata['category'];
		}

		$list->name = removeIllegalXmlChars($postdata['name']);
		$list->description = $postdata['description'];
		//if no option selected we use selfAndGuardian mode
		$list->recipientmode = $mode;
		$list->modifydate = QuickQuery("select now()");
		$list->userid = $USER->id;
		$list->deleted = 0;
		$list->type = ($method === 'sections') ? 'section' : 'person';
		$list->update();
		
		if ($maxguardians) {
			ListGuardianCategory::upsertListGuardianCategories($list->id, $categories);
		}

		if ($method == 'sections') {
			QuickUpdate('BEGIN');
			
				// Delete existing section listentries, then add new ones.
				QuickUpdate('delete from listentry where listid=? and type="section"', false, array($list->id));
				foreach ($postdata['sectionids'] as $sectionid) {
					QuickUpdate('insert into listentry set type="section", listid=?, sectionid=?', false, array($list->id, $sectionid));
				}
			
			QuickUpdate('COMMIT');
		}

		$_SESSION['listid'] = $list->id;

		// Save
		if ($list->id) {
			if ($ajax) {
				switch ($button) {
					case 'addrule':
					case 'updaterule':
						$noticemsg = ($button === 'updaterule') ? 'The rule for %s is now updated.' : 'The rule for %s is now added.';
						if ($method === 'rules') {
							$ruledata = json_decode($postdata['newrule']);
							$data = $ruledata[0];
							// CREATE rule.
							if (!isset($data->fieldnum, $data->logical, $data->op, $data->val)) {
								notice(_L('There is a problem adding the rule for %s.', escapehtml(FieldMap::getName($data->fieldnum))));
								$form->sendTo($methodlink);
								break;
							}

							if ($data->fieldnum == 'organization') {
								QuickUpdate('BEGIN');
									QuickUpdate("DELETE FROM listentry WHERE listid=? AND type='organization'", false, array($list->id));
								
									foreach ($data->val as $id) {
										$le = new ListEntry();
										$le->listid = $list->id;
										$le->type = "organization";
										$le->organizationid = $id + 0;
										$le->create();
									}
								
								QuickUpdate('COMMIT');

								notice(_L($noticemsg, getSystemSetting("organizationfieldname","Organization")));
							} else {
								if (!$type = Rule::getType($data->fieldnum)) {
									notice(_L('There is a problem adding the rule for %s.', escapehtml(FieldMap::getName($data->fieldnum))));
									$form->sendTo($methodlink);
									break;
								}

								//first delete the rule
								QuickUpdate("DELETE le.*, r.* FROM listentry le, rule r WHERE le.ruleid=r.id AND le.listid=? AND r.fieldnum=?", false, array($list->id, $data->fieldnum));
								$data->val = prepareRuleVal($type, $data->op, $data->val);
							
								if (!$rule = Rule::initFrom($data->fieldnum, $data->logical, $data->op, $data->val)) {
									notice(_L('There is a problem adding or updating the rule for %s.', escapehtml(FieldMap::getName($data->fieldnum))));
									$form->sendTo($methodlink);
									break;
								}
							
								QuickUpdate('BEGIN');
									$rule->create();
									$le = new ListEntry();
									$le->listid = $list->id;
									$le->type = "rule";
									$le->ruleid = $rule->id;
									$le->create();
								QuickUpdate('COMMIT');
								notice(_L($noticemsg, escapehtml(FieldMap::getName($data->fieldnum))));
							}
						}
						
						$form->sendTo($methodlink);
						break;

					case 'deleterule':
						if ($method === 'rules') {
							$fieldnum = $postdata['ruledelete'];
							if ($fieldnum == 'organization') {
								QuickUpdate("DELETE FROM listentry WHERE listid=? AND type='organization'", false, array($list->id));
							
								notice(_L('The rule for %s is now removed.',getSystemSetting("organizationfieldname","Organization")));
							} else if ($USER->authorizeField($fieldnum)) {
								QuickUpdate("DELETE le.*, r.* FROM listentry le, rule r WHERE le.ruleid=r.id AND le.listid=? AND r.fieldnum=?", false, array($list->id, $fieldnum));
							
								notice(_L('The rule for %s is now removed.', escapehtml(FieldMap::getName($fieldnum))));
							}
						}

						$form->sendTo($methodlink);
						break;

					case 'clearrules':
						if ($method === 'rules') {
							QuickUpdate("DELETE le.*, r.* FROM listentry le, rule r WHERE le.ruleid=r.id AND le.listid=?", false, array($list->id));
							QuickUpdate("DELETE FROM listentry WHERE listid=? AND type='organization'", false, array($list->id));
						
							notice(_L('All rules are now removed.'));
						}
						$form->sendTo($methodlink);
						break;

					case 'refresh': // handled same as case 'done'.
					case 'done':
						if (isset($_SESSION['origin']) && ($_SESSION['origin'] == 'start')) {
							unset($_SESSION['origin']);
							// TODO, Release 7.2, add notice()
							$form->sendTo('start.php');
						} else {
							unset($_SESSION['origin']);
							if ($button == 'refresh')
								$form->sendTo($methodlink);
							$form->sendTo('lists.php');
						}
						break;

					case 'preview':
						$form->sendTo("showlist.php?id=" . $list->id);
						break;

					case 'search':
						$form->sendTo("search.php?listsearchmode=rules&id=" . $list->id);
						break;
					case 'quickadd':
						$form->sendTo("search.php?listsearchmode=individual&id=" . $list->id);
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
	<? Validator::load_validators(array("ValRules", "ValListName", "ValSections")); ?>

	function toggleCategory(){
		if($('list_recipientmode-1').checked)
			$('list_category_fieldarea').hide();
		else
			$('list_category_fieldarea').show();
	}
	document.observe('dom:loaded', function () {
		//first check the initial value
		toggleCategory();
		$('list_recipientmode').observe('click', function(e) {
			toggleCategory();
		});
	});


</script>
<?
startWindow(_L('List Editor'));

echo $form->render();
endWindow();
?>
<script type='text/javascript'>
	document.observe('dom:loaded', function() {
		if (typeof ruleWidget !== "undefined") {
			ruleWidget.delayActions = true;
			function list_add_rule(event) {
				$('list_newrule').value = Object.toJSON([event.memo.ruledata]);
				form_submit(event, 'addrule');
			}
			function list_update_rule(event) {
				$('list_newrule').value = Object.toJSON([event.memo.ruledata]);
				form_submit(event, 'updaterule');
			}
			function list_delete_rule(event) {
				$('list_ruledelete').value = event.memo.fieldnum;
				form_submit(event, 'deleterule');
			}
			function list_clear_rules(event) {
				form_submit(event, 'clearrules');
			}
			ruleWidget.container.observe('RuleWidget:AddRule', list_add_rule);
			ruleWidget.container.observe('RuleWidget:UpdateRule', list_update_rule);
			ruleWidget.container.observe('RuleWidget:DeleteRule', list_delete_rule);
			ruleWidget.container.observe('RuleWidget:RemoveAllRules', list_clear_rules);
		}

	});
</script>
<?
include_once("navbottom.inc.php");
?>
