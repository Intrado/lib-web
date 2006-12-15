<?
$parentloginbypass=1;

include_once("common.inc.php");
include_once("ParentUser.obj.php");
include_once("../inc/html.inc.php");
include_once("../inc/form.inc.php");
$badinput=false;

$f = "parent";
$s = "main";
$reloadform=0;

// If user submitted the form
if (CheckFormSubmit($f,$s)){

	//check to see if formdata is valid
	if(CheckFormInvalid($f)) {
		error('Form was edited in another window, reloading data');
		$reloadform = 1;
	} else {
		MergeSectionFormData($f,$s);

		if( CheckFormSection($f, $s) ) {
				error('There was a problem trying to save your changes', 'Please verify that all required field information has been entered properly');
		} else {
			$login = GetFormData($f,$s,"login");
			$password = GetFormData($f, $s, "password");
			$firstname = GetFormData($f, $s, "firstname");
			$lastname = GetFormData($f, $s, "lastname");
			
			$customerid = QuickQuery("Select id from customer where customer.hostname = '$CUSTOMERURL'");
			if(!$customerid){
				error('Please check your school url');
			} else {
				$query = "Select count(*) from parentuser where login='". DBSafe($login)."' AND customerid='$customerid'";
				$same = QuickQuery($query);
				if( $same > 1) {
					error('That email address is already being used');
				} else {
					$parent = new ParentUser();
					$parent->login = $login;
					$parent->firstname = $firstname;
					$parent->lastname = $lastname;
					$parent->customerid = $customerid;
					$parent->update();
					$parent->setPassword($password);
	
					$_SESSION['parentloginid'] = $parent;
					redirect("parentportal.php");
				}
			}
		}
	}
} else {
	$reloadform = 1;
}

if($reloadform) {
	ClearFormData($f);
	PutFormData($f,$s,'login', $login,"text",1,255, true);
	PutFormData($f,$s,'password', "","password",1,255, true);
	PutFormData($f,$s,'firstname', $firstname,"text",1,255, true);
	PutFormData($f,$s,'lastname', $lastname,"text",1,255, true);

}

NewForm($f);
include("nav.inc.php");

?>
<table>
<tr><td>Your Email/Login: </td><td> <? NewFormItem($f, $s, 'login', 'text', 25, 255); ?></td></tr>
<tr><td>Password: </td><td><? NewFormItem($f, $s, 'password', 'password', 25, 255); ?></td></tr>
<tr><td>First Name: </td><td><? NewFormItem($f, $s, 'firstname', 'text', 25, 255); ?></td></tr>
<tr><td>Last Name: </td><td><? NewFormItem($f, $s, 'lastname', 'text', 25,255); ?></td></tr>
</table>

<?

NewFormItem($f, $s,"", 'submit');
EndForm();

include("navbottom.inc.php");

?>