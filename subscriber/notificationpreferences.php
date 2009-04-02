<?
require_once("common.inc.php");
require_once("../inc/html.inc.php");
require_once("../inc/table.inc.php");

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

$formdata = array(
    "todo" => array(
        "label" => "TODO: ",
        "value" => "",
        "validators" => array(
            array("ValRequired"),
            array("ValLength","min" => 3,"max" => 50)
        ),
        "control" => array("TextField","maxlength" => 50),
        "helpstep" => 1
    )
);

$helpsteps = array (
    "Welcome to the Guide system. You can use this guide to walk through the form, or access it as needed by clicking to the right of a section",
	"blah blah blah..."
);

$buttons = array(submit_button("Submit","submit","tick"),
                icon_button("Cancel","cross",null,"notificationpreferences.php"));
                
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
$PAGE = "contacts:notifications";
$TITLE = "Notification Preferences";

require_once("nav.inc.php");

startWindow(_L('Notifications') . help("Notifications"));
echo $form->render();
endWindow();
require_once("navbottom.inc.php");
?>