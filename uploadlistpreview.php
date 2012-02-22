<?
/*
 * This file handles ID based list uploads, see uploadlistmap.php for the contacts based list upload
 */
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
include_once("inc/common.inc.php");
include_once("inc/form.inc.php");
include_once("inc/html.inc.php");
require_once("inc/table.inc.php");
require_once("inc/utils.inc.php");
require_once("inc/securityhelper.inc.php");
require_once("inc/formatters.inc.php");
require_once("obj/Import.obj.php");
require_once("obj/ImportField.obj.php");
require_once("obj/Schedule.obj.php");
include_once("obj/PeopleList.obj.php");
include_once("obj/Person.obj.php");
include_once("obj/Phone.obj.php");
include_once("obj/Email.obj.php");
include_once("obj/FieldMap.obj.php");

////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$USER->authorize('createlist') || !($USER->authorize('listuploadids') || $USER->authorize('listuploadcontacts'))) {
	redirect('unauthorized.php');
}

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

$list = new PeopleList(getCurrentList());
$type = $_GET['type'] == "contacts" ? "contacts" : "ids";
$importid = QuickQuery("select id from import where listid='$list->id'");
if ($importid) {
	$import = new Import($importid);
	//we'd like to use fgetcsv without using temp files, so use a data uri instead. Uses more ram, but should be OK for list uploads
	$datauri = "data:text/plain;base64," . base64_encode($import->download());
} else {
	$import = false;
	$datauri = false;
}

$firstnameField = FieldMap::getFirstNameField();
$lastnameField = FieldMap::getLastNameField();


$f = "list";
$s = "uploadpreview";
$reloadform = 0;

//load preview data
$count = 0;
$listpreviewdata = array();
$notfound = 0;
$notfounddata = array();

if ($datauri && !CheckFormSubmit($f,'save')) {
	if ($fp = fopen($datauri, "r")) {
		$count = 5000;
		$total = 0;
		while (($row = fgetcsv($fp,4096)) !== FALSE) {
			$pkey = DBSafe(trim($row[0]));
			if ($pkey == "")
				continue;

			$total++;
			$count--;
			// only system contacts to be uploaded into a list
			$p = DBFind("Person","from person where pkey=? and type='system'", false, array($pkey));
			if ($p && $USER->canSeePerson($p->id)) {
				// preview up to limit, but continue to parse to verify all pkeys found or not
				if ($count > 0) {
					$phone = DBFind("Phone","from phone where personid=?", false, array($p->id));
					$email = DBFind("Email","from email where personid=?", false, array($p->id));
					//check if the object isnt false else display empty string
					$listpreviewdata[] = array($pkey,$p->$firstnameField,$p->$lastnameField, $phone ? Phone::format($phone->phone) : "", $email ? $email->email : "");
				}
			} else {
				$notfound++;
				$notfounddata[] = array($pkey, "### Not Found ###");
			}
		}
		fclose($fp);

		if ($notfound) {
			error("Some contacts were not found. " . ($total - $notfound) ." of $total contacts matched");
		}
	} else {
		error("Unable to open the file");
	}
}


//process the list?
if (CheckFormSubmit($f,'save') && count($GLOBALS['ERRORS']) == 0) {

	// read pkeys from file
	if ($fp = fopen($datauri, "r")) {
		$pkeys = array();
		while ($row = fgetcsv($fp,4096)) {
			$pkeys[] = DBSafe(trim($row[0]));
		}
		fclose($fp);
		// update the list of people
		$list->updateManualAddByPkeys($pkeys);
	} else {
		error("Unable to open the file");
	}

	redirect("list.php");
}


if( $reloadform )
{
	ClearFormData($f);
}



////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$PAGE = "notifications:lists";
$TITLE = "Upload List: " . escapehtml($list-> name);

include_once("nav.inc.php");

NewForm($f);

buttons(submit($f, 'save','Save'), button("Select Different File",NULL,"uploadlist.php"), button('Cancel',NULL,'list.php'));


if ($notfound > 0) {

	startWindow('Unmatched ID#s');

	if (count($notfounddata) >8) {
		?><div class="scrollTableContainer"><?
	}

	$titles = array("ID#", "Status");
	echo '<table width="100%" cellpadding="3" cellspacing="1" class="list">';
	showTable($notfounddata, $titles, array());
	echo '</table>';

	if (count($notfounddata) >8) {
		?></div><?
	}

	endWindow();
}


startWindow("Matched ID#s" . ($count <= 0 ? " - First 5000 Records" : ""));

if ($errormsg) {
	echo '<div align="center">    ' . $errormsg;
} else {

	$titles = array(0 => "ID#", 1=> "First Name", 2 => "Last Name", 3 => "Phone Number");
	if($USER->authorize('sendemail')){
		$titles[4] = "Email";
	}
	$formatters = array();

	if (count($listpreviewdata) >8) {
		?><div class="scrollTableContainer"><?
	}
	echo '<table width="100%" cellpadding="3" cellspacing="1" class="list">';
	showTable($listpreviewdata, $titles,$formatters);
	echo '</table>';

	if (count($listpreviewdata) >8) {
			?></div><?
	}
}

endWindow();

?><br><div style="margin-left: 10px;"><img src="img/bug_important.gif"> Please review your list then click Save.</div><?

buttons();
EndForm();

include_once("navbottom.inc.php");

?>
