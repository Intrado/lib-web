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
        "label" => _L("New Email Address"),
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
        "value" => "",
        "validators" => array(
            array("ValRequired"),
            array("ValLength","min" => 5,"max" => 50),
            array("ValSubscriberPassword")
        ),
        "control" => array("PasswordField","maxlength" => 50),
        "helpstep" => 1
    )
);

$helpsteps = array (
    "Welcome to the Guide system. You can use this guide to walk through the form, or access it as needed by clicking to the right of a section",
	"Enter a new email address.  Then enter your account password."
);

$buttons = array(submit_button("Save","submit","tick"),
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
$TITLE = _L('Username - %1$s', escapehtml($_SESSION['subscriber.username']));

require_once("nav.inc.php");

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
	<h2><?= _L("Thank you.  Your username has been changed.  Please check your email for the activation step.")?></h2>
	</div>
	<br>
	<br>
<?
} else {
	startWindow(_L('Change Username'));
	if (isset($_GET['err'])) {
		$err = "Sorry, an error has occurred.  Please try again.";
		if ($_GET['err'] == 1) {
			$err = "Sorry, that username already exists in the system.  Please try again.";
		} else if ($_GET['err'] == 2) {
			$err = "Sorry, that password is invalid.  Please try again.";
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