<?

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
	$method = ($list->type === 'section') ? 'sections' : 'rules';
	
	$renderedlist = new RenderedList($list);
	$renderedlist->calcStats();
}

if (!isset($method) || !in_array($method, array('rules', 'sections')))
	$method = 'rules';
$methodlink = $method == 'sections' ? 'editlistsections.php' : 'editlistrules.php';

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

$total = isset($renderedlist) ? $renderedlist->total : '0';
$showAdditions = isset($renderedlist) && $renderedlist->totaladded > 0;
$showSkips = isset($renderedlist) && $renderedlist->totalremoved > 0;

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
			array("ValLength","max" => 50),
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
			array("ValLength","max" => 50)
		),
		"control" => array("TextField","size" => 30, "maxlength" => 51),
		"helpstep" => 1
	),
	"preview" => array(
		"label" => 'Total',
		"fieldhelp" => _L('This number indicates how many people are currently in your list. Click the preview button to view contact information.'),
		"control" => array("FormHtml", 'html' => '<div id="listTotal" style="float:left; padding:5px; margin-right: 10px;">'.$total.'</div>' . submit_button(_L('Preview'), 'preview', 'tick')),
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
		"value" => QuickQueryList("
			select s.id, s.skey
			from listentry le
				inner join section s
					on (le.sectionid = s.id)
			where le.listid=? and le.type='section'
			order by s.skey",
			true, false, array($list->id)
		),
		"validators" => array(
			array("ValRequired"),
			array("ValSections")
		),
		"control" => array("SectionWidget"),
		"helpstep" => 2
	);
}

$formdata["additions"] = array(
	"label" => _L('Additions'),
	"control" => array("FormHtml", 'html' => '<div id="removeAllAdditions" style="float:left; margin:0;margin-top:3px;"></div><div id="listAdditionsContainer" style="margin:0; margin-bottom:10px; padding:0"></div>'),
	"helpstep" => 2
);

$formdata["skips"] = array(
	"label" => _L('Skips'),
	"control" => array("FormHtml", 'html' => '<div id="removeAllSkips" style="float:left; margin:0;margin-top:3px;"></div><div id="listSkipsContainer" style="margin:0;padding:0"></div>'),
	"helpstep" => 2
);

$advancedtools = '<tr class="listHeader"><th style="text-align:left">' . _L("Tool") . '</th><th style="text-align:left">' . _L("Description") . '</th></tr>';
$advancedtools .= '<tr><td>'.submit_button(_L('Enter Contacts'),'manualAdd').'</td><td>'._L('Manually type in new contacts').'</td></tr>';
$advancedtools .= '<tr class="listAlt"><td>'.submit_button(_L('Open Address Book'),'addressBookAdd').'</td><td>'._L('Choose from contacts you manually typed into your personal address book').'</td></tr>';
$advancedtools .= '<tr><td>'.submit_button(_L('Search Contacts'),'search').'</td><td>'._L('Search the shared system contact database').'</td></tr>';
if ($USER->authorize('listuploadids') || $USER->authorize('listuploadcontacts'))
	$advancedtools .= '<tr class="listAlt"><td>'.submit_button(_L('Upload List'),'uploadList').'</td><td>'._L('Upload a list of contacts using a CSV file').'</td></tr>';
$formdata[] = _L('Additional List Tools');
$formdata["advancedtools"] = array(
	"label" => '',
	"control" => array("FormHtml", 'html' => "<table  class='list' cellspacing='1' cellpadding='3' style='margin-bottom:10px;'>$advancedtools</table>"),
	"helpstep" => 3
);

$helpsteps = array (
	_L('Enter a name for your list. The best names describe the list\'s content, making the list easy to reuse.'), // 1
	_L('Rules are used to select groups of contacts from the data available to your account. For example, if you wanted to make a list of 6th graders from Springfield Elementary, you would create two rules: "Grade equals 6" and "School equals Springfield Elementary".'), // 2
	_L('This section contains tools to add specific individuals and add contacts that are not part of your regular database of contacts. '), // 3
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
		$list->modifydate = QuickQuery("select now()");
		$list->userid = $USER->id;
		$list->deleted = 0;
		$list->type = ($method === 'sections') ? 'section' : 'person';
		$list->update();
		
		if ($method == 'sections') {
			QuickUpdate('BEGIN');
			
				// Delete existing section listentries, then add new ones.
				QuickUpdate('delete from listentry where listid=? and type="section"', false, array($list->id));
				$sectionids = explode(',', $postdata['sectionids']);
				foreach ($sectionids as $sectionid) {
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
							
								notice(_L('The rule for Organization is now added.'));
							} else {
								if (!$type = Rule::getType($data->fieldnum)) {
									notice(_L('There is a problem adding the rule for %s.', escapehtml(FieldMap::getName($data->fieldnum))));
									$form->sendTo($methodlink);
									break;
								}
							
								$data->val = prepareRuleVal($type, $data->op, $data->val);
							
								if (!$rule = Rule::initFrom($data->fieldnum, $data->logical, $data->op, $data->val)) {
									notice(_L('There is a problem adding the rule for %s.', escapehtml(FieldMap::getName($data->fieldnum))));
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
							
								notice(_L('The rule for %s is now added.', escapehtml(FieldMap::getName($data->fieldnum))));
							}
						}
						
						$form->sendTo($methodlink);
						break;

					case 'deleterule':
						if ($method === 'rules') {
							$fieldnum = $postdata['ruledelete'];
							if ($fieldnum == 'organization') {
								QuickUpdate("DELETE FROM listentry WHERE listid=? AND type='organization'", false, array($list->id));
							
								notice(_L('The rule for Organization is now removed.'));
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

					case 'clearadditions':
						QuickUpdate("DELETE le.* FROM listentry le WHERE le.type='add' AND le.listid=?", false, array($list->id));

						notice(_L('All additions are now removed.'));
						$form->sendTo($methodlink);
						break;

					case 'clearskips':
						QuickUpdate("DELETE le.* FROM listentry le WHERE le.type='negate' AND le.listid=?", false, array($list->id));

						notice(_L('All skips are now removed.'));
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
	<? Validator::load_validators(array("ValRules", "ValListName", "ValSections")); ?>
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
				$('list_newrule').value = [event.memo.ruledata].toJSON();
				form_submit(event, 'addrule');
			}
			function list_delete_rule(event) {
				$('list_ruledelete').value = event.memo.fieldnum;
				form_submit(event, 'deleterule');
			}
			function list_clear_rules(event) {
				form_submit(event, 'clearrules');
			}
			ruleWidget.container.observe('RuleWidget:AddRule', list_add_rule);
			ruleWidget.container.observe('RuleWidget:DeleteRule', list_delete_rule);
			ruleWidget.container.observe('RuleWidget:RemoveAllRules', list_clear_rules);
		}
		
		$('listAdditionsContainer').up('tr').hide();
		$('listSkipsContainer').up('tr').hide();
		
		<?php if ($showAdditions) { ?>
			if ($('listAdditionsContainer')) {
				$('listAdditionsContainer').up('tr').show();
				$('listAdditionsContainer').update('<?=addslashes(list_get_results_html('listAdditionsContainer', $renderedlist))?>');
			}
		<?php } ?>

		<?php if ($showSkips) { ?>
			if ($('listSkipsContainer')) {
				$('listSkipsContainer').up('tr').show();
				$('listSkipsContainer').update('<?=addslashes(list_get_results_html('listSkipsContainer', $renderedlist))?>');
			}
		<?php } ?>

		$('removeAllAdditions').insert(action_link('<?=addslashes(_L("Remove All Additions"))?>', 'diagona/16/101', 'removeAllAdditionsLink').observe('click', function(event) {
			form_submit(event, 'clearadditions');
		}).setStyle({'margin':'0'}));
		$('removeAllAdditions').down('img').remove(); // no icon necessary
		$('removeAllSkips').insert(action_link('<?=addslashes(_L("Remove All Skips"))?>', 'diagona/16/101', 'removeAllSkipsLink').observe('click', function(event) {
			form_submit(event, 'clearskips');
		}).setStyle({'margin':'0'}));
		$('removeAllSkips').down('img').remove(); // no icon necessary
	});
</script>
<?
include_once("navbottom.inc.php");
?>
