<?

////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
require_once("inc/common.inc.php");
include_once("inc/securityhelper.inc.php");
require_once("inc/html.inc.php");
require_once("inc/table.inc.php");
require_once("inc/utils.inc.php");
require_once("inc/formatters.inc.php");
require_once("obj/FieldMap.obj.php");
require_once("obj/ReportInstance.obj.php");
require_once("obj/ReportSubscription.obj.php");
require_once("inc/form.inc.php");
require_once("inc/reportutils.inc.php");
require_once("obj/UserSetting.obj.php");
require_once("obj/ReportGenerator.obj.php");
require_once("obj/Validator.obj.php");
require_once("obj/Form.obj.php");
require_once("obj/FormItem.obj.php");
require_once("obj/Phone.obj.php");
require_once("obj/PhoneOptOutReport.obj.php");

			
////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!($USER->authorize('viewsystemreports'))) {
	redirect('unauthorized.php');
}
			
////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

$clear = 0;

if(isset($_GET['clear']) && $_GET['clear']){
	unset($_SESSION['report']['options']);
	unset($_SESSION['reportid']);
	$clear = 1;
}

if($clear)
	redirect();

$preselectedorderby = array();

$validOrdering = PhoneOptOutReport::getOrdering();
$formdata = array();

$formdata["dateoptions"] = array(
	"label" => _L("Date Option"),
	"fieldhelp" => _L("Use this menu to search by the date the contact information was updated."),
	"value" => json_encode(array(
		"reldate" => isset($options['reldate']) ? $options['reldate'] : 'today',
		"xdays" => isset($options['lastxdays']) ? $options['lastxdays'] : '',
		"startdate" => isset($options['startdate']) ? $options['startdate'] : '',
		"enddate" => isset($options['enddate']) ? $options['enddate'] : ''
	)),
	"control" => array("ReldateOptions"),
	"validators" => array(array("ValReldate")),
	"helpstep" => 1
);

$formdata["multipleorderby"] = array(
	"label" => _L('Sort By'),
	"fieldhelp" => _L("Choose which field you would like to sort the results by."),
	"value" => $preselectedorderby,
	"control" => array("MultipleOrderBy", "count" => 3, "values" => $validOrdering),
	"validators" => array(),
	"helpstep" => 1
);


$buttons = array(
	submit_button(_L("View Report"),"view","arrow_refresh"),
	submit_button(_L("Save/Schedule"),"save","arrow_refresh"),
	icon_button(_L('Cancel'),"cross", null, 'reports.php')
);

$form = new Form('reportcallssearch',$formdata,array(),$buttons);
$form->ajaxsubmit = true;
///////////////////////////////////////////////////////////
// FORM HANDLING
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
			$_SESSION['report']['options']['reporttype'] = "phoneoptoutreport";
			$_SESSION['report']['options']['format'] = "csv"; // pdf not supported, always set to csv

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
			
			if (isset($postdata['multipleorderby'])) {
				$multipleorderby = $postdata['multipleorderby'];
				if (is_array($multipleorderby)) {
					$_SESSION['reportcontactchange_orderby'] = array();
					foreach ($multipleorderby as $i=>$orderby) {
						if (in_array($orderby, $validOrdering)) {
							$_SESSION['reportcontactchange_orderby'][] = $orderby;
						}
					}
					set_session_options_orderby();
				}
			}
						
			switch ($button) {
			case 'view':
				$form->sendTo("reportphoneoptoutsummary.php");
				break;
			case 'save':
				set_session_options_activefields();
				$form->sendTo("reportedit.php");
				break;
			}
		} else {
			redirect("reportphoneoptout.php");
		}
	}
}

function set_session_options_activefields() {
	global $fields;

	$activefields = array();
	foreach($fields as $field){
		if(isset($_SESSION['report']['fields'][$field->fieldnum]) && $_SESSION['report']['fields'][$field->fieldnum]){
			$activefields[] = $field->fieldnum;
		}
	}
	$_SESSION['report']['options']['activefields'] = implode(",",$activefields);
}

function set_session_options_orderby() {
	if (!empty($_SESSION['reportcontactchange_orderby'])) {
		foreach ($_SESSION['reportcontactchange_orderby'] as $i => $orderby) {
			$_SESSION['report']['options']["order" . ($i+1)] = $orderby;
		}
	}
}

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$PAGE = "reports:reports";
$TITLE = "Phone Opt-Out";
if (isset($_SESSION['reportid'])) {
	$subscription = new ReportSubscription($_SESSION['reportid']);
	$TITLE .= " - " . escapehtml($subscription->name);
}
include_once("nav.inc.php");

startWindow(_L("Options"), "padding: 3px;");

echo "<div id='metadataTempDiv' style='display:none'>";
	select_metadata(null, null, $fields);
echo "</div>";
?>
		
<?
echo $form->render();
endWindow();
include_once("navbottom.inc.php");
?>
<script type="text/javascript" src="script/datepicker.js"></script>
