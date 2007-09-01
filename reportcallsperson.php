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
require_once("obj/Phone.obj.php");
require_once("inc/rulesutils.inc.php");

////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$USER->authorize('createreport')) {
	redirect('unauthorized.php');
}


////////////////////////////////////////////////////////////////////////////////
// Functions
////////////////////////////////////////////////////////////////////////////////

//result formatter for job details.
//index 2 is the delivery type
function fmt_contacthistory_result($row, $index){
	if($row[$index] == "nocontacts"){
		if($row[2] == 'phone')
			return "No Phone #";
		else if($row[2] == 'email')
			return "No Email";
		else 
			return "No Contacts";
	} else {
		return fmt_result($row, $index);
	}
}


////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

if(isset($_GET['pid'])){
	$_SESSION['report']['options']['pid'] = $_GET['pid'];
	redirect();
}

$options = isset($_SESSION['report']['options']) ? $_SESSION['report']['options'] : array();
$fields = FieldMap::getOptionalAuthorizedFieldMaps();
$activefields = array();
foreach($fields as $field){
	// used in pdf,csv
	if(isset($_SESSION['report']['fields'][$field->fieldnum]) && $_SESSION['report']['fields'][$field->fieldnum]){
		$activefields[] = $field->fieldnum; 
	}
}
$options['activefields'] = implode(",",$activefields);

$_SESSION['report']['options'] = $options;

$instance = new ReportInstance();
$instance->setParameters($options);
$generator = new CallsReport();
$generator->reportinstance = $instance;
$generator->format = "html";
$generator->userid = $USER->id;

$f="contacthistory";
$s="displayoptions";
$reload = 0;

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

$fallbackUrl = "reportcallssearch.php";
if(isset($_SESSION['report']['singleperson']))
	$back = button("Back", null, "reportcallssearch.php");
else
	$back = button("Back", "location.href='" . (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $fallbackUrl) . "'");
buttons($back, submit($f, $s, "Refresh"));
startWindow("Display Options", "padding: 3px;", "true");
?>
	<table border="0" cellpadding="3" cellspacing="0" width="100%">
		<tr valign="top"><th align="right" class="windowRowHeader bottomBorder">Display Fields:</th>
			<td class="bottomBorder">
<? 		
				select_metadata('searchresultstable', 5, $fields);
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