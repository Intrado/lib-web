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
	_L('Facebook Wall Text'),
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
		
		// get auth data for facebook
		$access_token = $USER->getSetting("fb_page_access_token", false);
		$pageid = $USER->getSetting("fb_pageid", "me");
			
		if ($postdata['fbwalltext'] && $pageid && $access_token) {
			
			
			// set up the post data, the magic is the offline/serverside usable access token
			$post = array(
				'access_token' => $access_token,
				'message' => $postdata['fbwalltext']
			);
			
			// configure facebook app settings
			$fbconfig = array (
				'appId' => $SETTINGS['facebook']['appid'],
				'cookie' => false,
				'secret' => $SETTINGS['facebook']['appsecret']
			);
	
			// get a new instance of the facebook api
			$facebookapi = new Facebook($fbconfig);
			
			// get a session
			$facebookapi->getSession();
			
			// attempt to post to the user's page
			try {
				$facebookapi->api("/$pageid/feed", 'POST', $post);
			} catch (FacebookApiException $e) {
				error_log($e);
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