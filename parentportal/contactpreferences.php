<?
require_once("common.inc.php");
require_once("../inc/table.inc.php");
require_once("../inc/html.inc.php");
require_once("../inc/form.inc.php");
require_once("../obj/FieldMap.obj.php");
require_once("../obj/Person.obj.php");
require_once("../obj/Phone.obj.php");
require_once("../obj/Email.obj.php");
require_once("parentportalutils.inc.php");

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

$contactList = false;
$personid = 0;
if($_SESSION['customerid']){

	if(isset($_GET['id'])){
		$personid = $_GET['id'] + 0;
	}
	
	$firstnamefield = FieldMap::getFirstNameField();
	$lastnamefield = FieldMap::getLastNameField();
	$contactList = getContacts($_SESSION['portaluserid']);
	if($personid){
		$person = new Person($personid);
		$phones = $person->getPhones();
		$emails = $person->getEmails();
	}
	
	if($accessiblePhonesSetting = getSystemSetting("accessiblePhones"))
		$accessiblePhonesSetting = explode(",", $accessiblePhonesSetting);
	else
		$accessiblePhonesSetting = array();
	
	$accessiblePhones = array_fill(0, getSystemSetting("maxphones")-1, 0);
	foreach($accessiblePhonesSetting as $accessible)
		$accessiblePhones[$accessible] = true;
	
	/****************** main message section ******************/
	
	$f = "contactpreferences";
	$s = "main";
	$reloadform = 0;
	
	
	if(CheckFormSubmit($f,$s))
	{
		//check to see if formdata is valid
		if(CheckFormInvalid($f))
		{
			error('Form was edited in another window, reloading data');
			$reloadform = 1;
		}
		else
		{
			MergeSectionFormData($f, $s);
	
			//do check
	
			if( CheckFormSection($f, $s) ) {
				error('There was a problem trying to save your changes', 'Please verify that all required field information has been entered properly');
			} else {
				//submit changes
	
	
				redirect("template.php");
			}
		}
	} else {
		$reloadform = 1;
	}
	
	if( $reloadform )
	{
		ClearFormData($f);
		if($personid){
			foreach($emails as $email)
				PutFormData($f, $s, "email" . $email->sequence, $email->email, "email", 0, 100);
			foreach($phones as $phone)
				PutFormData($f, $s, "phone" . $phone->sequence, Phone::format($phone->phone), "phone", 0, 100);
		}
	}
}

////////////////////////////////////////////////////////////////////////////////
// Functions
////////////////////////////////////////////////////////////////////////////////



////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
$PAGE = "contacts:contactpreferences";
$TITLE = "Contact Preferences";
if($personid){
	$TITLE .= " - " . $person->$firstnamefield . " " . $person->$lastnamefield;
}
include_once("nav.inc.php");
startWindow("Preferences", 'padding: 3px;');
?>
<table border="1" width="100%" cellpadding="3" cellspacing="1" >
<?
	if($contactList){
?>
		<tr>
			<td width="25%">
				<table>
<?
				foreach($contactList as $person){
?>
					<tr><td><a href="contactpreferences.php?id=<?=$person->id?>"/><?=$person->pkey?> <?=$person->$firstnamefield?> <?=$person->$lastnamefield?></a></td></tr>
<?
				}
?>
				</table>
			</td>
			<td width="75%">
				<table>
<?
				if($personid){
					foreach($phones as $phone){
?>
						<tr><td>Phone <?=$phone->sequence+1?>: 
<?
						if($accessiblePhones[$phone->sequence]){
							NewFormItem($f, $s, "phone" . $phone->sequence, "text", 15);
						} else {
							echo Phone::format($phone->phone);
						}
?>
						</td></tr>		
<?
					}
					foreach($emails as $email){
?>
						<tr><td>Email <?=$email->sequence+1?>: 
<?
							NewFormItem($f, $s, "email" . $email->sequence, "text", 100);
?>
						</td></tr>
<?
					}
				}
?>
			</table>
		</td>
	</tr>
<?
} else {
	?><tr><td>You are not associated with any contacts.  If you would like to add a contact, <a href="addcontact.php"/>Click Here</a></td></tr><?
}
?>
</table>
<?
endWindow();
include_once("navbottom.inc.php");
?>