<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
require_once("inc/common.inc.php");
require_once("obj/Schedule.obj.php");
require_once("obj/Import.obj.php");
require_once("obj/ImportField.obj.php");
require_once("obj/Schedule.obj.php");
require_once("obj/FieldMap.obj.php");
require_once("inc/form.inc.php");
require_once("inc/html.inc.php");
require_once("inc/table.inc.php");
require_once("inc/utils.inc.php");
require_once("inc/securityhelper.inc.php");
require_once("inc/formatters.inc.php");


////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$USER->authorize('managetasks')) {
	redirect('unauthorized.php');
}

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

if (isset($_GET['id'])) {
	$id = $_GET['id'] + 0;
	$_SESSION['importid'] = $id;
	$_SESSION['importcols'] = NULL;
	$_SESSION['importviewrows'] = 10;
	redirect();
}

if (!$_SESSION['importid'])
	redirect("tasks.php");

if (isset($_GET['previewrows'])) {
	$_SESSION['importviewrows'] = $_GET['previewrows'] + 0;
	$_SESSION['importviewrows'] = max(1,$_SESSION['importviewrows']);
	$_SESSION['importviewrows'] = min(50,$_SESSION['importviewrows']);
}

$previewrows = $_SESSION['importviewrows'];

$import = new Import($_SESSION['importid']);
$importfields = DBFindMany("importfield","from importfield where importid=" . $_SESSION['importid'] . " order by id");

$newimportfield = new ImportField();
$newimportfield->importid = $import->id;
$newimportfield->mapto="";
$newimportfield->mapfrom="";

$importfields[] = $newimportfield;


$importfieldmap = array();
$usedcols = array();
foreach ($importfields as $importfield) {
	if ($importfield->mapfrom == null)
		$importfield->mapfrom = "";
	if ($importfield->mapfrom != "") {
		$importfieldmap[] = $importfield->mapfrom;
		$usedcols[$importfield->mapfrom] = true;
	}
}

// find the data type of import
$datatype = $import->datatype;
if ($datatype == "person") {
	//menu of guardian sequence
	$guardiansequence = array();
	//$guardiansequence[-1] = "- Unmapped -";
	$guardiansequence[0] = _L("Contact");
	$maxguardians = getSystemSetting("maxguardians", 0);
	for ($i=1; $i<=$maxguardians; $i++) {
		$guardiansequence[$i] = _L("Guardian") . " " . $i;
	}

	//make a menu of all available fields
	$fieldmaps  = DBFindMany("FieldMap","from fieldmap where fieldnum like 'f%' order by fieldnum");
	$gfieldmaps = DBFindMany("FieldMap","from fieldmap where fieldnum like 'g%' order by fieldnum");
	// first, last, language only used by guardian
	$firstnamefield = FieldMap::getFirstNameField();
	$lastnamefield = FieldMap::getLastNameField();
	$languagefield = FieldMap::getLanguageField();

	//// student fields
	$studentmapto = array();
	
	$studentmapto[""] = "- Unmapped -";
	$studentmapto["key"] = "Unique ID";
	//F fields
	foreach ($fieldmaps as $fieldmap)
		$studentmapto[$fieldmap->fieldnum] = $fieldmap->name;

	$studentmapto["sep"] = "--------------";
	$studentmapto["okey"] = getSystemSetting("organizationfieldname","Organization");
	$studentmapto["sep2"] = "--------------";
	
	//G fields
	foreach ($gfieldmaps as $fieldmap)
		$studentmapto[$fieldmap->fieldnum] = $fieldmap->name;
	if (count($gfieldmaps) > 0) $studentmapto["sep3"] = "--------------";

	//phones, emails, SMS
	$maxphones = getSystemSetting("maxphones",3);
	for ($x = 0; $x < $maxphones; $x++)
		$studentmapto["p$x"] = destination_label("phone",$x); //"Phone " . ($x + 1);
	if (getSystemSetting('_hassms', false)) {
		$maxsms = getSystemSetting("maxsms",2);
		for ($x = 0; $x < $maxsms; $x++)
			$studentmapto["s$x"] = destination_label("sms",$x); //"SMS " . ($x + 1);
	}
	$maxemails = getSystemSetting("maxemails",2);
	for ($x = 0; $x < $maxemails; $x++)
		$studentmapto["e$x"] = destination_label("email",$x); //"Email " . ($x + 1);
	//address fields
	$studentmapto["a6"] = "Address ATTN";
	$studentmapto["a1"] = "Address 1";
	$studentmapto["a2"] = "Address 2";
	$studentmapto["a3"] = "City";
	$studentmapto["a4"] = "State";
	$studentmapto["a5"] = "Zip";

	
	////guardians fields
	$guardianmapto = array();
	$guardianmapto[""] = "- Unmapped -";
	$guardianmapto["key"] = "Unique ID";
	$guardianmapto["-cat"] = "Guardian Category";
	//F fields
	foreach ($fieldmaps as $fieldmap) {
		$fname = $fieldmap->fieldnum;
		
		if (!strcmp($fname, $firstnamefield) ||
			!strcmp($fname, $lastnamefield) ||
			!strcmp($fname, $languagefield)) {
				$guardianmapto[$fieldmap->fieldnum] = $fieldmap->name;
		} // else skip Ffields not supported by guardians
	}
	$guardianmapto["sep2"] = "--------------";

	//phones, emails, SMS
	$maxphones = getSystemSetting("maxphones",3);
	for ($x = 0; $x < $maxphones; $x++)
		$guardianmapto["p$x"] = destination_label("phone",$x); //"Phone " . ($x + 1);
	if (getSystemSetting('_hassms', false)) {
		$maxsms = getSystemSetting("maxsms",2);
		for ($x = 0; $x < $maxsms; $x++)
			$guardianmapto["s$x"] = destination_label("sms",$x); //"SMS " . ($x + 1);
	}
	$maxemails = getSystemSetting("maxemails",2);
	for ($x = 0; $x < $maxemails; $x++)
		$guardianmapto["e$x"] = destination_label("email",$x); //"Email " . ($x + 1);
	//address fields
	$guardianmapto["a6"] = "Address ATTN";
	$guardianmapto["a1"] = "Address 1";
	$guardianmapto["a2"] = "Address 2";
	$guardianmapto["a3"] = "City";
	$guardianmapto["a4"] = "State";
	$guardianmapto["a5"] = "Zip";
	
	
} else if ($datatype == "user") {
	$hasldap = getSystemSetting('_hasldap', '0');
	$hasenrollment = getSystemSetting('_hasenrollment', '0');
	
	// person column to select type for field map options
	$persontypeselection = array();
	$persontypeselection[0] = "User";
	$persontypeselection[1] = "Person";
	
	///////////////
	// user fields
	$usermapto = array();
	$usermapto[""] = "- Unmapped -";
	$usermapto["u1"] = "Username";
	$usermapto["u2"] = "First Name";
	$usermapto["u3"] = "Last Name";
	$usermapto["u4"] = "Description";
	$usermapto["u14"] = "Enabled"; // values enable, disable, disableonnew (default)
	if ($hasldap)
		$usermapto["u13"] = "Use LDAP";
	$usermapto["u5"] = "Telephone User ID";
	$usermapto["u6"] = "Email";
	$usermapto["u7"] = "Auto Report Emails";
	$usermapto["u8"] = "Phone";
	$usermapto["u9"] = "Caller ID";
	$usermapto["u11"] = "Access Profile";
	$usermapto["u12"] = "Restricted Job Types";
	if ($hasenrollment)
		$usermapto["u10"] = "Staff ID";
	$usermapto["sep"] = "--------------";

	//F fields, limit to multisearch
	$fieldmaps = DBFindMany("FieldMap","from fieldmap where fieldnum like 'f%' order by fieldnum");
	foreach ($fieldmaps as $fieldmap) {
		if ($fieldmap->isOptionEnabled("multisearch")) {
			$usermapto['u' . $fieldmap->fieldnum] = "Rule - " . $fieldmap->name;
		}
	}

	$usermapto["sep2"] = "--------------";
	$usermapto["uokey"] = getSystemSetting("organizationfieldname","Organization");
	
	//G fields
	$gfieldmaps = DBFindMany("FieldMap","from fieldmap where fieldnum like 'g%' order by fieldnum");
	if (count($gfieldmaps) > 0) $usermapto["sep3"] = "--------------";
	foreach ($gfieldmaps as $fieldmap)
		$usermapto['u' . $fieldmap->fieldnum] = "Rule - " . $fieldmap->name;

	//C fields (all assumed multisearch)
	$cfieldmaps = DBFindMany("FieldMap","from fieldmap where fieldnum like 'c%' order by fieldnum");
	if (count($cfieldmaps) > 1) $usermapto["sep4"] = "--------------";
	foreach ($cfieldmaps as $fieldmap) {
		if ($fieldmap->fieldnum == "c01") continue; // skip the teacherid
		$usermapto['u' . $fieldmap->fieldnum] = "Rule - " . $fieldmap->name;
	}
	
	////////////////////
	// person fields
	$studentmapto = array();
	
	$studentmapto[""] = "- Unmapped -";
	$studentmapto["key"] = "Unique ID";
	//F fields
	foreach ($fieldmaps as $fieldmap)
		$studentmapto[$fieldmap->fieldnum] = $fieldmap->name;

	$studentmapto["sep"] = "--------------";
	$studentmapto["okey"] = getSystemSetting("organizationfieldname","Organization");
	$studentmapto["sep2"] = "--------------";
	
	//G fields
	foreach ($gfieldmaps as $fieldmap)
		$studentmapto[$fieldmap->fieldnum] = $fieldmap->name;
	if (count($gfieldmaps) > 0) $studentmapto["sep3"] = "--------------";

	//phones, emails, SMS
	$maxphones = getSystemSetting("maxphones",3);
	for ($x = 0; $x < $maxphones; $x++)
		$studentmapto["p$x"] = destination_label("phone",$x); //"Phone " . ($x + 1);
	if (getSystemSetting('_hassms', false)) {
		$maxsms = getSystemSetting("maxsms",2);
		for ($x = 0; $x < $maxsms; $x++)
			$studentmapto["s$x"] = destination_label("sms",$x); //"SMS " . ($x + 1);
	}
	$maxemails = getSystemSetting("maxemails",2);
	for ($x = 0; $x < $maxemails; $x++)
		$studentmapto["e$x"] = destination_label("email",$x); //"Email " . ($x + 1);
	//address fields
	$studentmapto["a6"] = "Address ATTN";
	$studentmapto["a1"] = "Address 1";
	$studentmapto["a2"] = "Address 2";
	$studentmapto["a3"] = "City";
	$studentmapto["a4"] = "State";
	$studentmapto["a5"] = "Zip";
	

} else if ($datatype == "enrollment") {
	
	$maptofields = array();
	$maptofields[""] = "- Unmapped -";
	$maptofields["pkey"] = "Person ID";
	$maptofields["skey"] = "Section";
	$maptofields["okey"] = getSystemSetting("organizationfieldname","Organization");
	$maptofields[":enrollment:grade:letter"] = "Letter Grade";
	//$maptofields[":enrollment:grade:percent"] = "Percent Grade";
	
} else if ($datatype == "section") {
	
	$maptofields = array();
	$maptofields[""] = "- Unmapped -";
	$maptofields["skey"] = "Section";
	$maptofields["okey"] = getSystemSetting("organizationfieldname","Organization");
	$maptofields["u10"] = "Staff ID"; // 'u10' is used in user import, and keys of $maptofields must be 4 chars or less, so cannot use 'staffpkey' or something nicer

	$fieldmaps = DBFindMany("FieldMap","from fieldmap where fieldnum like 'c%' order by fieldnum");
	//C fields
	foreach ($fieldmaps as $fieldmap)
		$maptofields[$fieldmap->fieldnum] = $fieldmap->name;
}


// actions
$actions = array('copy' => "Copy",
				'staticvalue' => "Static Value",
				'curdate' => "Current Date",
				'numeric' => "Numeric",
				'currency' => "Currency",
				'currencyleadingzero' => "Currency with leading zero",
				'date' => "Date",
				'lookup' => "Data Lookup");

//open the csv file inside this import
$fp = $import->openCsvFile();

$colcount = 0;
if ($fp) {
	$count = $previewrows;
	$importdata = array();
	while (($row = fgetcsv($fp,4096)) !== FALSE && $count) {
		if (count($row) == 1 && trim($row[0]) === "")
			continue;
		for ($x = 0; $x < count($row); $x++)
			$importdata[$x][$previewrows - $count] = $row[$x] ;
		$colcount = max($colcount,count($row));
		$count--;
	}
	$_SESSION['importcols'] = $colcount;
	fclose($fp);
	$noimportdata = false;
} else {
	$_SESSION['importcols'] = 0;
	$noimportdata = true;
}


$mapfromcols = range(0,$colcount-1);
array_unshift($mapfromcols,"");


/****************** main message section ******************/
$f = "taskmap";
$s = "main";
$reloadform = false;

if (CheckFormSubmit($f, $s) || CheckFormSubmit($f, 'run') || CheckFormSubmit($f, 'add') || CheckFormSubmit($f, 'delete') !== false) {
	//check to see if formdata is valid
	if (CheckFormInvalid($f)) {
		error('Form was edited in another window, reloading data');
		$reloadform = true;
	} else {
		MergeSectionFormData($f, $s);


		//update required flags on each
		$count = 0;
		foreach ($importfields as $importfield) {

			$mapto_datafieldname = "mapto_$count"; // default
			
			// only person import has guardian sequence
			if ($datatype == "person") {
				$guardseq = GetFormData($f,$s,"guardseq_$count");
				if ($guardseq > 0)
					$mapto_datafieldname = "gmapto_$count";
				else
					$mapto_datafieldname = "smapto_$count";
			}
			// only user import has person type sequence
			if ($datatype == "user") {
				$ptype = GetFormData($f,$s,"ptype_$count");
				if ($ptype == 0)
					$mapto_datafieldname = "usermapto_$count";
				else
					$mapto_datafieldname = "smapto_$count";
			}
			if (GetFormData($f,$s,$mapto_datafieldname) != "") {

				$action = GetFormData($f,$s,"action_$count");

				if ($action == "date")
					SetRequired($f,$s,"date_$count",true);
				else
					SetRequired($f,$s,"date_$count",false);

				if ($action == "staticvalue" || $action == "curdate")
					SetRequired($f,$s,"mapfrom_$count",false);
				else
					SetRequired($f,$s,"mapfrom_$count",true);

			}
			$count++;
		}

		if (CheckFormSection($f, $s)) {
			error('There was a problem trying to save your changes', 'Please verify that all required field information has been entered properly');
		} else {

			$count = 0;
			foreach ($importfields as $importfield) {
				//check each map. if not static value, needs to have a col mapping

				$mapto_datafieldname = "mapto_$count"; // default
			
				// only person import has guardian sequence
				if ($datatype == "person") {
					$guardseq = GetFormData($f,$s,"guardseq_$count");
					if ($guardseq > 0)
						$mapto_datafieldname = "gmapto_$count";
					else
						$mapto_datafieldname = "smapto_$count";
				}
				// only user import has person type sequence
				if ($datatype == "user") {
					$ptype = GetFormData($f,$s,"ptype_$count");
					if ($ptype == 0)
						$mapto_datafieldname = "usermapto_$count";
					else
						$mapto_datafieldname = "smapto_$count";
				}

				$importfield->mapto = $mapto = GetFormData($f,$s,$mapto_datafieldname);

				//if the user submitted the form via delete button, check and delete the item as if it where unmapped
				if (CheckFormSubmit($f, 'delete') !== false &&
					CheckFormSubmit($f, 'delete') == $count) {
					$mapto = "";
				}


				if ($mapto == "") {
					if ($importfield->id)
						$importfield->destroy();
				} else {
					$importfield->action = $action = GetFormData($f,$s,"action_$count");

					//set the val
					switch ($action) {
					case "staticvalue":
					case "lookup":
					case "date":
						$importfield->val = GetFormData($f,$s,$action . "_" . $count);
						break;
					case "copy":
					case "curdate":
					default:
						$importfield->val = null;
						break;
					}

					//set the mapfrom
					if ($action == "staticvalue" || $action == "curdate")
						$importfield->mapfrom = null;
					else
						$importfield->mapfrom = GetFormData($f,$s,"mapfrom_$count");

					if ($importfield->mapfrom == "")
						$importfield->mapfrom = null;

					// only person import has guardian sequence
					if ($datatype == "person") {
						$guardseq = GetFormData($f,$s,"guardseq_$count");
						if ($guardseq > 0) {
							$importfield->guardiansequence = $guardseq;
						} else {
							$importfield->guardiansequence = null;
						}
					}
					
					$importfield->update();
				}

				$count++;
			}

			$import->notes = GetFormData($f, $s, "notes");
			$import->update();
			if (CheckFormSubmit($f, $s)) {
				redirect("tasks.php");
			} else if (CheckFormSubmit($f, 'run')) {
				$import->runNow();
				redirect("tasks.php");
			} else {
				redirect();
			}
		}
	}
} else {
	$reloadform = true;
}


if ($reloadform) {
	ClearFormData($f);

	//add every existing importfield
	$count = 0;
	foreach ($importfields as $importfield) {
		if ($datatype == "person") {
			//guardiansequence
			if ($importfield->guardiansequence == null) {
				PutFormData($f,$s,"guardseq_$count",0,"array",array_keys($guardiansequence));
				//mapto
				PutFormData($f,$s,"smapto_$count",$importfield->mapto, "array",array_keys($studentmapto));
				// define default guardian fields
				PutFormData($f,$s,"gmapto_$count",0, "array",array_keys($guardianmapto));
			} else {
				PutFormData($f,$s,"guardseq_$count",$importfield->guardiansequence,"array",array_keys($guardiansequence));
				//mapto
				PutFormData($f,$s,"gmapto_$count",$importfield->mapto, "array",array_keys($guardianmapto));
				// define default student fields
				PutFormData($f,$s,"smapto_$count",0, "array",array_keys($studentmapto));
			}
		} else if ($datatype == "user") {
			if (strncmp($importfield->mapto, 'u', 1) == 0 ||
				strlen($importfield->mapto) == 0) { // user or default to user
				//persontype
				PutFormData($f,$s,"ptype_$count",0,"array",array_keys($persontypeselection));
				//mapto user
				PutFormData($f,$s,"usermapto_$count",$importfield->mapto, "array",array_keys($usermapto));
				//mapto default personfields
				PutFormData($f,$s,"smapto_$count",0, "array",array_keys($studentmapto));
			} else { //person
				//persontype
				PutFormData($f,$s,"ptype_$count",1,"array",array_keys($persontypeselection));
				//mapto default user fields
				PutFormData($f,$s,"usermapto_$count",0, "array",array_keys($usermapto));
				//mapto person
				PutFormData($f,$s,"smapto_$count",$importfield->mapto, "array",array_keys($studentmapto));
			}
		} else {
			//mapto
			PutFormData($f,$s,"mapto_$count",$importfield->mapto, "array",array_keys($maptofields));
		}
		//action
		PutFormData($f,$s,"action_$count",$importfield->action, "array",array_keys($actions));

		//lookupdata|value|format
		//put defaults, then overwrite appropriate one with importfield->val
		PutFormData($f,$s,"lookup_$count","", "text");
		PutFormData($f,$s,"staticvalue_$count","", "text",0,255);
		PutFormData($f,$s,"date_$count","MM/dd/yy", "text",4,50);

		if ($importfield->action == "lookup")
			PutFormData($f,$s,"lookup_$count",$importfield->val, "text");
		else if ($importfield->action == "staticvalue")
			PutFormData($f,$s,"staticvalue_$count",$importfield->val, "text",0,255);
		else if ($importfield->action == "date")
			PutFormData($f,$s,"date_$count",$importfield->val, "text",4,50);

		//mapfrom
		PutFormData($f,$s,"mapfrom_$count",$importfield->mapfrom, "array",$mapfromcols);
		$count++;
	}
	PutFormData($f, $s, "notes", $import->notes, "text");
}


////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$PAGE = "admin:taskmanager";
$TITLE = "Import Field Mapping: " . escapehtml($import->name);
$DESCRIPTION = count($usedcols) . " of $colcount input columns mapped";

include_once("nav.inc.php");

NewForm($f);
if ($noimportdata) {
	buttons(button('Done',NULL,'tasks.php'));
} else {
	buttons(submit($f, $s), submit($f,'run',"Submit and Run Now"));
}
startWindow('Field Mapping');
if ($noimportdata) { ?>
			<br><h3>No import data could be found. Please check that a non empty file has been uploaded.</h3><br>
<? } else { ?>

<div class="hoverlinks" style="margin: 5px;">
<table>
	<tr>
		<td>Notes:</td>
		<td><? NewFormItem($f, $s, "notes", "textarea", 60, 3) ?></td>
	</tr>
	<tr>
		<td>Preview rows:</td>
		<td>
		<select onchange="if(confirm('Warning: changing the number of preview rows will undo any changes made on the page.\nClick cancel if you need to save your changes.')) {location.href='?previewrows=' + this.value;}">

		<option value="1" <?= 1 == $_SESSION['importviewrows'] ? "selected" : "" ?>>1</option>
		<?
			for ($x = 5; $x <= 50; $x += 5) {
		?>
				<option value="<?=$x?>" <?= $x == $_SESSION['importviewrows'] ? "selected" : "" ?>><?=$x?></option>
		<?
			}
		?>
		</select>
		<td>
	</tr>
</table>
<br>
<a style="display:none"id="editmappinglink" href="#" onclick="$('viewdata').hide(); $('datamapping').show(); $('editmappinglink').hide(); $('viewdatalink').show(); return undefined;">Switch to Mapping Editor</a>
<a id="viewdatalink" href="#" onclick="$('datamapping').hide(); $('viewdata').show(); $('editmappinglink').show(); $('viewdatalink').hide(); return undefined;">Switch to Data View</a>
</div>

<div id="datamapping">
<table width="100%" cellpadding="3" cellspacing="1" class="list">
	<tr class="listHeader">
<?
		if ($datatype == "person" || $datatype == "user") {
?>
		<th align="left">Person</th>
<?
		}
?>
		<th align="left">Field</th><th align="left">Translator</th><th align="left" >Translator&nbsp;Options</th><th align="left">Import File Data</th><th align="left">Actions</th>
	</tr>
<?
	$alt = 0;
	$count = 0;
	foreach ($importfields as $importfield) {
		echo ++$alt % 2 ? '<tr>' : '<tr class="listAlt">';
		
		// only person type has extra guardian column to map
		if ($datatype == "person") {
?>
		<td>
<?
			NewFormItem($f,$s,"guardseq_$count","selectstart",null,null,'onchange="switchmaptodata(' . $count . ',this.value);"');
			foreach ($guardiansequence as $seqid => $name) {
				NewFormItem($f,$s,"guardseq_$count","selectoption",$name,$seqid);
			}
			NewFormItem($f,$s,"guardseq_$count","selectend");
?>
		</td>
<?
		}
		// only user type has extra person type column to map
		if ($datatype == "user") {
?>
		<td>
<?
			NewFormItem($f,$s,"ptype_$count","selectstart",null,null,'onchange="switchusermaptodata(' . $count . ',this.value);"');
			foreach ($persontypeselection as $seqid => $name) {
				NewFormItem($f,$s,"ptype_$count","selectoption",$name,$seqid);
			}
			NewFormItem($f,$s,"ptype_$count","selectend");
?>
		</td>
<?
		}
?>
		<td>
<?
		// only person type has extra guardian column to map, and show/hide mapto options
		if ($datatype == "person") {

			// mapto fields, show/hide based on person type
			$show = GetFormData($f,$s,"guardseq_$count");
?>			
			<div id="mapto_<?=$count?>_student" style="<?= $show == 0 ? '' : 'display:none'?>">
<?
			//student
			NewFormItem($f,$s,"smapto_$count","selectstart");
			foreach ($studentmapto as $mapto => $name) {
				$extrahtml = "";
				if (strpos($mapto,"sep") === 0) $extrahtml = "disabled=\"disabled\"";
				NewFormItem($f,$s,"smapto_$count","selectoption",$name,$mapto,$extrahtml);
			}
			NewFormItem($f,$s,"smapto_$count","selectend");
?>
			</div>
			<div id="mapto_<?=$count?>_guardian" style="<?= $show == 0 ? 'display:none' : ''?>">
<?
			//guardian
			NewFormItem($f,$s,"gmapto_$count","selectstart");
			foreach ($guardianmapto as $mapto => $name) {
				$extrahtml = "";
				if (strpos($mapto,"sep") === 0) $extrahtml = "disabled=\"disabled\"";
				NewFormItem($f,$s,"gmapto_$count","selectoption",$name,$mapto,$extrahtml);
			}
			NewFormItem($f,$s,"gmapto_$count","selectend");
?>
			</div>
<?
		// only user type has extra person column to map, and show/hide mapto options
		} else if ($datatype == "user") {
			// mapto fields, show/hide based on person type
			$show = GetFormData($f,$s,"ptype_$count");
?>			
			<div id="mapto_<?=$count?>_student" style="<?= $show == 0 ? 'display:none' : ''?>">
<?
			//person
			NewFormItem($f,$s,"smapto_$count","selectstart");
			foreach ($studentmapto as $mapto => $name) {
				$extrahtml = "";
				if (strpos($mapto,"sep") === 0) $extrahtml = "disabled=\"disabled\"";
				NewFormItem($f,$s,"smapto_$count","selectoption",$name,$mapto,$extrahtml);
			}
			NewFormItem($f,$s,"smapto_$count","selectend");
?>
			</div>
			<div id="mapto_<?=$count?>_user" style="<?= $show == 0 ? '' : 'display:none'?>">
<?
			//user
			NewFormItem($f,$s,"usermapto_$count","selectstart");
			foreach ($usermapto as $mapto => $name) {
				$extrahtml = "";
				if (strpos($mapto,"sep") === 0) $extrahtml = "disabled=\"disabled\"";
				NewFormItem($f,$s,"usermapto_$count","selectoption",$name,$mapto,$extrahtml);
			}
			NewFormItem($f,$s,"usermapto_$count","selectend");
?>
			</div>
<?
		} else {
			NewFormItem($f,$s,"mapto_$count","selectstart");
			foreach ($maptofields as $mapto => $name) {
				$extrahtml = "";
				if (strpos($mapto,"sep") === 0) $extrahtml = "disabled=\"disabled\"";
				NewFormItem($f,$s,"mapto_$count","selectoption",$name,$mapto,$extrahtml);
			}
			NewFormItem($f,$s,"mapto_$count","selectend");
		}
?>
		</td>
		<td>
<?
			//show/hide actions, and if staticvalue hide mapfrom
			NewFormItem($f,$s,"action_$count","selectstart",null,null,'onchange="switchactiondata(' . $count . ',this.value);"');
			foreach ($actions as $action => $name)
				NewFormItem($f,$s,"action_$count","selectoption",$name,$action);
			NewFormItem($f,$s,"action_$count","selectend");
?>
		</td>
		<td>
<?
			//action datas, show/hide these based on action menu
			$show = GetFormData($f,$s,"action_$count");
?>
			<div id="actiondata_<?=$count?>_lookup" style="<?= $show == 'lookup' ? '' : 'display:none'?>">

				<? NewFormItem($f,$s,"lookup_$count","textarea",25,10); ?>

			</div>
			<div id="actiondata_<?=$count?>_staticvalue" style="<?= $show == 'staticvalue' ? '' : 'display:none'?>">

				<? NewFormItem($f,$s,"staticvalue_$count","text",20,255); ?>

			</div>
			<div id="actiondata_<?=$count?>_date" style="<?= $show == 'date' ? '' : 'display:none'?>">

				<? NewFormItem($f,$s,"date_$count","text",20,20); ?>

			</div>

		</td>
		<td>
		
			<div id="filedata_<?=$count?>" style="<?= $show == "staticvalue" || $show == "curdate" ? "display: none;" : "" ?>">
				
				<div class="import_file_data">
<?
				//file data map

				NewFormItem($f,$s,"mapfrom_$count","selectstart",null,null,'id="select_' . $count . '" onchange="setdata(' . $count . ');"');

				foreach ($mapfromcols as $col) {
					if ($col === "") {
						NewFormItem($f,$s,"mapfrom_$count","selectoption","- None -","");
					} else {
						$unused = !isset($usedcols[$col]) ? " *" : "";
						NewFormItem($f,$s,"mapfrom_$count","selectoption","Column " . ($col + 1) . $unused,$col);
					}
				}
				NewFormItem($f,$s,"mapfrom_$count","selectend");
?>
				
					<?= button('Previous',"prevselect($count)") ?>
				
					<?= button('Next',"nextselect($count)") ?>
				
				
				</div><!-- .import_file_data -->
				
				<div id="selectordata<?=$count?>">
				</div>
				
			</div><!-- .filedata -->

		</td>
		<td>
			<?= $count == count($importfields) -1 ? submit($f,'add',"Add") : submit($f,'delete',"Delete",$count); ?>
		</td>
<?
		$count++;
	}

?>
</table>
<div style="margin: 5px;">
<img src="img/bug_lightbulb.gif" > Columns with an * indicate that they have not yet been mapped.
</div>
</div>
<div id="viewdata" style="display: none;">
<table border=0><tr><td>
<?

//load a bunch of hidden, named divs with the data from the import file, then this data can be read later and copied to show values
	for ($x = 0; $x < $colcount; $x++) {
	?>
		<div style="float: left; margin-left: 10px; margin-bottom: 10px; padding: 3px; ">
		<b>Column <?= $x +1?></b>
		<div id="data<?= $x?>" >
		<table cellpadding="0" cellspacing="0" width="250" style="font-size: 8pt; border: 1px solid gray;">
<?
		for ($ci = 0; $ci < $previewrows; $ci++) {
			$cel = isset($importdata[$x][$ci]) ? $importdata[$x][$ci] : "";
?>
			<tr>
			<td style="border-bottom: 1px dotted black; padding-left: 3px; <?= $import->skipheaderlines > $ci ? ' background: #cccccc;' : ' background: white;'?>"><?= empty($cel) ? "-" : escapehtml($cel) ?></td>
			</tr>
<?
		}
?>
		</table>
		</div>
		</div>
<?
	}
?>
</td></tr></table>
</div><!-- #viewdata -->

<script>

function switchactiondata (num,newaction) {
	//hide any already showing action's div
	$("actiondata_" + num + "_lookup").hide();
	$("actiondata_" + num + "_staticvalue").hide();
	$("actiondata_" + num + "_date").hide();

	//see if a div exists for the new action
	var newactiondiv = $("actiondata_" + num + "_" + newaction);
	if (newactiondiv)
		newactiondiv.show();

	//show or hide the data mapping area
	if (newaction == 'staticvalue' || newaction == 'curdate') {
		$("filedata_" + num).hide();
	} else {
		$("filedata_" + num).show();
		setdata(num);
	}
}

// only for person imports
function switchmaptodata (num,newaction) {
	//hide any already showing mapto's div
	$("mapto_" + num + "_student").hide();
	$("mapto_" + num + "_guardian").hide();

	var persontype = 'guardian';
	if (newaction == 0)
		persontype = 'student';
		
	//see if a div exists for the new person type
	var newactiondiv = $("mapto_" + num + "_" + persontype);
	if (newactiondiv)
		newactiondiv.show();
}

// only for user imports
function switchusermaptodata (num, newaction) {
	//hide any already showing mapto's div
	$("mapto_" + num + "_student").hide();
	$("mapto_" + num + "_user").hide();

	var persontype = 'student';
	if (newaction == 0)
		persontype = 'user';
		
	//see if a div exists for the new person type
	var newactiondiv = $("mapto_" + num + "_" + persontype);
	if (newactiondiv)
		newactiondiv.show();
}

function nextselect (num) {
	var select = new getObj("select_" + num).obj;
	var next = select.selectedIndex + 1;
	if (next >= select.options.length)
		next = 0;
	select.selectedIndex = next;
	setdata(num);
}

function prevselect (num) {
	var select = new getObj("select_" + num).obj;
	var prev = select.selectedIndex - 1;
	if (prev < 0 )
		prev = select.options.length > 0 ? (select.options.length - 1) : 0;
	select.selectedIndex = prev;
	setdata(num);
}


function setdata(num) {

	var select = new getObj("select_" + num).obj;
	var sourcenum = select.value;

	var dest = new getObj("selectordata" + num);
	var source = new getObj("data" + sourcenum);
	if (source.obj && source.obj.innerHTML)
		dest.obj.innerHTML = source.obj.innerHTML;
	else
		dest.obj.innerHTML = '';
}

//set the import file data for each mapping
var importfieldcount = <?= count($importfields) ?>;

for (var x = 0; x < importfieldcount; x++) {
	setdata(x);
}

</script>

<?
}
endWindow();
buttons();
EndForm();

include_once("navbottom.inc.php");

?>
