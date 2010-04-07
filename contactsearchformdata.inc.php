<?
// NOTE to use this include be sure to have set the following:
// $renderedlist = new RenderedList2();
// $buttons = array(); of some buttons
// $redirectpage = ""; the calling page
// optional $additionalformdata = array();

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
} else if (isset($_SESSION['listsearch']['sectionids'])) {
	$renderedlist->initWithSearchCriteria(array(), array(), $_SESSION['listsearch']['sectionids']);
} else if (isset($_SESSION['listsearch']['individual'])) {
	$pkey = $_SESSION['listsearch']['individual']['pkey'];
	$phone = $_SESSION['listsearch']['individual']['phone'];
	$email = $_SESSION['listsearch']['individual']['email'];
	
	$renderedlist->initWithIndividualCriteria($pkey == "" ? false : $pkey,$phone == "" ? false : $phone,$email == "" ? false : $email);
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

$formdata["toggles"] = array(
	"label" => _L('Search Options'),
	"fieldhelp" => _L("Choose which method to search by."),
	"control" => array("FormHtml", 'html' => "
		<input id='searchByRules' type='radio' onclick=\"choose_search_by_rules();\"><label for='searchByRules'> Search by Rules </label>" .
		(getSystemSetting('_hasenrollment') ?
			"<input id='searchBySections' type='radio' onclick=\"choose_search_by_sections();\"><label for='searchBySections'> Search by Sections </label>" :
			""
		) .
		"<input id='searchByPerson' type='radio' onclick=\"choose_search_by_person();\"><label for='searchByPerson'> Search for Person </label>
	"),
	"helpstep" => 2
);

$formdata["ruledata"] = array(
	"label" => _L('Rules'),
	"fieldhelp" => _L("Select rules to filter search results."),
	"value" => $rulewidgetvaluejson,
	"control" => array("FormRuleWidget"),
	"validators" => array(array('ValRules')),
	"helpstep" => 2
);

	if (getSystemSetting('_hasenrollment')) {
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

	$formdata["sectionsearchbutton"] = array(
		"label" => _L(''),
		"control" => array("FormHtml", "html" => "<div id='sectionsearchButtonContainer'>" . submit_button(_L('Search'),'sectionsearch',"magnifier") . "</div>"),
		"helpstep" => 2
	);
}

$formdata["pkey"] = array(
	"label" => _L('Person ID'),
	"fieldhelp" => _L("Search for the person by their ID number."),
	"value" => isset($_SESSION['listsearch']['individual']['pkey']) ? $_SESSION['listsearch']['individual']['pkey'] : '',
	"validators" => array(
	),
	"control" => array("TextField"),
	"helpstep" => 2
);
$formdata["phone"] = array(
	"label" => _L('Phone or SMS Number'),
	"fieldhelp" => _L("Search for the person by their phone or SMS number."),
	"value" => isset($_SESSION['listsearch']['individual']['phone']) ? $_SESSION['listsearch']['individual']['phone'] : '',
	"validators" => array(array("ValPhone")),
	"control" => array("TextField"),
	"helpstep" => 2
);
$formdata["email"] = array(
	"label" => _L('Email Address'),
	"fieldhelp" => _L("Search for the person by their email address."),
	"value" => isset($_SESSION['listsearch']['individual']['email']) ? $_SESSION['listsearch']['individual']['email'] : '',
	"validators" => array(array("ValEmail")),
	"control" => array("TextField"),
	"helpstep" => 2
);

$formdata["personsearchbutton"] = array(
	"label" => _L(''),
	"control" => array("FormHtml", "html" => "<div id='personsearchButtonContainer'>" . submit_button(_L('Search'),'personsearch',"magnifier") . "</div>"),
	"helpstep" => 2
);

if (isset($additionalformdata)) {
	$formdata = array_merge($formdata, $additionalformdata);
}

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
					
				case 'sectionsearch':
					if (getSystemSetting('_hasenrollment')) {
						$_SESSION['listsearch'] = array (
							'sectionids' => $postdata['sectionids']
						);
					}
					break;
					
				case 'personsearch':
					$_SESSION['listsearch'] = array (
						"individual" => array (
							"pkey" => isset($postdata['pkey']) ? $postdata['pkey'] : false,
							"phone" => isset($postdata['phone']) ? $postdata['phone'] : false,
							"email" => isset($postdata['email']) ? $postdata['email'] : false,	
						)					
					);

					break;
					
				case 'refresh':
					break;
			}
			$form->sendTo($redirectpage);
		} else {
			redirect($redirectpage);
		}
	}
}

?>
