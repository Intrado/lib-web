<?
include("common.inc.php");
include_once("../inc/html.inc.php");
include_once("../inc/form.inc.php");
include_once("../obj/Phone.obj.php");
include_once("../obj/Email.obj.php");
include_once("../obj/Person.obj.php");
include_once("../obj/PersonData.obj.php");
include_once("../obj/FieldMap.obj.php");
//display phone and email only.


if (isset($_GET['sid'])) {
	$id = $_GET['sid']+0;
	if(in_array($id, $CHILDLIST))
		$_SESSION['currentsid']=$id;
		$_SESSION['currstudentname']=$studentname;
	redirect();
}

if(!$_SESSION['childlist'] || $_SESSION['childlist']===NULL ) {
	$_SESSION['childlist'] = $_SESSION['parentuser']->findChildren();
	$CHILDLIST = $_SESSION['childlist'];
}


$maxphones = getCustomerSystemSetting("maxphones", $PARENTUSER->customerid);
$maxphones = $maxphones === false ? 4 : $maxphones;
$maxemails = getCustomerSystemSetting("maxemails", $PARENTUSER->customerid);
$maxemails = $maxemails === false ? 2 : $maxphones;

$firstname = FieldMap::getFirstNameField();
$lastname = FieldMap::getlastNameField();

$allchildrenpd = array();
$allchildrenid = array();
foreach($CHILDLIST as $child) {
	$allchildrenpd[] = new PersonData($child);
	$allchildrenid[] = new Person($child);
}

$id = $_SESSION['currentsid'];
if(isset($id)) {
	$student = new Person($id);
	$phonelist = $student->getPhones();
	$emaillist = $student->getEmails();

	$studentfields = new PersonData($id);
	$studentname = $studentfields->$firstname . " ". $studentfields->$lastname;
}
$f = $id;
$s = "main";
$reloadform=0;
if (CheckFormSubmit($f,$s) && $id) {

	//check to see if formdata is valid
	if(CheckFormInvalid($f))
	{
		error('Form was edited in another window, reloading data');
		$reloadform = 1;
	} else {
		MergeSectionFormData($f,$s);

		if( CheckFormSection($f, $s) ) {
			error('There was a problem trying to save your changes', 'Please verify that all required field information has been entered properly');
		} else {
			$refresh=FALSE;
			$editphonecount = count($phonelist);
			$editemailcount = count($emaillist);
			if( GetFormData($f, $s, "checkbox")) {
				foreach($allchildrenid as $allchild) {
					$allphonelist = $allchild->getPhones();
					$allemaillist = $allchild->getEmails();
					updatePhonesEmails($allphonelist, $allemaillist,$editphonecount, $editemailcount, $allchild->id, $f, $s);
				}
			} else {
				updatePhonesEmails($phonelist, $emaillist, $editphonecount, $editemailcount,$id, $f, $s);
			}
			redirect();
		}
	}
} else {
	$reloadform = 1;
}

if($reloadform) {
	ClearFormData($f);
	for($count=0;$count<count($phonelist); $count++) {
		PutFormData($f,$s,'phone'.$count,Phone::format($phonelist[$count]->phone),"phone",10,10);
	}
	for($count=0;$count<count($emaillist); $count++) {
		PutFormData($f,$s,'email'.$count,$emaillist[$count]->email,"email",1,255);
	}
	PutFormData($f,$s,'newphone',"","phone",10,10);
	PutFormData($f,$s,'newemail', "","email",1,255);
	PutFormData($f, $s, 'checkbox', "", "checkbox", 0);
}

include("nav.inc.php");
?>
<table border=1>
	<td>
		<table border=1>
		<?
		for($itor=0;$itor< count($allchildrenpd); $itor++) {
			$name = $allchildrenpd[$itor]->$firstname . " " .$allchildrenpd[$itor]->$lastname;
			if($allchildrenpd[$itor]->personid == $_SESSION[currentsid]) {
				?><tr bgcolor="#CCCCCC"><?
			} else {
				?><tr><?
			}
			?>
			<td><a href="parentportal.php?sid=<?=$allchildrenpd[$itor]->personid?>"><?=$name." ".$allchildrenid[$itor]->pkey?></a></td></tr>
			<?
		}
		?>
		</table>
	</td>
	<td>
		<table border=1>
			<p> Student Info of: <?=$studentname?></p>
			<?

			NewForm($f);

			?>
			<table border=1>
			<?
			for($count=0;$count<count($phonelist);$count++) {
				?>
				<tr>
				<td>Phone Edit: <? NewFormItem($f, $s, 'phone'.$count, 'text', 25, 50); ?></td></tr>
				</tr>
				<?
			}
			if(count($phonelist) < $maxphones) {
				?>
				<tr>
				<td>Add Phone Number: </td>
				<td> <? NewFormItem($f, $s, 'newphone', 'text', 25, 50); ?></td></tr>
				</tr>
				<?
			}
			?></table>
				<table border=1>
				<?
				for($count=0;$count<count($emaillist);$count++) {
					?>
					<tr>
					<td>Email Edit: <? NewFormItem($f, $s, 'email'.$count, 'text', 25, 255); ?></td></tr>
					</tr>
					<?
				}
				if(count($emaillist) < $maxemails) {
					?>
					<tr>
					<td>Add Email: </td>
					<td><? NewFormItem($f, $s, 'newemail', 'text', 25, 255); ?></td></tr>
					</tr>
					<?
				}
				?>
				</table>

			<?
			NewFormItem($f, $s,'', 'submit');
			?>Apply to All Students<?
			NewFormItem($f, $s,'checkbox', 'checkbox');
			EndForm();
			?>
		</table>
	</td>
</table>

<?
include("navbottom.inc.php");


function updatePhonesEmails($phonelist, $emaillist, $editphonecount, $editemailcount, $id, $f, $s) {

	$phoneeditmax = max(count($phonelist), $editphonecount);
	$emaileditmax = max(count($emaillist), $editemailcount);

	for($count=0;$count < $phoneeditmax; $count++) {
		$editphone = GetFormData($f, $s, 'phone'.$count);
		if($count < count($phonelist)) {
			$phonelist[$count]->phone = Phone::parse($editphone);
			$phonelist[$count]->update();
		} else {
			$phone = new Phone;
			$phone->phone = Phone::parse($editphone);
			$phone->personid = $id;
			$phone->sequence = $count;
			$phone->update();
		}
	}
	for($count=0;$count < $emaileditmax;$count++) {
		$editemail = GetFormData($f, $s, "email".$count);
		if($count < count($emaillist)) {
			$emaillist[$count]->email = $editemail;
			$emaillist[$count]->update();
		} else {
			$email = new Email;
			$email->email = $editemail;
			$email->personid = $id;
			$email->sequence = $count;
			$email->update();
		}
	}
	if($newphone=GetFormData($f, $s, "newphone")) {
		if($editphonecount < count($phonelist)) {
			$phonelist[$editphonecount]->phone = $newphone;
			$phonelist[$editphonecount]->update();
		} else {
			$phone = new Phone;
			$phone->personid = $id;
			$phone->phone = Phone::parse($newphone);
			$phone->sequence = $editphonecount;
			$phone->update();
		}
	}
	if($newemail=GetFormData($f, $s, "newemail")) {
		if($editemailcount < count($emaillist)) {
			$emaillist[$editemailcount]->email = $newemail;
			$emaillist[$editemailcount]->update();
		} else {
			$email = new Email;
			$email->personid = $id;
			$email->email = $newemail;
			$email->sequence = $editemailcount;
			$email->update();
		}
	}
}
?>