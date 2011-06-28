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
require_once("obj/TwitterAuth.fi.php");
require_once("inc/twitteroauth/OAuth.php");
require_once("inc/twitteroauth/twitteroauth.php");
require_once("obj/Twitter.obj.php");

////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!getSystemSetting('_hastwitter', false) || !$USER->authorize('twitterpost'))
	redirect('unauthorized.php');


////////////////////////////////////////////////////////////////////////////////
// Form Data
////////////////////////////////////////////////////////////////////////////////

$formdata = array(
	_L('Connect to Twitter'),
	"twitterauth" => array(
		"label" => "",
		"value" => false,
		"validators" => array(),
		"control" => array("TwitterAuth"),
		"helpstep" => 1));

$helpsteps = array (
	_L('TODO: help')
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
					window.opener.document.fire("TwAuth:update", {"access_token": "'. $postdata['twitterauth']. '"});
					window.close();
				</script>');
		return;
	}
}
////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
include_once("popup.inc.php");

startWindow(_L('Twitter Authorization'));
echo $form->render();
?><div id="callbackdiv"></div><?
endWindow();
include_once("popupbottom.inc.php");
?>