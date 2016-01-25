<?
require_once("common.inc.php");
require_once("../inc/form.inc.php");
require_once("../inc/html.inc.php");
require_once("../inc/utils.inc.php");
require_once("../inc/table.inc.php");
require_once("../obj/Validator.obj.php");
require_once("../obj/Form.obj.php");
require_once("../obj/FormItem.obj.php");

require_once('../ifc/Page.ifc.php');
require_once('../obj/PageBase.obj.php');
require_once('../obj/PageForm.obj.php');

require_once("obj/SMSAggregator.fi.php");
require_once("obj/SMSAggregatorData.php");
require_once("obj/ShortcodeGroupTools.php");

// Authorize
if (!$MANAGERUSER->authorized("shortcodegrouptools")) {
	exit("Not Authorized");
}

// We will need some functionality from this class (a form item and JMX refreshing)
$smsAggregatorData = new SMSAggregatorData();
$smsAggregatorData->init();

$shortcodeGroupTools = new ShortcodeGroupTools();
$shortcodeGroupTools->init( $smsAggregatorData );


// If we have a request to update shortcodes then do so
if (isset($_POST["confirmNewShortcodeGroups_newShortcodeGroup"])) {

	$newShortcodeGroup = $_POST["confirmNewShortcodeGroups_newShortcodeGroup"];

	$customerIDString = $_POST["confirmNewShortcodeGroups_customerIDString"];
	
	// Attempt to update customer table
	$result = $shortcodeGroupTools->updateCustomerShortcodes( $newShortcodeGroup, $customerIDString );

	// SUCCESS!
	if($result) {
		notice( "Shortcode groups were successfully updated" );
	} else {
		notice( "<b style='color:red'> Query was unsuccessful </b>" );
	}

	$jmxResult = $smsAggregatorData->jmxUpdateShortcodeGroups();

	if(! empty($errorsArray)) {
		$_SESSION['confirmnotice'] = $errorsArray;
	}

}

// Vars to hold HTML for display later.
$bodyContents ='';
$windowTitle = '';
$drawPreviewTable = false;

// If no POST data then display default form
if(isset($_FILES["handleCSVUpload_fileUpload"]["tmp_name"]))  {

	// Get the requested new shortcode group.
	$newShortcodeGroup = $_POST["handleCSVUpload_shortcodegroup"];

	$windowTitle = "Preview and Confirm";

	// Get the CSV file contents
	$fileContents = null;

	if(! empty($_FILES["handleCSVUpload_fileUpload"]["tmp_name"])) {
		$fileContents = file_get_contents($_FILES["handleCSVUpload_fileUpload"]["tmp_name"]);

		$customerIDString = $shortcodeGroupTools->getCustomerIDStringFromFile($fileContents);

		$tableRows = $shortcodeGroupTools->getCustomerShortcodeData($customerIDString, $newShortcodeGroup);

		$drawPreviewTable = true;

		$formWrapper = $shortcodeGroupTools->getPreviewFormWrapper($customerIDString, $newShortcodeGroup);
		$formObj = $shortcodeGroupTools->createForm( $formWrapper );
		$formObj->handleRequest();

		$bodyContents .= "<h1>". $formWrapper["header"] ."</h1><hr />";
		$bodyContents .= $formObj->render();
		$bodyContents .= "<br />";

		
	} else {
		$bodyContents .= 'Missing file.';
	}

} else {

	$formWrapper = $shortcodeGroupTools->getUploadFormWrapper();
	$formObj = $shortcodeGroupTools->createForm( $formWrapper );
	$formObj->handleRequest();

	if ($formObj->getSubmit()) {
		$errors = $formObj->validate();
	}

	$windowTitle = 'Upload File';

	$bodyContents .= "<h1>". $formWrapper["header"] ."</h1><hr />";
	$bodyContents .= $formObj->render(); 
	$bodyContents .= "<br />";

	$bodyContents .=
	'<script type="text/javascript">
		$("handleCSVUpload_shortcodegroup").observe("change", function (event) {
			smsFunctions.showData();
		});
	</script>';

}

// Display page
$TITLE = _L("Shortcode Group Tools");
$PAGE = 'advanced:shortcodegrouptools';

include_once("nav.inc.php");
startWindow($windowTitle);
if($drawPreviewTable) {
	$shortcodeGroupTools->drawPreviewTable( $tableRows );
}
echo $bodyContents;
endWindow();
include_once("navbottom.inc.php");

?>
<script type="text/javascript">
	// Override default form action modification
	jQuery( document ).ready(function() {
		jQuery('form').attr('action', 'shortcodegrouptools.php');
	});
</script>

