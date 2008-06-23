<?

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
require_once("inc/ftpfile.inc.php");

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
if (isset($_SESSION['listuploadfiles'][$list->id])) {
	$curfilename = $_SESSION['listuploadfiles'][$list->id];

	$type = $_SESSION['listuploadfiles']['type'];
} else {
	$curfilename = false;
	$errormsg = "Please upload a file";
}

$phonefield = isset($_SESSION['listupload']['phonefield']) ? $_SESSION['listupload']['phonefield'] : 0;
$emailfield = isset($_SESSION['listupload']['emailfield']) ? $_SESSION['listupload']['emailfield'] : 0;

$f = "list";
$s = "uploadpreview";
$reloadform = 0;


//load preview data
$listpreviewdata = array();
$notfound = 0;
$notfounddata = array();
$errormsg = false;
$defaultareacode = getSystemSetting("defaultareacode");
$usersql = $USER->userSQL("p");
if ($curfilename && !(CheckFormSubmit($f,'save') && $type =="ids") ) {


	if ($type == "contacts") {
		if ($fp = @fopen($curfilename, "r")) {
			$count = 5000;
			$colcount = 0;
			while (($row = fgetcsv($fp,4096)) !== FALSE && $count--) {
				if (count($row) == 1 && $row[0] == "")
					continue;
				for ($x = 0; $x < 4; $x++)
					$row[$x] = (isset($row[$x]) ? $row[$x] : null);
				$phone = Phone::parse($row[2]);
				if ($defaultareacode && strlen($phone) == 7)
					$phone = Phone::parse($defaultareacode . $phone);
				$phone = strlen($phone) == 10 ? Phone::format($phone) : "Invalid";

				$listpreviewdata[] = array($row[0],$row[1],$phone,$row[3]) ;
				$colcount = max($colcount,count($row));
			}
			fclose($fp);

			if ($colcount != 3 && $colcount != 4) {
				$errormsg = "Invalid number of columns. There must be exactly 3 or 4 columns: First Name, Last Name, Phone Number, and optionally Email";
			}
		} else {
			$errormsg = "Unable to open the file";
		}
	} else {
		if ($fp = fopen($curfilename, "r")) {

			$personids = array();
			$count = 5000;
			$total = 0;
			while (($row = fgetcsv($fp,4096)) !== FALSE) {
				$pkey = DBSafe(trim($row[0]));
				if ($pkey == "")
					continue;
				$query = "
					select p.id
					from 		person p
					where p.pkey='$pkey' and not p.deleted $usersql
				";

				$personid = QuickQuery($query);
				$total++;
				$count--;
				if ($personid) {

					if ($count > 0) {
						$p = DBFind("Person","from person where id='$personid'");
						$phone = DBFind("Phone","from phone where personid='$personid'");
						$email = DBFind("Email","from email where personid='$personid'");
						//check if the object isnt false else display empty string
						$listpreviewdata[] = array($pkey,$p->f01,$p->f02, $phone ? Phone::format($phone->phone) : "", $email ? $email->email : "");
					}
				} else {
					$notfound++;

					//$listpreviewdata[] = array($pkey, "### Not Found ###", "### Not Found ###", "-","-");
					$notfounddata[] = array($pkey, "### Not Found ###");
				}
			}
			fclose($fp);

			if ($notfound) {
				error( "Some contacts were not found. " . ($total - $notfound) ." of $total contacts matched");
			}
		} else {
			$errormsg = "Unable to open the file";
		}
	}
}


//process the list?
if (CheckFormSubmit($f,'save') && !$errormsg) {


	if ($type == "contacts") {

		//check for an import record or create one
		$importid = QuickQuery("select id from import where listid='$list->id'");

		if (!$importid) {

			$import = new Import();
			$import->userid = $USER->id;
			$import->listid = $list->id;
			$import->name = "User list import (" . $USER->login . ")";
			$import->description = substr("list (" . $list->name . ")", 0,50);
			$import->status = "idle";
			$import->type = "list";
			$import->scheduleid = NULL;
			$import->ownertype = "user";
			$import->updatemethod = "full";

			$import->create();
			$importid = $import->id;

		} else {
			$import = new Import($importid);
			$import->name = "User list import (" . $USER->login . ")";
			$import->description = substr("list (" . $list->name . ")", 0,50);
			$import->update();
		}

		if($importid){
			//Delete all importfield mappings and recreate, instead of trying to search and update
			QuickUpdate("delete from importfield where importid = '" . $importid . "'");
			$iffn = new ImportField();
			$iffn->importid = $importid;
			$iffn->mapto = "f01";
			$iffn->mapfrom = 0;
			$iffn->create();

			$ifln = new ImportField();
			$ifln->importid = $importid;
			$ifln->mapto = "f02";
			$ifln->mapfrom = 1;
			$ifln->create();

			$ifph = new ImportField();
			$ifph->importid = $importid;
			$ifph->mapto = "p" . $phonefield;
			$ifph->mapfrom = 2;
			$ifph->create();

			$ife = new ImportField();
			$ife->importid = $importid;
			$ife->mapto = "e" . $emailfield;
			$ife->mapfrom = 3;
			$ife->create();
		}


		//upload or copy the file to the main import location
		$data = file_get_contents($curfilename);
		if ($import->upload($data)) {
			$import->runNow();

			$import->refresh();
			while ($import->status == "queued" || $import->status == "running") {
				sleep(1);
				$import->refresh();
			}

		} else {
			$errormsg = 'Unable to complete file upload. Please try again.';
		}

	} else {
		//just sync the IDs

		$personids = array();
		if ($fp = fopen($curfilename, "r")) {
			while ($row = fgetcsv($fp,4096)) {
				$pkey = DBSafe(trim($row[0]));
				if ($pkey == "")
					continue;
				$query = "
					select p.id
					from 		person p
					where p.pkey='$pkey' and not p.deleted $usersql
				";

				if ($personid = QuickQuery($query))
					$personids[] = $personid;
			}
			fclose($fp);


			$oldids = QuickQueryList("select p.id from person p, listentry le where p.id=le.personid and le.type='A' and p.userid is null and le.listid=$list->id");
			$deleteids = array_diff($oldids, $personids);
			$addids = array_diff($personids, $oldids);

			$query = "delete from listentry where personid in ('" . implode("','",$deleteids) . "') and listid = " . $list->id;
			QuickUpdate($query);
			if (count($addids) > 0) {
				$query = "insert into listentry (listid, type, personid) values ($list->id,'A','" . implode("'),($list->id,'A','",$addids) . "')";
				QuickUpdate($query);
			}
		} else {
			$errormsg = "Unable to open the file";
		}
	}

	//clean up the file and reset the session var

	@unlink($curfilename);
	unset($_SESSION['listuploadfiles'][$list->id]);

	redirect("list.php");
}


if( $reloadform )
{
	ClearFormData($f);
}

if ($errormsg)
	error($errormsg);


////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$PAGE = "notifications:lists";
$TITLE = "Upload List: " . $list-> name;

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


startWindow(($type=="contacts" ? 'Upload Preview' : "Matched ID#s") . ($count <= 0 ? " - First 5000 Records" : ""));


if ($errormsg) {
	echo '<div align="center">    ' . $errormsg;
} else {

	if ($type == "contacts") {

		$titles = array(0 => "First Name", 1 => "Last Name", 2 => "Phone Number");
		if($USER->authorize('sendemail')){
			$titles[3] = "Email";
		}
		$formatters = array(3 => "fmt_email");
	} else {
		$titles = array(0 => "ID#", 1=> "First Name", 2 => "Last Name", 3 => "Phone Number");
		if($USER->authorize('sendemail')){
			$titles[4] = "Email";
		}
		$formatters = array();
	}

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