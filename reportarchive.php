<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
require_once("inc/common.inc.php");
require_once("inc/table.inc.php");
require_once("inc/html.inc.php");
require_once("inc/themes.inc.php");
require_once("obj/Validator.obj.php");
require_once("obj/Form.obj.php");
require_once("obj/FormItem.obj.php");

////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$USER->authorize('viewsystemreports')) {
	redirect('unauthorized.php');
}

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

$reports = QuickQueryList('select date, contentid from reportarchive order by date desc', true);
$archivedata = '<div style="margin-top: 5px; padding: 4px; height: 150px; width: 75px; overflow: auto; border: 1px solid gray; scroll: auto;">';

foreach ($reports as $date => $content) {
	$displaydate = substr($date, 0, 7);
	if ($content) {
		$archivedata .= '<div>
			<a href="download_reportarchive.php/'. escapehtml($displaydate). '.zip?date='. escapehtml($date). '">'. escapehtml($displaydate). '</a>
			</div>';
	} else {
		$archivedata .= '<div>'. escapehtml($date). '</div>';
	}
}
if (!$reports)
	$archivedata .= escapehtml(_L("There are no archived reports at this time."));

$archivedata .= '</div>';


$formdata = array(
	'archive' => array(
		'label' => _L('Monthly Reports'),
		'fieldhelp' => _L("Database clean up routines store old report data in zip archives automatically. You are able to download this report data here."),
		'control' => array('FormHtml', 'html' => $archivedata),
		'helpstep' => 1
	)
);

$buttons = array(submit_button(_L("Done"),"submit","accept"));

$form = new Form("customerinfo", $formdata, array(), $buttons);
$form->ajaxsubmit = true;

//check and handle an ajax request (will exit early)
//or merge in related post data
$form->handleRequest();

if ($button = $form->getSubmit()) { //checks for submit and merges in post data
	$ajax = $form->isAjaxSubmit(); //whether or not this requires an ajax response
	if ($ajax) {
		$form->sendTo("reports.php");
	} else
		redirect("reports.php");
}

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$PAGE = "reports:reports";
$TITLE = _L('Systemwide Report Archive');

require_once("nav.inc.php");

startWindow(_L("Report Archive"));
echo $form->render();
endWindow();

require_once("navbottom.inc.php");
?>

