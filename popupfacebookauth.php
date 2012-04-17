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
require_once("inc/facebook.php");
require_once("inc/facebookEnhanced.inc.php");
require_once("obj/FacebookAuth.fi.php");
require_once("inc/facebook.inc.php");

////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!getSystemSetting('_hasfacebook', false) || !$USER->authorize('facebookpost'))
	redirect('unauthorized.php');


////////////////////////////////////////////////////////////////////////////////
// Form Data
////////////////////////////////////////////////////////////////////////////////

$formdata = array(
	_L('Connect to Facebook'),
	"facebookauth" => array(
		"label" => null,
		"value" => false,
		"validators" => array(),
		"control" => array("FacebookAuth"),
		"helpstep" => 4)
);

$helpsteps = array (
	_L('<p>To be able to post to Facebook, you must connect to the appropriate Facebook account and allow the SchoolMessenger App.</p>')
);

$buttons = array(submit_button(_L('Save'),"submit","tick"));
$form = new Form("templateform",$formdata,$helpsteps,$buttons,"vertical");

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
		
		// callback the function on the parent form to do something with this authtoken
		$form->modifyElement("callbackdiv", '
				<script type="text/javascript">
					window.opener.document.fire("FbAuth:update", {"access_token": "'. $postdata['facebookauth']. '"});
					window.close();
				</script>');
		return;
	}
}
////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
include_once("popup.inc.php");

startWindow(_L('Facebook Authorization'));
echo $form->render();
?><div id="callbackdiv"></div><?
endWindow();
include_once("popupbottom.inc.php");
?>