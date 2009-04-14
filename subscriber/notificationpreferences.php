<?
require_once("common.inc.php");
require_once("../inc/html.inc.php");
require_once("../inc/table.inc.php");
require_once("../obj/Email.obj.php");


////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

$pid = $_SESSION['personid'];
error_log("1");
$jobtypes = QuickQueryList("select name from jobtype");
error_log("2");
$emails = DBFindMany("Email", "from email where personid=?", false, array($pid));
error_log("3");

$formdata = array();

foreach ($emails as $email) {
	if ($email->email == '') continue;
	
	$formdata["email".$email->sequence] = array(
        "label" => $email->email,
        "value" => array(),
        "validators" => array(),
        "control" => array("MultiCheckbox","values" => $jobtypes),
        "helpstep" => 1
    );
}


$helpsteps = array (
    "Welcome to the Guide system. You can use this guide to walk through the form, or access it as needed by clicking to the right of a section",
	"blah blah blah..."
);

$buttons = array(submit_button("Submit","submit","tick"),
                icon_button("Cancel","cross",null,"notificationpreferences.php"));
                
$form = new Form("notifyprefs",$formdata,$helpsteps,$buttons);
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
        
        
        if ($ajax)
            $form->sendTo("notificationpreferences.php");
        else
            redirect("notificationpreferences.php");
    }
}


////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
$PAGE = "contacts:notificationprefs";
$TITLE = "Notification Preferences";

require_once("nav.inc.php");

startWindow(_L('Preferences'));
echo $form->render();
endWindow();
require_once("navbottom.inc.php");
?>