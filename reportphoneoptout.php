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
// Custom Form Controls And Validators
///////////////////////////////////////////////////////////////////////////////

class RestrictedValues extends FormItem {
	var $clearonsubmit = true;
	var $clearvalue = array();
	
	function render ($value) {
		$n = $this->form->name."_".$this->name;
		$style = isset($this->args['height']) ? ('style="height: ' . $this->args['height'] . '; overflow: auto;"') : '';

		$label = (isset($this->args['label']) && $this->args['label'])? $this->args['label']: _L('Restrict to the following:');
		$restrictchecked = count($value) > 0 ? "checked" : "";
		$str = '<input type="checkbox" id="'.$n.'-restrict" '.$restrictchecked .' onclick="restrictcheck(\''.$n.'-restrict\', \''.$n.'\')"><label for="'.$n.'-restrict">'.$label.'</label>';

		$str .= '<div id='.$n.' class="radiobox" '.$style.'>';

		$counter = 1;
		foreach ($this->args['values'] as $checkvalue => $checkname) {
			$id = $n.'-'.$counter;
			$checked = $value == $checkvalue || (is_array($value) && in_array($checkvalue, $value));
			$str .= '<input id="'.$id.'" name="'.$n.'[]" type="checkbox" value="'.escapehtml($checkvalue).'" '.($checked ? 'checked' : '').'  onclick="datafieldcheck(\''.$id.'\', \''.$n.'-restrict\')"/><label id="'.$id.'-label" for="'.$id.'">'.escapehtml($checkname).'</label><br />
				';
			$counter++;
		}
		$str .= '</div>
		';
		return $str;
	}
	
	function renderJavascript($value) {
		return '
		//if we uncheck the restrict box, uncheck each field
		function restrictcheck(restrictcheckbox, checkboxdiv) {
			restrictcheckbox = $(restrictcheckbox);
			checkboxdiv = $(checkboxdiv);
			if (!restrictcheckbox.checked) {
				checkboxdiv.descendants().each(function(e) {
					e.checked = false;
				});
			}
		}

		// if a data field is checked. Check the restrict box
		function datafieldcheck(checkbox, restrictcheckbox) {
			checkbox = $(checkbox);
			restrictcheckbox = $(restrictcheckbox);
			if (checkbox.checked)
					restrictcheckbox.checked = true;
		}';
	}
}

class ValOrganization extends Validator {
	var $onlyserverside = true;

	function validate ($value, $args) {
		if ($value) {
			$validorgs = QuickQueryList("select id, orgkey from organization where id in (". DBParamListString(count($value)) .")", true, false, $value);
			foreach ($value as $id)
				if (!isset($validorgs[$id]))
					return _L('%s has invalid data selected.', $this->label);
		}
		return true;
	}
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

if(isset($_GET['reportid'])){
	$reportid = $_GET['reportid'] +0;
	if(!userOwns("reportsubscription", $reportid)){
		redirect('unauthorized.php');
	}
	$_SESSION['reportid'] = $reportid;
	$subscription = new ReportSubscription($reportid);
	$instance = new ReportInstance($subscription->reportinstanceid);
	$options = $instance->getParameters();

	if (isset($options['order1'])) {
		$preselectedorderby[0] = $options['order1'];
	}
	if (isset($options['order2'])) {
		$preselectedorderby[1] = $options['order2'];
	}
	if (isset($options['order3'])) {
		$preselectedorderby[2] = $options['order3'];
	}
	
	$_SESSION['saved_report'] = true;
	$_SESSION['report']['options'] = $options;
} else {
	$options= isset($_SESSION['report']['options']) ? $_SESSION['report']['options'] : array();
	if(isset($_SESSION['reportid'])){
		$subscription = new ReportSubscription($_SESSION['reportid']);
		$_SESSION['saved_report'] = true;
	} else {
		$_SESSION['saved_report'] = false;
	}
}


$orgs = QuickQueryList("select id, orgkey from organization where not deleted order by orgkey", true);
$selectedorgs = array();
if (isset($_SESSION['report']['options']['organizationids'])) {
	$selectedorgs = $_SESSION['report']['options']['organizationids'];
}

$orgs = $USER->filterOrgs($orgs);

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

if (count($orgs) > 5) {
	$formdata["checkall"] = array (
		"label" => "",
		"control" => array("FormHtml", "html" => icon_button(_L('Check All'),"group_add",'checkAllCheckboxes(true);') . icon_button(_L('Uncheck All'),"group_delete",'checkAllCheckboxes(false);')),
		"helpstep" => 1
	);
}
$control = array("RestrictedValues", "values" => $orgs);
if (count($orgs) > 9) {
	$control = array("RestrictedValues", "height" => "150px", "values" => $orgs);
}
$formdata["organizationids"] = array(
	"label" => getSystemSetting("organizationfieldname","Organization"),
	"fieldhelp" => _L("Use this menu to narrow your report results to those contacts belonging to the selected organizations."),
	"value" => $selectedorgs,
	"validators" => array(
		array("ValInArray", 'values'=>array_keys($orgs))
     ),
	"control" => $control,
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
			$_SESSION['report']['options']['organizationids'] = $postdata['organizationids'];
			
			if (isset($postdata['multipleorderby'])) {
				$multipleorderby = $postdata['multipleorderby'];
				if (is_array($multipleorderby)) {
					$_SESSION['reportphoneoptout_orderby'] = array();
					foreach ($multipleorderby as $i=>$orderby) {
						if (in_array($orderby, $validOrdering)) {
							$_SESSION['reportphoneoptout_orderby'][] = $orderby;
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
	if (!empty($_SESSION['reportphoneoptout_orderby'])) {
		foreach ($_SESSION['reportphoneoptout_orderby'] as $i => $orderby) {
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
	<script type="text/javascript">
		<? Validator::load_validators(array("ValOrganization","ValReldate")); ?>
	</script>
<?
echo $form->render();
endWindow();
?>
<script>
function checkAllCheckboxes(docheck) {

	var form = document.forms[0].elements;
	for (var i = 0; i < form.length; i++) {
		if (form[i].type == "checkbox") {

			if (docheck) {
				if (!form[i].checked)
					form[i].click();
			} else {
				if (form[i].checked)
					form[i].click();
			}
		}
	}
}
</script>

<?
?>
	<script type="text/javascript">
		document.observe('dom:loaded', function() {
				$('metadataDiv').update($('metadataTempDiv').innerHTML);
			});
	</script>
<?
include_once("navbottom.inc.php");
?>
<script type="text/javascript" src="script/datepicker.js"></script>