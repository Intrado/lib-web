<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
include_once("inc/common.inc.php");
include_once("obj/Schedule.obj.php");
require_once("obj/Import.obj.php");
require_once("obj/ImportField.obj.php");
require_once("obj/Schedule.obj.php");
require_once("obj/FieldMap.obj.php");
include_once("inc/form.inc.php");
include_once("inc/html.inc.php");
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
	if (customerOwns("import",$id)) {
		$_SESSION['importid'] = $id;
		$_SESSION['importcols'] = NULL;
		$_SESSION['importviewrows'] = 10;
	}
	redirect();
}

if (!$_SESSION['importid'])
	redirect("tasks.php");

if (isset($_GET['previewrows'])) {
	$_SESSION['importviewrows'] = $_GET['previewrows'] + 0;
	$_SESSION['importviewrows'] = max(5,$_SESSION['importviewrows']);
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

//make a menu of all available fields

$fieldmaps = DBFindMany("FieldMap","from fieldmap order by fieldnum");

$maptofields = array();
$maptofields[""] = "- Unmapped -";
$maptofields["key"] = "Unique ID";
//F fields
foreach ($fieldmaps as $fieldmap)
	$maptofields[$fieldmap->fieldnum] = $fieldmap->name;

//phones, emails, SMS
$maxphones = getSystemSetting("maxphones",4);
for ($x = 0; $x < $maxphones; $x++)
	$maptofields["p$x"] = "Phone " . ($x + 1);
if (getSystemSetting('_hassms', false)) {
	$maxsms = getSystemSetting("maxsms",2);
	for ($x = 0; $x < $maxsms; $x++)
		$maptofields["s$x"] = "SMS " . ($x + 1);
}
$maxemails = getSystemSetting("maxemails",2);
for ($x = 0; $x < $maxemails; $x++)
	$maptofields["e$x"] = "Email " . ($x + 1);
//address fields
$maptofields["a6"] = "Address ATTN";
$maptofields["a1"] = "Address 1";
$maptofields["a2"] = "Address 2";
$maptofields["a3"] = "City";
$maptofields["a4"] = "State";
$maptofields["a5"] = "Zip";


$actions = array('copy' => "Copy",
				'staticvalue' => "Static Value",
				'curdate' => "Current Date",
				'currency' => "Currency",
				'date' => "Date",
				'lookup' => "Data Lookup");
//scan the file
$importfile = secure_tmpname("taskmap",".csv");
file_put_contents($importfile,$import->download());

$fp = @fopen($importfile , "r");
$colcount = 0;
if ($fp && filesize($importfile) > 0 ) {
	$count = $previewrows;
	$importdata = array();
	while (($row = fgetcsv($fp,4096)) !== FALSE && $count) {
		if (count($row) == 1 && trim($row[0]) === "")
			continue;
		for ($x = 0; $x < count($row); $x++)
			$importdata[$x][] = $row[$x] ;
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

@unlink($importfile);


$mapfromcols = range(0,$colcount-1);
array_unshift($mapfromcols,"");


/****************** main message section ******************/
$f = "taskmap";
$s = "main";
$reloadform = false;

if (CheckFormSubmit($f, $s) || CheckFormSubmit($f, 'add') || CheckFormSubmit($f, 'delete') !== false) {
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

			if (CheckFormSubmit($f, $s))
				redirect("tasks.php");
			else
				redirect();
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
}


////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$PAGE = "admin:taskmanager";
$TITLE = "Import Field Mapping: " . htmlentities($import->name);
$DESCRIPTION = count($usedcols) . " of $colcount input columns mapped";

include_once("nav.inc.php");

NewForm($f);
buttons(($noimportdata ? button('Done',NULL,'tasks.php') : submit($f, $s)));
startWindow('Field Mapping');
if ($noimportdata) { ?>
			<br><h3>No import data could be found. Please check that a non empty file has been uploaded.</h3><br>
<? } else { ?>

<div class="hoverlinks" style="margin: 5px;">
Preview rows:
<select onchange="if(confirm('Warning: changing the number of preview rows will undo any changes made on the page.\nClick cancel if you need to save your changes.')) {location.href='?previewrows=' + this.value;}"
<?
	for ($x = 5; $x <= 50; $x += 5) {
?>
		<option value="<?=$x?>" <?= $x == $_SESSION['importviewrows'] ? "selected" : "" ?>><?=$x?></option>
<?
	}
?>
</select>
<br>
<a href="#" onclick="hide('viewdata'); show('datamapping'); return undefined;">Edit Mapping</a> |
<a href="#" onclick="hide('datamapping'); show('viewdata'); return undefined;">View Data</a>
</div>

<div id="datamapping">
<table width="100%" cellpadding="3" cellspacing="1" class="list">
	<tr class="listHeader">
		<th align="left">Field</th><th align="left">Translator</th><th align="left" >Translator&nbsp;Options</th><th align="left">File Data</th><th align="left">Actions</th>
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
			foreach ($maptofields as $mapto => $name)
				NewFormItem($f,$s,"mapto_$count","selectoption",$name,$mapto);
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
			<tr ><td style="border-bottom: 1px dotted black; padding-left: 3px; <?= $import->skipheaderlines > $ci ? ' background: #cccccc;' : ' background: white;'?>"><?= $cel == "" ? "&nbsp;" : htmlentities($cel) ?></td></tr>
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
	hide("actiondata_" + num + "_lookup");
	hide("actiondata_" + num + "_staticvalue");
	hide("actiondata_" + num + "_date");

	show("actiondata_" + num + "_" + newaction);

	var select = new getObj("select_" + num).obj;

	if (newaction == 'staticvalue' || newaction == 'curdate') {
		hide("filedata_" + num);
	} else {
		show("filedata_" + num);
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