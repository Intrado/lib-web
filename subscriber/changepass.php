<?
require_once("common.inc.php");
require_once("../inc/html.inc.php");
require_once("../inc/form.inc.php");
require_once("../inc/table.inc.php");
require_once("subscribervalidators.inc.php");


////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

$formdata = array(
    "newpassword1" => array(
        "label" => _L("New Password"),
        "fieldhelp" => _L('Enter the new password you would like to use when logging into the system.'),
        "value" => "",
        "validators" => array(
            array("ValRequired"),
            array("ValLength","min" => 5,"max" => 50),
            array("ValChangePassword")
        ),
        "control" => array("PasswordField","maxlength" => 50),
        "helpstep" => 1
    ),
    "newpassword2" => array(
        "label" => _L("Confirm Password"),
        "fieldhelp" => _L('Enter the new password to confirm.'),
        "value" => "",
        "validators" => array(
            array("ValRequired"),
            array("ValLength","min" => 5,"max" => 50),
            array("ValFieldConfirmation", "field" => "newpassword1")
        ),
        "requires" => array("newpassword1"),
        "control" => array("PasswordField","maxlength" => 50),
        "helpstep" => 1
    ),
    "password" => array(
        "label" => _L("Old Password"),
        "fieldhelp" => _L('Enter your old password.'),
        "value" => "",
        "validators" => array(
            array("ValRequired"),
            array("ValSubscriberPassword")
        ),
        "control" => array("PasswordField","maxlength" => 50),
        "helpstep" => 1
    )
);

$buttons = array(submit_button(_L("Save"),"submit","tick"),
                icon_button(_L("Cancel"),"cross",null,"account.php"));
                
$form = new Form("testform",$formdata,null,$buttons);
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
        
        if (QuickUpdate("update subscriber set `password`=password(?) where id=?", false, array($postdata['newpassword1'], $_SESSION['subscriberid'])));
        	$params = "?thanks";
        
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
$TITLE = _L("Change Password");

require_once("nav.inc.php");

?>
<script type="text/javascript">
Event.observe( document, 'unload', Event.unloadCache );
<? Validator::load_validators(array("ValSubscriberPassword","ValChangePassword")); ?>
</script>

<?
if (isset($_GET['thanks'])) {
?>
	<div>
	<h2><img src="img/icons/tick.gif"/>&nbsp;&nbsp;<?=_L("Your password has been changed.")?></h2><BR>
	<?=icon_button(_L("Done"),"tick",null,"account.php")?>
	</div>
	<br>
	<br>
<?
} else {
	startWindow(_L('Change Password'));
	if (isset($_GET['err'])) {
?>
	<div>
	<h3>&nbsp;<?=_L("Sorry, an error has occurred.  Please try again.")?></h3>
	</div>
	<br>
<?
	}
	echo $form->render();
	endWindow();
}

require_once("navbottom.inc.php");
?>