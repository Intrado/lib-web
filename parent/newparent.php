<?
$isparentlogin=1;

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
			$login = DBSafe(GetFormData($f,$s,"login"));
			$password = DBSafe(GetFormData($f, $s, "password"));
			$firstname = DBSafe(GetFormData($f, $s, "firstname"));
			$lastname = DBSafe(GetFormData($f, $s, "lastname"));
			
			$customerid = QuickQuery("Select id from customer where customer.hostname = '$CUSTOMERURL'");
			if(!$customerid){
					error('Bad Customer URL');
			} else {
				$query = "Select count(*) from parentuser where login= '$login'";
				$same = QuickQuery($query);
				if( $same > 1) {
					error('That email address is already being used.');
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

if(isset($ERRORS) && is_array($ERRORS)) {
	foreach($ERRORS as $key => $value) {
		$ERRORS[$key] = addslashes($value);
	}
	print '<script language="javascript">window.alert(\'' . implode('.\n', $ERRORS) . '.\');</script>';
}
?>
<a href="index.php">Back</a>

