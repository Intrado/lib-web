<?

////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
require_once("inc/common.inc.php");
include_once("inc/securityhelper.inc.php");
require_once("obj/Job.obj.php");
require_once("obj/Phone.obj.php");
require_once("inc/html.inc.php");
require_once("inc/table.inc.php");
require_once("inc/utils.inc.php");
require_once("inc/reportutils.inc.php");
require_once("inc/formatters.inc.php");
require_once("obj/FieldMap.obj.php");
require_once("obj/MessageAttachment.obj.php");
require_once("obj/SurveyQuestionnaire.obj.php");
require_once("obj/SurveyQuestion.obj.php");
require_once("obj/ReportInstance.obj.php");
require_once("obj/ReportGenerator.obj.php");
require_once("obj/ReportSubscription.obj.php");
require_once("inc/reportgeneratorutils.inc.php");
require_once("inc/form.inc.php");
require_once("obj/UserSetting.obj.php");
require_once("inc/date.inc.php");
require_once("obj/AttachmentDetailReport.obj.php");
require_once("obj/JobType.obj.php");
require_once("obj/Person.obj.php");
require_once("inc/rulesutils.inc.php");

////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$USER->authorize('createreport') && !$USER->authorize('viewsystemreports')) {
	redirect('unauthorized.php');
}

////////////////////////////////////////////////////////////////////////////////
// Functions
////////////////////////////////////////////////////////////////////////////////
function fmt_dst_src($row, $index) {
	if ($row[$index] != null) {
		$type = $row[$index + 1];
		$maxtypes = fetch_max_types();
		$actualsequence = isset($maxtypes[$type]) ? ($row[$index] % $maxtypes[$type]) : $row[$index];
		return escapehtml(destination_label($type, $actualsequence));
	} else {
		return "";
	}
}

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

$ffields = FieldMap::getOptionalAuthorizedFieldMapsLike('f');
$gfields = FieldMap::getOptionalAuthorizedFieldMapsLike('g');
$fields = $ffields + $gfields;

unset($_SESSION['report']['edit']);
$redirect = 0;
if (isset($_GET['reportid'])) {
	$_SESSION['reportid'] = $_GET['reportid']+0;
	if (!userOwns("reportsubscription", $_SESSION['reportid'])) {
		redirect("unauthorized.php");
	}
	$subscription = new ReportSubscription($_SESSION['reportid']);
	$instance = new ReportInstance($subscription->reportinstanceid);
	$options = $instance->getParameters();
	$activefields = array();
	if (isset($options['activefields'])) {
		$activefields = explode(",", $options['activefields']) ;
	}
	foreach ($fields as $field) {
		if (in_array($field->fieldnum, $activefields)) {
			$_SESSION['report']['fields'][$field->fieldnum] = true;
		} else {
			$_SESSION['report']['fields'][$field->fieldnum] = false;
		}
	}
	$_SESSION['report']['options'] = $options;
	redirect();
}

if (isset($_GET['attachmentid'])) {
	$_SESSION['report']['jobdetail']=1;
	$options = $_SESSION['report']['options'];
	$options['attachmentid'] = $_GET['attachmentid'];
	$_SESSION['report']['options'] = $options;
	$redirect = 1;
}

if (!isset($_SESSION['report']['options'])) {
	redirect("reports.php");
}

if ($redirect) {
	redirect();
}

// $ordering = AttachmentDetailReport::getOrdering();
$ordercount=3;

$pagestartflag=0;
$pagestart=0;
if (isset($_GET['pagestart'])) {
	$pagestart = (int) $_GET['pagestart'];
	$pagestartflag=1;
}

$options = $_SESSION['report']['options'];
$options["pagestart"] = $pagestart;

if (!isset($_SESSION['reportid'])) {
	$_SESSION['saved_report'] = false;
}

/* @TODO
if (!isset($_SESSION['report']['fields'])) {
	foreach ($fields as $field) {
		$fieldnum = $field->fieldnum;
		$usersetting = DBFind("UserSetting", "from usersetting where name = '" . DBSafe($field->fieldnum) . "' and userid = '$USER->id'");
		$_SESSION['report']['fields'][$fieldnum] = false;
		if ($usersetting!= null && $usersetting->value == "true") {
			$_SESSION['report']['fields'][$fieldnum] = true;
		}
	}
}

$activefields = array();
foreach ($fields as $field) {
	// used in pdf,csv
	if (isset($_SESSION['report']['fields'][$field->fieldnum]) && $_SESSION['report']['fields'][$field->fieldnum]) {
		$activefields[] = $field->fieldnum;
	}
}
$options['activefields'] = implode(",",$activefields);
*/
$instance = new ReportInstance();

if (isset($_SESSION['reportid'])) {
	$_SESSION['saved_report'] = true;
} else {
	$_SESSION['saved_report'] = false;
}
if (isset($options['jobid'])) {
	$jobid = $options['jobid'];
	if (!(userOwns("job",$jobid) || $USER->authorize('viewsystemreports'))) {
		redirect('unauthorized.php');
	}
}

if (isset($jobid)) {
	$job = new Job($jobid);
}

$_SESSION['report']['options'] = $options;

$options['pagestart'] = $pagestart;

$instance->setParameters($options);
$reportgenerator = new AttachmentDetailReport();
$reportgenerator->reportinstance = $instance;
$reportgenerator->userid = $USER->id;

if (isset($_GET['csv']) && $_GET['csv']) {
	$reportgenerator->format = "csv";
} else if (isset($_GET['pdf']) && $_GET['pdf']) {
	$reportgenerator->format = "pdf";
} else {
	$reportgenerator->format = "html";
}

$f="reports";
$s="jobs";
$reload = 0;
$submit=0;

if (CheckFormSubmit($f,$s) || CheckFormSubmit($f, "save")) {
	//check to see if formdata is valid
	if(CheckFormInvalid($f)) {
		error('Form was edited in another window, reloading data');
		$reload = 1;
	} else {
		MergeSectionFormData($f, $s);
		//do check
		if (CheckFormSection($f, $s)) {
			error('There was a problem trying to save your changes', 'Please verify that all required field information has been entered properly');
		} else {
			$submit=1;
			$options = $instance->getParameters();
			$hideinprogress = GetFormData($f, $s, "hideinprogress");
			if ($hideinprogress) {
				$options['hideinprogress'] = "true";
			} else {
				$options['hideinprogress'] = "false";
			}
			for ($i=1; $i<=$ordercount; $i++) {
				$options["order$i"] = DBSafe(GetFormData($f, $s, "order$i"));
			}
			$_SESSION['report']['options']= $options;

			if (CheckFormSubmit($f, "save")) {
				$_SESSION['report']['edit'] = 1;
				ClearFormData($f);
				redirect("reportedit.php");
			}
			redirect();
		}
	}
} else {
	$reload = 1;
}

if ($reload) {
	ClearFormData($f);
	for ($i=1; $i<=$ordercount; $i++) {
		$order="order$i";
		if ($i==1) {
			if (!isset($options[$order])) {
				if (isset($_SESSION['reportid'])) {
					$orderquery = "";
				} else {
					$orderquery = "rp.pkey";
				}
			} else {
				$orderquery = $options[$order];
			}
			PutFormData($f, $s, $order, $orderquery);
		} else {
			PutFormData($f, $s, $order, isset($options[$order]) ? $options[$order] : "");
		}
	}
	$hideinprog = 0;
	if (isset($options['hideinprogress']) && $options['hideinprogress'] == 'true') {
		$hideinprog = 1;
	}

	PutFormData($f, $s, "hideinprogress", $hideinprog, "bool", "0", "1");

}

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$error = false;
if ($reportgenerator->format != "html") {
	if ($reportgenerator->format == "pdf") {
		if ($result = $reportgenerator->testSize()) {
			error($result);
			$error = true;
		} else {
			$reportgenerator->generate();
		}
	} else {
		$reportgenerator->generate();
	}
}

if ($error || $reportgenerator->format == "html") {
	$reportgenerator->format = "html";
	$reportgenerator->generateQuery();
	$PAGE = "reports:reports";
	$TITLE = "Hosted Attachments Log";
	if (isset($options['attachmentid'])) {
		$attachment = new MessageAttachment($options['attachmentid']);
		$TITLE .= " - " . escapehtml($attachment->displayName);
	}

	include_once("nav.inc.php");
	NewForm($f);

	$csvbutton = icon_button("Download CSV", "page_white_excel", null, "reportattachmentdetails.php/report.csv?csv=true");

	//check to see if referer came from summary page. if so, go to history instead of referer
	if (isset($_SESSION['report']['jobdetail']) || $error || $submit || $pagestartflag) {
		$back = icon_button(_L("Back"), "fugue/arrow_180", "window.history.go(-1)");
	} else {
		$fallbackUrl = "reports.php";
		$back = icon_button(_L("Back"), "fugue/arrow_180", "location.href='" . (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $fallbackUrl) . "'");
	}
	buttons($back, submit($f, $s, "Refresh", null, "arrow_refresh"));
	startWindow("Display Options ".help('ReportAttachmentDetails_DisplayOptions'), "padding: 3px;", "true");
	?>
	<table border="0" cellpadding="3" cellspacing="0" width="100%">
		<tr><th align="right" class="windowRowHeader bottomBorder">Output Format:</th>
			<td class="bottomBorder"> <? echo $csvbutton ?> </td>
		</tr>
<?
		if (isset($_SESSION['report']['options']['reporttype']) && $_SESSION['report']['options']['reporttype'] == "notcontacted") {
?>
			<tr><th align="right" class="windowRowHeader"><?= _L("Finalized %s:", getJobsTitle()) ?></th>
				<td class="bottomBorder"><?=NewFormItem($f, $s, "hideinprogress", "checkbox");?>Only Display Final Results</td>
			</tr>
<?
		}
?>
	</table>
	<?
	endWindow();

	if (isset($reportgenerator)) {
		$reportgenerator->runHtml();
	}
	buttons();
	endForm();
	include_once("navbottom.inc.php");
}
