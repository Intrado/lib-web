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
require_once('obj/facebook.php');
require_once('inc/facebook.inc.php');
require_once('obj/FormFacebookPages.fi.php');

////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////

////////////////////////////////////////////////////////////////////////////////
// Action/Request Processing
////////////////////////////////////////////////////////////////////////////////

////////////////////////////////////////////////////////////////////////////////
// Optional Form Items And Validators
////////////////////////////////////////////////////////////////////////////////

////////////////////////////////////////////////////////////////////////////////
// Form Data
////////////////////////////////////////////////////////////////////////////////

$formdata = array(
	_L('Facebook Posting'),
	"fbpages" => array(
		"label" => _L('Pages'),
		"value" => json_encode(array("access_token" => $USER->getSetting("fb_access_token", false), "page" => array())),
		"validators" => array(),
		"control" => array("FacebookPages"),
		"helpstep" => 1
	),
	"fbwalltext" => array(
		"label" => _L('Text'),
		"value" => "",
		"validators" => array(),
		"control" => array("TextArea", "cols" => 50, "rows" => 10),
		"helpstep" => 1
	)
);

$helpsteps = array (
	_L('Post this text')
);

$buttons = array(submit_button(_L('Save'),"submit","tick"),
				icon_button(_L('Cancel'),"cross",null,"start.php"));
$form = new Form("templateform",$formdata,$helpsteps,$buttons);

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
		
		// read/store access token
		$fbdata = json_decode($postdata['fbpages']);
		$USER->setSetting("fb_access_token", $fbdata->access_token);
		
		// foreach pageid, post the message
		foreach ($fbdata->page as $pageid => $accessToken) {
			
			if (!fb_post($pageid, $accessToken, $postdata['fbwalltext'])) {
				// TODO: unable to post error
			}
		}
		
		if ($ajax)
			$form->sendTo("testfbpost.php");
		else
			redirect("testfbpost.php");
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
$PAGE = "start:facebooktest";
$TITLE = _L('');

include_once("nav.inc.php");

// Optional Load Custom Form Validators
?>
<script type="text/javascript">
<? Validator::load_validators(array()); ?>
</script>
<?

startWindow(_L('Facebook API Test'));
echo $form->render();
endWindow();
include_once("navbottom.inc.php");
?>