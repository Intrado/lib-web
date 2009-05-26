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


////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////


$_SESSION['colorscheme']['_brandtheme']   = "3dblue";
$_SESSION['colorscheme']['_brandtheme1']  = "89A3CE";
$_SESSION['colorscheme']['_brandtheme2']  = "89A3CE";
$_SESSION['colorscheme']['_brandprimary'] = "26477D";
$_SESSION['colorscheme']['_brandratio']   = ".3";


// pass along the customerurl (used by phone activation feature to find a customer without any existing associations)
$appendcustomerurl = "";
if (isset($_GET['u'])) {
	$appendcustomerurl = "?u=".urlencode($_GET['u']);
}

$tos = file_get_contents("terms.html");


$formdata = array(
    "firstname" => array(
        "label" => "First Name",
        "value" => "",
        "validators" => array(
            array("ValRequired"),
            array("ValLength","min" => 1,"max" => 50)
        ),
        "control" => array("TextField","maxlength" => 50),
        "helpstep" => 1
    ),
    "lastname" => array(
        "label" => "Last Name",
        "value" => "",
        "validators" => array(
            array("ValRequired"),
            array("ValLength","min" => 1,"max" => 50)
        ),
        "control" => array("TextField","maxlength" => 50),
        "helpstep" => 1
    ),
    "username" => array(
        "label" => "Account Email",
        "value" => "",
        "validators" => array(
            array("ValRequired"),
            array("ValEmail")
        ),
        "control" => array("TextField","maxlength" => 50),
        "helpstep" => 2
    ),
    "confirmusername" => array(
        "label" => "Confirm Email",
        "value" => "",
        "validators" => array(
            array("ValRequired"),
            array("ValEmail"),
            array("ValFieldConfirmation", "field" => "username")
        ),
        "requires" => array("username"),
        "control" => array("TextField","maxlength" => 50),
        "helpstep" => 2
    ),
    "password" => array(
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
    ),
    "confirmpassword" => array(
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
    ),
    "terms" => array(
        "label" => "Terms Of Service",
        "control" => array("FormHtml","html" => '<div style="height: 200px; overflow:auto;">'.$tos.'</div>'),
        "helpstep" => 4
    ),
    "acceptterms" => array(
        "label" => "Accept Terms",
        "value" => false,
        "validators" => array(
            array("ValRequired")
        ),
        "control" => array("CheckBox"),
        "helpstep" => 4
    )
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
            
        $options = json_encode(array('firstname' => $postdata['firstname'], 'lastname' => $postdata['lastname']));
		
		$result = subscriberCreateAccount($CUSTOMERURL, $postdata['username'], $postdata['password'], $options);
		if ($result['result'] != "") {
			if ($result['result'] == "duplicate") {
				$errordetails = "That email address is already in use";
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

<? Validator::load_validators(array("ValPassword")); ?>

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

		<table width="100%" style="color: #365F8D;" >
			<tr>
				<td><div style="font-size: 20px; font-weight: bold; text-align: left;"><?=$TITLE?></div></td>
			</tr>
			<tr>
				<td>Please complete this form to create your SUBSCRIBER account.  A confirmation code will be sent to activate your new account so a valid email address is required.  Your password must be at least 5 characters long and cannot be similiar to your first name, last name, or email address.</td>
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
				<td><br><a href="index.php<?echo $appendcustomerurl;?>">Return to Sign In</a></td>
			</tr>
		</table>
			
<?
require_once("loginbottom.inc.php");
?>