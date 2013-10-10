<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
require_once("inc/common.inc.php");
require_once("inc/securityhelper.inc.php");
require_once("inc/table.inc.php");
require_once("inc/html.inc.php");
require_once("inc/utils.inc.php");
require_once("obj/Validator.obj.php");
require_once("obj/Form.obj.php");
require_once("obj/FormItem.obj.php");

////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////

if(!(getSystemSetting('_hastargetedmessage', false) && $USER->authorize('viewsystemreports'))){
	redirect('unauthorized.php');
}

////////////////////////////////////////////////////////////////////////////////
// Action/Request Processing
////////////////////////////////////////////////////////////////////////////////
$doredirect = false;

if (isset($_GET['clear']) && $_GET['clear']) {
	unset($_SESSION['report']['options']);
	$doredirect = true;
}

if (!isset($_SESSION['report']['options'])) {
	$_SESSION['report']['options'] = array('classroomreporttype' => 'person');
}

if (isset($_GET['type'])) {
	$_SESSION['report']['options']['classroomreporttype'] = $_GET['type'] == 'organization'?'organization':'person';
	$doredirect = true;
}

if($doredirect)
	redirect();

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////


////////////////////////////////////////////////////////////////////////////////
// Custom Validators
////////////////////////////////////////////////////////////////////////////////
class ValContactHistorySearch extends Validator {
	function validate ($value, $args, $requiredvalues) {
		global $formdata;

		switch ($requiredvalues[$args['field']]) {
			case 'phone':
				$validator = new ValPhone();
				$validator->label = $formdata["searchvalue"]["label"];
				$validator->name = "ValPhone";

				return $validator->validate($value, $args);

			case 'email':
				$validator = new ValEmail();
				$validator->label = $formdata["searchvalue"]["label"];
				$validator->name = "ValEmail";
				return $validator->validate($value, $args);

			case 'personid':
				return true;

			default:
				return $this->label . _L(" is not valid");
		}
	}

	function getJSValidator () {
		return
			'function (name, label, value, args, requiredvalues) {
				switch (requiredvalues[args["field"]]) {
					case "phone":
						var validator = new document.validators["ValPhone"](name,label,args);
						return validator.validate(name, label, value, args);

					case "email":
						var validator = new document.validators["ValEmail"](name,label,args);
						return validator.validate(name, label, value, args);

					case "personid":
						return true;

					default:
						return label + " is not valid";
				}
			}';
	}
}

////////////////////////////////////////////////////////////////////////////////
// Form Data
////////////////////////////////////////////////////////////////////////////////
$formdata = array();
$helpsteps = array();
$helpstepscount = 1;

$options = $_SESSION['report']['options'];
if($options['classroomreporttype'] == 'person') {

	if (!empty($options['personid'])) {
		$searchmethod = 'personid';
		$searchvalue = $options['personid'];
	} else if (!empty($options['phone'])) {
		$searchmethod = 'phone';
		$searchvalue = $options['phone'];
	} else if (!empty($options['email'])) {
		$searchmethod = 'email';
		$searchvalue = $options['email'];
	} else {
		$searchmethod = 'personid';
		$searchvalue = '';
	}
	$validsearchmethods = array(
		"personid" => _L("ID Number"), // pkey
		//"phone" => _L("Phone"),
		"email" => _L("Email")
	);
	$formdata["searchmethod"] = array(
		"label" => _L("Search By"),
		"fieldhelp" => _L("Search for an individual by their ID number or Email address."),
		"value" => $searchmethod,
		"control" => array("RadioButton", "values" => $validsearchmethods),
		"validators" => array(array("ValRequired"), array("ValInArray", "values" => array_keys($validsearchmethods))),
		"helpstep" => $helpstepscount
	);
	$helpsteps[] = _L('You can search for an individual by their ID Number or Email address. Select which one you would prefer to use.');
	$helpstepscount++;

	$formdata["searchvalue"] = array(
		"label" => _L("Search Value"),
		"fieldhelp" => _L("Enter a search value that matches the type you selected above."),
		"value" => $searchvalue,
		"control" => array("TextField"),
		"validators" => array(
			array("ValRequired"),
			array("ValContactHistorySearch", "field" => "searchmethod")
		),
		"requires" => array("searchmethod"),
		"helpstep" => $helpstepscount
	);
	$helpsteps[] = _L('Enter a term to search for. It must be an ID Number or Email address, depending on what you chose in the previous step.');
	$helpstepscount++;
}

if ($options['classroomreporttype'] == 'organization') {	
	$orgs = Organization::getAuthorizedOrgKeys();	
	$orgSelect = array(0 => escapehtml(_L("All " . getSystemSetting("organizationfieldname","Organization") . "s"))) + $orgs;

	$formdata["organizationid"] = array(
		"label" => _L("Organization"),
		"fieldhelp" => _L("Select the organization that the report should cover."),
		"value" =>  $orgSelect[0],
		"validators" => array(),
		"control" => array("SelectMenu", "values" => $orgSelect),
		"helpstep" => $helpstepscount
	);
	$helpsteps[] = _L('The Organization menu contains the list of available Organizations to filter your search results by.');
	$helpstepscount++;
}

$formdata["dateoptions"] = array(
	"label" => _L("Date Options"),
	"fieldhelp" => _L("Select the date or date range that the report should cover."),
	"value" => json_encode(array(
		"reldate" => isset($options['reldate']) ? $options['reldate'] : 'today',
		"xdays" => isset($options['lastxdays']) ? $options['lastxdays'] : '',
		"startdate" => isset($options['startdate']) ? $options['startdate'] : '',
		"enddate" => isset($options['enddate']) ? $options['enddate'] : ''
	)),
	"control" => array("ReldateOptions"),
	"validators" => array(array("ValReldate")),
	"helpstep" => $helpstepscount
);

$helpsteps[] = _L('The Date Options menu contains date options that are relative to today as well as date ranges which you can configure.');
// $helpstepscount++;

$buttons = array(
	submit_button(_L("View Report"),"view","arrow_refresh"),
	icon_button(_L('Cancel'),"cross", null, 'reports.php')
);
$form = new Form("classroomreport",$formdata,$helpsteps,$buttons);

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
		if ($ajax) {
			if ($button == 'view') {
				if($options['classroomreporttype'] == 'person') {
					if ($postdata['searchmethod'] == 'personid') {
						$_SESSION['report']['options']['personid'] = $postdata['searchvalue'];
						unset($_SESSION['report']['options']['email']);
					}
					else if ($postdata['searchmethod'] == 'email') {
						$_SESSION['report']['options']['email'] = $postdata['searchvalue'];
						unset($_SESSION['report']['options']['personid']);
					}
				}
				else if ($options['classroomreporttype'] == 'organization') {
					$_SESSION['report']['options']['organizationid'] = $postdata['organizationid'];
				}

				$dateOptions = json_decode($postdata['dateoptions'], true);
				if (!empty($dateOptions['reldate'])) {
					$_SESSION['report']['options']['reldate'] = $dateOptions['reldate'];

					if ($dateOptions['reldate'] == 'xdays' && isset($dateOptions['xdays'])) {
						$_SESSION['report']['options']['lastxdays'] = $dateOptions['xdays'] + 0;
					} else if ($dateOptions['reldate'] == 'daterange') {
						if (!empty($dateOptions['startdate']))
							$_SESSION['report']['options']['startdate'] = $dateOptions['startdate'];
						if (!empty($dateOptions['enddate']))
							$_SESSION['report']['options']['enddate'] = $dateOptions['enddate'];
					}
				}
				// if requestion the person report there might be multiple match result, redirect to reportclassroomresult.php
				$form->sendTo($options['classroomreporttype'] == 'person'?"reportclassroomresult.php":"reportclassroom.php");
			}
		} else {
			redirect("reports.php");
		}
	}
}

////////////////////////////////////////////////////////////////////////////////
// Display Functions
////////////////////////////////////////////////////////////////////////////////

function fmt_template ($obj, $field) {
	return $obj->$field;
}

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
$PAGE = "reports:reports";
//$TITLE = _L('Classroom Messaging Report');

if(isset($_SESSION['report']['options']['classroomreporttype'])){
	if($_SESSION['report']['options']['classroomreporttype'] == 'organization'){
		$TITLE = "Classroom Messaging Summary";
	} else if($_SESSION['report']['options']['classroomreporttype'] == 'person'){
		$TITLE = "Classroom Messaging Report";
	}
}


include_once("nav.inc.php");

// Optional Load Custom Form Validators
?>
<script type="text/javascript">
<? Validator::load_validators(array("ValReldate","ValContactHistorySearch")); ?>
</script>
<?

startWindow(_L('Options'));
echo $form->render();
endWindow();
include_once("navbottom.inc.php");
?>
