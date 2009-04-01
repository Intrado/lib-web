<?
require_once("common.inc.php");
require_once("../inc/html.inc.php");
require_once("../inc/form.inc.php");
require_once("../inc/table.inc.php");

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////


$error_badpass = _L("That password is incorrect");
$error_generalproblem = _L("There was a problem changing your password, please try again later");

/****************** main message section ******************/

$f = "changepass";
$s = "main";
$reloadform = 0;
$success = false;

if(CheckFormSubmit($f,$s))
{
	//check to see if formdata is valid
	if(CheckFormInvalid($f))
	{
		error(_L('Form was edited in another window, reloading data'));
		$reloadform = 1;
	}
	else
	{
		MergeSectionFormData($f, $s);

		TrimFormData($f, $s, "newpass");

		//do check
		if( CheckFormSection($f, $s) ) {
			error(_L('There was a problem trying to save your changes'), _L('Please verify that all required field information has been entered properly'));
		} else {
			//submit changes
			$email = GetFormData($f, $s, "newpass");
			$pass = GetFormData($f, $s, "password");
			
			$result = subscriberUpdateUsername($email, $pass);
			if($result['result'] == ""){
				$success = true;
			} else {
				$resultcode = $result['result'];
				if($resultcode == "invalid argument"){
					if(strpos($result['resultdetail'], "username") !== false){
						error($error_badusername);
					} else {
						error($error_badpass);
					}
				} else {
					error($error_generalproblem);
				}
			}
		}
	}
} else {
	$reloadform = 1;
}

if( $reloadform )
{
	ClearFormData($f);
	PutFormData($f, $s, "newpass", "", "text", "0", "100", true);
	PutFormData($f, $s, "password", "", "text", "0", "100", true);

}


////////////////////////////////////////////////////////////////////////////////

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
            array("ValLength","min" => 3,"max" => 50)
        ),
        "control" => array("PasswordField","maxlength" => 50),
        "helpstep" => 1
    )
);

$helpsteps = array (
    "Welcome to the Guide system. You can use this guide to walk through the form, or access it as needed by clicking to the right of a section",
	"Enter a new email address.  Then enter your account password."
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
            
        
        //save data here
        
        
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
$TITLE = "Change Password";

include_once("nav.inc.php");

if($success){
	startWindow(_L('Change Password') . help("Changepass"));
	?>
	<div style="margin:5px"><?=_L("You should receive an email shortly at the new address with a confirmation code.")?></div>
	<form method='POST' action="index.php?c" name="activate" id="activate">
		<table>
			<tr>
				<td><?=_L("Confirmation Code")?>: </td>
				<td><input type="text" name="token" size="50" /></td>
			</tr>
			<tr>
				<td><?=_L("Password")?>:</td>
				<td><input type="password" name="password" /></td>
			</tr>
			<tr>
				<td>&nbsp;</td>
				<td><?=submit("activate", "main", "Submit")?></td>
			</tr>
		</table>
	</form>
	<?
	endWindow();
} else {
	startWindow(_L('Change Password') . help("Changepass"));
	echo $form->render();
	endWindow();
}

include_once("navbottom.inc.php");
?>