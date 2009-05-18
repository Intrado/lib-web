<?
require_once("common.inc.php");
require_once("../inc/html.inc.php");
require_once("../inc/table.inc.php");
require_once("../obj/Phone.obj.php");
require_once("../obj/Email.obj.php");
require_once("../obj/Sms.obj.php");


class Destination {
	var $id;
	var $name;
	var $nodelete;
}

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

$pid = $_SESSION['personid'];

if (isset($_GET['delete'])) {
	$id = $_GET['delete'];
	$sequence = substr($id, strlen($id)-1);
	$type = substr($id, 0, strlen($id)-1);
	 
	QuickUpdate("update ".$type." set ".$type."='' where personid=? and sequence=?", false, array($pid, $sequence));
	redirect();
}


$phoneList = DBFindMany("Phone", "from phone where personid=?", false, array($pid));
$emailList = DBFindMany("Email", "from email where personid=?", false, array($pid));
$smsList = DBFindMany("Sms", "from sms where personid=?", false, array($pid));

$destinations = array();

foreach ($phoneList as $phone) {
	if ($phone->phone == '') continue;
	$dest = new Destination();
	$dest->id = 'phone'.$phone->sequence;
	$dest->name = Phone::format($phone->phone);
	$dest->nodelete = false;
	$destinations[] = $dest;
}
foreach ($emailList as $email) {
	if ($email->email == '') continue;
	$dest = new Destination();
	$dest->id = 'email'.$email->sequence;
	$dest->name = $email->email;
	if ($_SESSION['subscriber.username'] == $email->email)
		$dest->nodelete = true;
	else
		$dest->nodelete = false;
	$destinations[] = $dest;
}

$titles = array(
			"name" => "Destination",
			"action" => "Actions"
			);


function fmt_actions ($obj, $name) {
	if ($obj->nodelete)
		return '';
	return '<a href="?delete=' . $obj->id . '" onclick="return confirmDelete();">Delete</a>';
}


////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
$PAGE = "contacts:notificationdests";
$TITLE = _L("Notification Destinations");

require_once("nav.inc.php");

echo "<font color=\"red\">TODO DO NOT TEST YET, will have wizard to add and confirm phone, etc</font><BR><BR>";

startWindow(_L('Active Destinations'));
echo "<table>";
showObjects($destinations, $titles, array("action" => "fmt_actions"));
echo "</table>";
endWindow();
buttons(icon_button("Add", "tick", null, "destinationwizard.php"));
require_once("navbottom.inc.php");
?>