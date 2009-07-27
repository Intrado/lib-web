<?
require_once("common.inc.php");

require_once("../obj/Validator.obj.php");
require_once("../obj/Form.obj.php");
require_once("../obj/FormItem.obj.php");
require_once("../obj/Person.obj.php");
require_once("../inc/table.inc.php");
require_once("../inc/html.inc.php");


////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

$person = new Person($_SESSION['personid']);

$formhtmlemail = escapehtml($_SESSION['subscriber.username']) . '<br><br>' .
				'<a href="changeemail.php">'._L("Change Account Email").'</a><br><br>';

$formhtmlpass = '<a href="changepass.php">'._L("Change Password").'</a><br><br>';

$formhtmlclose = '<a href="closeaccount.php">'._L("Permanently close my account").'</a><br><br>';

$formdata = array();

$formdata['f01'] = array (
	"label" => _L("First Name"),
	"value" => $person->f01,
	"validators" => array(
		array("ValRequired"),
		array("ValLength","min" => 1,"max" => 50)
	),
	"control" => array("TextField","maxlength" => 50),
	"helpstep" => 1
);

$formdata['f02'] = array (
	"label" => _L("Last Name"),
	"value" => $person->f02,
	"validators" => array(
		array("ValRequired"),
		array("ValLength","min" => 1,"max" => 50)
	),
	"control" => array("TextField","maxlength" => 50),
	"helpstep" => 1
);

$formdata["changeemail"] = array(
   	"label" => _L("Account Email"),
   	"control" => array("FormHtml","html" => $formhtmlemail),
	"helpstep" => 1
);
$formdata["changepass"] = array(
   	"label" => _L("Account Password"),
   	"control" => array("FormHtml","html" => $formhtmlpass),
	"helpstep" => 1
);
$formdata["closeaccount"] = array(
   	"label" => _L("Account Status"),
   	"control" => array("FormHtml","html" => $formhtmlclose),
	"helpstep" => 1
);

$buttons = array(submit_button(_L("Save"),"submit","tick"),
                icon_button(_L("Cancel"),"cross",null,"account.php"));

$formname = "contactinfo";                
$_REQUEST['form'] = $formname;                
$form = new Form($formname,$formdata,null,$buttons);
$form->ajaxsubmit = true;

//check and handle an ajax request (will exit early)
//or merge in related post data
$form->handleRequest();

//check for form submission
if ($button = $form->getSubmit()) { //checks for submit and merges in post data
    if (($errors = $form->validate()) === false) { //checks all of the items in this form
        $postdata = $form->getData(); //gets assoc array of all values {name:value,...}
        $ajax = $form->isAjaxSubmit(); //whether or not this requires an ajax response        
        
        //save data here
		$person->f01 = $postdata['f01'];
		$person->f02 = $postdata['f02'];
		$person->update();
        
        if ($ajax)
            $form->sendTo("account.php");
        else
            redirect("account.php");
    }
}


$PAGE = "account:account";
$TITLE = _L("Account Information") . ": " . escapehtml($_SESSION['subscriber.firstname']) . " " . escapehtml($_SESSION['subscriber.lastname']);
require_once("nav.inc.php");

startWindow(_L('User Information'));
echo $form->render();
endWindow();

require_once("navbottom.inc.php");
?>