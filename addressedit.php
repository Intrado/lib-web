<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
require_once("inc/common.inc.php");
require_once("inc/securityhelper.inc.php");
require_once("inc/table.inc.php");
require_once("inc/html.inc.php");
require_once("inc/utils.inc.php");
require_once("obj/Validator.obj.php");
require_once("obj/Form.obj.php");
require_once("obj/FormItem.obj.php");
require_once("obj/FieldMap.obj.php");
require_once("obj/Person.obj.php");
require_once("obj/Address.obj.php");
require_once("obj/Phone.obj.php");
require_once("obj/Email.obj.php");
require_once("obj/Language.obj.php");
require_once("obj/ListEntry.obj.php");
require_once("obj/Sms.obj.php");
require_once("obj/JobType.obj.php");
require_once("obj/PeopleList.obj.php");

////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////

//No authorization required to edit any user's own addressbook

////////////////////////////////////////////////////////////////////////////////
// Action/Request Processing
////////////////////////////////////////////////////////////////////////////////

if (isset($_GET['origin'])) {
	$_SESSION['addresseditorigin'] = $_GET['origin'];
}

if (isset($_GET['id'])) {
	$id = $_GET['id'] == "new" ? null : $_GET['id'] + 0;
	
	$query = "select count(*) from person where id=? and userid=? and not deleted";
	if ($id == null || QuickQuery($query,false, array($id,$USER->id)))
		$_SESSION['addresseditid'] = $id;
	
	//if this was a preview, save the refering page url
	$_SESSION['previewreferer'] = (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : NULL);
	
	redirect();
}

////////////////////////////////////////////////////////////////////////////////
// Optional Form Items And Validators
////////////////////////////////////////////////////////////////////////////////


////////////////////////////////////////////////////////////////////////////////
// Form Data
////////////////////////////////////////////////////////////////////////////////

$personid = $_SESSION['addresseditid'];
$languages = Language::getLanguageMap();

$langfield = FieldMap::getLanguageField();
$fnamefield = FieldMap::getFirstNameField();
$lnamefield = FieldMap::getLastNameField();

if ($personid == NULL) {
	// create a new person with empty data
	$person = new Person();
	$person->$langfield = "en"; // default language, so that first in alphabet is not selected (example, Chinese)
	$address = new Address();
} else {
	// editing existing person
	$person = DBFind("Person", "from person where id = ?",null,array($personid));
	//TODO if not person?
	$address = DBFind("Address", "from address where personid = ?",null,array($personid));
	if ($address === false) $address = new Address(); // contact was imported/uploaded without any address data, create one now

	// get existing phones from db, then create any additional based on the max allowed
	// what if the max is less than the number they already have? the GUI does not allow to decrease this value, so NO WORRIES :)
	$tempphones = resequence(DBFindMany("Phone", "from phone where personid= ? order by sequence",null,array($personid)),"sequence");
	$tempemails = resequence(DBFindMany("Email", "from email where personid= ? order by sequence",null,array($personid)),"sequence");
	$tempsmses = resequence(DBFindMany("Sms", "from sms where personid= ? order by sequence",null,array($personid)),"sequence");
}

$contactprefs = $personid ? getContactPrefs($personid) : array();
$defaultcontactprefs = getDefaultContactPrefs();

function isprefset ($type,$seq,$jobtypeid) {
	global $contactprefs, $defaultcontactprefs;
	if (isset($contactprefs[$type][$seq][$jobtypeid])) {
		if ($contactprefs[$type][$seq][$jobtypeid]) {
			return true;
		}
	} else {
		if (isset($defaultcontactprefs[$type][$seq][$jobtypeid]) && $defaultcontactprefs[$type][$seq][$jobtypeid]) {
			return true;
		}
	}
	return false;
}

$jobtypes = JobType::getUserJobTypes(false);
if (getSystemSetting('_hassurvey', true) && $USER->authorize('survey'))
	$jobtypes = array_merge($jobtypes, JobType::getUserJobTypes(true));

$jobtypenames = array();
$jobtypehoverdata = array();
foreach ($jobtypes as $jobtype) {
	$jobtypenames[$jobtype->id] = $jobtype->name;
	$jobtypehoverdata[$jobtype->id] = escapehtml(nl2br($jobtype->info));
}

$types = array();

$helpstep = 1;
$helpsteps = array (_L("Enter the contact's name and address."));
$formdata = array(
	"firstname" => array(
		"label" => _L('First Name'),
		"fieldhelp" => _L("Enter the contact's first name."), 
		"value" => $person->$fnamefield ,
		"validators" => array(
			array("ValLength","max" => 50)
		),
		"control" => array("TextField","size" => 20, "maxlength" => 50),
		"helpstep" => $helpstep
	),
	"lastname" => array(
		"label" => _L('Last Name'),
		"fieldhelp" => _L("Enter the contact's last name."),
		"value" => $person->$lnamefield,
		"validators" => array(
			array("ValLength","max" => 50)
		),
		"control" => array("TextField","size" => 20, "maxlength" => 50),
		"helpstep" => $helpstep
	),
	"language" => array(
		"label" => _L('Language Preference'),
		"fieldhelp" => _L('When available, messages will be sent in the person\'s language preference.'), 
		"value" => $person->$langfield ? $person->$langfield : "en",
		"validators" => array(
			array("ValRequired"),
			array("ValInArray","values" => array_keys($languages))
		),
		"control" => array("SelectMenu","values" => $languages),
		"helpstep" => $helpstep
	),
	"addr1" => array(
		"label" => _L('Address Line 1'),
		"fieldhelp" => _L("Enter the contact's address."),
		"value" => $address->addr1,
		"validators" => array(
			array("ValLength","max" => 50)
		),
		"control" => array("TextField","size" => 25, "maxlength" => 50),
		"helpstep" => $helpstep
	),
	"addr2" => array(
		"label" => _L('Address Line 2'),
		"fieldhelp" => _L("Enter the contact's address."),
		"value" =>  $address->addr2,
		"validators" => array(
			array("ValLength","max" => 50)
		),
		"control" => array("TextField","size" => 25, "maxlength" => 50),
		"helpstep" => $helpstep
	),
	"city" => array(
		"label" => _L('City'),
		"fieldhelp" => _L("Enter the city where the address is located."),
		"value" =>  $address->city,
		"validators" => array(
			array("ValLength","max" => 50)
		),
		"control" => array("TextField","size" => 20, "maxlength" => 50),
		"helpstep" => $helpstep
	),
	"state" => array(
		"label" => _L('State'),
		"fieldhelp" => _L("Enter the state for the address."),
		"value" =>  $address->state,
		"validators" => array(
			array("ValLength","min" => 2,"max" => 2)
		),
		"control" => array("TextField","size" => 2, "maxlength" => 2),
		"helpstep" => $helpstep
	),
	"zip" => array(
		"label" => _L('ZIP Code'),
		"fieldhelp" => _L("Enter the contact's ZIP code."),
		"value" =>  $address->zip,
		"validators" => array(
			array("ValNumeric","min" => 5, "max" => 5)
		),
		"control" => array("TextField","size" => 5, "maxlength" => 5),
		"helpstep" => $helpstep
	)
);


//add phone settings
if ($USER->authorize('sendphone')) {
	$helpsteps[$helpstep++] = _L('Enter the phone numbers where the contact can be reached. Then select the types of jobs that should be sent to each number.');
	$formdata[] = _L('Phone Settings');
	
	$maxphones = getSystemSetting("maxphones",3);
	$phones = array();
	for ($i=0; $i<$maxphones; $i++) {
		if(!isset($tempphones[$i])){
			$phones[$i] = new Phone();
			$phones[$i]->sequence = $i;
		} else {
			$phones[$i] = $tempphones[$i];
		}
	}
	$types["phone"] = $phones;
	
	$x = 0;
	foreach ($phones as $phone) {		
		$formdata["phone$x"] = array (
			"label" => escapehtml(destination_label("phone",$x)),
			"fieldhelp" => _L("Enter a phone number for the contact."), 
			"value" => Phone::format($phone->phone),
			"validators" => array(
				array("ValPhone")
			),
			"control" => array("TextField","size" => 20, "maxlength" => 20),
			"helpstep" => $helpstep
		);
		
		$prefjobids = array();
		foreach ($jobtypenames as $jobtypeid => $dummy) {
			if (isprefset('phone',$x,$jobtypeid))
				$prefjobids[] = $jobtypeid;
		}
		
		$formdata["phone$x-jobtypes"] = array (
			"label" => _L('Preferences'),
			"fieldhelp" => _L("Select the types of jobs which should be sent to this number."), 
			"value" => $prefjobids,
			"validators" => array(
				array("ValInArray","values" => array_keys($jobtypenames))
			),
			"control" => array("MultiCheckBox","values" => $jobtypenames, "hover" => $jobtypehoverdata),
			"helpstep" => $helpstep
		);
		
		$x++;
	}
}


//add email settings
if ($USER->authorize('sendemail')) {
	$helpsteps[$helpstep++] = _L("Enter the contact's email addresses and then select which types of jobs should be sent to each address.");
	$formdata[] = _L('Email Settings');
	
	$maxemails = getSystemSetting("maxemails",2);
	$emails = array();
	for ($i=0; $i<$maxemails; $i++) {
		if(!isset($tempemails[$i])){
			$emails[$i] = new Email();
			$emails[$i]->sequence = $i;
		} else {
			$emails[$i] = $tempemails[$i];
		}
	}
	$types["email"] = $emails;
	
	$x = 0;
	foreach ($emails as $email) {		
		$formdata["email$x"] = array (
			"label" => escapehtml(destination_label("email",$x)),
			"fieldhelp" => _L("Enter the contact's email address."), 
			"value" => $email->email,
			"validators" => array(
				array("ValEmail")
			),
			"control" => array("TextField","size" => 30, "maxlength" => 100),
			"helpstep" => $helpstep
		);
		
		$prefjobids = array();
		foreach ($jobtypenames as $jobtypeid => $dummy) {
			if (isprefset('email',$x,$jobtypeid))
				$prefjobids[] = $jobtypeid;
		}
				
		$formdata["email$x-jobtypes"] = array (
			"label" => _L('Preferences'),
			"fieldhelp" => _L("Select the types of jobs which should be sent to this email."), 
			"value" => $prefjobids,
			"validators" => array(
				array("ValInArray","values" => array_keys($jobtypenames))
			),
			"control" => array("MultiCheckBox","values" => $jobtypenames, "hover" => $jobtypehoverdata),
			"helpstep" => $helpstep
		);
		
		$x++;
	}
}

//add sms settings
if (getSystemSetting('_hassms', false) && $USER->authorize('sendsms')) {
	$helpsteps[$helpstep++] = _L("Enter numbers where the contact may receive SMS text messages. Then select the types of jobs which should be sent to each number.");
	$formdata[] = _L('SMS Text Settings');
	
	$maxsms = getSystemSetting("maxsms",2);
	$smses = array();
	for ($i=0; $i<$maxsms; $i++) {
		if(!isset($tempsmses[$i])){
			$smses[$i] = new Sms();
			$smses[$i]->sequence = $i;
		} else {
			$smses[$i] = $tempsmses[$i];
		}
	}
	$types["sms"] = $smses;
	
	$x = 0;
	foreach ($smses as $sms) {		
		$formdata["sms$x"] = array (
			"label" => escapehtml(destination_label("sms",$x)),
			"fieldhelp" => _L('Enter a number where SMS text messages may be received.'), 
			"value" => Phone::format($sms->sms),
			"validators" => array(
				array("ValPhone")
			),
			"control" => array("TextField","size" => 13, "maxlength" => 20),
			"helpstep" => $helpstep
		);
		
		$prefjobids = array();
		foreach ($jobtypenames as $jobtypeid => $dummy) {
			if (isprefset('sms',$x,$jobtypeid))
				$prefjobids[] = $jobtypeid;
		}
		
		$formdata["sms$x-jobtypes"] = array (
			"label" => _L('Preferences'),
			"fieldhelp" => _L('Select the types of jobs which should be sent to this SMS number.'), 
			"value" => $prefjobids,
			"validators" => array(
				array("ValInArray","values" => array_keys($jobtypenames))
			),
			"control" => array("MultiCheckBox","values" => $jobtypenames, "hover" => $jobtypehoverdata),
			"helpstep" => $helpstep
		);
		
		$x++;
	}
}

//if this is from the list page, add a checkbox to save to addrbook
if ($_SESSION['addresseditorigin'] == "manualadd") {
	$helpsteps[$helpstep++] = _L('Check this option to save the contact to your address book so you may easily include them in future lists.');
	$formdata[] = _L('Save to Address Book');
	
	$formdata["savetoaddrbook"] = array(
		"label" => _L('Save to Address Book'),
		"fieldhelp" => _L("Check this option to save this contact to your address book."),
		"value" =>  true,
		"validators" => array(
		),
		"control" => array("CheckBox"),
		"helpstep" => $helpstep
	);
}

// set the redirect page, used by done or cancel button
switch ($_SESSION['addresseditorigin']) {
case "nav":
	$redirectPage = "addresses.php";
	break;
case "manualaddbook":
	$redirectPage = "addresses.php?origin=manualadd";
	break;
case "manualadd":
	$redirectPage = "list.php";
	break;
case "preview":
	$redirectPage =  $_SESSION['previewreferer'];
	break;
}

$buttons = array(submit_button(_L('Done'),"submit","tick"),
				icon_button(_L('Cancel'),"cross",null,$redirectPage));
$form = new Form("addressedit",$formdata,$helpsteps,$buttons);

////////////////////////////////////////////////////////////////////////////////
// Form Data Handling
////////////////////////////////////////////////////////////////////////////////

//check and handle an ajax request (will exit early)
//or merge in related post data
$form->handleRequest();

$datachange = false;
$errors = false;
//check for form submission
if ($button = $form->getSubmit()) { //checks for submit and merges in post data
	$ajax = $form->isAjaxSubmit(); //whether or not this requires an ajax response	
	
	if ($form->checkForDataChange()) {
		$datachange = true;
	} else if (($errors = $form->validate()) === false) { //checks all of the items in this form
		$postdata = $form->getData(); //gets assoc array of all values {name:value,...}
		
		//save data here
		
		QuickUpdate("begin");
		
		$person->userid = $USER->id;		
		$person->deleted = 0;
		// if new person, set if addressbook/manualadd (else of editing, leave this setting alone)
		if (!$person->id) {
			$person->type = "addressbook";
			//the only time we don't set to addressbook is from manual add page where they've uncheck the box
			if ($_SESSION['addresseditorigin'] == "manualadd" && !$postdata['savetoaddrbook'])
				$person->type = "manualadd";
		}
		
		$person->$fnamefield = $postdata['firstname'];
		$person->$lnamefield = $postdata['lastname'];
		$person->$langfield = $postdata['language'];
		$person->update();
		
		$personid = $person->id;
		
		$address->personid = $person->id;
		$address->addr1 = $postdata['addr1'];
		$address->addr2 = $postdata['addr2'];
		$address->city = $postdata['city'];
		$address->state = $postdata['state'];
		$address->zip = $postdata['zip'];
		$address->update();
		
		if (isset($types["phone"])) {
			$x = 0;
			foreach ($phones as $phone) {
				$phone->personid = $person->id;
				$phone->sequence = $x;
				$phone->phone = Phone::parse($postdata["phone$x"]);
				$phone->editlock = 0;
				$phone->update();
				$x++;
			}
		}
		
		if (isset($types["email"])) {
			$x = 0;
			foreach ($emails as $email) {
				$itemname = "email".($x+1);
				$email->personid = $person->id;
				$email->sequence = $x;
				$email->email = trim($postdata["email$x"]);
				$email->editlock = 0;
				$email->update();
				$x++;
			}
		}
		
		if (isset($types["sms"])) {
			$x = 0;
			foreach ($smses as $sms) {
				$sms->personid = $person->id;
				$sms->sequence = $x;
				$sms->sms = Phone::parse($postdata["sms$x"]);
				$sms->editlock = 0;
				$sms->update();
				$x++;
			}
		}
		
		// if manual add to a list, and entry not found, then create one
		// (otherwise they edit existing contact on the list)

		if (($_SESSION['addresseditorigin'] == "manualadd" || $_SESSION['addresseditorigin'] == "manualaddbook") && isset($_SESSION['listid'])) {
			
			//don't add another if this record already exists (like from an edit?)
			
			if (!QuickQuery("select count(*) from listentry where listid = ? and personid = ?", null, array($_SESSION['listid'], $personid))) {
				$le = new ListEntry();
				$le->listid = $_SESSION['listid'];
				$le->type = "add";
				$le->sequence = 0;
				$le->personid = $person->id;
				$le->create();
				
				$list = new PeopleList($_SESSION['listid']);
				$list->modifydate = date("Y-m-d H:i:s");
				$list->update(array("modifydate"));
			}
		}
		
		//check contact prefs for each type, item, and jobtype
		foreach (array_keys($types) as $type) {
			foreach($types[$type] as $item) {
				foreach(array_keys($jobtypenames) as $jobtypeid) {
					$oldsetting = isprefset($type,$item->sequence,$jobtypeid);
					$newsetting = in_array($jobtypeid,$postdata[$type . $item->sequence . "-jobtypes"]);
					
					//detect if there is a change, if so insert/update the pref
					//otherwise leave contact pref unchanged (or nonexistant)
					if ($newsetting != $oldsetting) {
						$query = "insert into contactpref (personid, jobtypeid, type, sequence, enabled)
									values (?, ?, ?, ?, ?) 
									on duplicate key update enabled=values(enabled)";
						QuickUpdate($query, false, array($personid, $jobtypeid, $type, $item->sequence, $newsetting ? 1 : 0));
					}
				}
			}
		}
		
		QuickUpdate("commit");
		
		if ($ajax)
			$form->sendTo($redirectPage);
		else
			redirect($redirectPage);
	}
}

////////////////////////////////////////////////////////////////////////////////
// Display Functions
////////////////////////////////////////////////////////////////////////////////

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
$PAGE = ($_SESSION['addresseditorigin'] == "nav") ? "start:addressbook" : "notifications:lists";
$TITLE = escapehtml(_L('Address Book: %1$s %2$s', $personid ? $person->$fnamefield : _L("New Contact"), $personid ? $person->$lnamefield : ""));

include_once("nav.inc.php");

// Optional Load Custom Form Validators
?>
<script type="text/javascript">
<? Validator::load_validators(array()); ?>
</script>
<?

startWindow(_L('Address Book Editor'));
echo $form->render();
endWindow();
include_once("navbottom.inc.php");
?>