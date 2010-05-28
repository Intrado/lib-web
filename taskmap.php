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

	//make a menu of all available fields
	$fieldmaps  = DBFindMany("FieldMap","from fieldmap where fieldnum like 'f%' order by fieldnum");
	$gfieldmaps = DBFindMany("FieldMap","from fieldmap where fieldnum like 'g%' order by fieldnum");

	$maptofields = array();
	$maptofields[""] = "- Unmapped -";
	$maptofields["key"] = "Unique ID";
	//F fields
	foreach ($fieldmaps as $fieldmap)
		$maptofields[$fieldmap->fieldnum] = $fieldmap->name;

	$maptofields["sep"] = "--------------";
	//G fields
	$maptofields["okey"] = getSystemSetting("organizationfieldname","Organization");
	foreach ($gfieldmaps as $fieldmap)
		$maptofields[$fieldmap->fieldnum] = $fieldmap->name;
	if (count($gfieldmaps) > 0) $maptofields["sep2"] = "--------------";

	//phones, emails, SMS
	$maxphones = getSystemSetting("maxphones",3);
	for ($x = 0; $x < $maxphones; $x++)
		$maptofields["p$x"] = destination_label("phone",$x); //"Phone " . ($x + 1);
	if (getSystemSetting('_hassms', false)) {
		$maxsms = getSystemSetting("maxsms",2);
		for ($x = 0; $x < $maxsms; $x++)
			$maptofields["s$x"] = destination_label("sms",$x); //"SMS " . ($x + 1);
	}
	$maxemails = getSystemSetting("maxemails",2);
	for ($x = 0; $x < $maxemails; $x++)
		$maptofields["e$x"] = destination_label("email",$x); //"Email " . ($x + 1);
	//address fields
	$maptofields["a6"] = "Address ATTN";
	$maptofields["a1"] = "Address 1";
	$maptofields["a2"] = "Address 2";
	$maptofields["a3"] = "City";
	$maptofields["a4"] = "State";
	$maptofields["a5"] = "Zip";

} else if ($datatype == "user") {
	$hasldap = getSystemSetting('_hasldap', '0');
	$hasenrollment = getSystemSetting('_hasenrollment', '0');
	
	$maptofields = array();
	$maptofields[""] = "- Unmapped -";
	$maptofields["u1"] = "Username";
	$maptofields["u2"] = "First Name";
	$maptofields["u3"] = "Last Name";
	$maptofields["u4"] = "Description";
	if ($hasldap)
		$maptofields["u13"] = "Use LDAP";
	$maptofields["u5"] = "Telephone User ID";
	$maptofields["u6"] = "Email";
	$maptofields["u7"] = "Auto Report Emails";
	$maptofields["u8"] = "Phone";
	$maptofields["u9"] = "Caller ID";
	$maptofields["u11"] = "Access Profile";
	$maptofields["u12"] = "Restricted Job Types";
	if ($hasenrollment)
		$maptofields["u10"] = "Staff ID";
	$maptofields["sep"] = "--------------";

	//F fields, limit to multisearch
	$fieldmaps = DBFindMany("FieldMap","from fieldmap where fieldnum like 'f%' order by fieldnum");
	foreach ($fieldmaps as $fieldmap) {
		if ($fieldmap->isOptionEnabled("multisearch")) {
			$maptofields[$fieldmap->fieldnum] = "Rule - " . $fieldmap->name;
		}
	}

	//G fields
	$gfieldmaps = DBFindMany("FieldMap","from fieldmap where fieldnum like 'g%' order by fieldnum");
	if (count($gfieldmaps) > 0) $maptofields["sep2"] = "--------------";
	$maptofields["okey"] = getSystemSetting("organizationfieldname","Organization");
	foreach ($gfieldmaps as $fieldmap)
		$maptofields[$fieldmap->fieldnum] = "Rule - " . $fieldmap->name;

	//C fields (all assumed multisearch)
	$cfieldmaps = DBFindMany("FieldMap","from fieldmap where fieldnum like 'c%' order by fieldnum");
	if (count($cfieldmaps) > 1) $maptofields["sep3"] = "--------------";
	foreach ($cfieldmaps as $fieldmap) {
		if ($fieldmap->fieldnum == "c01") continue; // skip the teacherid
		$maptofields[$fieldmap->fieldnum] = "Rule - " . $fieldmap->name;
	}

} else if ($datatype == "enrollment") {
	
	$maptofields = array();
	$maptofields[""] = "- Unmapped -";
	$maptofields["pkey"] = "Person ID";
	$maptofields["skey"] = "Section";
	$maptofields["okey"] = getSystemSetting("organizationfieldname","Organization");
	
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


//helper function to scan a zip file for likely import files
function scan_zip($zip,$mode) {
	$max = 0;
	$foundentry = false;
	for ($x = 0; $x < $zip->numFiles; $x++) {
		$entry = $zip->statIndex($x);
		$name = $entry["name"];
		$basename = basename($entry["name"]);
		
		//skip all hidden files that start with '.'
		if (strpos($basename,".") === 0)
			continue;	
		//skip maxosx stuff
		if (strpos($name,"__MACOSX") !== false)
			continue;
		//skip directories
		if ($name[strlen($name)-1] == "/")
			continue;
		//skip empty files
		if ($entry['size'] == 0)
			continue;
		
		switch ($mode) {
			case "largest":
				if ($entry['size'] > $max) {
					$max = $entry['size'];
					$foundentry = $entry;
				}
				break;
			case "extension":
				$bits = explode(".",$basename);				
				if (($count = count($bits)) > 1) {
					$ext = strtolower($bits[$count-1]);
					if ($ext == "csv" || $ext == "txt") {
						$foundentry = $entry;
					}
				}
				break;
		}
	}
	
	return $foundentry;
}

//scan the file
$importfile = secure_tmpname("taskmap",".dat");
file_put_contents($importfile,$import->download());

//see if this will open with zip
$fp = false;
$zip = new ZipArchive();
$res = $zip->open($importfile);
if ($res === true) {
	//try to find best file match
	$entry = scan_zip($zip,"extension");
	if ($entry === false)
		$entry = scan_zip($zip,"largest");
	//see if we found a file, and open a stream for it
	if ($entry !== false)
		$fp = $zip->getStream($entry["name"]);
} else {
	$fp = @fopen($importfile , "r");
}
$colcount = 0;
if ($fp && filesize($importfile) > 0 ) {
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

unlink($importfile);


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

			if (GetFormData($f,$s,"mapto_$count") != "") {

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

				$importfield->mapto = $mapto = GetFormData($f,$s,"mapto_$count");

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
		//mapto
		PutFormData($f,$s,"mapto_$count",$importfield->mapto, "array",array_keys($maptofields));

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
		<th align="left">Field</th><th align="left">Translator</th><th align="left" >Translator&nbsp;Options</th><th align="left">Import File Data</th><th align="left">Actions</th>
	</tr>
<?
	$alt = 0;
	$count = 0;
	foreach ($importfields as $importfield) {

		echo ++$alt % 2 ? '<tr>' : '<tr class="listAlt">';
?>
		<td>
<?
			NewFormItem($f,$s,"mapto_$count","selectstart");
			foreach ($maptofields as $mapto => $name) {
				$extrahtml = "";
				if (strpos($mapto,"sep") === 0) $extrahtml = "disabled=\"disabled\"";
				NewFormItem($f,$s,"mapto_$count","selectoption",$name,$mapto,$extrahtml);
			}
			NewFormItem($f,$s,"mapto_$count","selectend");
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
			//TODO action datas, show/hide these based on action menu

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
				<table border="0" cellpadding="3" cellspacing="0"><tr><td>
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
				</td><td>
					<?= button('Previous',"prevselect($count)") ?>
				</td><td>
					<?= button('Next',"nextselect($count)") ?>
				</td></tr></table>
				<div id="selectordata<?=$count?>">
				</div>
				</div>

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
			<tr ><td style="border-bottom: 1px dotted black; padding-left: 3px; <?= $import->skipheaderlines > $ci ? ' background: #cccccc;' : ' background: white;'?>"><?= empty($cel) ? "-" : escapehtml($cel) ?></td></tr>
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
<div>

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
