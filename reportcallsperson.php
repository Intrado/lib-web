<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
require_once("inc/common.inc.php");
include_once("inc/securityhelper.inc.php");
require_once("inc/table.inc.php");
require_once("inc/html.inc.php");
require_once("inc/form.inc.php");
require_once("inc/utils.inc.php");
require_once("inc/date.inc.php");
require_once("inc/reportutils.inc.php");
require_once("inc/reportgeneratorutils.inc.php");
require_once("inc/formatters.inc.php");
require_once("obj/FieldMap.obj.php");
require_once("obj/ReportInstance.obj.php");
require_once("obj/ReportGenerator.obj.php");
require_once("obj/ReportSubscription.obj.php");
require_once("obj/CallsReport.obj.php");
require_once("obj/UserSetting.obj.php");
require_once("obj/JobType.obj.php");

////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$USER->authorize('createreport')) {
	redirect('unauthorized.php');
}


////////////////////////////////////////////////////////////////////////////////
// Functions
////////////////////////////////////////////////////////////////////////////////




////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

if(isset($_REQUEST['pid'])){
	$_SESSION['report']['options']['pid'] = $_REQUEST['pid'];
	redirect();
}

$fields = FieldMap::getOptionalAuthorizedFieldMaps();
$f="contacthistory";
$s="displayoptions";
$reload = 0;

if(isset($_REQUEST['reportid'])){
	if(!userOwns("reportsubscription", $_REQUEST['reportid']+0))
		redirect("unauthorized.php");
	$_SESSION['reportid'] = $_REQUEST['reportid'];
	$subscription = new ReportSubscription($_REQUEST['reportid']);
	$instance = new ReportInstance($subscription->reportinstanceid);
	$_SESSION['report']['options'] = $instance->getParameters();
	
	$activefields = array();
	if(isset($_SESSION['report']['options']['activefields'])){
		$activefields = explode(",", $_SESSION['report']['options']['activefields']) ;
	}
	foreach($fields as $field){
		if(in_array($field->fieldnum, $activefields)){
			$_SESSION['fields'][$field->fieldnum] = true;
		} else {
			$_SESSION['fields'][$field->fieldnum] = false;
		}
	}
	redirect();
} else {
	$options = isset($_SESSION['report']['options']) ? $_SESSION['report']['options'] : array();
	$activefields = array();
	foreach($fields as $field){
		// used in pdf,csv
		if(isset($_SESSION['fields'][$field->fieldnum]) && $_SESSION['fields'][$field->fieldnum]){
			$activefields[] = $field->fieldnum; 
		}
	}
	$options['activefields'] = implode(",",$activefields);
	$_SESSION['report']['options'] = $options;
}

if(isset($_SESSION['reportid'])){
	$_SESSION['savedreport'] = true;
} else {
	$_SESSION['savedreport'] = false;
}

$instance = new ReportInstance();
$instance->setParameters($options);
$generator = new CallsReport();
$generator->reportinstance = $instance;
$generator->format = "html";
$generator->userid = $USER->id;

if(CheckFormSubmit($f, $s))
{
	//check to see if formdata is valid
	if(CheckFormInvalid($f))
	{
		print '<div class="warning">Form was edited in another window, reloading data.</div>';
	}
	else
	{
		MergeSectionFormData($f, $s);
		//do check
		if( CheckFormSection($f, $s) ) {
			error('There was a problem trying to save your changes', 'Please verify that all required field information has been entered properly');
		} else {

			$_SESSION['report']['options'] = $options;
			if(CheckFormSubmit($f,"save")){
				redirect("reportedit.php");
			}
			redirect();
		}
	}
} else {
	$reload = 1;
}


if($reload){
	ClearFormData($f);
}


////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$PAGE = "reports:reports";
$TITLE = "Contact History";
if(isset($_SESSION['reportid'])){
	$subscription = new ReportSubscription($_SESSION['reportid']);
	$TITLE .= " - " . $subscription->name;
}

include_once("nav.inc.php");
NewForm($f);

buttons(button("Back", "window.history.go(-1)"), submit($f, $s, "Refresh"));
startWindow("Display Options", "padding: 3px;", "true");
?>
	<table border="0" cellpadding="3" cellspacing="0" width="100%">
		<tr valign="top"><th align="right" class="windowRowHeader bottomBorder">Display Fields:</th>
			<td class="bottomBorder">
<? 		
				select_metadata('searchresultstable', 4, $fields);
?>
			</td>
		</tr>
	</table>
<?
endWindow();
?>
<br>
<?
	
$generator->generate();	

include("navbottom.inc.php");
?>