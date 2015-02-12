<?

////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
include_once("inc/common.inc.php");
include_once("inc/utils.inc.php");
include_once("inc/securityhelper.inc.php");
include_once("inc/form.inc.php");
require_once("inc/formatters.inc.php");
include_once("inc/html.inc.php");
include_once("inc/table.inc.php");
include_once("obj/FieldMap.obj.php");
include_once("obj/Person.obj.php");
include_once("obj/Address.obj.php");
include_once("obj/Phone.obj.php");
include_once("obj/Email.obj.php");
include_once("obj/Device.obj.php");
include_once("obj/DeviceDto.obj.php");
include_once("obj/Language.obj.php");
include_once("obj/JobType.obj.php");
include_once("obj/Sms.obj.php");
include_once("obj/LinkedAccountManager.obj.php");
include_once("obj/DeviceServiceApiClient.obj.php");

$FORMDISABLE = " DISABLED ";
if(isset($method)){
	if($method == "edit"){
		$FORMDISABLE = "";
	}
} else {
	exit();
}

$iFrameAppend = (isset($_GET["iframe"])?"&iframe=true":"");
$iFramePrepend = (isset($_GET["iframe"])?"?iframe=true":"");

if (isset($_GET['id'])) {
	$personid = $_GET['id'] + 0;
	if ($personid == "") {
		// bad
		redirect('unauthorized.php');
	}

	// validate user has rights to view this contact
	if (!$USER->canSeePerson($personid)) {
		// bad
		redirect('unauthorized.php');
	}
	
	//check to see if trying to viewcontact on a person this user created, and redirect to addressbook edit
	$person = new Person($personid);
	if ($person->userid == $USER->id)
		redirect("addressedit.php?id=$personid&origin=preview$iFrameAppend");
	
	$_SESSION['currentpid'] = $personid;
	if (!isset($_GET['ajax'])) 
		redirect($_SERVER["SCRIPT_NAME"] . $iFramePrepend);
} else if(isset($_SESSION['currentpid'])){
	$personid = $_SESSION['currentpid'];
} else {
	redirect('unauthorized.php');
}

$person = $csApi->getPerson($personid, "dependents,guardians");

if(getSystemSetting("_hasportal", false) && $USER->authorize("portalaccess")){
	if(isset($_GET['create']) && $_GET['create']){
		if(generatePersonTokens(array($personid))){
			notice(_L("An activation code is now created for %s.", escapehtml(Person::getFullName($personid))));
			redirect($_SERVER["SCRIPT_NAME"] . $iFramePrepend);		
		} else {
			error("There was an error generating a new token");
		}
	}

	if(isset($_GET['revoke'])){
		$revokeid = $_GET['revoke'] + 0;
		$count = revokePersonTokens(array($revokeid));
		if($count){
			notice(_L("The activation code for %s is now revoked.", escapehtml(Person::getFullName($revokeid))));
			redirect($_SERVER["SCRIPT_NAME"] . $iFramePrepend);
		} else {
			error("There was an error revoking this person's token");
		}
	}
}

if((getSystemSetting("_hasportal", false) || getSystemSetting("_hasinfocenter", false)) && $USER->authorize("portalaccess")){
	if(isset($_GET['disassociate'])){
		$portaluserid = $_GET['disassociate'] + 0;
		$infoCenter = getSystemSetting("_hasinfocenter", false);
		if ($infoCenter) {
			$linkedAccountManager = new LinkedAccountManager();
			$count = $linkedAccountManager->disassociateAccount($personid, $portaluserid);
		} else {
			$count = QuickUpdate("delete from portalperson where personid = '" . $personid . "' and portaluserid = '" . $portaluserid . "'");
		}
		if($count){
			$portaluser = getPortalUsers(array($portaluserid));
			$portalusername = $portaluser[$portaluserid]['portaluser.username'];
			notice(_L("%1s is now dissociated from user %2s.", escapehtml(Person::getFullName($personid)), escapehtml($portalusername)));
			redirect($_SERVER["SCRIPT_NAME"] . $iFramePrepend);
		} else {
			error("An error occurred while disassociating the Portal User to this person");
		}
	}
}

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

// prepopulate person phone and email lists
if (!$maxphones = getSystemSetting("maxphones"))
	$maxphones = 3;

if (!$maxemails = getSystemSetting("maxemails"))
	$maxemails = 2;

if (!$maxsms = getSystemSetting("maxsms"))
	$maxsms = 2;

if (isset($personid)) {
	// editing existing person
	$data = DBFind("Person", "from person where id = " . $personid);
	// block access to students contact data? (is complicated flag for Nate)
	$blockDataAccess = $data->getSetting("block_data_access");
	$query = "select group_concat(oz.orgkey separator ', ') from organization oz join personassociation pa on (pa.organizationid = oz.id) where personid=?";
	$organization = QuickQuery($query, false, array($personid));
	$address = DBFind("Address", "from address where personid = " . $personid);
	if ($address === false) $address = new Address(); // contact was imported/uploaded without any address data, create one now
	$types = array();
	// get existing phones from db, then create any additional based on the max allowed
	// what if the max is less than the number they already have? the GUI does not allow to decrease this value, so NO WORRIES :)
	// use array_values to reset starting index to 0
	$tempphones = resequence(DBFindMany("Phone", "from phone where personid=" . $personid . " order by sequence"), "sequence");
	$phones = array();
	for ($i=0; $i<$maxphones; $i++) {
		if(!isset($tempphones[$i])){
			$phones[$i] = new Phone();
			$phones[$i]->sequence = $i;
			$phones[$i]->personid = $personid;
		} else {
			$phones[$i] = $tempphones[$i];
		}
	}
	$types["phone"] = $phones;

	$tempemails = resequence(DBFindMany("Email", "from email where personid=" . $personid . " order by sequence"), "sequence");
	$emails = array();
	for ($i=0; $i<$maxemails; $i++) {
		if(!isset($tempemails[$i])){
			$emails[$i] = new Email();
			$emails[$i]->sequence = $i;
			$emails[$i]->personid = $personid;
		} else {
			$emails[$i] = $tempemails[$i];
		}
	}
	$types["email"] = $emails;

	if(getSystemSetting("_hassms", false)){
		$tempsmses = resequence(DBFindMany("Sms", "from sms where personid=" . $personid . " order by sequence"), "sequence");
		for ($i=0; $i<$maxsms; $i++) {
			if(!isset($tempsmses[$i])){
				$smses[$i] = new Sms();
				$smses[$i]->sequence = $i;
				$smses[$i]->personid = $personid;
			} else {
				$smses[$i] = $tempsmses[$i];
			}
		}
		$types["sms"] = $smses;
	}
	
	if (getSystemSetting("_hasinfocenter", false)) {
		$deviceService = DeviceServiceApiClient::instance($SETTINGS);
		$deviceDbmos = DBFindMany("Device", "from device where personId = ? order by sequence", false, array($personid));
		$devices = array();
		foreach ($deviceDbmos as $d) {
			$response = $deviceService->getDevice($d->deviceUuid);
			//only add a device when device service returns device details
			if ($response) {
				$dto = new DeviceDto();
				$dto->sequence = $d->sequence;
				$dto->personId = $d->personId;
				$dto->uuid = $d->deviceUuid;
				if ($response) {
					$dto->name = $response->name;
				}
				$devices[] = $dto;
			}
		}
		if(count($devices) > 0)
			$types["device"] = $devices;
	}
	
	$contacttypes = array("phone", "email");
	if (getSystemSetting("_hassms", false))
		$contacttypes[] = "sms";
	if (getSystemSetting("_hasinfocenter", false))
		$contacttypes[] = "device";
	
	// check if viewing a guardian
	if ($data->type == "guardianauto" || $data->type == "guardiancm") {
		$isGuardian = true;
	} else {
		$isGuardian = false;
		// used to redirect guardian view back to the student view
		$_SESSION['guardian_of_personid'] = $personid;
	}
	// set page to redirect back to
	if ($isGuardian && isset($_SESSION['guardian_of_personid'])) {
		// return to last viewed student, assume that is how we got to viewing this guardian
		$backTo = "viewcontact.php?id=" . $_SESSION['guardian_of_personid'];
	} else {
		// return to page that launched into student view
		$backTo = $_SESSION['contact_referer'];
	}
	
	$associateids = QuickQueryList("select portaluserid from portalperson where personid = '" . $personid . "' order by portaluserid");
	$associates = getPortalUsers($associateids);
	$tokendata = QuickQueryRow("select token, expirationdate, creationuserid from portalpersontoken where personid = '" . $personid . "'", true);
	$creationusername = "";
	if($tokendata){
		$creationuser = new User($tokendata['creationuserid']);
		$tokendata['creationusername'] = $creationuser->firstname . " " . $creationuser->lastname;
		$tokendata['expirationdate'] = ($tokendata['expirationdate'] != "") ? date("M j, Y", strtotime($tokendata['expirationdate'])) : "";
		if(strtotime($tokendata['expirationdate']) < strtotime("now")){
			$tokendata['token'] = "Expired";
		}
	} else {
		$tokendata = array("token" => "",
							"creationusername" => "",
							"expirationdate" => "");
	}
	
	if (getSystemSetting('_hassurvey', true))
		$jobtypes = DBFindMany("JobType", "from jobtype where not deleted order by systempriority, issurvey, name");
	else
		$jobtypes = DBFindMany("JobType", "from jobtype where not issurvey and not deleted order by systempriority, issurvey, name");
	$contactprefs = getContactPrefs($personid);
	$defaultcontactprefs = getDefaultContactPrefs();
} else {
	// error, person should always be set, this is a viewing page!
	redirect('unauthorized.php');
}

/****************** main message section ******************/

$f = "viewcontact";
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

		// trim or blank all fields
		foreach($contacttypes as $type){
			if(!isset($types[$type])) continue;
			foreach($types[$type] as $item){
				if(!GetFormData($f, $s, 'editlock_' . $type . $item->sequence)){
					$putformtype = $type;
					if($type == "sms"){
						$putformtype = "phone";
					}
					PutFormData($f, $s, $type . $item->sequence, "", $putformtype);
				}
				TrimFormData($f, $s, $type . $item->sequence);
			}
		}

		//do check
		if( CheckFormSection($f, $s) ) {
			error('There was a problem trying to save your changes', 'Please verify that all required field information has been entered properly');
		} else {
			//submit changes
			$error = false;
			if(GetFormData($f, $s, "block_data_access") || $blockDataAccess != GetFormData($f, $s, "block_data_access")){
				$data->setSetting("block_data_access", GetFormData($f, $s, "block_data_access"));
			}
			foreach($contacttypes as $type){
				if ($error) continue;
				if (!isset($types[$type])) continue;
				foreach($types[$type] as $item){
					// device type does not have editlock
					if($type != "device" && GetFormData($f, $s, "editlock_" . $type . $item->sequence) || $item->editlock != GetFormData($f, $s, "editlock_" . $type . $item->sequence)){
						$item->editlock = GetFormData($f, $s, "editlock_" . $type . $item->sequence);
						if($item->editlock){
							if($type == "email")
								$item->$type = GetFormData($f, $s, $type . $item->sequence);
							else {
								$p = GetFormData($f, $s, $type . $item->sequence);
								if ($p != "" && $phoneerror = Phone::validate($p)) {
									error($phoneerror);
									$error = true;
									continue;
								}
								$item->$type = Phone::parse($p);
							}
						}
						if (!$error)
							$item->update();
					}
					if (!$error) {
					  foreach($jobtypes as $jobtype){
						if((!isset($contactprefs[$type][$item->sequence][$jobtype->id]) && !isset($defaultcontactprefs[$type][$item->sequence][$jobtype->id]) &&
										GetFormData($f, $s, $type . $item->sequence . "jobtype" . $jobtype->id))
							||
							(!isset($contactprefs[$type][$item->sequence][$jobtype->id]) && isset($defaultcontactprefs[$type][$item->sequence][$jobtype->id]) &&
										GetFormData($f, $s, $type . $item->sequence . "jobtype" . $jobtype->id) != $defaultcontactprefs[$type][$item->sequence][$jobtype->id])){
								QuickUpdate("insert into contactpref (personid, jobtypeid, type, sequence, enabled)
											values ('" . $personid . "','" . $jobtype->id . "','$type','" . $item->sequence . "','"
											. DBSafe(GetFormData($f, $s, $type . $item->sequence . "jobtype" . $jobtype->id)) . "')");
							} else if(isset($contactprefs[$type][$item->sequence][$jobtype->id]) &&
										GetFormData($f, $s, $type . $item->sequence . "jobtype" . $jobtype->id) != $contactprefs[$type][$item->sequence][$jobtype->id]){
								QuickUpdate("update contactpref set enabled = '" . DBSafe(GetFormData($f, $s, $type . $item->sequence . "jobtype" . $jobtype->id)) . "'
												where personid = '" . $personid . "' and jobtypeid = '" . $jobtype->id . "' and sequence = '" . $item->sequence . "' and type='$type'");
						}
					  }
					}
				}
			}
			if (!$error) {
				$portalphoneactivation = GetFormData($f, $s, 'allowphoneactivation');
				QuickUpdate("delete from personsetting where personid=".$personid." and name='portalphoneactivation'");
				QuickUpdate("insert into personsetting (personid, name, value) values ($personid, 'portalphoneactivation', $portalphoneactivation)");

				redirect($backTo);
			}
		}
	}
} else {
	$reloadform = 1;
}

if( $reloadform )
{
	ClearFormData($f);
	PutFormData($f, $s, "block_data_access", $blockDataAccess, "bool", 0, 1);
	foreach($contacttypes as $type){
		if(!isset($types[$type])) continue;
		foreach($types[$type] as $item){
			if($type == "device")
				PutFormData($f, $s, $type . $item->sequence, $type, "device");
			else if($type == "email")
				PutFormData($f, $s, $type . $item->sequence, $item->$type, "email");
			else
				PutFormData($f, $s, $type . $item->sequence, Phone::format($item->$type), "phone");
			if ($type != "device")
				PutFormData($f, $s, "editlock_" . $type . $item->sequence, $item->editlock, "bool", 0, 1);
			foreach($jobtypes as $jobtype){
				$contactpref = 0;
				if(isset($contactprefs[$type][$item->sequence][$jobtype->id]))
					$contactpref = $contactprefs[$type][$item->sequence][$jobtype->id];
				else if(isset($defaultcontactprefs[$type][$item->sequence][$jobtype->id]))
					$contactpref = $defaultcontactprefs[$type][$item->sequence][$jobtype->id];
				PutFormData($f, $s, $type . $item->sequence . "jobtype" . $jobtype->id, $contactpref, "bool", 0, 1);
			}
		}
	}
	$portalphoneactivation = QuickQuery("select value from personsetting where personid=$personid and name='portalphoneactivation'");
	if ($portalphoneactivation === false)
		$portalphoneactivation = "1"; // default is checked
	PutFormData($f, $s, 'allowphoneactivation', $portalphoneactivation, "bool", 0, 1);
}

////////////////////////////////////////////////////////////////////////////////
// Functions
////////////////////////////////////////////////////////////////////////////////
function displayValue($s) {
	echo($s."&nbsp;");
}

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

// where did we come from, list preview or contact tab
$PAGE = "notifications:lists";
if (strpos($_SESSION['contact_referer'],"contacts.php") !== false) $PAGE = "system:contacts";
if (strpos($_SESSION['contact_referer'],"activationcodemanager.php") !== false) $PAGE = "system:contacts";

$contactFullName = "";
$firstnamefield = FieldMap::getFirstNameField();
$contactFullName .= $data->$firstnamefield;
$lastnamefield = FieldMap::getLastNameField();
$contactFullName .= " ".$data->$lastnamefield;

$TITLE = "View Contact Information: " . escapehtml($contactFullName);

if (!isset($_GET['ajax'])) {
	include_once("nav.inc.php");
	startWindow('Contact');
	
	NewForm($f);
	
	if($method == "edit"){
		buttons(submit($f, $s, _L("Save"),null,'tick'),
				icon_button(_L("Cancel"),"cross", null, $backTo));
	} else {
		buttons(icon_button(_L("Back"),"fugue/arrow_180", null, $backTo),
			$USER->authorize('managecontactdetailsettings') ? icon_button(_L("Edit"),"pencil", "if(confirm('You are about to edit contact data that may impact other people\'s lists.  Are you sure you want to continue?')) window.location='editcontact.php" . $iFramePrepend . " '") : ""
		);
	}
	echo "<br />";
} else {
	echo "<div style='width:346px; height:200px; overflow:auto'>";
}

?>
<table border="0" cellpadding="3" cellspacing="0" width="100%">
	<tr>
		<th align="right" class="windowRowHeader bottomBorder">ID#:</th>
		<td class="bottomBorder">
			<? displayValue($data->pkey); ?>
		</td>
	</tr>
	<tr>
		<th align="right" class="windowRowHeader bottomBorder">Name:</th>
		<td class="bottomBorder">
			<? displayValue($contactFullName); ?>
		</td>
	</tr>
	<tr>
		<th align="right" class="windowRowHeader bottomBorder">Language Preference:</th>
		<td  class="bottomBorder">
			<? $languagefield=FieldMap::getLanguageField(); displayValue(Language::getName($data->$languagefield)); ?>
		</td>
	</tr>
<?

if (!$isGuardian) {
// Ffields
$fieldmaps = FieldMap::getAuthorizedFieldMaps();
foreach ($fieldmaps as $map) {
	$fname = $map->fieldnum;
	$header = $map->name;
	$fval = $data->$fname;

	if (!strcmp($fname, FieldMap::getFirstNameField()) ||
		!strcmp($fname, FieldMap::getLastNameField()) ||
		!strcmp($fname, FieldMap::getLanguageField())) {
			continue; // skip field, it was in layout above
	}

?>
	<tr>
		<th align="right" class="windowRowHeader bottomBorder"><?= $header ?>:</th>
		<td class="bottomBorder"><? displayValue($fval); ?></td>
	</tr>
<?
}

// Gfields
$fieldmaps = FieldMap::getAuthorizedFieldMapsLike('g');
foreach ($fieldmaps as $map) {
	$fname = $map->fieldnum;
	$header = $map->name;
	$query = "select group_concat(value separator ', ') from groupdata where fieldnum=".substr($fname,1)." and personid=".$personid;
	$fval = QuickQuery($query);
?>
	<tr>
		<th align="right" class="windowRowHeader bottomBorder"><?= $header ?>:</th>
		<td class="bottomBorder"><? displayValue($fval); ?></td>
	</tr>
<?
}
?>

	<tr>
		<th align="right" class="windowRowHeader bottomBorder"><?=escapehtml(getSystemSetting("organizationfieldname","Organization"))?>:</th>
		<td class="bottomBorder"><? displayValue($organization); ?></td>
	</tr>
<? 
} // end if not guardian
?>	
	<tr>
		<th align="right" valign="top" class="windowRowHeader bottomBorder" style="padding-top: 10px;">Address:</th>
		<td class="bottomBorder">
			<table border="0">
				<tr>
					<td><? displayValue($address->addr1); ?></td>
				</tr>
				<tr>
					<td><? displayValue($address->addr2); ?></td>
				</tr>
				<tr>
					<td>
						<?
							if (strlen(trim($address->city)) == 0 &&
								strlen(trim($address->state)) == 0) {
									displayValue($address->zip);
								} else {
						 			displayValue($address->city.",");
						 			displayValue($address->state." ");
						 			displayValue($address->zip);
								}
						 ?>
					</td>
				</tr>
			</table>
		</td>
	</tr>
	<tr>
		<th align="right" class="windowRowHeader bottomBorder">Contact Details: </th>
		<td class="bottomBorder">
			<table  cellpadding="2" cellspacing="1">

<?


	foreach($contacttypes as $type){
		if(!isset($types[$type])) continue;
?>
		<tr class="listHeader">
			<th align="left" colspan="<?=count($jobtypes)+3; ?>"><?=format_delivery_type($type); ?></th>
		</tr>
		<tr class="windowRowHeader">
			<th align="left">Contact&nbsp;Type</th>
<?
			if ($type == 'device') {
?>
			<th>&nbsp;&nbsp;</th>
<?
			} else {
?>
			<th>Override</th>
<?
			}
?>
			<th align="left">Destination</th>
<?
			foreach($jobtypes as $jobtype){
?>
				<th>
<?
					if(($type=='sms' || $type == 'device') && $jobtype->issurvey)
						echo "&nbsp;";
					else
						echo jobtype_info($jobtype);
?>
				</th>
<?
			}
?>
		</tr>
<?
		foreach($types[$type] as $item){
			$header = escapehtml(destination_label($type, $item->sequence));
?>
			<tr>
				<td class="bottomBorder"><?= $header ?></td>
<?				// Override checkbox
				if ($type == "device") {
?>
					<td align="center"  class="bottomBorder">&nbsp;</td>
<?
				} else {
?>
					<td align="center"  class="bottomBorder"><? NewFormItem($f, $s, "editlock_" . $type . $item->sequence, "checkbox", 0, 1, $FORMDISABLE . 'id="editlock_' . $type . $item->sequence . '" onclick="new getObj(\'' . $type . $item->sequence . '\').obj.disabled = !this.checked"'); ?></td>
<?				}
				// Destination field
				$disabled = "";
				if($type != "device" && !$item->editlock)
					$disabled = " Disabled ";
				?><td class="bottomBorder"><?
				if($type == "email"){
						if($FORMDISABLE){
							echo $item->$type . "&nbsp;";
						} else {
							NewFormItem($f, $s, $type . $item->sequence, "text", 30, 100, "id='" . $type . $item->sequence . "'". $disabled);
						}
				} else if ($type == "device") {
						echo $item->name . "&nbsp;";
				} else {
						if($FORMDISABLE){
							echo Phone::format($item->$type) . "&nbsp;";
						} else {
							NewFormItem($f, $s, $type . $item->sequence, "text", 14, null, "id='" . $type . $item->sequence . "'". $disabled);
						}
				}
				?></td><?
				// for each jobtype checkbox
				foreach($jobtypes as $jobtype){
?>
					<td align="center"  class="bottomBorder">
<?
						if(($type == "sms" || ($type == "device"))
							 && $jobtype->issurvey){
							echo "&nbsp;";
						} else {
							echo NewFormItem($f, $s, $type . $item->sequence . "jobtype" . $jobtype->id, "checkbox", 0, 1, $FORMDISABLE);
						}
?>
					</td>
<?
				}
?>
			</tr>
<?
		}
	}
?>
			</table>
		<td>
	</tr>
<?
	if (getSystemSetting('_hasenrollment', false) && !$isGuardian) {
?>
	<tr>
		<th align="right" class="windowRowHeader bottomBorder">Enrollment Data:</th>
		<td class="bottomBorder">
		<table cellpadding="3" cellspacing="1" class="list sortable" id="enrollmenttable">
<?
		// find all sections associated with this person
		$sections = QuickQueryMultiRow("
			select section.id, skey, c01, c02, c03, c04, c05, c06, c07, c08, c09, c10, oz.orgkey
			from section
				inner join personassociation pa
					on (section.id = pa.sectionid)
				join organization oz
					on (section.organizationid = oz.id)
			where personid=$personid",
			true
		);
		// sort sections by c01 (period) strip non-numerics, second sort by id
		$assocdata = array();
		foreach ($sections as $row) {
			$index = preg_replace('/[^0-9]+/','',$row['c01']) . $row['id'];
			$assocdata[$index] = $row;
		}
		ksort($assocdata);
//var_dump($assocdata);
		
		// find display fields for this user
		$fieldmaps = FieldMap::getAuthorizedFieldMapsLike('c');
?>
		<tr class="listHeader">
			<th align="left"><?=_L("Section")?></th>

<?
		foreach ($fieldmaps as $map) {
			$header = $map->name;
?>
			<th align="left"><?=escapehtml($header)?></th>
<?
		}
?>
			<th align="left"><?=escapehtml(getSystemSetting("organizationfieldname","Organization"))?></th>
		</tr>
<?
		$alt = 0;
		foreach ($assocdata as $row) {
			echo ++$alt % 2 ? '<tr>' : '<tr class="listAlt">';
?>
				<td><?=escapehtml($row['skey'])?></td>
<?
			foreach ($fieldmaps as $map) {
?>
				<td><?=escapehtml($row[$map->fieldnum])?></td>
<?
			}
?>
				<td><?=escapehtml($row['orgkey'])?></td>
			</tr>
<?
		}
?>
		</table>
		</td>
	</tr>
<?
	} // end _hasenrollment
?>
	
<?
	if (getSystemSetting("maxguardians", 0) && $person && count($person->guardians) > 0) {
?>
		<tr>
		<th align="right" class="windowRowHeader bottomBorder">Guardians:</th>
		<td class="bottomBorder">
			<table width="100%" cellpadding="3" cellspacing="1" class="list">
				<?
				$associations = associationData($person->guardians);
				$titles = array("firstname" => "First Name", "lastname" => 'Last Name', "category" => 'Category', "actions" => 'Actions');
				showTable($associations, $titles, array("actions" => "fmt_person_view_actions"));
				?>
			</table>
		</td>
	</tr>
<?
	} // end guardians


	if ($person && count($person->dependents) > 0) {
	?>
	<tr>
			<th align="right" class="windowRowHeader bottomBorder">Dependents:</th>
			<td class="bottomBorder">
				<table width="100%" cellpadding="3" cellspacing="1" class="list">
					<?
					$associations = associationData($person->dependents);
					$titles = array("firstname" => "First Name", "lastname" => 'Last Name', "category" => 'Category', "actions" => 'Actions');
					showTable($associations, $titles, array("actions" => "fmt_person_view_actions"));
					?>
				</table>
			</td>
		</tr>
	<?
} // end dependents

/**
 *
 * @param array $associations list of associations
 * @return array rows of associations
 */
function associationData($associations) {
	$rows = array();
	foreach ($associations as $association) {
		$p = $association->person;
		$row = array(
			"personid" => $p->id,
			"firstname" => $p->firstName,
			"lastname" => $p->lastName,
			"category" => $association->guardianCategory,
			"canview" => $association->canView
		);
		$rows[] = $row;
	}
	return $rows;
}

//***************************************************************************************************************************
	// Associated Accounts settings
	//***************************************************************************************************************************

if (getSystemSetting("_hasinfocenter", false) && $USER->authorize("portalaccess")) {
	$linkedAccountManager = new LinkedAccountManager();
	$guardianAssociations = $linkedAccountManager->getAssociatedAccounts($personid);
	?>
		<tr class="listheader"><th align="left" colspan="2"><b style="font-size: 16px"><?= _L("Associated Accounts") ?></b></th></tr>
			<tr>
			<th align="right" class="windowRowHeader bottomBorder"></th>
			<td class="bottomBorder">
				<table  cellpadding="2" cellspacing="1" >
					<tr class="listheader">
						<th align="left"><b>User Name<b></th>
						<th align="left"><b>Last Login<b></th>
						<th align="left"><b>Actions<b></th>
					</tr>
	<?
	foreach ($guardianAssociations as $portaluserid => $associate) {
		if ($associate['portaluser.lastlogin']) {
			$lastlogin = date("M j, Y g:i a", $associate['portaluser.lastlogin'] / 1000);
		} else {
			$lastlogin = "Never";
		}
	?>
							<tr>
								<td style="padding-right:5px;"><?= escapehtml($associate['portaluser.username']) ?></td>
								<td style="padding-right:5px;"><?= escapehtml($lastlogin) ?></td>
								<td><a href="#" onclick="if(confirmDisassociate()) window.location='?disassociate=<?= $portaluserid . $iFrameAppend ?> '" />Disassociate</a></td>
							</tr>
		<?
	}
		?>
				</table>
			</td>
		</tr>
	<?
}

	//***************************************************************************************************************************
	// Contact Manager settings
	//***************************************************************************************************************************

	if(getSystemSetting("_hasportal", false) && $USER->authorize("portalaccess")){
		?>
		<tr class="listheader"><th align="left" colspan="2"><b style="font-size: 16px"><?=_L("Contact Manager")?></b></th></tr>
		<tr>
			<th align="right" class="windowRowHeader bottomBorder"><?=_L("Limit Access:")?></th>
			<td class="bottomBorder">
				<?echo NewFormItem($f, $s, "block_data_access", "checkbox", 0, 1, $FORMDISABLE);?>
				<?=_L("Disallow associations from viewing/modifying above contact detail data.");?>
			</td>
		</tr>
<?
		if($associates && !$isGuardian){
?>
	<tr>
		<th align="right" class="windowRowHeader bottomBorder">Associations:</th>
		<td class="bottomBorder">
			<table  cellpadding="2" cellspacing="1" >
				<tr class="listheader">
					<th align="left"><b>First Name<b></th>
					<th align="left"><b>Last Name<b></th>
					<th align="left"><b>User Name<b></th>
					<th align="left"><b>Last Login<b></th>
					<th align="left"><b>Actions<b></th>
				</tr>
<?
			foreach($associates as $portaluserid => $associate){
				if($associate['portaluser.lastlogin']){
					$lastlogin = date("M j, Y g:i a", $associate['portaluser.lastlogin'] / 1000);
				} else {
					$lastlogin = "Never";
				}
?>
				<tr>
					<td style="padding-right:5px;"><?=escapehtml($associate['portaluser.firstname'])?></td>
					<td style="padding-right:5px;"><?=escapehtml($associate['portaluser.lastname'])?></td>
					<td style="padding-right:5px;"><?=escapehtml($associate['portaluser.username'])?></td>
					<td style="padding-right:5px;"><?=escapehtml($lastlogin)?></td>
					<td><a href="#" onclick="if(confirmDisassociate()) window.location='?disassociate=<?=$portaluserid . $iFrameAppend ?> '" />Disassociate</a></td>
				</tr>
<?
				}
?>
			</table>
		</td>
	</tr>
<?
		}
		if(!$isGuardian){
?>
	<tr>
		<th align="right" class="windowRowHeader">Activation Code Information:</th>
		<td>
			<table>
<?
			if (getSystemSetting("portalphoneactivation", false)) {
?>
				<tr>
					<td><? $disabled = "";
							if ($FORMDISABLE)
								$disabled = "disabled";
							NewFormItem($f, $s, "allowphoneactivation", "checkbox", 40, "nooption", $disabled); ?>
						Allow activation via phone <?=help("Person_PortalPhoneActivation", NULL, "small")?></td>
				</tr>
<?			} ?>

				<tr>
					<td>Activation Code:</td>
					<td id="activation_code"><?=$tokendata['token'] ?></td>
				</tr>
				<tr>
					<td>Expiration Date:</td>
					<td><?=$tokendata['expirationdate'] ?></td>
				</tr>
				<tr>
					<td>Creation User:</td>
					<td><?=$tokendata['creationusername'] ?></td>
				</tr>
				<tr>
					<td>
<?
			if(!isset($_GET['ajax']) && $tokendata['token'] && strtotime($tokendata['expirationdate']) > strtotime("now"))
				echo button("Generate Activation Code", "if(confirmGenerateActive()) window.location='?create=1{$iFrameAppend}'");
			else if(!isset($_GET['ajax']))
				echo button("Generate Activation Code", "if(confirmGenerate()) window.location='?create=1{$iFrameAppend}'");
?>
					</td>
<?
			if($tokendata['token']){
?>
						<td>
<?
				if (!isset($_GET['ajax']))
					echo button("Revoke Activation Code", "if(confirmRevoke()) window.location='?revoke={$personid}{$iFrameAppend}'");
?>
						</td>
<?
			}
?>
				</tr>
			</table>
		</td>
	</tr>
<?
		}
	}
?>
</table>
<script type="text/javascript">
	function confirmDisassociate(){
		return confirm('Are you sure you want to disassociate this Associated Account?');
	}
	function confirmRevoke(){
		return confirm('Are you sure you want to revoke this contact\'s activation code?');
	}
	function confirmGenerate(){
		return confirm('Are you sure you want to generate a new activation code for this person?');
	}
	function confirmGenerateActive(){
		return confirm('Are you sure you want to overwrite the current activation code?');
	}
<?
	if($method == "edit"){
		//update disabled flags for error case
		foreach($contacttypes as $type){
			if(!isset($types[$type])) continue;
			foreach($types[$type] as $item){
?>
				var contactdetail<?=$type?><?=$item->sequence?> = new getObj("<?=$type?><?=$item->sequence?>").obj;
				var contactcheckbox<?=$type?><?=$item->sequence?> = new getObj("editlock_<?=$type?><?=$item->sequence?>").obj;
				if(contactcheckbox<?=$type?><?=$item->sequence?>.checked){
					contactdetail<?=$type?><?=$item->sequence?>.disabled = false;
				} else {
					contactdetail<?=$type?><?=$item->sequence?>.disabled = true;
				}
<?
			}
		}
	}
?>
</script>
<?


if (!isset($_GET['ajax'])) {
	buttons();
	endWindow();
	EndForm();

	include_once("navbottom.inc.php");
} else {
        echo "</div>";
}

?>
