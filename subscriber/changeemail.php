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
    "newusername1" => array(
        "label" => _L("New Account Email"),
        "fieldhelp" => _L('Enter the new email you would like to use as the primary email for this account. <br><br><b>Note:</b> This will become the email address that you use when logging into the system.'),
        "value" => "",
        "validators" => array(
            array("ValRequired"),
            array("ValLength","min" => 3,"max" => 255),
            array("ValEmail")
        ),
        "control" => array("TextField","maxlength" => 255),
        "helpstep" => 1
    ),
    "newusername2" => array(
        "label" => _L("Confirm New Email"),
        "fieldhelp" => _L('Enter the new email again to confirm.'),
        "value" => "",
        "validators" => array(
            array("ValRequired"),
            array("ValLength","min" => 3,"max" => 255),
            array("ValEmail"),
            array("ValFieldConfirmation", "field" => "newusername1")
        ),
        "requires" => array("newusername1"),
        "control" => array("TextField","maxlength" => 255),
        "helpstep" => 1
    ),
    "password" => array(
        "label" => _L("Password"),
        "fieldhelp" => _L('Enter your password.'),
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
        
		$result = subscriberUpdateUsername($postdata['newusername1'], $postdata['password']);
		$resultcode = $result['result'];
		if ($resultcode == "") {
			$params = "?thanks";
		} else if ($resultcode == "invalid argument") {
			if (strpos($result['resultdetail'], "username") !== false) {
				$params = "?err=1"; // bad username
			} else {
				$params = "?err=2"; // bad password
			}
		} else {
			$params = "?err=3"; // general failure
		}
        
        if ($ajax)
            $form->sendTo("changeemail.php".$params);
        else
            redirect("changeemail.php".$params);
    }
}



////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
$PAGE = "account:account";
$TITLE = _L('Account Email : ') . escapehtml($_SESSION['subscriber.username']);

require_once("nav.inc.php");

?>
<script type="text/javascript">
Event.observe( document, 'unload', Event.unloadCache );
<? Validator::load_validators(array("ValSubscriberPassword")); ?>
</script>
<?
if (isset($_GET['thanks'])) {
?>
	<div>
	<h2><img src="img/icons/tick.gif"/>&nbsp;&nbsp;<?= _L("Your account email has been changed.  Please check your email for the activation step.")?></h2><BR>
	<?=icon_button(_L("Done"),"tick",null,"account.php")?>
	</div>
	<br>
	<br>
<?
} else {
	startWindow(_L('Change Account Email'));
	if (isset($_GET['err'])) {
		$err = _L("Sorry, an error has occurred.  Please try again.");
		if ($_GET['err'] == 1) {
			$err = _L("Sorry, that account email already exists in the system.  Please try again.");
		} else if ($_GET['err'] == 2) {
			$err = _L("Sorry, that password is invalid.  Please try again.");
		}
?>
	<div>
	<h3>&nbsp;<?=$err?></h3>
	</div>
	<br>
<?
	}
	echo $form->render();
	endWindow();
}

require_once("navbottom.inc.php");
?>