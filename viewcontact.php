<?
// read-only view of an imported contact with all their metadata

////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
include_once("inc/common.inc.php");
include_once("inc/utils.inc.php");
include_once("inc/securityhelper.inc.php");
include_once("inc/form.inc.php");
include_once("inc/html.inc.php");
include_once("inc/table.inc.php");
include_once("obj/FieldMap.obj.php");
include_once("obj/Person.obj.php");
include_once("obj/Address.obj.php");
include_once("obj/Phone.obj.php");
include_once("obj/Email.obj.php");
include_once("obj/Language.obj.php");
include_once("obj/JobType.obj.php");
include_once("obj/Sms.obj.php");

////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////

if(isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'],"viewcontact.php") === false){
	$_SESSION['viewcontact_referer'] = $_SERVER['HTTP_REFERER'];
}

if (isset($_GET['id'])) {
	$personid = DBSafe($_GET['id']);
	if ($personid == "") {
		// bad
		redirect('unauthorized.php');
	}

	// validate user has rights to view this contact
	$usersql = $USER->userSQL("p");
	$query = "
		select p.id
		from 		person p
		where p.id='$personid' $usersql
	";

	if (!($personid = QuickQuery($query))) {
		// bad
		redirect('unauthorized.php');
	}
	$_SESSION['currentpid'] = $personid;
	redirect();
} else if(isset($_SESSION['currentpid'])){
	$personid = $_SESSION['currentpid'];
} else {
	redirect('unauthorized.php');
}
if(getSystemSetting("_hasportal") && $USER->authorize("portalaccess")){
	if(isset($_GET['create']) && $_GET['create']){
		if(generatePersonTokens(array($personid))){
			redirect();
		} else {
			error("There was an error generating a new token");
		}
	}
	
	if(isset($_GET['revoke'])){
		$revokeid = $_GET['revoke'] + 0;
		$count = revokePersonTokens(array($revokeid));
		if($count){
			redirect();
		} else {
			error("There was an error revoking this person's token");
		}
	}
	
	if(isset($_GET['disassociate'])){
		$portaluserid = $_GET['disassociate'] + 0;
		$count = QuickUpdate("delete from portalperson where personid = '" . $personid . "' and portaluserid = '" . $portaluserid . "'");
		if($count){
			redirect();
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
	$maxphones = 4;

if (!$maxemails = getSystemSetting("maxemails"))
	$maxemails = 2;
	
if (!$maxsms = getSystemSetting("maxsms"))
	$maxsms = 2;

if (isset($personid)) {
	// editing existing person
	$data = DBFind("Person", "from person where id = " . $personid);
	$address = DBFind("Address", "from address where personid = " . $personid);
	if ($address === false) $address = new Address(); // contact was imported/uploaded without any address data, create one now

	// get existing phones from db, then create any additional based on the max allowed
	// what if the max is less than the number they already have? the GUI does not allow to decrease this value, so NO WORRIES :)
	// use array_values to reset starting index to 0
	$phones = array_values(DBFindMany("Phone", "from phone where personid=" . $personid . " order by sequence"));
	for ($i=count($phones); $i<$maxphones; $i++) {
		$phones[$i] = new Phone();
		$phones[$i]->sequence = $i;
		$phones[$i]->personid = $personid;
	}
	$emails = array_values(DBFindMany("Email", "from email where personid=" . $personid . " order by sequence"));
	for ($i=count($emails); $i<$maxemails; $i++) {
		$emails[$i] = new Email();
		$emails[$i]->sequence = $i;
		$emails[$i]->personid = $personid;
	}
	$smses = array_values(DBFindMany("Sms", "from sms where personid=" . $personid . " order by sequence"));
	for ($i=count($smses); $i<$maxsms; $i++) {
		$smses[$i] = new Sms();
		$smses[$i]->sequence = $i;
		$smses[$i]->personid = $personid;
	}
	$associateids = QuickQueryList("select portaluserid from portalperson where personid = '" . $personid . "' order by portaluserid");
	$associates = getPortalUsers($associateids);
	$tokendata = QuickQueryRow("select token, expirationdate, creationuserid from portalpersontoken where personid = '" . $personid . "'", true);
	$creationusername = "";
	if($tokendata){
		$creationuser = new User($tokendata['creationuserid']);
		$tokendata['creationusername'] = $creationuser->firstname . " " . $creationuser->lastname;
		$tokendata['expirationdate'] = ($tokendata['expirationdate'] != "") ? date("M d, Y", strtotime($tokendata['expirationdate'])) : "";
		if(strtotime($tokendata['expirationdate']) < strtotime("now")){
			$tokendata['token'] = "Expired";
		}
	} else {
		$tokendata = array("token" => "",
							"creationusername" => "",
							"expirationdate" => "");
	}
	$jobtypes = DBFindMany("JobType", "from jobtype where not deleted order by systempriority, issurvey, name");
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

		//do check

		if( CheckFormSection($f, $s) ) {
			error('There was a problem trying to save your changes', 'Please verify that all required field information has been entered properly');
		} else {
			//submit changes
			foreach($phones as $phone){
				$phone->phone = Phone::parse(GetFormData($f,$s, "phone" . $phone->sequence));
				$phone->editlock = GetFormData($f, $s, "editlock_phone" . $phone->sequence);
				$phone->update();
				foreach($jobtypes as $jobtype){
					QuickUpdate("insert into contactpref (personid, jobtypeid, type, sequence, enabled)
								values ('" . $personid . "','" . $jobtype->id . "','phone','" . $phone->sequence . "','" 
								. DBSafe(GetFormData($f, $s, "phone" . $phone->sequence . "jobtype" . $jobtype->id)) . "') 
								on duplicate key update
								personid = '" . $personid . "',
								jobtypeid = '" . $jobtype->id . "',
								type = 'phone',
								sequence = '" . $phone->sequence . "',
								enabled = '" . DBSafe(GetFormData($f, $s, "phone" . $phone->sequence . "jobtype" . $jobtype->id)) . "'");
				}
			}
			foreach($emails as $email){
				$email->email = GetFormData($f,$s, "email" . $email->sequence);
				$email->editlock = GetFormData($f, $s, "editlock_email" . $email->sequence);
				$email->update();
				foreach($jobtypes as $jobtype){
					QuickUpdate("insert into contactpref (personid, jobtypeid, type, sequence, enabled)
								values ('" . $personid . "','" . $jobtype->id . "','email','" . $email->sequence . "','" 
								. DBSafe(GetFormData($f, $s, "email" . $email->sequence . "jobtype" . $jobtype->id)) . "') 
								on duplicate key update
								personid = '" . $personid . "',
								jobtypeid = '" . $jobtype->id . "',
								type = 'email',
								sequence = '" . $email->sequence . "',
								enabled = '" . DBSafe(GetFormData($f, $s, "email" . $email->sequence . "jobtype" . $jobtype->id)) . "'");
				}
			}
			if(getSystemSetting("_hassms")){
				foreach($smses as $sms){
					$sms->sms = Phone::parse(GetFormData($f,$s, "sms" . $sms->sequence));
					$sms->editlock = GetFormData($f, $s, "editlock_sms" . $sms->sequence);
					$sms->update();
					foreach($jobtypes as $jobtype){
						QuickUpdate("insert into contactpref (personid, jobtypeid, type, sequence, enabled)
									values ('" . $personid . "','" . $jobtype->id . "','sms','" . $sms->sequence . "','" 
									. DBSafe(GetFormData($f, $s, "sms" . $sms->sequence . "jobtype" . $jobtype->id)) . "') 
									on duplicate key update
									personid = '" . $personid . "',
									jobtypeid = '" . $jobtype->id . "',
									type = 'sms',
									sequence = '" . $sms->sequence . "',
									enabled = '" . DBSafe(GetFormData($f, $s, "sms" . $sms->sequence . "jobtype" . $jobtype->id)) . "'");
					}
				}
			}
			

			redirect();
		}
	}
} else {
	$reloadform = 1;
}

if( $reloadform )
{
	ClearFormData($f);
	foreach($phones as $phone){
		PutFormData($f, $s, "phone" . $phone->sequence, Phone::format($phone->phone), "phone", 10);
		PutFormData($f, $s, "editlock_phone" . $phone->sequence, $phone->editlock, "bool", 0, 1);
		foreach($jobtypes as $jobtype){
			$contactpref = 0;
			if(isset($contactprefs["phone"][$phone->sequence][$jobtype->id]))
				$contactpref = $contactprefs["phone"][$phone->sequence][$jobtype->id];
			else if(isset($defaultcontactprefs["phone"][$phone->sequence][$jobtype->id]))
				$contactpref = $defaultcontactprefs["phone"][$phone->sequence][$jobtype->id];
			PutFormData($f, $s, "phone" . $phone->sequence . "jobtype" . $jobtype->id, $contactpref, "bool", 0, 1);
		}
	}
	foreach($emails as $email){
		PutFormData($f, $s, "email" . $email->sequence, $email->email, "email", 0, 100);
		PutFormData($f, $s, "editlock_email" . $email->sequence, $email->editlock, "bool", 0, 1);
		foreach($jobtypes as $jobtype){
			$contactpref = 0;
			if(isset($contactprefs["email"][$email->sequence][$jobtype->id]))
				$contactpref = $contactprefs["email"][$email->sequence][$jobtype->id];
			else if(isset($defaultcontactprefs["email"][$email->sequence][$jobtype->id]))
				$contactpref = $defaultcontactprefs["email"][$email->sequence][$jobtype->id];
			PutFormData($f, $s, "email" . $email->sequence . "jobtype" . $jobtype->id, $contactpref, "bool", 0, 1);
		}
	}
	if(getSystemSetting("_hassms")){
		foreach($smses as $sms){
			PutFormData($f, $s, "sms" . $sms->sequence, Phone::format($sms->sms), "phone", 10);
			PutFormData($f, $s, "editlock_sms" . $sms->sequence, $sms->editlock, "bool", 0, 1);
			foreach($jobtypes as $jobtype){
				$contactpref = 0;
				if(isset($contactprefs["sms"][$sms->sequence][$jobtype->id]))
					$contactpref = $contactprefs["sms"][$sms->sequence][$jobtype->id];
				else if(isset($defaultcontactprefs["sms"][$sms->sequence][$jobtype->id]))
					$contactpref = $defaultcontactprefs["sms"][$sms->sequence][$jobtype->id];
				PutFormData($f, $s, "sms" . $sms->sequence . "jobtype" . $jobtype->id, $contactpref, "bool", 0, 1);
			}
		}
	}
	
}


function displayValue($s) {
	echo($s."&nbsp;");
}

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

// where did we come from, list preview or contact tab
$PAGE = "notifications:lists";
if (strpos($_SESSION['viewcontact_referer'],"contacts.php") !== false) $PAGE = "system:contacts";
if (strpos($_SESSION['viewcontact_referer'],"portalmanagement.php") !== false) $PAGE = "admin:portal";

$contactFullName = "";
$firstnamefield = FieldMap::getFirstNameField();
$contactFullName .= $data->$firstnamefield;
$lastnamefield = FieldMap::getLastNameField();
$contactFullName .= " ".$data->$lastnamefield;

$TITLE = "View Contact Information: " . $contactFullName;

include_once("nav.inc.php");
NewForm($f);
buttons(button('Done', NULL,$_SESSION['viewcontact_referer']), submit($f, $s, "Save"));

startWindow('Contact');

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
			<? $languagefield=FieldMap::getLanguageField(); displayValue($data->$languagefield); ?>
		</td>
	</tr>
<?


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
		<th align="right" class="windowRowHeader bottomBorder"><?= $header ?></th>
		<td class="bottomBorder"><? displayValue($fval); ?></td>
	</tr>
<?
}
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
			<table padding="3px">
				<tr>
					<th>Contact Type</th>
					<th>Destination</th>
					<th>Edit Lock</th>
<?
					foreach($jobtypes as $jobtype){
						?><th><?=jobtype_info($jobtype)?></th><?
					}
?>
				</tr>
<?

	$x = 0;
	foreach ($phones as $phone) {
		$header = "Phone " . ($x+1) . ":";
		$itemname = "phone".($x+1);
?>
	<tr>
		<td><?= $header ?></td>
		<td><? NewFormItem($f, $s, "phone" . $phone->sequence, "text", 15); ?></td>
		<td align="middle"><? NewFormItem($f, $s, "editlock_phone" . $phone->sequence, "checkbox", 0, 1); ?></td>
<?
		foreach($jobtypes as $jobtype){
			if(!$jobtype->issurvey){
				?><td align="middle"><? NewFormItem($f, $s, "phone" . $phone->sequence . "jobtype" . $jobtype->id, "checkbox", 0, 1) ?></td><?
			}
		}
		foreach($jobtypes as $jobtype){
			if($jobtype->issurvey){
				?><td align="middle"><? NewFormItem($f, $s, "phone" . $phone->sequence . "jobtype" . $jobtype->id, "checkbox", 0, 1) ?></td><?
			}
		}
?>
	</tr>
<?
		$x++;
	}

	$x = 0;
	foreach ($emails as $email) {
		$header = "Email " . ($x+1) . ":";
		$itemname = "email".($x+1);
?>
	<tr>
		<td><?= $header ?></td>
		<td><? NewFormItem($f, $s, "email" . $email->sequence, "text", 50, 100) ?></td>
		<td align="middle"><? NewFormItem($f, $s, "editlock_email" . $email->sequence, "checkbox", 0,1);?></td>
<?
		foreach($jobtypes as $jobtype){
			if(!$jobtype->issurvey){
				?><td align="middle"><? NewFormItem($f, $s, "email" . $email->sequence . "jobtype" . $jobtype->id, "checkbox", 0, 1) ?></td><?
			}
		}
		foreach($jobtypes as $jobtype){
			if($jobtype->issurvey){
				?><td align="middle"><? NewFormItem($f, $s, "email" . $email->sequence . "jobtype" . $jobtype->id, "checkbox", 0, 1) ?></td><?
			}
		}
?>
	</tr>
<?
		$x++;
	}
	if(getSystemSetting("_hassms")){
		$x = 0;
		foreach ($smses as $sms) {
			$header = "Sms " . ($x+1) . ":";
			$itemname = "sms".($x+1);
?>
		<tr>
			<td><?= $header ?></td>
			<td><? NewFormItem($f, $s, "sms" . $sms->sequence, "text", 50, 100) ?></td>
			<td align="middle"><? NewFormItem($f, $s, "editlock_sms" . $sms->sequence, "checkbox", 0,1);?></td>
<?
			foreach($jobtypes as $jobtype){
				if(!$jobtype->issurvey){
					?><td align="middle"><? NewFormItem($f, $s, "sms" . $sms->sequence . "jobtype" . $jobtype->id, "checkbox", 0, 1) ?></td><?
				}
			}
			foreach($jobtypes as $jobtype){
				if($jobtype->issurvey){
					?><td align="middle"><? NewFormItem($f, $s, "sms" . $sms->sequence . "jobtype" . $jobtype->id, "checkbox", 0, 1) ?></td><?
				}
			}
?>
		</tr>
<?
			$x++;
		}
	}
?>
			</table>
		<td>
	</tr>
<?
	if(getSystemSetting("_hasportal") && $USER->authorize("portalaccess")){
?>
	<tr>
		<th align="right" class="windowRowHeader bottomBorder">Associations</th>
		<td class="bottomBorder">
			<table>
<?
			if($associates){
				foreach($associates as $portaluserid => $associate){
					$name = $associate['portaluser.firstname'] . " " . $associate['portaluser.lastname'] . " (" . $associate['portaluser.username'] . ")";
?>
					<tr>
						<td><?=$name?></td>
						<td><?=button("Disassociate", "if(confirmDisassociate()) window.location='?disassociate=" . $portaluserid . "'")?></td>
					</tr>
<?
				}
			} else {
				?><tr><td>&nbsp</td></tr><?
			}
?>
			</table>
		</td>
	</tr>
	<tr>
		<th align="right" class="windowRowHeader">Token Information</th>
		<td>
			<table>
				<tr><td>Activation Code: <?=$tokendata['token'] ?></td></tr>
				<tr><td>Expiration Date: <?=$tokendata['expirationdate'] ?></td></tr>
				<tr><td>Creation User: <?=$tokendata['creationusername'] ?></td></tr>
				<tr>
					<td>
<?
					echo button("Generate Activation Code", "window.location='?create=1'");
					if($tokendata['token']){
						echo button("Revoke ACtivation Code", "if(confirmRevoke()) window.location='?revoke=" . $personid . "'");
					}
?>
					</td>
				</tr>
			</table>
		</td>
	</tr>
<?
	}
?>
</table>
<script>
	function confirmDisassociate(){
		return confirm('Are you sure you want to disassociate this Portal User?');
	}
	function confirmRevoke(){
		return confirm('Are you sure you want to revoke this contact\'s token?');
	}
</script>
<?


endWindow();
buttons();
EndForm();
include_once("navbottom.inc.php");

