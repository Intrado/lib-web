<?
/*
 * This file handles contacts based list uploads, see uploadlistpreview.php for the ID based list upload
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

//set up field mapping options array
$maptofields = array("" => "-Not Used-");

$fieldmaps = FieldMap::getAuthorizedMapNamesLike('f'); //this should have f01-f03 and any other insertable fields the user can use num->name
foreach ($fieldmaps as $fieldnum => $name)
	$maptofields[$fieldnum] = $name;

$maxphones = getSystemSetting("maxphones",3);
for ($x = 0; $x < $maxphones; $x++)
	$maptofields["p$x"] = destination_label("phone",$x); //"Phone " . ($x + 1);

//skip SMS, user could use this to bypass optin
	
if ($USER->authorize("sendemail")) {
	$maxemails = getSystemSetting("maxemails",2);
	for ($x = 0; $x < $maxemails; $x++)
		$maptofields["e$x"] = destination_label("email",$x); //"Email " . ($x + 1);
}


//load mapping from import
$importfields = DBFindMany("importfield","from importfield where importid=?", null, array($importid));
if (count($importfields) == 0) {
	//load old default 3/4 col csv format by default
	$datamap = array( 0 => "f01", 1 => "f02", 2 => "p0");
	if ($USER->authorize('sendemail'))
		$datamap[3] = "e0";
} else {
	$datamap = array();
	foreach ($importfields as $importfield)
		$datamap[$importfield->mapfrom] = $importfield->mapto;
}

$f = "list";
$s = "uploadpreview";
$reloadform = 0;
//process the list?
if (CheckFormSubmit($f,'save') || CheckFormSubmit($f,'preview')) {
	
	if (CheckFormInvalid($f)) {
		error('Form was edited in another window, reloading data');
		$reloadform = 1;
	} else { 
		MergeSectionFormData($f, $s);
		
		//TODO count mapped fields, error on no destination fields
		
		if( CheckFormSection($f, $s) ) {
			error('There was a problem trying to use your selections', 'Please verify that all required field information has been entered properly');
		} else {
			//load datamap from form data
			$colcount = GetFormData($f, $s, "colcount");
			$datamap = array();
			for ($i = 0; $i < $colcount; $i++) {
				$datamap[$i] = GetFormData($f, $s, "map" . $i);
			}
			
			//update import mapping
			//Delete all importfield mappings and recreate, instead of trying to search and update
			QuickUpdate("delete from importfield where importid = '" . $importid . "'");
			foreach ($datamap as $mapfrom => $mapto) {
				if ($mapto != "") {
					$importfield = new ImportField();
					$importfield->importid = $importid;
					$importfield->mapto = $mapto;
					$importfield->mapfrom = $mapfrom;
					$importfield->create();
				}
			}
			
			if (CheckFormSubmit($f,'save')) {
				
				$import->runNow();
	
				$import->refresh();
				while ($import->status == "queued" || $import->status == "running") {
					sleep(1);
					$import->refresh();
				}
			
				redirect("list.php");
			}
			//otherwise show preview again, with new mapping (need to reload form data?)
		}
	}
} else {
	$reloadform = 1;
}

//load preview data
$count = 0;
$listpreviewdata = array();
$defaultareacode = getSystemSetting("defaultareacode");

if ($datauri && !CheckFormSubmit($f,'save')) {
	if ($fp = @fopen($datauri, "r")) {
		$count = 5000;
		$colcount = 0;
		$lastrow = null;
		while (($row = fgetcsv($fp,4096)) !== FALSE && $count--) {
			//skip duplicate or blank lines (can be created by excel)
			if ($lastrow == $row || (count($row) == 1 && $row[0] == ""))
				continue;

			$listpreviewdata[] = $row;
			$colcount = max($colcount,count($row));
			$lastrow = $row;
		}
		fclose($fp);


	} else {
		error("Unable to open the file");
	}
}


if( $reloadform )
{	
	ClearFormData($f);
	
	//create a field dropdown for each col
	for ($i = 0; $i < $colcount; $i++) {
		PutFormData($f, $s, "map" . $i, isset($datamap[$i]) ? $datamap[$i] : "", "array", null, array_keys($mapto));
	}
}

PutFormData($f,$s,"colcount", $colcount); //save for later


////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$PAGE = "notifications:lists";
$TITLE = "Upload List: " . escapehtml($list-> name);

include_once("nav.inc.php");

NewForm($f);

buttons(submit($f, 'save','Save'), submit($f, 'preview','Preview'),  button("Select Different File",NULL,"uploadlist.php"), button('Cancel',NULL,'list.php'));


startWindow('Upload Preview' . ($count <= 0 ? " - First 5000 Records" : ""));

if ($errormsg) {
	echo '<div align="center">    ' . $errormsg;
} else {

	$titles = array(0 => "First Name", 1 => "Last Name", 2 => "Phone Number");
	if($USER->authorize('sendemail')){
		$titles[3] = "Email";
	}
	$formatters = array(3 => "fmt_email");

	if (count($listpreviewdata) >8) {
		?><div class="scrollTableContainer"><?
	}
	echo '<table width="100%" cellpadding="3" cellspacing="1" class="list">';
	//can't use showTable and stuff input elements in headers, so fall back to a regular table
	
	echo '<tr class="listHeader">';
	for ($i = 0; $i < $colcount; $i++) {
		echo '<th align="left">';
		NewFormSelect($f, $s, "map" . $i, $maptofields);
		echo '</th>';
	}
	echo '</tr>';
	
	$alt = 0;
	foreach ($listpreviewdata as $row) {
		$alt++;		
		echo $alt % 2 ? '<tr>' : '<tr class="listAlt">';
		foreach ($row as $mapfrom => $cel) {
			$cel = trim($cel);
			
			//check for unmapped fields
			if (!isset($datamap[$mapfrom])) {
				 $cel = "";
			} else {
				//do some basic formatting and validation based on field type
				$fieldnum = $datamap[$mapfrom];
				$typechar = substr($fieldnum, 0, 1);
				if ($typechar == "p") {
					$phone = Phone::parse($cel);
					if ($defaultareacode && strlen($phone) == 7)
						$phone = Phone::parse($defaultareacode . $phone);
					if (strlen($phone) == 10)
						$cel = Phone::format($phone);
					else
						$cel = "<b>Invalid Phone</b>: $cel";
				} else if ($typechar == "e") {
					if (validEmail($cel)) {
						$cel = escapehtml($cel);
					} else {
						$cel = "<b>Invalid Email</b>: " . escapehtml($cel);
					}
				} else {
					$cel = escapehtml($cel);
				}
			}
			echo '<td>' . $cel. '</td>';
		}
		echo '</tr>';
	}
	
	
	
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
