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
	$id = DBSafe($_GET['id']);
	if (customerOwns("import",$id)) {
		$_SESSION['importid'] = $id;
		$_SESSION['importcols'] = NULL;
	}
	redirect();
}


/****************** main message section ******************/
$form = "taskmap";
$section = "main";
$reloadform = false;

if(CheckFormSubmit($form, $section))
{
	//check to see if formdata is valid
	if(CheckFormInvalid($form))
	{
		error('Form was edited in another window, reloading data');
		$reloadform = true;
	}
	else
	{
		MergeSectionFormData($form, $section);
		if( CheckFormSection($form, $section) )
		{
			error('There was a problem trying to save your changes', 'Please verify that all required field information has been entered properly');
		} else if (CheckFormSubmit($form, $section)) {

			for ($x = 0; $x < $_SESSION['importcols']; $x++) {
				$mapto = GetFormData($form,$section,'mapto_' . $x);


				$importfield = DBFind("ImportField","from importfield where mapfrom=$x and importid=" . $_SESSION['importid']);
				if ($importfield && $mapto == "") {
					$importfield->destroy();
				} else if ($importfield && $mapto != "") {
					$importfield->mapto = $mapto;
					$importfield->update();
				} else if ($mapto != "") {
					$importfield = new ImportField();
					$importfield->importid = $_SESSION['importid'];
					$importfield->mapto = $mapto;
					$importfield->mapfrom = $x;
					$importfield->create();
				}
			}

			//get rid of any mappings that are beyond the scope of the current file
			QuickUpdate("delete from importfield where importid='" . $_SESSION['importid'] . "' and mapfrom >= $x");

			//$reloadform = true;
			redirect("tasks.php");
		}
	}
} else {
	$reloadform = true;
}


$id = $_SESSION['importid'];
if (!$id || $id == 'new') {
	$IMPORT = new Import();
} else {
	$IMPORT = new Import($id);
}
if ($IMPORT->id)
	$IMPORTFIELDS = DBFindMany("importfield","from importfield where importid=" . $_SESSION['importid']);
else
	$IMPORTFIELDS = array();




//make a menu of all available fields

$fieldmaps = DBFindMany("FieldMap","from fieldmap order by fieldnum");

$maptofields = array();

$maptofields[""] = "- Unmapped -";

$maptofields["key"] = "Unique ID";
foreach ($fieldmaps as $fieldmap)
	$maptofields[$fieldmap->fieldnum] = $fieldmap->name;


if (!$maxphones = getSystemSetting("maxphones"))
	$maxphones = 4;

for ($x = 0; $x < $maxphones; $x++) {
	if ($x == 0) {
		$maptofields["p0"] = "Phone";
		if ($SETTINGS['feature']['has_sms'] && getSystemSetting('_hassms', false)) {
			$maptofields["s0"] = "Phone (SMS)";
		}
	} else {
		$maptofields["p$x"] = "Phone " . ($x + 1);
		if ($SETTINGS['feature']['has_sms']&& getSystemSetting('_hassms', false)) {
			$maptofields["s$x"] = "Phone " . ($x + 1) . " (SMS)";
		}
	}
}

if (!$maxemails = getSystemSetting("maxemails"))
	$maxemails = 2;

for ($x = 0; $x < $maxemails; $x++) {
	if ($x == 0)
		$maptofields["e0"] = "Email";
	else
		$maptofields["e$x"] = "Email " . ($x + 1);
}

$maptofields["a6"] = "Address ATTN";
$maptofields["a1"] = "Address 1";
$maptofields["a2"] = "Address 2";
$maptofields["a3"] = "City";
$maptofields["a4"] = "State";
$maptofields["a5"] = "Zip";


//scan the file

$importfile = secure_tmpname("taskmap",".csv");
file_put_contents($importfile,$IMPORT->download());

$fp = @fopen($importfile , "r");
$colcount = 0;
if ($fp && filesize($importfile) > 0 ) {
	$count = 10;
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


if( $reloadform )
{
	ClearFormData($form);

	//set defaults to unmapped
	for ($x = 0; $x < $colcount; $x++) {
		PutFormData($form,$section, "mapto_" . $x, "");
	}

	foreach ($IMPORTFIELDS as $importfield) {
		PutFormData($form,$section, "mapto_" . $importfield->mapfrom, $importfield->mapto);
	}
}


////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$PAGE = "admin:taskmanager";
$TITLE = "Import Field Mapping: " . ($IMPORT != null ? $IMPORT->name : 'New Task');

include_once("nav.inc.php");

NewForm($form);
buttons(($noimportdata ? button('Done',NULL,'tasks.php') : submit($form, $section)));
startWindow('Field Mapping');
?>
<br>
<? if ($noimportdata) { ?>
			<br><h3>No import data could be found. Please check that a non empty file has been uploaded.</h3><br>
<? } else { ?>

<?
		//spit out a column and mapto menu for each col in the input file
		for ($x = 0; $x < $colcount; $x++) {
?>
			<div style="float: left; border: 1px dotted black; margin-left: 10px; margin-bottom: 10px; padding: 3px; "><table border="0" cellpadding="2" cellspacing="0" border="2">
				<tr><th>Col <?= $x ?><br>
<?
			NewFormItem($form, $section, 'mapto_' . $x, 'selectstart');

			foreach ($maptofields as $mapto => $name) {
				NewFormItem($form, $section, 'mapto_' . $x, 'selectoption', $name, $mapto);
			}
			NewFormItem($form, $section, 'mapto_' . $x, 'selectend');
?>
				</th></tr>
<?
			for ($ci = 0; $ci < 10; $ci++) {
				$cel = isset($importdata[$x][$ci]) ? $importdata[$x][$ci] : "";
				echo "</tr><td>" . ($cel == "" ? "&nbsp;" : htmlentities($cel)) . "</td></tr>";
			}
?>
			</table></div>
<?
		}
?>

<?
}
endWindow();
buttons();
EndForm();

include_once("navbottom.inc.php");

?>