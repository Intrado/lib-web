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

// API mode only!
//
if (!isset($_REQUEST["api"])) {
	exit();
}

if (isset($_GET['id']) && $_GET['id'] != '') {
	$personid = $_GET['id'] + 0;
	$pquery = "from person where id = ?";
	$bind = array($personid);
} else if (isset($_GET['pkey']) && $_GET['pkey'] != '') {
	$pkey = $_GET['pkey'];
	$pquery = "from person where pkey = ?";
	$bind = array($pkey);
} else {
	header("HTTP/1.1 404 Not Found");
	header('Content-Type: application/json');
	exit(json_encode(Array("code" => "resourceNotFound")));
}

// validate user has rights to view this contact
if (!$USER->canSeePerson($personid, $pkey)) {
	header("HTTP/1.1 404 Not Found");
	header('Content-Type: application/json');
	exit(json_encode(Array("code" => "personNotFound")));
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

$data = DBFind("Person", $pquery, false, $bind);
$personid = $data->id;

//$person = $csApi->getPerson($personid, "dependents,guardians");

//$query = "select group_concat(oz.orgkey separator ', ') from organization oz join personassociation pa on (pa.organizationid = oz.id) where personid=?";
$query = "select oz.id, oz.orgkey from organization oz join personassociation pa on (pa.organizationid = oz.id) where personid=?";

	$organizations = QuickQueryMultiRow($query, true, false, array($personid));
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
				$dto->name = $response->name;
				$dto->sequence = $d->sequence;
				$dto->personId = $d->personId;
				$dto->uuid = $d->deviceUuid;
				$devices[] = $dto;
			}
		}
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
	}

	$contactprefs = getContactPrefs($personid);
	$defaultcontactprefs = getDefaultContactPrefs();

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

$result = cleanObjects($data);
$result["id"] = $personid;

$firstnamefield = FieldMap::getFirstNameField();
$result["firstName"] = $data->$firstnamefield;

$lastnamefield = FieldMap::getLastNameField();
$result["lastName"] = $data->$lastnamefield;

$gradeField = FieldMap::getGradeField();
$result["grade"] = $data->$gradeField;

$languageField = FieldMap::getLanguageField();
$result["language"] = $data->$languageField;

$result["fields"] = cleanObjects(FieldMap::retrieveFieldMaps());
$result["organizations"] = cleanObjects($organizations);
$result["address"] = cleanObjects($address);
$result["contacts"] = cleanObjects($types);
$result["prefs"] = cleanObjects($contactprefs);
$result["defaults"] = cleanObjects($defaultcontactprefs);
$result["sections"] = cleanObjects($sections);
//$result["dependents"] = cleanObjects(associationData($person->dependents));
//$result["guardians"] = cleanObjects(associationData($person->guardians));

header('Content-Type: application/json');

exit(json_encode($result));

?>
