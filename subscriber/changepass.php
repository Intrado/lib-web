<?
require_once("common.inc.php");
require_once("../inc/html.inc.php");
require_once("../inc/form.inc.php");
require_once("../inc/table.inc.php");
require_once("subscribervalidators.inc.php");


////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////


$error_badpass = _L("That password is incorrect");
$error_generalproblem = _L("There was a problem changing your password, please try again later");


$formdata = array(
    "newpassword1" => array(
        "label" => "New Password: ",
        "value" => "",
        "validators" => array(
            array("ValRequired"),
            array("ValLength","min" => 3,"max" => 50)
        ),
        "control" => array("PasswordField","maxlength" => 50),
        "helpstep" => 1
    ),
    "newpassword2" => array(
        "label" => "Confirm Password: ",
        "value" => "",
        "validators" => array(
            array("ValRequired"),
            array("ValLength","min" => 3,"max" => 50)
        ),
        "control" => array("PasswordField","maxlength" => 50),
        "helpstep" => 1
    ),
    "password" => array(
        "label" => "Old Password: ",
        "value" => "",
        "validators" => array(
            array("ValRequired"),
            array("ValSubscriberPassword")
        ),
        "control" => array("PasswordField","maxlength" => 50),
        "helpstep" => 1
    )
);

$helpsteps = array (
    "Welcome to the Guide system. You can use this guide to walk through the form, or access it as needed by clicking to the right of a section",
	"Enter a new password.  Then enter your account password."
);

$buttons = array(submit_button("Submit","submit","tick"),
                icon_button("Cancel","cross",null,"account.php"));
                
$form = new Form("testform",$formdata,$helpsteps,$buttons);
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
            
        $params = "?err";
        
        // more validation
        if ($postdata['newpassword1'] != $postdata['newpassword2'])
        	$error = "passwords do not match";
         else {
        	// success
        	QuickUpdate("update subscriber set `password`=password(?) where id=?", false, array($postdata['newpassword1'], $_SESSION['subscriberid']));
        	$params = "?thanks";
        }
        
        if ($ajax)
            $form->sendTo("changepass.php".$params);
        else
            redirect("changepass.php".$params);
    }
}


////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
$PAGE = "account:account";
$TITLE = "Change Password";

include_once("nav.inc.php");

?>
<script type="text/javascript">
Event.observe( document, 'unload', Event.unloadCache );

<? Validator::load_validators(array("ValSubscriberPassword")); ?>

<? if ($datachange) { ?>

alert("data has changed on this form!");
window.location = '<?= addcslashes($_SERVER['REQUEST_URI']) ?>';

<? } ?>

</script>

<?
if (isset($_GET['thanks'])) {
?>
	<div>
	<h2>Thank you.  Your password has been changed.</h2>
	</div>
	<br>
	<br>
<?
} else {
	startWindow(_L('Change Password'));
	if (isset($_GET['err'])) {
?>
	<div>
	<h3>&nbsp;Sorry, an error has occurred.  Please try again.</h3>
	</div>
	<br>
<?
	}
	echo $form->render();
	endWindow();
}

include_once("navbottom.inc.php");
?>