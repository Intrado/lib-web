<?

include_once("common.inc.php");
include_once("../inc/formatters.inc.php");
include_once("../inc/form.inc.php");
include_once("../inc/html.inc.php");
include_once("ParentUser.obj.php");
include_once("../obj/Person.obj.php");
include_once("../obj/PersonData.obj.php");


$parentvalidationfield = DBSafe(getCustomerSystemSetting("parentvalidationfield", $PARENTUSER->customerid));

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

			$studentkey = DBSafe(GetFormData($f, $s, 'studentid'));
			$parentcode = DBsafe(GetFormData($f, $s, 'parentvalid'));

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
					?> <div style="color: red;">You already added that student.</div> <?
					$alreadyadded = true;
				}
				if(!$alreadyadded) {
					$query = "INSERT INTO personparent (parentuserid, personid) VALUES ('$parentid', '$studentid')";
					if(Query($query)) {
						$CHILDLIST=NULL;
						$_SESSION['childlist']=NULL;
						redirect("parentportal.php");
					} else {
						?> <div style="color: red;">Problem adding to link table.</div> <?
					}
				}
			} else {
				?> <div style="color: red;">Incorrect StudentID/security code combination. Please try again.</div> <?
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
?><a href="index.php">Back</a><?

if(isset($ERRORS) && is_array($ERRORS)) {
	foreach($ERRORS as $key => $value) {
		$ERRORS[$key] = addslashes($value);
	}
	print '<script language="javascript">window.alert(\'' . implode('.\n', $ERRORS) . '.\');</script>';
}

include("navbottom.inc.php");
?>