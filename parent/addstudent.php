<?

include_once("common.inc.php");
include_once("../inc/formatters.inc.php");
include_once("../inc/form.inc.php");
include_once("../inc/html.inc.php");
include_once("ParentUser.obj.php");
include_once("../obj/Person.obj.php");
include_once("../obj/PersonData.obj.php");


$parentvalidationfield =getCustomerSystemSetting("parentvalidationfield", $PARENTUSER->customerid);

$f = "add";
$s = "main";


if (CheckFormSubmit($f,$s)) {

	//check to see if formdata is valid
	if(CheckFormInvalid($f)) {
		error('Form was edited in another window, reloading data');
		$reloadform = 1;
	} else {
		MergeSectionFormData($f,$s);

		if( CheckFormSection($f, $s) ) {
			error('There was a problem trying to save your changes', 'Please verify that all required field information has been entered properly');
		} else {

			$studentkey = GetFormData($f, $s, 'studentid');
			$parentcode = GetFormData($f, $s, 'parentvalid');

			$student = Person::findPerson($PARENTUSER->customerid, $studentkey);
			$studentdata = new PersonData($student->id);
			$studentid = $student->id;
			$parentid = $PARENTUSER->id;
			
			$alreadyadded = false;
			
			$parentvalid=0;
			if($parentvalidationfield != "")
				$parentvalid = 1;
				
			$validated = true;
			if($parentvalid){
				if($studentdata->$parentvalidationfield != $parentcode)
					$validated = false;
			}
			if($validated && $studentid){
				if(in_array($studentid, $CHILDLIST)){
					error("You already added that student");
					$alreadyadded = true;
				}
				if(!$alreadyadded) {
					$query = "INSERT INTO personparent (parentuserid, personid) VALUES ('$parentid', '$studentid')";
					QuickUpdate($query);
					$CHILDLIST=NULL;
					$_SESSION['childlist']=NULL;
					redirect("parentportal.php");
				}
			} else {
				error("Incorrect StudentID/security code combination. Please try again");
			}
		}
	}
} else {
	$reloadform = 1;
}
if($reloadform) {
	ClearFormData($f);
	PutFormData($f,$s,'studentid',"","text",1,50);
	PutFormData($f,$s,'parentvalid', "","text",1,50);
}

include("nav.inc.php");

NewForm($f);
?>
<table border=1>
<p> Please enter you child's unique ID <?NewFormItem($f, $s, 'studentid', 'text', 25, 50);?> </p>
<?
if($parentvalidationfield) {
?>
<p> Please enter special code <?NewFormItem($f, $s, 'parentvalid', 'text', 25, 50);?> </p>
<?
}
NewFormItem($f, $s,"", 'submit');
EndForm();


include("navbottom.inc.php");
?>