<?
$FORMDISABLE = " DISABLED ";
if(isset($method)){
	if($method == "edit"){
		$FORMDISABLE = "";
	}
} else {
	exit();
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
if(getSystemSetting("_hasportal", false) && $USER->authorize("portalaccess")){
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
	$maxphones = 3;

if (!$maxemails = getSystemSetting("maxemails"))
	$maxemails = 2;

if (!$maxsms = getSystemSetting("maxsms"))
	$maxsms = 2;

if (isset($personid)) {
	// editing existing person
	$data = DBFind("Person", "from person where id = " . $personid);
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
	$contacttypes = array("phone", "email");
	if(getSystemSetting("_hassms", false))
		$contacttypes[] = "sms";
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
			}
		}


		if( CheckFormSection($f, $s) ) {
			error('There was a problem trying to save your changes', 'Please verify that all required field information has been entered properly');
		} else {
			//submit changes
			foreach($contacttypes as $type){
				if(!isset($types[$type])) continue;
				foreach($types[$type] as $item){
					if(GetFormData($f, $s, "editlock_" . $type . $item->sequence) || $item->editlock != GetFormData($f, $s, "editlock_" . $type . $item->sequence)){
						$item->editlock = GetFormData($f, $s, "editlock_" . $type . $item->sequence);
						if($item->editlock){
							if($type == "email")
								$item->$type = GetFormData($f, $s, $type . $item->sequence);
							else
								$item->$type = Phone::parse(GetFormData($f,$s, $type . $item->sequence));
						}
						$item->update();
					}
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
												where personid = '" . $personid . "' and jobtypeid = '" . $jobtype->id . "' and sequence = '" . $item->sequence . "'");
						}
					}
				}
			}
			redirect($_SESSION['contact_referer']);
		}
	}
} else {
	$reloadform = 1;
}

if( $reloadform )
{
	ClearFormData($f);
	foreach($contacttypes as $type){
		if(!isset($types[$type])) continue;
		foreach($types[$type] as $item){
			if($type == "email")
				PutFormData($f, $s, $type . $item->sequence, $item->$type, "email");
			else
				PutFormData($f, $s, $type . $item->sequence, Phone::format($item->$type), "phone");
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
if (strpos($_SESSION['contact_referer'],"activationcodemanager.php") !== false) $PAGE = "admin:settings";

$contactFullName = "";
$firstnamefield = FieldMap::getFirstNameField();
$contactFullName .= $data->$firstnamefield;
$lastnamefield = FieldMap::getLastNameField();
$contactFullName .= " ".$data->$lastnamefield;

$TITLE = "View Contact Information: " . $contactFullName;

include_once("nav.inc.php");
NewForm($f);
if($method == "edit"){
	buttons(submit($f, $s, "Done"));
} else {
	buttons(button("Done", null, $_SESSION['contact_referer']),

		$USER->authorize('managecontactdetailsettings') ? button("Edit", "if(confirm('You are about to edit contact data that may impact other people\'s lists.  Are you sure you want to continue?')) window.location='editcontact.php'") : "");
}

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
			<th>Override</th>
			<th align="left">Destination</th>
<?
			foreach($jobtypes as $jobtype){
				?><th><?=jobtype_info($jobtype)?></th><?
			}
?>
		</tr>
<?
		foreach($types[$type] as $item){
			$header = destination_label($type, $item->sequence);
?>
			<tr>
				<td class="bottomBorder"><?= $header ?></td>
				<td align="center"  class="bottomBorder"><? NewFormItem($f, $s, "editlock_" . $type . $item->sequence, "checkbox", 0, 1, $FORMDISABLE . 'id="editlock_' . $type . $item->sequence . '" onclick="new getObj(\'' . $type . $item->sequence . '\').obj.disabled = !this.checked"'); ?></td>
<?
				$disabled = "";
				if(!$item->editlock)
					$disabled = " Disabled ";
				?><td class="bottomBorder"><?
				if($type == "email"){
						if($FORMDISABLE){
							echo $item->$type . "&nbsp;";
						} else {
							NewFormItem($f, $s, $type . $item->sequence, "text", 30, 100, "id='" . $type . $item->sequence . "'". $disabled);
						}
				} else {
						if($FORMDISABLE){
							echo Phone::format($item->$type) . "&nbsp;";
						} else {
							NewFormItem($f, $s, $type . $item->sequence, "text", 14, null, "id='" . $type . $item->sequence . "'". $disabled);
						}

				}
				?></td><?
				foreach($jobtypes as $jobtype){
?>
					<td align="center"  class="bottomBorder">
<?
						if($type != "sms" || ($type == "sms" && !$jobtype->issurvey)){
							echo NewFormItem($f, $s, $type . $item->sequence . "jobtype" . $jobtype->id, "checkbox", 0, 1, $FORMDISABLE);
						} else {
							echo "&nbsp;";
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
	if(getSystemSetting("_hasportal", false) && $USER->authorize("portalaccess") && $associates){
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
						$lastlogin = date("M d, Y h:i a", $associate['portaluser.lastlogin'] / 1000);
					} else {
						$lastlogin = "Never";
					}
?>
					<tr>
						<td class="bottomBorder"><?=$associate['portaluser.firstname']?></td>
						<td class="bottomBorder"><?=$associate['portaluser.lastname']?></td>
						<td class="bottomBorder"><?=$associate['portaluser.username']?></td>
						<td class="bottomBorder"><?=htmlentities($lastlogin)?></td>
						<td class="bottomBorder"><a href="#" onclick="if(confirmDisassociate()) window.location='?disassociate=<?=$portaluserid?>'" />Disassociate</a></td>
					</tr>
<?
				}
?>
			</table>
		</td>
	</tr>
<?
	}
	if(getSystemSetting("_hasportal", false) && $USER->authorize("portalaccess")){
?>
	<tr>
		<th align="right" class="windowRowHeader">Activation Code Information:</th>
		<td>
			<table>
				<tr>
					<td>Activation Code:</td>
					<td><?=$tokendata['token'] ?></td>
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
					if($tokendata['token'] && strtotime($tokendata['expirationdate']) > strtotime("now"))
						echo button("Generate Activation Code", "if(confirmGenerateActive()) window.location='?create=1'");
					else
						echo button("Generate Activation Code", "if(confirmGenerate()) window.location='?create=1'");
?>
					</td>
<?
					if($tokendata['token']){
?>
						<td>
<?
							echo button("Revoke Activation Code", "if(confirmRevoke()) window.location='?revoke=" . $personid . "'");
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
?>
</table>
<script>
	function confirmDisassociate(){
		return confirm('Are you sure you want to disassociate this Contact Manager User?');
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


endWindow();
buttons();
EndForm();
include_once("navbottom.inc.php");
?>