<?
$isNotLoggedIn = 1;

////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
require_once("common.inc.php");
//require_once("recaptchalib.php");
require_once("../inc/html.inc.php");
require_once("../inc/table.inc.php");
require_once("../obj/Phone.obj.php");

require_once("../obj/Validator.obj.php");
require_once("../obj/Form.obj.php");
require_once("../obj/FormItem.obj.php");


// reCaptcha keys
//$publickey = "6LcVswUAAAAAAOaU8PxzEpv22culkZ7OG0FHjMOX";
//$privatekey = "6LcVswUAAAAAAMFesdVgOw3VDSiRjOGGVQ9bqvd1";



		$_SESSION['colorscheme']['_brandtheme']   = "3dblue";
		$_SESSION['colorscheme']['_brandtheme1']  = "89A3CE";
		$_SESSION['colorscheme']['_brandtheme2']  = "89A3CE";
		$_SESSION['colorscheme']['_brandprimary'] = "26477D";
		$_SESSION['colorscheme']['_brandratio']   = ".3";



////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

// pass along the customerurl (used by phone activation feature to find a customer without any existing associations)
$appendcustomerurl = "";
if (isset($_GET['u'])) {
	$appendcustomerurl = "?u=".urlencode($_GET['u']);
}

$login = "";
$confirmlogin="";
$firstname = "";
$lastname = "";
$zipcode = "";
$notify = 0;
$notifysms = 0;
$sms = "";
$success = false;
$tos = file_get_contents("terms.html");

if ((strtolower($_SERVER['REQUEST_METHOD']) == 'post') ) {

//	$resp = recaptcha_check_answer ($privatekey,
  //                              $_SERVER["REMOTE_ADDR"],
    //                            $_POST["recaptcha_challenge_field"],
      //                          $_POST["recaptcha_response_field"]);

	$login = trim(get_magic_quotes_gpc() ? stripslashes($_POST['login']) : $_POST['login']);
	$confirmlogin = trim(get_magic_quotes_gpc() ? stripslashes($_POST['confirmlogin']) : $_POST['confirmlogin']);
	$firstname = get_magic_quotes_gpc() ? stripslashes($_POST['firstname']) : $_POST['firstname'];
	$lastname = get_magic_quotes_gpc() ? stripslashes($_POST['lastname']) : $_POST['lastname'];
	$password1 = get_magic_quotes_gpc() ? stripslashes($_POST['password1']) : $_POST['password1'];
	$password2 = get_magic_quotes_gpc() ? stripslashes($_POST['password2']) : $_POST['password2'];
	$acceptterms = isset($_POST['acceptterms']);
	if ($login != $confirmlogin) {
		error("The emails you have entered do not match");
	} else if (!validEmail($login)) {
		error("That is not a valid email format");
	} else if ($_POST['password1'] == "") {
		error("You must enter a password");
	} else if ($password1 != $password2) {
		error("Your passwords don't match");
	} else if (strlen($password1) < 5) {
		error("Passwords must be at least 5 characters long");
	} else if ($passworderror = validateNewPassword($login, $password1, $firstname, $lastname)) {
		error($passworderror);
//	} else if (!$resp->is_valid) {
//		error("The reCAPTCHA wasn't entered correctly. Go back and try it again." .
//		   			"(reCAPTCHA said: " . $resp->error . ")");
	} else {
	
		$options = json_encode(array('firstname' => $firstname, 'lastname' => $lastname));
		
		$result = subscriberCreateAccount($CUSTOMERURL, $login, $password1, $options);
		if ($result['result'] != "") {
			if ($result['result'] == "duplicate") {
				$errordetails = "That email address is already in use";
			} else {
				$errordetails = "An unknown error occured, please try again";
			}
			error("Your account was not created", $errordetails);
		} else {
			$success = true;
		}
	}
}



$formdata = array(
    "username" => array(
        "label" => "Email (this will be your login name)",
        "value" => "",
        "validators" => array(
            array("ValRequired"),
            array("ValLength","min" => 3,"max" => 50)
        ),
        "requires" => "confirmusername",
        "control" => array("TextField","maxlength" => 50),
        "helpstep" => 1
    ),
    "confirmusername" => array(
        "label" => "Confirm Email",
        "value" => "",
        "validators" => array(
            array("ValRequired"),
            array("ValLength","min" => 3,"max" => 50)
        ),
        "requires" => "username",
        "control" => array("TextField","maxlength" => 50),
        "helpstep" => 1
    ),
    "password" => array(
        "label" => "Password",
        "value" => "",
        "validators" => array(
            array("ValRequired"),
            array("ValLength","min" => 3,"max" => 50)
        ),
        "requires" => "confirmpassword",
        "control" => array("PasswordField","maxlength" => 50),
        "helpstep" => 2
    ),
    "confirmpassword" => array(
        "label" => "Confirm Password",
        "value" => "",
        "validators" => array(
            array("ValRequired"),
            array("ValLength","min" => 3,"max" => 50)
        ),
        "requires" => "password",
        "control" => array("PasswordField","maxlength" => 50),
        "helpstep" => 2
    ),
    "firstname" => array(
        "label" => "First Name",
        "value" => "",
        "validators" => array(
            array("ValRequired"),
            array("ValLength","min" => 3,"max" => 50)
        ),
        "control" => array("TextField","maxlength" => 50),
        "helpstep" => 3
    ),
    "lastname" => array(
        "label" => "Last Name",
        "value" => "",
        "validators" => array(
            array("ValRequired"),
            array("ValLength","min" => 3,"max" => 50)
        ),
        "control" => array("TextField","maxlength" => 50),
        "helpstep" => 3
    ),
    "terms" => array(
        "label" => "Terms Of Service",
        "value" => "blah blah blah",
        "validators" => array(
            array("ValRequired"),
            array("ValLength","min" => 3,"max" => 500)
        ),
        "control" => array("TextArea","rows" => 10),
        "helpstep" => 4
    ),
    "acceptterms" => array(
        "label" => "I accept the terms of service",
        "value" => false,
        "validators" => array(
            array("ValRequired")
        ),
        "control" => array("CheckBox"),
        "helpstep" => 4
    )
);

$helpsteps = array (
    "Welcome to the Guide system. You can use this guide to walk through the form, or access it as needed by clicking to the right of a section",
	"Your email",
	"Your password",
	"Your name",
	"The terms"
);

$buttons = array(submit_button("Create Account","save","tick"));
//$buttons = array();


$form = new Form("createaccount",$formdata,$helpsteps,$buttons);
$form->ajaxsubmit = true;

//check and handle an ajax request (will exit early)
//or merge in related post data
$form->handleRequest();

$datachange = false;

//check for form submission
if ($button = $form->getSubmit()) { //checks for submit and merges in post data
    $ajax = $form->isAjaxSubmit(); //whether or not this requires an ajax response    
    
    if ($form->checkForDataChange()) {
        $datachange = true;
    } else if (($errors = $form->validate()) === false) { //checks all of the items in this form
        $postdata = $form->getData(); //gets assoc array of all values {name:value,...}
        
        // do more validation
	if (false) {        
        // TODO
        
	} else {
	
		$options = json_encode(array('firstname' => $postdata['firstname'], 'lastname' => $postdata['lastname']));
		
		$result = subscriberCreateAccount($CUSTOMERURL, $postdata['username'], $postdata['password'], $options);
		if ($result['result'] != "") {
			if ($result['result'] == "duplicate") {
				$errordetails = "That email address is already in use";
			} else {
				$errordetails = "An unknown error occured, please try again";
			}
			$errors .= "Your account was not created" . $errordetails;
		} else {
			$success = true;
		}
	}
	
	if ($success) {
        if ($ajax)
            $form->sendTo("activate.php");
        else
            redirect("activate.php");
	} else {
        if ($ajax)
            $form->sendTo("newsubscriber.php?formerrors");
        else
            redirect("newsubscriber.php?formerrors");
	}
	
	
    }
}



////////////////////////////////////////////////////////////////////////////////
// Functions
////////////////////////////////////////////////////////////////////////////////

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$TITLE = "Create a New Account";
include_once("logintop.inc.php");
if (!$success) {

if (isset($_GET['formerrors'])) {
?>
    <h1>Form Errors: <?=$_SESSION['formerrors']?> </h1>
<?
}
?>

<script type="text/javascript">
var errors = <?= json_encode($errors) ?>;
if (errors)
    alert("this form contains some errors");
</script>

<noscript>
<h1><?= $errors ? "This form contains some errors" : "" ?></h1>
</noscript>


		<table width="100%" style="color: #365F8D;" >
			<tr>
				<td colspan="2"><div style="font-size: 20px; font-weight: bold; text-align: left;"><?=$TITLE?></div></td>
			</tr>
			<tr>
				<td colspan="2">Please complete this form to create your SUBSCRIBER account.  A confirmation code will be sent to activate your new account so a valid email address is required.  Your password must be at least 5 characters long and cannot be similiar to your first name, last name, or email address.</td>
			</tr>
			<tr>
				<td colspan="4">&nbsp;</td>
			</tr>
			<tr>
				<td>

<?= $form->render(); ?>

				</td>
			</tr>
			<tr>
				<td colspan="2"><br><a href="index.php<?echo $appendcustomerurl;?>">Return to Sign In</a></td>
			</tr>
		</table>
			
<?
} else {
?>
	<table style="color: #365F8D;">
		<tr>
			<td>&nbsp;</td>
			<td>
				<div style="margin:5px">
					Thank you, Your account has been created.
					<br>Please check your email to activate your account.
					<br>You will be redirected to the activate page in 10 seconds or <a href="index.php<?echo $appendcustomerurl; if ($appendcustomerurl == "") echo "?n"; else echo "&n"; ?>">Click Here.</a>
				</div>
				<meta http-equiv="refresh" content="10;url=index.php<?echo $appendcustomerurl; if ($appendcustomerurl == "") echo "?n"; else echo "&n"; ?>">
			</td>
			<td>&nbsp;</td>
		</tr>
	</table>
<?
}
include_once("loginbottom.inc.php");
?>