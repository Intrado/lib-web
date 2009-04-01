<?
$isNotLoggedIn = 1;

////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
require_once("common.inc.php");
require_once("../inc/html.inc.php");
require_once("../inc/table.inc.php");
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
    "username" => array(
        "label" => "Email:",
        "value" => "",
        "validators" => array(
            array("ValRequired"),
            array("ValEmail")
        ),
        "requires" => "confirmusername",
        "control" => array("TextField","maxlength" => 50),
        "helpstep" => 1
    ),
    "confirmusername" => array(
        "label" => "Confirm Email:",
        "value" => "",
        "validators" => array(
            array("ValRequired"),
            array("ValEmail")
        ),
        "requires" => "username",
        "control" => array("TextField","maxlength" => 50),
        "helpstep" => 1
    ),
    "password" => array(
        "label" => "Password:",
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
        "label" => "Confirm Password:",
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
        "label" => "First Name:",
        "value" => "",
        "validators" => array(
            array("ValRequired"),
            array("ValLength","min" => 3,"max" => 50)
        ),
        "control" => array("TextField","maxlength" => 50),
        "helpstep" => 3
    ),
    "lastname" => array(
        "label" => "Last Name:",
        "value" => "",
        "validators" => array(
            array("ValRequired"),
            array("ValLength","min" => 3,"max" => 50)
        ),
        "control" => array("TextField","maxlength" => 50),
        "helpstep" => 3
    ),
    "terms" => array(
        "label" => "Terms Of Service:",
        "control" => array("FormHtml","html" => '<div style="height: 200px; overflow:auto;">'.$tos.'</div>'),
        "helpstep" => 4
    ),
    "acceptterms" => array(
        "label" => "Accept Terms:",
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
            
        
        //save data here
        
        $_SESSION['postdata'] = $postdata;
        
        
        
        if ($ajax)
            $form->sendTo("activate.php");
        else
            redirect("activate.php");
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
include_once("loginbottom.inc.php");
?>