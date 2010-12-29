<?
// NOTE to use this include be sure to have set the following:
// $renderedlist = new RenderedList2();
// $buttons = array(); of some buttons to add to the form (ie for done, etc)
// $redirectpage = ""; the calling page
// optional $additionalformdata = array();
// optional $list (a list if editing one)
// optional $disablerenderedlistajax = true to turn off rendered list ajax refreshing (ie activation code manager)


// README need to paste this in each page in data handler section (TOOD move all of this inc into functions so we dont have to include this inline in the page)
/*

//handle list search mode switches (contactsearchformdata.inc.php)
if (isset($_GET['listsearchmode'])) {

	if ($_GET['listsearchmode'] == "rules" && !isset($_SESSION['listsearch']['rules'])) {
		unset($_SESSION['listsearch']); //defaults to rules mode with no search criteria
	}
	
	if ($_GET['listsearchmode'] == "individual" && !isset($_SESSION['listsearch']['individual'])) {
		$_SESSION['listsearch'] = array ("individual" => array ("quickaddsearch" => ''));
	}
	
	if ($_GET['listsearchmode'] == "sections" && !isset($_SESSION['listsearch']['sectionx'])) {
		$_SESSION['listsearch'] = array ("sectionids" => array ());
	}
	
	if ($_GET['listsearchmode'] == "showall" && !isset($_SESSION['listsearch']['showall'])) {
		$_SESSION['listsearch'] = array("showall" => true);
	}
}

*/

//README additionally, you will need to add this after nav.inc for the rule buttons to work
/*

//load validator for rules, handle rule add/delete to form submit (contactsearchformdata.inc.php)
?>
	<script type="text/javascript">
		<? Validator::load_validators(array("ValSections", "ValRules")); ?>

		function rulewidget_add_rule(event) {
			$('listsearch_ruledata').value = event.memo.ruledata.toJSON();
			form_submit(event, 'addrule');
		}

		function rulewidget_delete_rule(event) {
			$('listsearch_ruledata').value = event.memo.fieldnum;
			form_submit(event, 'deleterule');
		}

		document.observe('dom:loaded', function() {
			ruleWidget.delayActions = true;
			ruleWidget.container.observe('RuleWidget:AddRule', rulewidget_add_rule);
			ruleWidget.container.observe('RuleWidget:DeleteRule', rulewidget_delete_rule);
		});
	</script>
<?

*/

$hassomesearchcriteria = true;
if (isset($_SESSION['listsearch']['rules'])) {
	$rules = $_SESSION['listsearch']['rules'];
	//take out any orgids
	$orgids = array();
	if (isset($rules['organization'])) {
		$orgids = array_keys($rules['organization']['val']);
		unset($rules['organization']);
	}
	
	$renderedlist->initWithSearchCriteria($rules, $orgids,array());
} else if (isset($_SESSION['listsearch']['sectionids']) && count($_SESSION['listsearch']['sectionids']) > 0) {
	$renderedlist->initWithSearchCriteria(array(), array(), $_SESSION['listsearch']['sectionids']);
} else if (isset($_SESSION['listsearch']['individual']['quickaddsearch']) && $_SESSION['listsearch']['individual']['quickaddsearch'] != "") {
	$renderedlist->initWithQuickAddSearch($_SESSION['listsearch']['individual']['quickaddsearch']);
} else if (isset($_SESSION['listsearch']['showall'])) {
	$renderedlist->initWithSearchCriteria(array(), array(), array());
} else {
	$hassomesearchcriteria = false;
}

////////////////////////////////////////////////////////////////////////////////
// Form Data
////////////////////////////////////////////////////////////////////////////////

// NOTE: $_SESSION['listsearchrules'] may also contain array("fieldnum" => "organization", "val" =>  $organizationids)
$rulewidgetvaluejson = '';
$rulewidgetdata = array();
if (isset($_SESSION['listsearch']['rules']) && count($_SESSION['listsearch']['rules']) > 0) {
	$rules = $_SESSION['listsearch']['rules'];
	$rulewidgetdata = cleanObjects(array_values($rules));
	$rulewidgetvaluejson = json_encode($rulewidgetdata);
}


$formdata = array();


if (isset($_SESSION['listsearch']['sectionids']))
	$searchmode = "sections";
else if (isset($_SESSION['listsearch']['individual']))
	$searchmode = "individual";
else if (isset($_SESSION['listsearch']['showall']))
	$searchmode = "showall";
else
	$searchmode = "rules";

$togglehtml = '
	<input name="searchbymode" id="searchByRules" type="radio" '. ($searchmode == "rules" ? "checked" : "") . ' onclick="window.location=\'?listsearchmode=rules\'">
	<label for="searchByRules"> Search by Rules </label>
	<input name="searchbymode" id="searchByPerson" type="radio" '. ($searchmode == "individual" ? "checked" : "") . ' onclick="window.location=\'?listsearchmode=individual\'">
	<label for="searchByPerson"> Search for Person </label>
	<input name="searchbymode" id="searchShowAll" type="radio" '. ($searchmode == "showall" ? "checked" : "") . ' onclick="window.location=\'?listsearchmode=showall\'">
	<label for="searchShowAll"> Show All Contacts </label>';


$contactsearchbuttons = array();

if (getSystemSetting('_hasenrollment')) {
	$togglehtml .= '
		<input name="searchbymode" id="searchBySections" type="radio" '. ($searchmode == "sections" ? "checked" : "") . ' onclick="window.location=\'?listsearchmode=sections\'">
		<label for="searchBySections"> Search by Sections </label>';
}

$formdata["toggles"] = array(
	"label" => _L('Search Options'),
	"fieldhelp" => _L("Choose which method to search by."),
	"control" => array("FormHtml", 'html' => $togglehtml),
	"helpstep" => 2
);


if ($searchmode == "rules") {
	
	$formdata["ruledata"] = array(
		"label" => _L('Rules'),
		"fieldhelp" => _L("Select rules to filter search results."),
		"value" => $rulewidgetvaluejson,
		"control" => array("FormRuleWidget"),
		"validators" => array(array('ValRules')),
		"helpstep" => 2
	);
}

if ($searchmode == "sections" && getSystemSetting('_hasenrollment')) {
	$formdata["sectionids"] = array(
		"label" => _L('Sections'),
		"fieldhelp" => _L('Select sections from an organization.'),
		"value" => "",
		"validators" => array(
			array("ValSections")
		),
		"control" => array("SectionWidget", "sectionids" => isset($_SESSION['listsearch']['sectionids']) ? $_SESSION['listsearch']['sectionids'] : array()),
		"helpstep" => 2
	);
	
	$contactsearchbuttons[] = submit_button(_L('Search'),'sectionsearch',"find");
}

if ($searchmode == "individual") {

	$formdata["quickaddsearch"] = array(
		"label" => _L("Search"),
		"fieldhelp" => _L('You may enter a name, phone number, email address, or ID #. 
						You may also enter both a first and last name to narrow the search in either "first last" or "last, first" format.'),
		"value" => "",
		"validators" => array(
			array("ValLength","min" => 2, "max" => 255)
		),
		"control" => array("TextField", "size" => 50, "blankfieldvalue" => "Enter a name, phone number, ID#, or email" ),
		"helpstep" => 2
	);
	
	$contactsearchbuttons[] = submit_button(_L('Search'),"personsearch","find");
}



if (isset($additionalformdata)) {
	$formdata = array_merge($formdata, $additionalformdata);
}

$form = new Form('listsearch',$formdata,array(),array_merge($contactsearchbuttons, $buttons));
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
					
					if (!isset($_SESSION['listsearch']['rules']))
						$_SESSION['listsearch'] = array ('rules' => array());
					
					$data = json_decode($postdata['ruledata']);
					if (isset($data->fieldnum, $data->logical, $data->op, $data->val)) {
						if ($data->fieldnum == 'organization') {
							$orgmap = array();
							
							$organizations = Organization::getAuthorizedOrgKeys();
							foreach ($data->val as $id) {
								$id = $id + 0;
								$orgmap[$id] = $organizations[$id];
							}
							
							$_SESSION['listsearch']['rules']['organization'] = array(
								"fieldnum" => "organization",
								"val" => $orgmap
							);
						} else if ($type = Rule::getType($data->fieldnum)) {
							$data->val = prepareRuleVal($type, $data->op, $data->val);
							
							if ($rule = Rule::initFrom($data->fieldnum, $data->logical, $data->op, $data->val)) {
								$_SESSION['listsearch']['rules'][$data->fieldnum] = $rule;
							}
						}
					}
					
					break;
					
				case 'deleterule':
					$fieldnum = $postdata['ruledata'];
					unset($_SESSION['listsearch']['rules'][$fieldnum]);
					//if user removes last rule, default back to no search mode (instead of showing all)
					if (count($_SESSION['listsearch']['rules']) == 0)
						unset($_SESSION['listsearch']);
					break;
				default:
					
					//for others, we can just check which postdata exists
					//sections
					if (isset($postdata['sectionids'])) {
						if (getSystemSetting('_hasenrollment')) {
							$sids = $postdata['sectionids'];
							// if no sections selected, send array of sectionid=0 to return empty list
							if (!is_array($sids))
								$sids = array(0);
							$_SESSION['listsearch'] = array (
								'sectionids' => $sids
							);
						}
						
					//individual
					} else if (isset($postdata['quickaddsearch'])) {
						$_SESSION['listsearch'] = array (
							"individual" => array (
								"quickaddsearch" => isset($postdata['quickaddsearch']) ? $postdata['quickaddsearch'] : ''
							)
						);
						
						if (!isset($disablerenderedlistajax) || !$disablerenderedlistajax) {
							$renderedlist->initWithQuickAddSearch($postdata['quickaddsearch']);
							
							ob_start();
							$_GET['pagestart'] = 0; //override previous paging offsets which are still stuck in the GET query
							if (isset($list))
								showRenderedListTable($renderedlist, $list);
							else
								showRenderedListTable($renderedlist);
							$renderedlisthtml = ob_get_clean();
							
							$form->modifyElement("renderedlistcontent", $renderedlisthtml);
						}
					}
					break;
					
			}
			$form->sendTo($redirectpage);
		} else {
			redirect($redirectpage);
		}
	}
}

?>
