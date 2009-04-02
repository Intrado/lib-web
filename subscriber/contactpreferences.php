<?
require_once("common.inc.php");

require_once("../obj/Validator.obj.php");
require_once("../obj/Form.obj.php");
require_once("../obj/FormItem.obj.php");

require_once("../inc/table.inc.php");
require_once("../inc/html.inc.php");
require_once("../inc/form.inc.php");
require_once("../obj/FieldMap.obj.php");
require_once("../obj/Person.obj.php");
require_once("../obj/Phone.obj.php");
require_once("../obj/Email.obj.php");
require_once("../obj/Sms.obj.php");
require_once("../obj/JobType.obj.php");
require_once("subscriberutils.inc.php");


////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

$pid = $_SESSION['personid'];
$person = new Person($_SESSION['personid']);

$firstnameField = FieldMap::getFirstNameField();
$lastnameField = FieldMap::getLastNameField();
$subscribeFields = FieldMap::getSubscribeMapNames();

$subscribeFieldValues = array();
foreach ($subscribeFields as $fieldnum => $name) {
	if ('f' == substr($fieldnum, 0, 1)) {
		$subscribeFieldValues[$fieldnum] = QuickQueryList("select value, value from persondatavalues where fieldnum='".$fieldnum."' and editlock=1", true);
	} else {
		$gfield = substr($fieldnum, 1, 3);
		$subscribeFieldValues[$fieldnum] = QuickQueryList("select value, value from groupdata where fieldnum='".$gfield."' and personid=0 and importid=0", true);
	}
}


$formdata = array(
    "firstname" => array(
        "label" => "First Name",
        "value" => $_SESSION['subscriber.firstname'],
        "validators" => array(
            array("ValRequired"),
            array("ValLength","min" => 1,"max" => 50)
        ),
        "control" => array("TextField","maxlength" => 50),
        "helpstep" => 1
    ),
    "lastname" => array(
        "label" => "Last Name",
        "value" => $_SESSION['subscriber.lastname'],
        "validators" => array(
            array("ValRequired"),
            array("ValLength","min" => 1,"max" => 50)
        ),
        "control" => array("TextField","maxlength" => 50),
        "helpstep" => 1
    )
);

foreach ($subscribeFields as $fieldnum => $name) {
	if ('f' == substr($fieldnum, 0, 1)) {
		$formdata[$fieldnum] = array (
    	    "label" => $name,
        	"value" => $subscribeFieldValues[$fieldnum][$person->$fieldnum],
        	"validators" => array(),
        	"control" => array("RadioButton","values" => $subscribeFieldValues[$fieldnum]),
        	"helpstep" => 2
		);
	} else { // Gfield
		$gfield = substr($fieldnum, 1, 3);
		$arr = QuickQueryList("select value, value from groupdata where personid=".$person->id." and fieldnum=".$gfield);
		$formdata[$fieldnum] = array (
    	    "label" => $name,
        	"value" => $arr,
        	"validators" => array(),
        	"control" => array("MultiCheckbox", "values" => $subscribeFieldValues[$fieldnum]),
        	"helpstep" => 3
		);
	}
}

$helpsteps = array (
    "Welcome to the Guide system. You can use this guide to walk through the form, or access it as needed by clicking to the right of a section",
    "Step 1, name please",
    "Ffields are defined by the admin",
    "Group fields are defined by the admin"
);

$buttons = array(submit_button("Save","save","tick"),
                icon_button("Cancel","cross",null,"contactpreferences.php?cancel"));

$formname = "contactinfo";                
$_REQUEST['form'] = $formname;                
$form = new Form($formname,$formdata,$helpsteps,$buttons);
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
        
        $person->$firstnameField = $postdata["firstname"];
        $person->$lastnameField = $postdata["lastname"];
        
		foreach ($subscribeFields as $fieldnum => $name) {
			$val = $postdata[$fieldnum];
				
			if ('f' == substr($fieldnum, 0, 1)) {
				$person->$fieldnum = $subscribeFieldValues[$fieldnum][$val];
			} else { // 'g'
				$gfield = substr($fieldnum, 1, 3);
				QuickUpdate("delete from groupdata where fieldnum=".$gfield." and personid=".$person->id);
				
				if (count($val) > 0) {
					$query = "insert into groupdata (personid, fieldnum, value, importid) values ";
					$args = array();
					foreach ($val as $v) {
						$query .= "(?, ?, ?, 0), ";
						$args[] = $person->id;
						$args[] = $gfield;
						$args[] = $v;
					}
					$query = substr($query, 0, strlen($query)-2); // remove trailing comma
					QuickUpdate($query, false, $args);
				}
			}
		}
        
        $person->update();
        $_SESSION['subscriber.firstname'] = $person->$firstnameField;
        $_SESSION['subscriber.lastname'] = $person->$lastnameField;
        
        if ($ajax)
            $form->sendTo("contactpreferences.php");
        else
            redirect("contactpreferences.php");
    }
}


///////////////////////////////////////////////////////////////////
// Functions
///////////////////////////////////////////////////////////////////


////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
$PAGE = "contacts:contactpreferences";
$TITLE = "Contact Information";

require_once("nav.inc.php");

startWindow(_L('Personal Information'));

echo $form->render();

endWindow();

require_once("navbottom.inc.php");
?>