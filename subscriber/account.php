<?
require_once("common.inc.php");
require_once("../inc/html.inc.php");
require_once("../inc/form.inc.php");
require_once("../inc/table.inc.php");
require_once("../obj/Phone.obj.php");
require_once("../obj/Person.obj.php");
require_once("../obj/FieldMap.obj.php");


$person = new Person($_SESSION['personid']);

$firstnameField = FieldMap::getFirstNameField();
$lastnameField = FieldMap::getLastNameField();
$subscribeFields = FieldMap::getSubscribeMapNames();

$subscribeFieldValues = array();
foreach ($subscribeFields as $fieldnum => $name) {
	$subscribeFieldValues[$fieldnum] = QuickQueryList("select value from persondatavalues where fieldnum='".$fieldnum."' and editlock=1");
}


$error_failedupdate = "There was an error updating your information";
$error_failedupdatepassword = "There was an error updating your password";
$error_badpassword = "The old password provided is invalid";

$f="subscriber";
$s="main";
$reloadform = 0;
$error = 0;

if (CheckFormSubmit($f,$s)) {
	//check to see if formdata is valid
	if (CheckFormInvalid($f)) {
		error('Form was edited in another window, reloading data');
		$reloadform = 1;
	} else {
		MergeSectionFormData($f, $s);

		//do check
		$firstname = TrimFormData($f,$s,"firstname");
		$lastname = TrimFormData($f,$s,"lastname");
		$oldpassword = TrimFormData($f,$s,"oldpassword");
		$newpassword1 = TrimFormData($f, $s, "newpassword1");
		$newpassword2 = TrimFormData($f, $s, "newpassword2");
		$_SESSION['_locale'] = getFormData($f, $s, "_locale");
		if (CheckFormSection($f, $s)) {
			error('There was a problem trying to save your changes', 'Please verify that all required field information has been entered properly');
		} else if(strlen($newpassword1) > 0 && strlen($newpassword1) < 5){
			error("Passwords must be at least 5 characters long");
		} else if($newpassword1 && $passworderror = validateNewPassword($_SESSION['portaluser']['portaluser.username'], $newpassword1, $firstname, $lastname)){
			error($passworderror);
		} else if($newpassword1 != $newpassword2){
			error('Password confirmation does not match');
		} else {
			//submit changes
			

			if ($newpassword1) {
			/*
				$result = portalUpdatePortalUserPassword($newpassword1, $oldpassword);
				if ($result['result'] != "") {
					$updateuser = false;
					$error = 1;
					if(strpos($result['resultdetail'], "oldpassword") !== false){
						error($error_badpassword);
					} else {
						error($error_failedupdatepassword);
					}
				}
			*/
			}
			if (!$error) {
				redirect("start.php");
			}
		}
	}
} else {
	$reloadform = 1;
}

if ($reloadform) {
	ClearFormData($f);
	
	PutFormData($f, $s, "newpassword1", "", "text");
	PutFormData($f, $s, "newpassword2", "", "text");
	PutFormData($f, $s, "oldpassword", "", "text");

	PutFormData($f, $s, "_locale", $_SESSION['_locale'], "text", "nomin", "nomax");
}



$formdata = array(
    "locale" => array(
        "label" => "Choose your display language:",
        "value" => "",
        "validators" => array(    
            array("ValRequired")
        ),
        "control" => array("RadioButton","values" => array(1 => "English", 2 => "Spanish",3 => "French")),
        "helpstep" => 1
    )
);

$helpsteps = array (
    "Welcome to the Guide system. You can use this guide to walk through the form, or access it as needed by clicking to the right of a section",
	"Select a language"
);

$buttons = array(submit_button("Submit","submit","tick"),
                icon_button("Cancel","cross",null,"account.php"));

$form = new Form("accountform",$formdata,$helpsteps,$buttons);
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