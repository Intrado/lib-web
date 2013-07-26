<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
require_once("inc/common.inc.php");
require_once("inc/securityhelper.inc.php");
require_once("inc/table.inc.php");
require_once("inc/html.inc.php");
require_once("inc/utils.inc.php");
require_once("inc/form.inc.php");
require_once("obj/Phone.obj.php");
require_once("obj/Validator.obj.php");
require_once("obj/Form.obj.php");
require_once("obj/FormItem.obj.php");
require_once("obj/FormUserItems.obj.php");
require_once("inc/facebook.php");
require_once("inc/facebookEnhanced.inc.php");
require_once("obj/FacebookAuth.fi.php");
require_once("inc/facebook.inc.php");
require_once("obj/TwitterAuth.fi.php");
require_once("inc/twitteroauth/OAuth.php");
require_once("inc/twitteroauth/twitteroauth.php");
require_once("obj/Twitter.obj.php");
require_once("obj/CallerID.fi.php");
require_once("obj/ValTimeWindowCallEarly.val.php");
require_once("obj/ValTimeWindowCallLate.val.php");
////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$USER->authorize('managemyaccount')) {
	redirect('unauthorized.php');
}

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////
$readonly = false;
if ($USER->importid) {
	$readonly = true;
	$query = "select * from import where id = '{$USER->importid}';";
	if ($importRes = Query($query)) {
		$importInfo = $importRes->fetch(PDO::FETCH_ASSOC); // there should only be one result...
		$readonly = ($importInfo['updatemethod'] != 'createonly'); // readonly for any import updatemethod other than 'createonly'
	}
}

$ldapuser = $USER->ldap;

$usernamelength = getSystemSetting("usernamelength", 5);
if ($USER->ldap)
	$usernamelength = 1;

$passwordlength = getSystemSetting("passwordlength", 5); // minimum password length, poor naming
$checkpassword = (getSystemSetting("checkpassword")==0) ? getSystemSetting("checkpassword") : 1;
if ($checkpassword) {
	if ($passwordlength < 6) {
		$passwordlength = 6;
	}
	$securityrules = _L('The username must be at least %1$s characters.  The password cannot be made from your username/firstname/lastname.  It cannot be a dictionary word and it must be at least %2$s characters.  It must contain at least one letter and number', $usernamelength, $passwordlength);
} else {
	$securityrules = _L('The username must be at least %1$s characters.  The password cannot be made from your username/firstname/lastname.  It must be at least %2$s characters.  It must contain at least one letter and number', $usernamelength, $passwordlength);
}

// if oauth_token is set, this is a redirect back from twitter authorization
if (isset($_GET['oauth_token']) && isset($_GET['oauth_verifier']) && isset($_SESSION['twitterRequestToken'])) {
	$twitter = new Twitter($_SESSION['twitterRequestToken']['oauth_token']);
	$twAccessToken = $twitter->getAccessToken($_GET['oauth_verifier']);
	$USER->setSetting("tw_access_token", json_encode($twAccessToken));
	unset($_SESSION['twitterRequestToken']);
	redirect();
}

////////////////////////////////////////////////////////////////////////////////
// Form Data
////////////////////////////////////////////////////////////////////////////////
$formdata = array();
$helpsteps = array();

$formdata[] = _L("Account Information");

if ($readonly) {
	$formdata["firstname"] = array(
		"label" => _L("First Name"),
		"fieldhelp" => _L("The user's first name."),
		"control" => array("FormHtml","html" => $USER->firstname),
		"helpstep" => 1
	);
	$formdata["lastname"] = array(
		"label" => _L("Last Name"),
		"fieldhelp" => _L("The user's last name."),
		"control" => array("FormHtml","html" => $USER->lastname),
		"helpstep" => 1
	);
} else {
	$formdata["firstname"] = array(
		"label" => _L("First Name"),
		"fieldhelp" => _L("The user's first name."),
		"value" => $USER->firstname,
		"validators" => array(
			array("ValRequired"),
			array("ValLength","min" => 1,"max" => 50)
		),
		"control" => array("TextField","maxlength" => 50, "size" => 30),
		"helpstep" => 1
	);
	$formdata["lastname"] = array(
		"label" => _L("Last Name"),
		"fieldhelp" => _L("The user's last name."),
		"value" => $USER->lastname,
		"validators" => array(
			array("ValRequired"),
			array("ValLength","min" => 1,"max" => 50)
		),
		"control" => array("TextField","maxlength" => 50, "size" => 30),
		"helpstep" => 1
	);
}

if ($ldapuser || $readonly) {
	$formdata["login"] = array(
		"label" => _L("Username"),
		"fieldhelp" => _L("The username is used to log into the account online."),
		"control" => array("FormHtml","html" => $USER->login),
		"helpstep" => 1
	);
} else {
	$formdata["login"] = array(
		"label" => _L("Username"),
		"fieldhelp" => _L("The username is used to log into the account online."),
		"value" => $USER->login,
		"validators" => array(
			array("ValRequired"),
			array("ValLength","min" => getSystemSetting("usernamelength", 5),"max" => 20),
			array("ValLogin", "userid" => $USER->id)
		),
		"control" => array("TextField","maxlength" => 20, "size" => 20),
		"helpstep" => 1
	);
}

if (!$ldapuser) {
	$pass = $USER->id ? 'nopasswordchange' : '';
	$passlength = getSystemSetting("passwordlength", 5);
	if ($readonly) {
		$formdata["password"] = array(
			"label" => _L("Password"),
			"fieldhelp" => _L("The password is used to log into this account online."),
			"value" => $pass,
			"validators" => array(
				array("ValRequired"),
				array("ValLength","min" => $passlength,"max" => 50),
				array("ValPassword", "login" => $USER->login, "firstname" => $USER->firstname, "lastname" => $USER->lastname)
			),
			"control" => array("TextPasswordStrength","maxlength" => 50, "size" => 25, "minlength" => $passlength),
			"helpstep" => 1
		);
	} else {
		$formdata["password"] = array(
			"label" => _L("Password"),
			"fieldhelp" => _L("The password is used to log into this account online."),
			"value" => $pass,
			"validators" => array(
				array("ValRequired"),
				array("ValLength","min" => $passlength,"max" => 50),
				array("ValPassword", "login" => $USER->login, "firstname" => $USER->firstname, "lastname" => $USER->lastname)
			),
			"requires" => array("firstname", "lastname", "login"),
			"control" => array("TextPasswordStrength","maxlength" => 50, "size" => 25, "minlength" => $passlength),
			"helpstep" => 1
		);
	}

	$formdata["passwordconfirm"] = array(
		"label" => _L("Confirm Password"),
		"fieldhelp" => _L("Enter your password a second time to make sure it is correct."),
		"value" => $pass,
		"validators" => array(
			array("ValRequired"),
			array("ValLength","min" => $passlength,"max" => 50),
			array("ValFieldConfirmation", "field" => "password")
		),
		"requires" => array("password"),
		"control" => array("PasswordField","maxlength" => 50, "size" => 25),
		"helpstep" => 1
	);
}

if ($readonly) {
	$formdata["accesscode"] = array(
		"label" => _L("Phone User ID"),
		"fieldhelp" => _L("The telephone user ID is used to log into your account via phone."),
		"control" => array("FormHtml","html" => $USER->accesscode),
		"helpstep" => 1
	);
} else {
	$formdata["accesscode"] = array(
		"label" => _L("Phone User ID"),
		"fieldhelp" => _L("The telephone user ID is used to log into your account via phone."),
		"value" => $USER->accesscode,
		"validators" => array(
			array("ValNumeric", "min" => 4),
			array("ValAccesscode", "userid" => $USER->id)
		),
		"control" => array("TextField","maxlength" => 20, "size" => 8),
		"helpstep" => 1
	);
}

$pin = $USER->accesscode ? '00000' : '';
if ($readonly) {
	$validators = array(
						array("ValNumeric", "min" => 4),
						array("ValPin", "accesscode" => $USER->accesscode));
	if ($USER->accesscode)
		$validators[] = array("ValRequired");
	
	$formdata["pin"] = array(
		"label" => _L("Phone PIN Code"),
		"fieldhelp" => _L("The PIN code is your password for logging into your account via phone."),
		"value" => $pin,
		"validators" => $validators,
		"control" => array("PasswordField","maxlength" => 20, "size" => 8),
		"helpstep" => 1
	);
} else {
	$formdata["pin"] = array(
		"label" => _L("Phone PIN Code"),
		"fieldhelp" => ("The PIN code is your password for logging into your account via phone."),
		"value" => $pin,
		"validators" => array(
			array("ValConditionallyRequired", "field" => "accesscode"),
			array("ValNumeric", "min" => 4),
			array("ValPin", "accesscode" => $USER->accesscode)
		),
		"requires" => array("accesscode"),
		"control" => array("PasswordField","maxlength" => 20, "size" => 8),
		"helpstep" => 1
	);
}

$formdata["pinconfirm"] = array(
	"label" => _L("Confirm PIN"),
	"fieldhelp" => ("Enter your PIN code a second time to make sure it's correct."),
	"value" => $pin,
	"validators" => array(
		array("ValConditionallyRequired", "field" => "pin"),
		array("ValNumeric"),
		array("ValFieldConfirmation", "field" => "pin")
	),
	"requires" => array("pin"),
	"control" => array("PasswordField","maxlength" => 20, "size" => 8),
	"helpstep" => 1
);

if ($readonly) {
	$formdata["email"] = array(
		"label" => _L("Account Email"),
		"fieldhelp" => ("This is used for forgot passwords, reporting, and as the return address in email messages."),
		"control" => array("FormHtml","html" => $USER->email),
		"helpstep" => 1
	);

	$formdata["aremail"] = array(
		"label" => _L("Auto Report Emails"),
		"fieldhelp" => ("If reports should be sent to any additional email addresses, enter them here."),
		"control" => array("FormHtml","html" => $USER->aremail),
		"helpstep" => 1
	);

	$formdata["phone"] = array(
		"label" => _L("Phone"),
		"fieldhelp" => ("Enter your direct access phone number here."),
		"control" => array("FormHtml","html" => Phone::format($USER->phone)),
		"helpstep" => 1
	);
} else {
	$formdata["email"] = array(
		"label" => _L("Account Email"),
		"fieldhelp" => ("This is used for forgot passwords, reporting, and as the return address in email messages."),
		"value" => $USER->email,
		"validators" => array(
			array("ValLength","min" => 0,"max" => 255),
			array("ValEmail")
		),
		"control" => array("TextField","maxlength" => 255, "size" => 35),
		"helpstep" => 1
	);

	$formdata["aremail"] = array(
		"label" => _L("Auto Report Emails"),
		"fieldhelp" => ("If reports should be sent to any additional email addresses, enter them here."),
		"value" => $USER->aremail,
		"validators" => array(
			array("ValLength","min" => 0,"max" => 1024),
			array("ValEmailList")
		),
		"control" => array("TextField","maxlength" => 1024, "size" => 50),
		"helpstep" => 1
	);

	$formdata["phone"] = array(
		"label" => _L("Phone"),
		"fieldhelp" => ("Enter your direct access phone number here."),
		"value" => Phone::format($USER->phone),
		"validators" => array(
			array("ValLength","min" => 2,"max" => 20),
			array("ValPhone")
		),
		"control" => array("TextField","maxlength" => 20, "size" => 15),
		"helpstep" => 1
	);
}
// Notification Defaults
$formdata[] = _L("%s Defaults", getJobTitle());

$startvalues = newform_time_select(NULL, $ACCESS->getValue('callearly'), $ACCESS->getValue('calllate'), $USER->getCallEarly());
$formdata["callearly"] = array(
	"label" => _L("Default Start Time"),
	"fieldhelp" => ("This is the earliest time to send calls. This is also determined by your security profile."),
	"value" => $USER->getCallEarly(),
	"validators" => array(
		array("ValRequired"),
		array("ValInArray", "values" => array_keys($startvalues)),
		array("ValTimeWindowCallEarly")
	),
	"control" => array("SelectMenu", "values"=>$startvalues),
	"requires" => array("calllate"),
	"helpstep" => 2
);
$endvalues = newform_time_select(NULL, $ACCESS->getValue('callearly'), $ACCESS->getValue('calllate'), $USER->getCallLate());
$formdata["calllate"] = array(
	"label" => _L("Default End Time"),
	"fieldhelp" => ("This is the latest time to send calls. This is also determined by your security profile."),
	"value" => $USER->getCallLate(),
	"validators" => array(
		array("ValRequired"),
		array("ValInArray", "values" => array_keys($endvalues)),
		array("ValTimeWindowCallLate")
	),
	"control" => array("SelectMenu", "values"=>$endvalues),
	"requires" => array("callearly"),
	"helpstep" => 2
);

$callmax = $ACCESS->getValue('callmax');
if (!$callmax)
	$callmax = 10;
$usercallmax = $USER->getSetting("callmax", 3);
$formdata["callmax"] = array(
	"label" => _L("Call Attempts"),
	"fieldhelp" => ("This indicates the default number of times the system should try to call an individual number before considering the message undelivered."),
	"value" => $usercallmax,
	"validators" => array(
		array("ValRequired"),
		array("ValNumber", "min" => 1, "max" => $callmax),
		array("ValNumeric")
	),
	"control" => array("SelectMenu", "values"=>array_combine(range(1,first($callmax, 1)),range(1,first($callmax, 1)))),
	"helpstep" => 2
);

$maxjobdays = $USER->getSetting("maxjobdays", $ACCESS->getValue('maxjobdays'));
$maxdays = $ACCESS->getValue('maxjobdays');
if (!$maxdays)
	$maxdays = 7;
$formdata["maxjobdays"] = array(
	"label" => _L("Days to Run"),
	"fieldhelp" => ("Use this menu to set the default number of days your jobs should run."),
	"value" => $maxjobdays,
	"validators" => array(
		array("ValRequired"),
		array("ValNumber", "min" => 1, "max" => $maxdays),
		array("ValNumeric")
	),
	"control" => array("SelectMenu", "values"=>array_combine(range(1,$maxdays),range(1,$maxdays))),
	"helpstep" => 2
);

if (!getSystemSetting('_hascallback', false)) {
	$requireapprovedcallerid = getSystemSetting("requireapprovedcallerid",false);
	$setcallerid = $USER->authorize('setcallerid');
	
	if ($readonly || (!$requireapprovedcallerid && !$setcallerid)) {
		$formdata["callerid"] = array(
			"label" => _L("Personal Caller ID"),
			"fieldhelp" => ("Enter the Caller ID phone number to be associated with your jobs."),
			"control" => array("FormHtml","html" => Phone::format($USER->getSetting("callerid",""))),
			"helpstep" => 1
		);
	} else {
		$callerids = getAuthorizedUserCallerIDs($USER->id);
		$formdata["callerid"] = array(
			"label" => _L("Personal Caller ID"),
			"fieldhelp" => ("Enter the Caller ID phone number to be associated with your jobs."),
			"value" => $USER->getSetting("callerid",""),
			"validators" => array(
				array("ValLength","min" => 0,"max" => 20),
				array("ValPhone"),
				array("ValCallerID")
			),
			"control" => array("CallerID","maxlength" => 20, "size" => 15,"selectvalues"=>$callerids, "allowedit" => $setcallerid),
			"helpstep" => 2
		);
	}
}

// Social Media options
if ((getSystemSetting('_hastwitter', false) && $USER->authorize('twitterpost')) || 
		(getSystemSetting('_hasfacebook', false) && $USER->authorize('facebookpost'))) {
	$formdata[] = _L('Social Media Options');
}
	
if (getSystemSetting('_hasfacebook', false) && $USER->authorize('facebookpost')) {
	
	$formdata["facebookauth"] = array(
		"label" => _L('Add Facebook Account'),
		"fieldhelp" => _L("Authorize this application to post to your Facebook account. If you want to authorize a different account, you must first log out of Facebook from their web site."),
		"value" => false,
		"validators" => array(),
		"control" => array("FacebookAuth"),
		"helpstep" => 4
	);
}

if (getSystemSetting('_hastwitter', false) && $USER->authorize('twitterpost')) {
	
	$formdata["twitterauth"] = array(
		"label" => _L('Add Twitter Account'),
		"fieldhelp" => _L("Authorize this application to post to your Twitter account. If you want to authorize a different account, you must first log out of Twitter from their web site."),
		"value" => false,
		"validators" => array(),
		"control" => array("TwitterAuth", "submit" => true),
		"helpstep" => 4
	);
}


// Display Defaults
$formdata[] = _L("Display Settings");

$actionlinkvalues = array("both"=>"Icons and Text", "icons"=>"Icons Only", "text"=>"Text Only");
$formdata["actionlinks"] = array(
	"label" => _L("Action Links"),
	"fieldhelp" => ("This determines the appearance of the Actions column on all Builder pages. You can choose to have text, icons, or both."),
	"value" => $USER->getSetting("actionlinks","both"),
	"validators" => array(
		array("ValRequired"),
		array("ValInArray", "values" => array_keys($actionlinkvalues))
	),
	"control" => array("SelectMenu", "values" => $actionlinkvalues),
	"helpstep" => 3
);

/*
// release 7.0 not translated, take this option out for now
$formdata["locale"] = array(
	"label" => _L("Display Language"),
	"fieldhelp" => ("Use this menu to select the language for the user interface."),
	"value" => $USER->getSetting('_locale', getSystemSetting('_locale')),
	"validators" => array(
	),
	"control" => array("SelectMenu", "values"=>$LOCALES),
	"helpstep" => 3
);
*/

$formdata["hideemailtools"] = array(
	"label" => _L("Hide Email Editor Tools"),
	"fieldhelp" => ("For a less cluttered UI, you can hide the email editor's toolbar by default."),
	"value" => $USER->getSetting('hideemailtools'),
	"validators" => array(),
	"control" => array("CheckBox"),
	"helpstep" => 3
);

$buttons = array(submit_button(_L("Done"),"submit","tick"),
				icon_button(_L("Cancel"),"cross",null,"start.php"));

$form = new Form("account", $formdata, null, $buttons);
$form->ajaxsubmit = true;

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

		//save data here
		Query('BEGIN');

		if (!$readonly) {
			$USER->firstname = $postdata['firstname'];
			$USER->lastname = $postdata['lastname'];
			if (!$USER->ldap) {
				$USER->login = $postdata['login'];
			}
			$USER->accesscode = $postdata['accesscode'];
			$USER->email = $postdata['email'];
			$USER->aremail = $postdata['aremail'];
			$USER->phone = Phone::parse($postdata['phone']);
			$USER->update();
			
			if (isset($postdata['callerid'])) {
				$callerid = Phone::parse($postdata['callerid']);
				if (canSetCallerid($callerid)) {
					$USER->setSetting("callerid",$callerid);
				}
			}
		}

		// If the pincode is all 0 characters then it was a default form value, so ignore it
		$newpin = $postdata['pin'];
		if (!preg_match("/^0*$/", $newpin))
			$USER->setPincode($newpin);

		$USER->setSetting("callearly", $postdata['callearly']);
		$USER->setSetting("calllate", $postdata['calllate']);
		$USER->setSetting("callmax", $postdata['callmax']);
		$USER->setSetting("maxjobdays", $postdata['maxjobdays']);

		$USER->setSetting("actionlinks", $postdata['actionlinks']);
		//$USER->setSetting("_locale", $postdata['locale']);
		//$_SESSION['_locale'] = $postdata['locale'];
		$USER->setSetting("hideemailtools", $postdata['hideemailtools']);
		
		Query('COMMIT');

		// MUST set password outside of the transaction or the authserver will get a lock timeout on the user object
		// If the password is all 0 characters then it was a default form value, so ignore it
		if (!$USER->ldap) {
			$newpassword = $postdata['password'];
			if ($newpassword !== "nopasswordchange")
				$USER->setPassword($newpassword);
		}
		
		// check submit button, if it's twitter auth request, redirect to twitterauth
		if ($button == "twitterauth") {
			$thispage = substr($_SERVER["SCRIPT_NAME"], strrpos($_SERVER["SCRIPT_NAME"], "/") + 1);
			$form->sendTo("twitterauth.php/$thispage");
		}
		
		// TODO, Release 7.2, add notice()

		if ($ajax)
			$form->sendTo("start.php");
		else
			redirect("start.php");
	}
}


////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$PAGE = "start:account";
$TITLE = _L("Account Information") . ": " . escapehtml($USER->firstname) . " " . escapehtml($USER->lastname);

include_once("nav.inc.php");

?>
<script type="text/javascript">
<? Validator::load_validators(array("ValLogin","ValPassword", "ValAccesscode", "ValPin","ValCallerID","ValTimeWindowCallEarly","ValTimeWindowCallLate")); ?>
</script>
<?

if($USER->authorize('loginphone')) {
	$tollfree = Phone::format(getSystemSetting("inboundnumber"));
	echo '<br>Your toll free number is: <b>' . $tollfree . '</b><br><br>';
}

startWindow(_L("User Information"));
echo $form->render();
endWindow();
include_once("navbottom.inc.php");
?>
