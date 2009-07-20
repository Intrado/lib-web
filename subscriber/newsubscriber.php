<?
$isNotLoggedIn = 1;

////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////

require_once("common.inc.php");

require_once("../inc/html.inc.php");
require_once("../inc/table.inc.php");
require_once("../inc/utils.inc.php");
require_once("subscribervalidators.inc.php");
require_once("../obj/Phone.obj.php");

// start the session for captcha value
doStartSession();

if (!isset($_SESSION['captcha'])) {
	redirect("newsubscribersession.php");
}

//////////////////////////////

class CaptchaField extends FormItem {
	function render ($value) {
		$n = $this->form->name."_".$this->name;
		return '<img src="captcha.png.php?'.mt_rand().'" /><br><input id="'.$n.'" name="'.$n.'" type="text" value="" maxlength="50" size="14"/>';
	}
}

// case-insensitive even though the username part before the at sign can be (ABC@example.com and abc@example.com are valid, but we will not allow)
class ValUsernameUnique extends Validator {
	var $onlyserverside = true;
	
	function validate ($value, $args) {
		global $CUSTOMERURL;
		if (!isUsernameUnique($CUSTOMERURL, $value))
			return "$this->label already exists, please Return to Sign In";
		
		return true;
	}
}

// case-insensitive
class ValCaptcha extends Validator {
	var $onlyserverside = true;
	
	function validate ($value, $args) {
		if (strtolower($value) != strtolower($_SESSION['captcha']))
			return "$this->label is not the correct value";
		
		return true;
	}
}

// case-insensitive
class ValSiteCode extends Validator {
	var $onlyserverside = true;
	
	function validate ($value, $args) {
		if (strtolower(trim($value)) != strtolower($_SESSION['sitecode']))
			return "$this->label is not the correct value";
		
		return true;
	}
}

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

$authdomain = "0";
$emaildomain = "";
$authcode = "0";
$result = getCustomerAuthOptions($CUSTOMERURL);
if ($result['result'] == "") {
	// success
	$authdomain = $result['authdomain'];
	$emaildomain = $result['emaildomain'];
	$authcode = $result['authcode'];
	$_SESSION['sitecode'] = $result['sitecode'];
}

if ($authdomain == "1" && $emaildomain != "") {
	$emailvalidators = array(
						array("ValRequired"),
						array("ValUsernameUnique"),
						array("ValEmail","domain"=>$emaildomain,"subdomain"=>true)
					);
} else {
	$emailvalidators = array(
						array("ValRequired"),
						array("ValUsernameUnique"),
						array("ValEmail")
					);
}

$tos = file_get_contents("locale/en_US/terms.html");

$formdata = array();
$formdata["firstname"] = array(
        "label" => "First Name",
        "value" => "",
        "validators" => array(
            array("ValRequired"),
            array("ValLength","min" => 1,"max" => 50)
        ),
        "control" => array("TextField","maxlength" => 50),
        "helpstep" => 1
    );
$formdata["lastname"] = array(
        "label" => "Last Name",
        "value" => "",
        "validators" => array(
            array("ValRequired"),
            array("ValLength","min" => 1,"max" => 50)
        ),
        "control" => array("TextField","maxlength" => 50),
        "helpstep" => 1
    );
$formdata["username"] = array(
        "label" => "Account Email",
        "value" => "",
        "validators" => $emailvalidators,
        "control" => array("TextField","maxlength" => 50),
        "helpstep" => 2
    );
$formdata["confirmusername"] = array(
        "label" => "Confirm Email",
        "value" => "",
        "validators" => array(
            array("ValRequired"),
            array("ValFieldConfirmation", "field" => "username")
        ),
        "requires" => array("username"),
        "control" => array("TextField","maxlength" => 50),
        "helpstep" => 2
    );
$formdata["password"] = array(
        "label" => "Password",
        "value" => "",
        "validators" => array(
            array("ValRequired"),
            array("ValLength","min" => 5,"max" => 50),
            array("ValPassword")
        ),
        "requires" => array("firstname", "lastname", "username"),
        "control" => array("PasswordField","maxlength" => 50),
        "helpstep" => 3
    );
$formdata["confirmpassword"] = array(
        "label" => "Confirm Password",
        "value" => "",
        "validators" => array(
            array("ValRequired"),
            array("ValLength","min" => 5,"max" => 50),
            array("ValFieldConfirmation", "field" => "password")
        ),
        "requires" => array("password"),
        "control" => array("PasswordField","maxlength" => 50),
        "helpstep" => 3
    );
if ($authcode == "1" && $_SESSION['sitecode'] != "") {
	$formdata["sitecode"] = array(
        "label" => "Site Access Code",
        "value" => "",
        "validators" => array(
            array("ValRequired"),
            array("ValLength","max" => 255),
            array("ValSiteCode")
        ),
        "control" => array("TextField","maxlength" => 255),
        "helpstep" => 3
	);
}
$formdata["captcha"] = array(
        "label" => "Captcha",
        "value" => "",
        "validators" => array(
            array("ValRequired"),
            array("ValCaptcha",)
        ),
        "control" => array("CaptchaField"),
        "helpstep" => 3
    );
$formdata["terms"] = array(
        "label" => "Terms Of Service",
        "control" => array("FormHtml","html" => '<div style="height: 200px; overflow:auto;">'.$tos.'</div>'),
        "helpstep" => 4
    );
$formdata["acceptterms"] = array(
        "label" => "Accept Terms",
        "value" => false,
        "validators" => array(
            array("ValRequired")
        ),
        "control" => array("CheckBox"),
        "helpstep" => 4
    );


$helpsteps = array (
    "This guide will help you complete the signup process.  Click the arrow to begin.",
	"Provide your first and last name.",
	"Your email will be used as your login username.  This must be a valid email address; your account is not activated until you confirm receipt of this email.",
	"Your password",
	"The terms"
);

$buttons = array(submit_button("Create Account","save","tick"));

$form = new Form("createaccount",$formdata,$helpsteps,$buttons);
$form->ajaxsubmit = true;

//check and handle an ajax request (will exit early)
//or merge in related post data
$form->handleRequest();

$datachange = false;
$errors = "";

//check for form submission
if ($button = $form->getSubmit()) { //checks for submit and merges in post data
    $ajax = $form->isAjaxSubmit(); //whether or not this requires an ajax response    
    
    if ($form->checkForDataChange()) {
        $datachange = true;
    } else if (($errors = $form->validate()) === false) { //checks all of the items in this form
        $postdata = $form->getData(); //gets assoc array of all values {name:value,...}
        
        $sitecode = "";
        if (isset($postdata['sitecode']))
        	$sitecode = $postdata['sitecode'];
        	
        $options = json_encode(array('firstname' => $postdata['firstname'], 'lastname' => $postdata['lastname']));
		
		$result = subscriberCreateAccount($CUSTOMERURL, $postdata['username'], $postdata['password'], $sitecode, $options);
		if ($result['result'] != "") {
			if ($result['result'] == "duplicate") {
				$errordetails = "That email address is already in use, please Return to Sign In";
			} else {
				$errordetails = "An unknown error occurred, please try again";
			}
			$errors .= "Your account was not created" . $errordetails;
		} else {
			// success
        	if ($ajax)
            	$form->sendTo("activate.php");
        	else
	            redirect("activate.php");
		}
        
        $_SESSION['postdata'] = $postdata;
        if ($ajax)
            $form->sendTo("newsubscriber.php?err");
        else
            redirect("newsubscriber.php?err");
    }
}



////////////////////////////////////////////////////////////////////////////////
// Functions
////////////////////////////////////////////////////////////////////////////////

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$TITLE = "Create a New Account";
require_once("logintop.inc.php");

if (isset($_GET['err'])) {
?>
    <h2>Sorry, an error has occurred.  Please try again.</h2>
<?
}
?>
<script type="text/javascript">

<? Validator::load_validators(array("ValPassword","ValCaptcha","ValSiteCode","ValUsernameUnique")); ?>

<? if ($datachange) { ?>

alert("data has changed on this form!");
window.location = '<?= addcslashes($_SERVER['REQUEST_URI']) ?>';

<? } ?>

</script>

<script type="text/javascript">
var errors = <?= json_encode($errors) ?>;
if (errors)
    alert("This form contains some errors");
</script>

<noscript>
<h1><?= $errors ? "This form contains some errors" : "" ?></h1>
</noscript>

		<table width="100%" style="color: #<?=$primary?>;" >
			<tr>
				<td><div style="font-size: 20px; font-weight: bold; text-align: left;"><?=$TITLE?></div></td>
			</tr>
			<tr>
				<td>Please complete this form to create your account.  A confirmation code will be sent to activate your new account so a valid email address is required.  Your password must be at least 5 characters long and cannot be similiar to your first name, last name, or email address.</td>
			</tr>
			<tr>
				<td>&nbsp;</td>
			</tr>
			<tr>
				<td>

<?= $form->render(); ?>

				</td>
			</tr>
			<tr>
				<td><br><a href="index.php">Return to Sign In</a></td>
			</tr>
		</table>
			
<?
require_once("loginbottom.inc.php");
?>