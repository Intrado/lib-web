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
	"radioselect" => array(
		"label" => _L("Close Account"),
		"fieldhelp" => _L('Selecting \'Yes\' will cancel your account and cause you to not receive any more messages.'),
        "value" => 1,
        "validators" => array(
            array("ValRequired")
        ),
        "control" => array("RadioButton","values"=>array(1=>"No", 2=>"Yes")),
        "helpstep" => 1
    ),
    "password" => array(
        "label" => _L("Password"),
        "fieldhelp" => _L('Enter your password to confirm your selection.'),
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
		if ($postdata['radioselect'] == 2) { // YES
			$result = subscriberCloseAccount($postdata['password']);
			$resultcode = $result['result'];
			if ($resultcode == "") {
		        if ($ajax)
            		$form->sendTo("index.php?logout=1");
        		else
		            redirect("index.php?logout=1");
			}
        
        	if ($ajax)
            	$form->sendTo("closeaccount.php?err");
        	else
	            redirect("closeaccount.php?err");
	    }
		// else NO
        if ($ajax)
           	$form->sendTo("account.php");
        else
	        redirect("account.php");
    }
}


////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
$PAGE = "account:account";
$TITLE = _L("Close Account");

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
	startWindow(_L('Close Account'));
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