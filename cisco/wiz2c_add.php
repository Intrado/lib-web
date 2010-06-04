<?
require_once("common.inc.php");
require_once("../obj/Person.obj.php");
require_once("../obj/Phone.obj.php");
require_once("../obj/Person.obj.php");
require_once("../obj/PeopleList.obj.php");
include_once("../obj/Rule.obj.php");
include_once("../obj/ListEntry.obj.php");
include_once("../obj/RenderedList.obj.php");
include_once("../obj/FieldMap.obj.php");

$list = new PeopleList($_SESSION['newjob']['list']);

if (!$USER->authorize('createlist')) {
	header("Location: $URL/index.php");
	exit();
}

$flagfname = false;
$flaglname = false;
$flagphone = false;

if (isset($_GET['firstname']) || isset($_GET['lasttname']) || isset($_GET['phone'])) {

	$fname='';
	$lname='';
	$phone='';

	if (isset($_GET['firstname']) && $_GET['firstname'])
		$fname = $_SESSION['manualadd']['firstname'] = $_GET['firstname'];
		
	if (isset($_GET['lasttname']) && $_GET['lasttname'])
			$lname = $_SESSION['manualadd']['lasttname'] = $_GET['lasttname'];
			
	if (isset($_GET['phone']) && $_GET['phone'])
			$phone = Phone::parse($_SESSION['manualadd']['phone'] = $_GET['phone']);

	if (strlen($fname) == 0) {
		$flagfname = true;
		$prompt = "Required";
	} else if (strlen($lname) == 0) {
		$flaglname = true;
		$prompt = "Required";
	} else if (strlen($phone) != 10) {
		$flagphone = true;
		$prompt = "Must be exactly 10 digits";
	} else {
		//make the person and go back to the list info page
		$fnf = FieldMap::getFirstNameField();
		$lnf = FieldMap::getLastNameField();
		$langf = FieldMap::getLanguageField();

		$person = new Person();
		$person->userid = $USER->id;
		$person->deleted = 0;
		$person->$fnf = $fname;
		$person->$lnf = $lname;
		$person->$langf = "en";
		$person->type = "manualadd";
		$person->create();

		$ph = new Phone();
		$ph->personid = $person->id;
		$ph->sequence = 0;
		$ph->phone = Phone::parse($phone);
		$ph->create();

		$le = new ListEntry();
		$le->listid = $list->id;
		$le->type = "add";
		$le->personid = $person->id;
		$le->create();

		$_SESSION['manualadd'] = array();

		header("Location: $URL/wiz2b_listinfo.php");
		exit();
	}

} else {
	//flag phone to prompt for area code
	$flagphone = true;
	$prompt = "Please include area code";
}





header("Content-type: text/xml");

?>
<CiscoIPPhoneInput>
<Title>List - Manual Add</Title>
<Prompt>* <?=$prompt?></Prompt>
<URL><?= htmlentities($URL . "/wiz2c_add.php") ?></URL>


<InputItem>
<DisplayName><?= $flagfname ? "* " : "" ?>First Name</DisplayName>
<QueryStringParam>firstname</QueryStringParam>
<DefaultValue><?= htmlentities(isset($_SESSION['manualadd']['firstname']) ? $_SESSION['manualadd']['firstname']:"") ?></DefaultValue>
<InputFlags>A</InputFlags>
</InputItem>

<InputItem>
<DisplayName><?= $flaglname ? "* " : "" ?>Last Name</DisplayName>
<QueryStringParam>lasttname</QueryStringParam>
<DefaultValue><?= htmlentities(isset($_SESSION['manualadd']['lasttname']) ? $_SESSION['manualadd']['lasttname']:"") ?></DefaultValue>
<InputFlags>A</InputFlags>
</InputItem>


<InputItem>
<DisplayName><?= $flagphone ? "* " : "" ?>Phone</DisplayName>
<QueryStringParam>phone</QueryStringParam>
<DefaultValue><?= htmlentities(isset($_SESSION['manualadd']['phone']) ? $_SESSION['manualadd']['phone']:"") ?></DefaultValue>
<InputFlags>N</InputFlags>
</InputItem>

<SoftKeyItem>
<Name>Submit</Name>
<URL>SoftKey:Submit</URL>
<Position>1</Position>
</SoftKeyItem>

<SoftKeyItem>
<Name>&lt;&lt;</Name>
<URL>SoftKey:&lt;&lt;</URL>
<Position>2</Position>
</SoftKeyItem>

<SoftKeyItem>
<Name>Cancel</Name>
<URL><?= htmlentities($URL . "/wiz2b_listinfo.php") ?></URL>
<Position>3</Position>
</SoftKeyItem>

</CiscoIPPhoneInput>
