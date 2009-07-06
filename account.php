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
require_once("inc/themes.inc.php");
require_once("obj/Validator.obj.php");
require_once("obj/Form.obj.php");
require_once("obj/FormItem.obj.php");
require_once("obj/FormBrandTheme.obj.php");
require_once("obj/FormUserItems.obj.php");

////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$USER->authorize('managemyaccount')) {
	redirect('unauthorized.php');
}

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////
$readonly = $USER->importid != null;
$ldapuser = $USER->ldap;

$usernamelength = getSystemSetting("usernamelength", 5);
if ($USER->ldap)
	$usernamelength = 1;

$passwordlength = getSystemSetting("passwordlength", 5);
$checkpassword = (getSystemSetting("checkpassword")==0) ? getSystemSetting("checkpassword") : 1;
if ($checkpassword) {
	if ($passwordlength < 6) {
		$passwordlength = 6;
	}
	$securityrules = _L('The username must be at least %1$s characters.  The password cannot be made from your username/firstname/lastname.  It cannot be a dictionary word and it must be at least %2$s characters.  It must contain at least one letter and number', $usernamelength, $passwordlength);
} else {
	$securityrules = _L('The username must be at least %1$s characters.  The password cannot be made from your username/firstname/lastname.  It must be at least %2$s characters.  It must contain at least one letter and number', $usernamelength, $passwordlength);
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
		"control" => array("FormHtml","html" => $USER->firstname),
		"helpstep" => 1
	);
	$formdata["lastname"] = array(
		"label" => _L("Last Name"),
		"control" => array("FormHtml","html" => $USER->lastname),
		"helpstep" => 1
	);
} else {
	$formdata["firstname"] = array(
		"label" => _L("First Name"),
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
		"control" => array("FormHtml","html" => $USER->login),
		"helpstep" => 1
	);
} else {
	$formdata["login"] = array(
		"label" => _L("Username"),
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
	$pass = $USER->id ? '00000000' : '';
	$passlength = getSystemSetting("passwordlength", 5);
	if ($readonly) {
		$formdata["password"] = array(
			"label" => _L("Password"),
			"value" => $pass,
			"validators" => array(
				array("ValRequired"),
				array("ValLength","min" => $passlength,"max" => 20),
				array("ValPassword", "login" => $USER->login, "firstname" => $USER->firstname, "lastname" => $USER->lastname)
			),
			"control" => array("TextPasswordStrength","maxlength" => 20, "size" => 25, "minlength" => $passlength),
			"helpstep" => 1
		);
	} else {
		$formdata["password"] = array(
			"label" => _L("Password"),
			"value" => $pass,
			"validators" => array(
				array("ValRequired"),
				array("ValLength","min" => $passlength,"max" => 20),
				array("ValPassword", "login" => $USER->login, "firstname" => $USER->firstname, "lastname" => $USER->lastname)
			),
			"requires" => array("firstname", "lastname", "login"),
			"control" => array("TextPasswordStrength","maxlength" => 20, "size" => 25, "minlength" => $passlength),
			"helpstep" => 1
		);
	}
	
	$formdata["passwordconfirm"] = array(
		"label" => _L("Confirm Password"),
		"value" => $pass,
		"validators" => array(
			array("ValRequired"),
			array("ValLength","min" => $passlength,"max" => 20),
			array("ValFieldConfirmation", "field" => "password")
		),
		"requires" => array("password"),
		"control" => array("PasswordField","maxlength" => 20, "size" => 25),
		"helpstep" => 1
	);
}

if ($readonly) {
	$formdata["accesscode"] = array(
		"label" => _L("Phone User ID"),
		"control" => array("FormHtml","html" => $USER->accesscode),
		"helpstep" => 1
	);
} else {
	$formdata["accesscode"] = array(
		"label" => _L("Phone User ID"),
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
	$formdata["pin"] = array(
		"label" => _L("Phone PIN Code"),
		"value" => $pin,
		"validators" => array(
			array("ValNumeric", "min" => 4),
			array("ValPin", "accesscode" => $USER->accesscode)
		),
		"control" => array("PasswordField","maxlength" => 20, "size" => 8),
		"helpstep" => 1
	);
} else {
	$formdata["pin"] = array(
		"label" => _L("Phone PIN Code"),
		"value" => $pin,
		"validators" => array(
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
	"value" => $pin,
	"validators" => array(
		array("ValNumeric"),
		array("ValFieldConfirmation", "field" => "pin")
	),
	"requires" => array("pin"),
	"control" => array("PasswordField","maxlength" => 20, "size" => 8),
	"helpstep" => 1
);

if ($readonly) {
	$formdata["email"] = array(
		"label" => _L("Email"),
		"control" => array("FormHtml","html" => $USER->email),
		"helpstep" => 1
	);

	$formdata["aremail"] = array(
		"label" => _L("Auto Report Emails"),
		"control" => array("FormHtml","html" => $USER->aremail),
		"helpstep" => 1
	);

	$formdata["phone"] = array(
		"label" => _L("Phone"),
		"control" => array("FormHtml","html" => Phone::format($USER->phone)),
		"helpstep" => 1
	);
} else {
	$formdata["email"] = array(
		"label" => _L("Email"),
		"value" => $USER->email,
		"validators" => array(
			array("ValRequired"),
			array("ValLength","min" => 3,"max" => 255),
			array("ValEmail")
		),
		"control" => array("TextField","maxlength" => 255, "size" => 35),
		"helpstep" => 1
	);

	$formdata["aremail"] = array(
		"label" => _L("Auto Report Emails"),
		"value" => $USER->aremail,
		"validators" => array(
			array("ValLength","min" => 3,"max" => 1024),
			array("ValEmailList")
		),
		"control" => array("TextField","maxlength" => 1024, "size" => 50),
		"helpstep" => 1
	);

	$formdata["phone"] = array(
		"label" => _L("Phone"),
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
$formdata[] = _L("Notification Defaults");

$startvalues = newform_time_select(NULL, $ACCESS->getValue('callearly'), $ACCESS->getValue('calllate'), $USER->getCallEarly());
$formdata["callearly"] = array(
	"label" => _L("Default Start Time"),
	"value" => $USER->getCallEarly(),
	"validators" => array(
	),
	"control" => array("SelectMenu", "values"=>$startvalues),
	"helpstep" => 2
);
$endvalues = newform_time_select(NULL, $ACCESS->getValue('callearly'), $ACCESS->getValue('calllate'), $USER->getCallLate());
$formdata["calllate"] = array(
	"label" => _L("Default End Time"),
	"value" => $USER->getCallLate(),
	"validators" => array(
	),
	"control" => array("SelectMenu", "values"=>$endvalues),
	"helpstep" => 2
);

$usercallmax = $USER->getSetting("callmax", $ACCESS->getValue('callmax'));
$formdata["callmax"] = array(
	"label" => _L("Call Attempts"),
	"value" => $usercallmax,
	"validators" => array(
		array("ValInArray", "values" => range(1,first($ACCESS->getValue('callmax'), 1)))
	),
	"control" => array("SelectMenu", "values"=>array_combine(range(1,first($ACCESS->getValue('callmax'), 1)),range(1,first($ACCESS->getValue('callmax'), 1)))),
	"helpstep" => 2
);

$maxjobdays = $USER->getSetting("maxjobdays", $ACCESS->getValue('maxjobdays'));
$maxdays = $ACCESS->getValue('maxjobdays', 7);
$formdata["maxjobdays"] = array(
	"label" => _L("Days to Run"),
	"value" => $maxjobdays,
	"validators" => array(
		array("ValInArray", "values" => range(1,$maxdays))
	),
	"control" => array("SelectMenu", "values"=>array_combine(range(1,$maxdays),range(1,$maxdays))),
	"helpstep" => 2
);

if ($USER->authorize('setcallerid')) {
	/*CSDELETEMARKER_START*/
	if (getSystemSetting('_hascallback', false)) {
		$formdata["usecallerid"] = array(
			"label" => _L("Caller ID Preference"),
			"value" => "",
			"validators" => array(
				array("ValInArray", "values" => array(0,1,2))
			),
			"control" => array("RadioButton", "values" => 
				array(
					0 => _L("Use toll free"). " - ". Phone::format(getSystemSetting("inboundnumber")),
					1 => _L("Use default"). " - ". Phone::format(getSystemSetting("callerid")),
					2 => _L("Use personal"). " - ". Phone::format($USER->getSetting("callerid",""))
				)
			),
			"helpstep" => 2
		);
	}
	/*CSDELETEMARKER_END*/
	if ($readonly) {
		$formdata["callerid"] = array(
			"label" => _L("Personal Caller ID"),
			"control" => array("FormHtml","html" => Phone::format($USER->getSetting("callerid",""))),
			"helpstep" => 1
		);
	} else {
		$formdata["callerid"] = array(
			"label" => _L("Personal Caller ID"),
			"value" => Phone::format($USER->getSetting("callerid","")),
			"validators" => array(
				array("ValLength","min" => 3,"max" => 20),
				array("ValPhone")
			),
			"control" => array("TextField","maxlength" => 20, "size" => 15),
			"helpstep" => 2
		);
	}
}

// Display Defaults
$formdata[] = _L("Display Settings");

$formdata["actionlinks"] = array(
	"label" => _L("Action Links"),
	"value" => $USER->getSetting("actionlinks","both"),
	"validators" => array(
	),
	"control" => array("SelectMenu", "values"=>array("both"=>"Icons and Text", "icons"=>"Icons Only", "text"=>"Text Only")),
	"helpstep" => 3
);

$formdata["locale"] = array(
	"label" => _L("Display Language"),
	"value" => $USER->getSetting('_locale', getSystemSetting('_locale')),
	"validators" => array(
	),
	"control" => array("SelectMenu", "values"=>$LOCALES),
	"helpstep" => 3
);

$formdata["brandtheme"] = array(
	"label" => _L("Customize Theme"),
	"value" => json_encode(array("theme"=>$USER->getSetting('_brandtheme',getSystemSetting('_brandtheme')), 
		"color"=>$USER->getSetting('_brandprimary',getSystemSetting('_brandprimary')), 
		"ratio"=>$USER->getSetting('_brandratio',getSystemSetting('_brandratio')),
		"customize"=>($USER->getSetting('_brandtheme'))?true:false
		)),
	"validators" => array(array("ValBrandTheme")),
	"control" => array("BrandTheme","values"=>$COLORSCHEMES,"toggle"=>true),
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
		
		if (!$readonly) {
			$USER->firstname = $postdata['firstname'];
			$USER->lastname = $postdata['lastname'];
			$USER->login = $postdata['login'];
			$USER->accesscode = $postdata['accesscode'];
			$USER->email = $postdata['email'];
			$USER->aremail = $postdata['aremail'];
			$USER->phone = $postdata['phone'];
			$USER->update();
			if ($USER->authorize('setcallerid')) {
				$USER->setSetting("callerid",$postdata['callerid']);
				/*CSDELETEMARKER_START*/
				// if customer has callback feature
				if (getSystemSetting('_hascallback', false)) {
					// TODO: Something is missing here. We have three options but only two DB values?
					$USER->setSetting('prefermycallerid', ($postdata['usecallerid'] == 2)? 1: 0);
				}
				/*CSDELETEMARKER_END*/
			}
		}

		// If the password is all 0 characters then it was a default form value, so ignore it
		if(!$USER->ldap) {
			$newpassword = $postdata['password'];
			if (!ereg("^0*$", $newpassword))
				$USER->setPassword($newpassword);
		}

		// If the pincode is all 0 characters then it was a default form value, so ignore it
		$newpin = $postdata['pin'];
		if (!ereg("^0*$", $newpin))
			$USER->setPincode($newpin);
		
		$USER->setSetting("callearly", $postdata['callearly']);
		$USER->setSetting("calllate", $postdata['calllate']);
		$USER->setSetting("callmax", $postdata['callmax']);
		$USER->setSetting("maxjobdays", $postdata['maxjobdays']);
		
		$USER->setSetting("actionlinks", $postdata['actionlinks']);
		$USER->setSetting("_locale", $postdata['locale']);
		$_SESSION['_locale'] = $postdata['locale'];

		$newTheme = json_decode($postdata['brandtheme']);
		
		if ($newTheme->customize) {

			$USER->setSetting("_brandtheme", $newTheme->theme);
			$USER->setSetting("_brandprimary", $newTheme->color);
			$USER->setSetting("_brandratio", $newTheme->ratio);
			$USER->setSetting("_brandtheme1", $COLORSCHEMES[$newTheme->theme]["_brandtheme1"]);
			$USER->setSetting("_brandtheme2", $COLORSCHEMES[$newTheme->theme]["_brandtheme2"]);

			$_SESSION['colorscheme']['_brandtheme'] = $newTheme->theme;
			$_SESSION['colorscheme']['_brandprimary'] = $newTheme->color;
			$_SESSION['colorscheme']['_brandratio'] = $newTheme->ratio;
			$_SESSION['colorscheme']['_brandtheme1'] = $COLORSCHEMES[$newTheme->theme]["_brandtheme1"];
			$_SESSION['colorscheme']['_brandtheme2'] = $COLORSCHEMES[$newTheme->theme]["_brandtheme2"];
			
		} else {
			$USER->setSetting("_brandtheme", "");
			$USER->setSetting("_brandtheme1", "");
			$USER->setSetting("_brandtheme2", "");
			$USER->setSetting("_brandprimary", "");
			$USER->setSetting("_brandratio", "");

			$_SESSION['colorscheme']['_brandtheme'] = getSystemSetting("_brandtheme");
			$_SESSION['colorscheme']['_brandtheme1'] = getSystemSetting("_brandtheme1");
			$_SESSION['colorscheme']['_brandtheme2'] = getSystemSetting("_brandtheme2");
			$_SESSION['colorscheme']['_brandprimary'] = getSystemSetting("_brandprimary");
			$_SESSION['colorscheme']['_brandratio'] = getSystemSetting("_brandratio");
		}
		
		
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
<? Validator::load_validators(array("ValLogin","ValPassword","ValBrandTheme", "ValAccesscode", "ValPin")); ?>
</script>
<?

/*CSDELETEMARKER_START*/
if($USER->authorize('loginphone') && !$IS_COMMSUITE) {
	$tollfree = Phone::format(getSystemSetting("inboundnumber"));
	echo '<br>Your toll free number is: <b>' . $tollfree . '</b><br><br>';
}
/*CSDELETEMARKER_END*/

startWindow(_L("User Information"));
echo $form->render();
endWindow();
include_once("navbottom.inc.php");
?>
