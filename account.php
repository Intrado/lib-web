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
require_once("inc/text.inc.php");
require_once("obj/Phone.obj.php");
require_once("inc/themes.inc.php");

require_once("obj/Validator.obj.php");
require_once("obj/Form.obj.php");
require_once("obj/FormItem.obj.php");
require_once("obj/FormBrandTheme.obj.php");


////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$USER->authorize('managemyaccount')) {
	redirect('unauthorized.php');
}

////////////////////////////////////////////////////////////////////////////////
// Validators
////////////////////////////////////////////////////////////////////////////////

class ValLogin extends Validator {
    var $onlyserverside = true;
    
    function validate ($value, $args) {
		global $USER;
		if (User::checkDuplicateLogin($value, $USER->id))
			return "$this->label already exists, please choose another.";
		else
			return true;
    }
}

class ValPassword extends Validator {
    var $onlyserverside = true;
    
    function validate ($value, $args, $requiredvalues) {
		global $USER;
		if ($USER->ldap) return true;

		if ($detail = validateNewPassword($requiredvalues['login'], $value, $requiredvalues['firstname'], $requiredvalues['lastname']))
			return "$this->label is invalid.  ".$detail;

		$checkpassword = (getSystemSetting("checkpassword")==0) ? getSystemSetting("checkpassword") : 1;
		if ($checkpassword && ($detail = isNotComplexPass($value)) && !ereg("^0*$", $value))
			return "$this->label is invalid.  ".$detail;

		return true;
    }
}


////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

$readonly = $USER->importid != null;

$usernamelength = getSystemSetting("usernamelength", 5);
if ($USER->ldap)
	$usernamelength = 1;

$passwordlength = getSystemSetting("passwordlength", 5);
$checkpassword = (getSystemSetting("checkpassword")==0) ? getSystemSetting("checkpassword") : 1;
if ($checkpassword) {
	if ($passwordlength < 6) {
		$passwordlength = 6;
	}
	$securityrules = "The username must be at least " . $usernamelength . " characters.  The password cannot be made from your username/firstname/lastname.  It cannot be a dictionary word and it must be at least " . $passwordlength . " characters.  It must contain at least one letter and number";
} else {
	$securityrules = "The username must be at least " . $usernamelength . " characters.  The password cannot be made from your username/firstname/lastname.  It must be at least " . $passwordlength . " characters.  It must contain at least one letter and number";
}


$formdata = array();
$helpsteps = array();

// readonly users have some data from imports, so not all of it goes into the form
if ($readonly) {

} else {
	$formdata[] = "Account Information";
	$formdata["firstname"] = array(
        "label" => "First Name",
        "value" => $USER->firstname,
        "validators" => array(
            array("ValRequired"),
            array("ValLength","min" => 1,"max" => 50)
        ),
        "control" => array("TextField","maxlength" => 50),
        "helpstep" => 1
    );
	$formdata["lastname"] = array(
        "label" => "Last Name",
        "value" => $USER->lastname,
        "validators" => array(
            array("ValRequired"),
            array("ValLength","min" => 1,"max" => 50)
        ),
        "control" => array("TextField","maxlength" => 50),
        "helpstep" => 1
    );
	$formdata["login"] = array(
        "label" => "Username",
        "value" => $USER->login,
        "validators" => array(
            array("ValRequired"),
            array("ValLength","min" => $usernamelength,"max" => 20),
            array("ValLogin")
            // TODO unique in system
        ),
        "control" => array("TextField","maxlength" => 20),
        "helpstep" => 1
    );
    
    $pass = $USER->id ? '00000000' : '';
    $formdata["password"] = array(
        "label" => "Password",
        "value" => $pass,
        "validators" => array(
            array("ValRequired"),	// TODO not if ldap
            array("ValLength","min" => $passwordlength,"max" => 20),
            array("ValPassword")
            // TODO not like first/last/login
        ),
        "requires" => array("firstname", "lastname", "login"),
        "control" => array("PasswordField","maxlength" => 20),
        "helpstep" => 1
    );
	$formdata["passwordconfirm"] = array(
        "label" => "Confirm Password",
        "value" => $pass,
        "validators" => array(
            array("ValRequired"),	// TODO not if ldap
            array("ValLength","min" => $passwordlength,"max" => 20),
            array("ValFieldConfirmation", "field" => "password")
        ),
        "requires" => array("password"),
        "control" => array("PasswordField","maxlength" => 20),
        "helpstep" => 1
    );
    
	$formdata["accesscode"] = array(
        "label" => "Telephone User ID#",
        "value" => $USER->accesscode,
        "validators" => array(
        	array("ValNumeric")
        	// unique in system
        ),
        "control" => array("TextField","maxlength" => 20),
        "helpstep" => 1
    );
    $pin = $USER->accesscode ? '00000000' : '';
    $formdata["pin"] = array(
        "label" => "Telephone PIN Code#",
        "value" => $pin,
        "validators" => array(
        	array("ValNumeric")
        	//TODO
        ),
        "requires" => array("accesscode"),
        "control" => array("PasswordField","maxlength" => 20),
        "helpstep" => 1
    );
	$formdata["pinconfirm"] = array(
        "label" => "Confirm Telephone PIN",
        "value" => $pin,
        "validators" => array(
        	array("ValNumeric"),
        	array("ValFieldConfirmation", "field" => "pin")
        ),
        "requires" => array("pin"),
        "control" => array("PasswordField","maxlength" => 20),
        "helpstep" => 1
    );
	$formdata["email"] = array(
        "label" => "Email",
        "value" => $USER->email,
        "validators" => array(
            array("ValRequired"),
            array("ValLength","min" => 3,"max" => 255),
            array("ValEmail")
        ),
        "control" => array("TextField","maxlength" => 255),
        "helpstep" => 1
    );
	$formdata["aremail"] = array(
        "label" => "Auto Report Email(s)",
        "value" => $USER->aremail,
        "validators" => array(
            array("ValLength","min" => 3,"max" => 1024),
            array("ValEmailList")
        ),
        "control" => array("TextField","maxlength" => 1024), // TODO maxlength for text
        "helpstep" => 1
    );
	$formdata["phone"] = array(
        "label" => "Phone",
        "value" => Phone::format($USER->phone),
        "validators" => array(
            array("ValLength","min" => 2,"max" => 20),
            array("ValPhone")
        ),
        "control" => array("TextField","maxlength" => 20),
        "helpstep" => 1
    );
}

// Notification Defaults
$formdata[] = "Notification Defaults";

$startvalues = newform_time_select(NULL, $ACCESS->getValue('callearly'), $ACCESS->getValue('calllate'), $USER->getCallEarly());
$formdata["callearly"] = array(
        "label" => "Default Delivery Window (Earliest)",
        "value" => $USER->getCallEarly(),
        "validators" => array(
        ),
        "control" => array("SelectMenu", "values"=>$startvalues),
        "helpstep" => 2
);
$endvalues = newform_time_select(NULL, $ACCESS->getValue('callearly'), $ACCESS->getValue('calllate'), $USER->getCallLate());
$formdata["calllate"] = array(
        "label" => "Default Delivery Window (Latest)",
        "value" => $USER->getCallLate(),
        "validators" => array(
        ),
        "control" => array("SelectMenu", "values"=>$endvalues),
        "helpstep" => 2
);
if (($usercallmax = $USER->getSetting("callmax")) === false) {
	$usercallmax = min(4,$ACCESS->getValue('callmax'));
} else {
	$usercallmax = min($USER->getSetting("callmax"), $ACCESS->getValue('callmax'));
}
$callmax = first($ACCESS->getValue('callmax'), 1);
$callattemptvalues = array();
for ($i=1; $i<=$callmax; $i++) {
	$callattemptvalues[$i] = $i;
}
$formdata["callmax"] = array(
        "label" => "Call Attempts",
        "value" => $usercallmax,
        "validators" => array(
        ),
        "control" => array("SelectMenu", "values"=>$callattemptvalues),
        "helpstep" => 2
);
if (($maxjobdays = $USER->getSetting("maxjobdays")) === false) {
	$maxjobdays = 1;
} else {
	$maxjobdays = min($USER->getSetting("maxjobdays"), $ACCESS->getValue('maxjobdays'));
}
$maxdays = $ACCESS->getValue('maxjobdays');
if ($maxdays == null) {
	$maxdays = 7; // Max out at 7 days if the permission is not set.
}
$jobdaysvalues = array();
for ($i=1; $i<=$maxdays; $i++) {
	$jobdaysvalues[$i] = $i;
}
$formdata["maxjobdays"] = array(
        "label" => "Days to Run",
        "value" => $maxjobdays,
        "validators" => array(
        ),
        "control" => array("SelectMenu", "values"=>$jobdaysvalues),
        "helpstep" => 2
);
$calleridvalues = array();
$calleridvalues[] = "Use toll free " . Phone::format(getSystemSetting("inboundnumber"));
$calleridvalues[] = "Use default " . Phone::format(getSystemSetting("callerid"));
$calleridvalues[] = "Use personal " . Phone::format($USER->getSetting("callerid",""));
$formdata["usecallerid"] = array(
        "label" => "Caller ID Preference",
        "value" => "",
        "validators" => array(
        ),
        "control" => array("RadioButton", "values" => $calleridvalues),
        "helpstep" => 2
);
$formdata["callerid"] = array(
        "label" => "Personal Caller ID",
        "value" => Phone::format($USER->getSetting("callerid","")),
        "validators" => array(
            array("ValLength","min" => 3,"max" => 20),
            array("ValPhone")
        ),
        "control" => array("TextField","maxlength" => 20),
        "helpstep" => 2
);

$formdata[] = "Display Settings";

// Display Defaults
$formdata["actionlinks"] = array(
		"label" => "Action Links",
        "value" => $USER->getSetting("actionlinks","both"),
        "validators" => array(
        ),
        "control" => array("SelectMenu", "values"=>array("both"=>"Icons and Text", "icons"=>"Icons Only", "text"=>"Text Only")),
        "helpstep" => 3
);
$formdata["locale"] = array(
		"label" => "Display Language",
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

$buttons = array(submit_button(_L("Done"),"submit","accept"),
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
        
        $USER->firstname = $postdata['firstname'];
        $USER->lastname = $postdata['lastname'];
		$USER->login = $postdata['login'];
		$USER->accesscode = $postdata['accesscode'];
		$USER->email = $postdata['email'];
		$USER->aremail = $postdata['aremail'];
		$USER->phone = $postdata['phone'];
		$USER->update();

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
		
        // TODO callerid
        
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
$TITLE = "Account Information: " . escapehtml($USER->firstname) . " " . escapehtml($USER->lastname);

include_once("nav.inc.php");

?>
<script type="text/javascript">

<? Validator::load_validators(array("ValLogin","ValPassword","ValBrandTheme")); ?>

<? if ($datachange) { ?>

alert("data has changed on this form!");
window.location = '<?= addcslashes($_SERVER['REQUEST_URI']) ?>';

<? } ?>

</script>
<?

/*CSDELETEMARKER_START*/
if($USER->authorize('loginphone') && !$IS_COMMSUITE) {
	$tollfree = Phone::format(getSystemSetting("inboundnumber"));
	echo '<br>Your toll free number is: <b>' . $tollfree . '</b><br><br>';
}
/*CSDELETEMARKER_END*/

startWindow("User Information");
echo $form->render();
endWindow();
include_once("navbottom.inc.php");
?>
