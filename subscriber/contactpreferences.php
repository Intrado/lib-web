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
	$subscribeFieldValues[$fieldnum] = QuickQueryList("select value, value from persondatavalues where fieldnum='".$fieldnum."' and editlock=1", true);
}

$jobtypes=DBFindMany("JobType", "from jobtype where not deleted order by systempriority, issurvey, name");


	$maxphones = getSystemSetting("maxphones", 3);
	$maxemails = getSystemSetting("maxemails", 2);
	$maxsms = getSystemSetting("maxsms", 2);
	$tempphones = resequence($person->getPhones(), "sequence");
	$phones = array();
	for ($i=0; $i<$maxphones; $i++) {
		if(!isset($tempphones[$i])){
			$phones[$i] = new Phone();
			$phones[$i]->sequence = $i;
			$phones[$i]->personid = $pid;
		} else {
			$phones[$i] = $tempphones[$i];
		}
	}
	$tempemails = resequence($person->getEmails(), "sequence");
	$emails = array();
	for ($i=0; $i<$maxemails; $i++) {
		if(!isset($tempemails[$i])){
			$emails[$i] = new Email();
			$emails[$i]->sequence = $i;
			$emails[$i]->personid = $pid;
		} else {
			$emails[$i] = $tempemails[$i];
		}
	}
	if(getSystemSetting("_hassms")){
		$tempsmses = resequence($person->getSmses(), "sequence");
		$smses = array();
		for ($i=0; $i<$maxsms; $i++) {
			if(!isset($tempsmses[$i])){
				$smses[$i] = new Sms();
				$smses[$i]->sequence = $i;
				$smses[$i]->personid = $pid;
			} else {
				$smses[$i] = $tempsmses[$i];
			}
		}
	} else {
		$smses = array();
	}
	$locked = getLockedDestinations($maxphones, $maxemails, $maxsms);
	$lockedphones = $locked['phones'];
	$lockedemails = $locked['emails'];
	$lockedsms = $locked['sms'];

	$contactprefs = getContactPrefs($pid);
	$defaultcontactprefs = getDefaultContactPrefs();

	/****************** main message section ******************/

	$f = "contactpreferences";
	$s = "main";
	$reloadform = 0;

	if (CheckFormSubmit($f,$s) || CheckFormSubmit($f, "all"))
	{
		//check to see if formdata is valid
		if (CheckFormInvalid($f))
		{
			error('Form was edited in another window, reloading data');
			$reloadform = 1;
		}
		else
		{
			MergeSectionFormData($f, $s);

			//do check
			
			$firstname = TrimFormData($f,$s,"firstname");
			$lastname = TrimFormData($f,$s,"lastname");
			
			if( CheckFormSection($f, $s) ) {
				error('There was a problem trying to save your changes', 'Please verify that all required field information has been entered properly');
			} else {
				if(getSystemSetting('priorityenforcement') && $error = checkPriorityPhone($f, $s, $phones)){
					error("You must have at least one phone number that can receive calls for these job types: " . implode(", ", $error));
				} else {
					$person->$firstnameField = $firstname;
					$person->$lastnameField = $lastname;
			
					foreach ($subscribeFields as $fieldnum => $name) {
						$val = GetFormData($f, $s, "fnum_".$fieldnum);
						error_log("VALVAL ".$val);
				
						if ('f' == substr($fieldnum, 0, 1)) {
							$person->$fieldnum = $subscribeFieldValues[$fieldnum][$val];
						} else { // 'g'
							// TODO groupdata
						}
					}
					$person->update();

					$_SESSION['subscriber.firstname'] = $person->$firstnameField;
					$_SESSION['subscriber.lastname'] = $person->$lastnameField;
							
					getsetContactFormData($f, $s, $pid, $phones, $emails, $smses, $jobtypes, $locked);

					redirect();
				}
			}
		}
	} else {
		$reloadform = 1;
	}

if ($reloadform) {
	ClearFormData($f);

	PutFormData($f, $s, "firstname", $_SESSION['subscriber.firstname'], "text", "1", "100", true);
	PutFormData($f, $s, "lastname", $_SESSION['subscriber.lastname'], "text", "1", "100", true);

	foreach ($subscribeFields as $fieldnum => $name) {
		$val = $person->$fieldnum;
		PutFormData($f, $s, "fnum_".$fieldnum, $val, "text", "nomin", "nomax");
	}

	putContactPrefFormData($f, $s, $contactprefs, $defaultcontactprefs, $phones, $emails, $smses, $jobtypes, $locked);
}



class ValServertest extends Validator {
    var $onlyserverside = true;
    
    function validate ($value, $args) {    
        if ($args[1] + $args[2] != $value)
            return "$this->label should be " . $args[1] ."+". $args[2];
        
        return true;
    }
}

$formdata = array(
    "firstname" => array(
        "label" => "First Name",
        "value" => $_SESSION['subscriber.firstname'],
        "validators" => array(
            array("ValRequired"),
            array("ValLength","min" => 3,"max" => 50)
        ),
        "requires" => "someotherfield",
        "control" => array("TextField","maxlength" => 50),
        "helpstep" => 1
    ),
    "lastname" => array(
        "label" => "Last Name",
        "value" => $_SESSION['subscriber.lastname'],
        "validators" => array(
            array("ValRequired"),
            array("ValLength","min" => 3,"max" => 50)
        ),
        "requires" => "someotherfield",
        "control" => array("TextField","maxlength" => 50),
        "helpstep" => 1
    )
);

foreach ($subscribeFields as $fieldnum => $name) {
	$formdata[$fieldnum] = array (
        "label" => $name,
        "value" => $subscribeFieldValues[$fieldnum][$person->$fieldnum],
        "validators" => array(),
        "control" => array("RadioButton","values" => $subscribeFieldValues[$fieldnum]),
        "helpstep" => 2
	);
}

$helpsteps = array (
    "Welcome to the Guide system. You can use this guide to walk through the form, or access it as needed by clicking to the right of a section",
    "Step 1, name please",
    "Ffields are defined by the admin"
);

$buttons = array(submit_button("Submit","submit","tick"),
                submit_button("Save","save","emoticon_smile"),
                icon_button("Cancel","cross",null,"superform.php?cancel"));

$formname = "gjbtest";                
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
				// TODO groupdata
			}
		}
        
        $person->update();
        $_SESSION['subscriber.firstname'] = $person->$firstnameField;
        $_SESSION['subscriber.lastname'] = $person->$lastnameField;
        
/*        
        if ($ajax)
            $form->sendTo("superform.php?thanksajax");
        else
            redirect("superform.php?thanksnormal");
*/
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

include_once("nav.inc.php");

//NewForm($f);

startWindow(_L('Personal Information'));

?>

<script type="text/javascript">
<? Validator::load_validators(array("ValServertest")); ?>
</script>

<div style="width: 80%; border: 5px inset;">

<? if (isset($_GET['thanksajax'])) { ?>
    <h1>Thanks for using ajax!</h1>
<? } else if (isset($_GET['thanksnormal'])) { ?>
    <h1>Thanks for using normal browser post!</h1>
<? } else { ?>

<script type="text/javascript">
var errors = <?= json_encode($errors) ?>;
if (errors)
    alert("this form contains some errors");
</script>

<noscript>
<h1><?= $errors ? "This form contains some errors" : "" ?></h1>
</noscript>


<?= $form->render() ?>

<? } ?>

</div>

<?
/*
?>
	<table border="0" cellpadding="3" cellspacing="0" width="100%">
		<tr>
			<th valign="top" width="70" class="windowRowHeader bottomBorder" align="right" valign="top" style="padding-top: 6px;"><?=_L("Account Info")?>:</th>
			<td class="bottomBorder">
				<table border="0" cellpadding="1" cellspacing="0">
					<tr>
						<td align="right"><?=_L("First Name")?>:</td>
						<td><? NewFormItem($f,$s, 'firstname', 'text', 20,100); ?></td>
					</tr>
					<tr>
						<td align="right"><?=_L("Last Name")?>:</td>
						<td><? NewFormItem($f,$s, 'lastname', 'text', 20,100); ?></td>
					</tr>
					
<?
					foreach ($subscribeFields as $fieldnum => $name) {
?>
					<tr>
						<td align="right"><?=$name ?>:</td>
						<td>
<?
							NewFormItem($f, $s, "fnum_".$fieldnum, 'selectstart', null, null, "id='fnum_".$fieldnum."'");
							foreach ($subscribeFieldValues[$fieldnum] as $index => $value) {
								NewFormItem($f, $s, "fnum_".$fieldnum, 'selectoption', $value, $index);
							}
							NewFormItem($f, $s, "fnum_".$fieldnum, 'selectend');
?>
						</td>
					</tr>
					
<?					
					}
?>					
				</table>
			</td>
		</tr>
	</table>

<?

*/
endWindow();



/*
?><a name="edit"></a><?
startWindow("Contact Preferences", 'padding: 3px;');
?>
	<table width="100%">
		<tr>
			<td>
<?
	include_once("contactedit.php");
?>
			</td>
		</tr>
	</table>
<?
endWindow();

buttons(submit($f, $s, _L('Save')));
EndForm();
*/


include_once("navbottom.inc.php");
?>