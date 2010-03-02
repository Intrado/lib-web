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
$helpsteps = array ();
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
		"value" => $searchmethod,
		"control" => array("RadioButton", "values" => $validsearchmethods),
		"validators" => array(array("ValRequired"), array("ValInArray", "values" => array_keys($validsearchmethods))),
		"helpstep" => $helpstepscount
	);
	$helpsteps[] = _L('Search Method');
	$helpstepscount++;

	$formdata["searchvalue"] = array(
		"label" => _L("Search Value"),
		"value" => $searchvalue,
		"control" => array("TextField"),
		"validators" => array(
			array("ValRequired"),
			array("ValContactHistorySearch", "field" => "searchmethod")
		),
		"requires" => array("searchmethod"),
		"helpstep" => $helpstepscount
	);
	$helpsteps[] = _L('Search Value');
	$helpstepscount++;
}

$formdata["dateoptions"] = array(
	"label" => _L("Date Options"),
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

$helpsteps[] = _L('Date Reange');
$helpstepscount++;

$buttons = array(
	icon_button(_L('Back'), 'fugue/arrow_180', null, 'reports.php'),
	submit_button(_L("View Report"),"view","arrow_refresh")
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
$TITLE = _L('Classroom Comment Report');

include_once("nav.inc.php");

// Optional Load Custom Form Validators
?>
<script type="text/javascript">
<? Validator::load_validators(array("ValReldate","ValContactHistorySearch")); ?>
</script>
<?

startWindow(_L('Classroom Comment Report'));
echo $form->render();
endWindow();
include_once("navbottom.inc.php");
?>
