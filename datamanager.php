<?

////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
include_once("inc/common.inc.php");
include_once("obj/Job.obj.php");
include_once("obj/Schedule.obj.php");
include_once("inc/form.inc.php");
include_once("inc/html.inc.php");
require_once("inc/table.inc.php");
require_once("inc/utils.inc.php");
require_once("inc/securityhelper.inc.php");
require_once("inc/formatters.inc.php");
require_once("obj/FieldMap.obj.php");


////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$USER->authorize('metadata')) {
	redirect('unauthorized.php');
}

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

if (isset($_GET['delete'])) {
	$id = DBSafe($_GET['delete']);
	if (customerOwns("fieldmap",$id)) {
		$fm = new FieldMap($id);
		$fm->destroy();
	}
	redirect();
}

if (isset($_GET['clear'])) {
	$fieldnum = DBSafe($_GET['clear']);
	if (ereg("^f[0-9]{2}$",$fieldnum)) {
		QuickUpdate("update persondata pd inner join person p use index (ownership) on (pd.personid = p.id and p.customerid=$USER->customerid and p.userid is NULL) set `$fieldnum`=NULL ");
	}

	redirect();
}


$VALID_TYPES = array('text', 'reldate', 'multisearch');
$FIELDMAPS = DBFindMany("FieldMap", "from fieldmap where customerid = $USER->customerid order by fieldnum");
$availablefields = array();
for ($x = 1; $x <= 20; $x++)
	$availablefields[] = sprintf("%02d",$x);



/****************** main message section ******************/
$form = "datamanager";
$section = "main";
$reloadform = false;

if(CheckFormSubmit($form, $section) || CheckFormSubmit($form, 'add'))
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
		} else if (CheckFormSubmit($form, 'add')) { // The add button was chosen
			// Check that new name contains only alphanumerics, underscores, and spaces
			$cleanedname = DBSafe(preg_replace('/[^\w\ \-\.]/', '#', GetFormData($form, $section, "newfield_name")));
			$type = DBSafe(GetFormData($form, $section, 'newfield_type'));
			PutFormData($form, $section, 'newfield_name', $cleanedname);
			if (!preg_match("/[a-zA-Z0-9]/", $cleanedname)) { // Find at least one alphanumeric character
				error("Please choose a field name that is at least one alphanumeric character long");
			} else if (QuickQuery("select * from fieldmap where name = '$cleanedname' and customerid = '$USER->customerid'")) {
				error("Please choose a unique field name. This one is already in use.");
			} else if (!in_array($type, $VALID_TYPES)) {
				error("The field type, $type, is not valid");
			} else {
				$newfield = new FieldMap();
				// Submit new item

				$newfield->name = $cleanedname;
				$newfield->customerid = $USER->customerid;
				$newfield->fieldnum = "f" . GetFormData($form,$section,"newfield_fieldnum");
				$newfield->options = (GetFormData($form, $section, "newfield_searchable") ? 'searchable,' : '') .
										DBSafe(GetFormData($form, $section, "newfield_type"));
				if ($newfield->update()) {
					// Requery to get the newly inserted row
					$FIELDMAPS = DBFindMany("FieldMap", "from fieldmap where customerid = $USER->customerid order by fieldnum");
				} else {
					error("Uknown database error: unable to add new field data");
				}
			}
		} else { // Error check then submit changes
			foreach($FIELDMAPS as $field) {
				$fieldnum = $field->fieldnum;
				if ($fieldnum != FieldMap::getFirstNameField() &&
					$fieldnum != FieldMap::getLastNameField() &&
					$fieldnum != FieldMap::getLanguageField()) {
					$name = DBSafe(GetFormData($form, $section, "name_$fieldnum"));
					$type = DBSafe(GetFormData($form, $section, "type_$fieldnum"));
					$searchable = GetFormData($form, $section, "searchable_$fieldnum");

					// Check that new name contains only alphanumerics, underscores, and spaces
					$cleanedname = preg_replace('/[^\w ]/', '#', DBSafe(GetFormData($form, $section, "name_$fieldnum")));
					PutFormData($form, $section, 'newfield_name', $cleanedname);
					if (!preg_match("/\w/", $cleanedname)) { // Find at least one alphanumeric or underscore character
						error("Please choose a field name that is at least one alphanumeric character long");
					} else if (!in_array($type, $VALID_TYPES)) {
						error("The field type, $type, is not valid");
					} else if ($name !== null || $type !== null || $searchable !== null) {
						$updatefield = DBFind("FieldMap", "from fieldmap where customerid = $USER->customerid and fieldnum = '$fieldnum'");
						$updatefield->name = $cleanedname;
						$updatefield->options = ($searchable ? 'searchable,' : '') . $type;
						$updatefield->update();
						// Requery to get the newly inserted row
						$FIELDMAPS = DBFindMany("FieldMap", "from fieldmap where customerid = $USER->customerid order by fieldnum");
					}
				}
			}
		}

		$reloadform = true;
	}
} else {
	$reloadform = true;
}

//load this after possibly saving a new field
$availablefields = array_diff($availablefields, QuickQueryList("select right(fieldnum,2) from fieldmap where customerid = $USER->customerid"));

if( $reloadform )
{
	ClearFormData($form);
	PutFormData($form, $section, 'newfield_type', 'text', 'text');

	foreach($FIELDMAPS as $field) {
		$fieldnum = $field->fieldnum;
		$name = $field->name;
		$searchable = $field->isOptionEnabled('searchable');
		if ($field->isOptionEnabled('text')) {
			$type = 'text';
		} else if ($field->isOptionEnabled('reldate')) {
			$type = 'reldate';
		} else if ($field->isOptionEnabled('multisearch')) {
			$type = 'multisearch';
		} else {
			$type = 'text';
		}

		PutFormData($form, $section, 'name_' . $fieldnum, $name, 'text');
		PutFormData($form, $section, 'type_' . $fieldnum, $type, 'text');
		PutFormData($form, $section, 'searchable_' . $fieldnum, $searchable, 'bool');
	}

	PutFormData($form, $section, 'newfield_name', '', 'text', 1, 20, false); // This item is only required on an add operation
	PutFormData($form, $section, 'newfield_type', 'text', 'text');
	PutFormData($form, $section, 'newfield_searchable', '1', 'bool');


	PutFormData($form,$section,"newfield_fieldnum","array",$availablefields);
}



////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$PAGE = "admin:datamanager";
$TITLE = "Metadata Manager";

include_once("nav.inc.php");

NewForm($form);
buttons(submit($form, $section));
startWindow('Fields ' . help('DataManager_Fields', NULL, 'blue'), 'padding: 3px;');
?>

<table width="50%" cellpadding="3" cellspacing="1" class="list">
<tr class="listHeader">
	<th></th><th>Name</th><th>Type</th><th>Searchable</th><th></th>
</tr>
<?
$alt = 0;
if (count($FIELDMAPS) > 0) {
	foreach ($FIELDMAPS as $field) {
		echo ++$alt % 2 ? '<tr>' : '<tr class="listAlt">';
		echo "\n<td>";
		$fieldnum = $field->fieldnum;
		$num = substr($fieldnum, 1) + 0;
		echo $num;
		echo "</td>";
		// These 3 items are read-only so display them in the else block
		if ($fieldnum != $field->getFirstNameField() &&
			$fieldnum != $field->getLastNameField() &&
			$fieldnum != $field->getLanguageField()) {
			echo "\n<td>";
			NewFormItem($form, $section, 'name_' . $fieldnum, 'text',20);
			echo "</td>";
			echo "\n<td>";
			NewFormItem($form, $section, 'type_' . $fieldnum, 'selectstart');
			NewFormItem($form, $section, 'type_' . $fieldnum, 'selectoption', 'Text', 'text');
			NewFormItem($form, $section, 'type_' . $fieldnum, 'selectoption', 'Date', 'reldate');
			NewFormItem($form, $section, 'type_' . $fieldnum, 'selectoption', 'List', 'multisearch');
			NewFormItem($form, $section, 'type_' . $fieldnum, 'selectend');
			echo "\n</td>";
			echo "\n<td>";
			NewFormItem($form, $section, 'searchable_' . $fieldnum, 'checkbox');
			echo "</td>";
			echo "\n<td>";
			echo "<a href='datamanager.php?delete=$field->id' onclick=\"return confirmDelete();\">Delete</a>";
			echo "&nbsp;|&nbsp;<a href='datamanager.php?clear=$fieldnum' onclick=\"return confirm('Are you sure you want to clear (erase) all data for this field?');\">Clear&nbsp;data</a>";
			echo "</td>";
		} else {
			echo "\n<td>";
			echo GetFormData($form, $section, "name_$fieldnum");
			echo "</td>";
			echo "\n<td>";

			$type = GetFormData($form, $section, "type_$fieldnum");
			switch ($type) {
				case 'multisearch':
					$type = 'List';
					break;
				case 'reldate':
					$type = 'Date';
					break;
				case 'text':
					$type = 'Text';
				default:
					$type = ucfirst($type);
			}

			echo $type;
			echo "\n</td>";
			echo "</td>";
			echo "\n<td>";
			NewFormItem($form, $section, 'searchable_' . $fieldnum, 'checkbox', null, null, 'DISABLED');
			echo "</td>";
			echo "\n<td>";
			echo "&nbsp";
			echo "</td>";
		}
	}

		echo "</tr>\n";
}

// Print extra row for adding new items
print "\n<tr>";
print "\n<td>";

NewFormItem($form, $section, 'newfield_fieldnum', 'selectstart');
foreach ($availablefields as $field)
	NewFormItem($form, $section, 'newfield_fieldnum', 'selectoption', $field , $field);
NewFormItem($form, $section, 'newfield_fieldnum', 'selectend');

print "</td>";
print "\n<td>";
NewFormItem($form, $section, 'newfield_name', 'text',20);
print "\n</td>";
print "\n<td>";
NewFormItem($form, $section, 'newfield_type', 'selectstart');
NewFormItem($form, $section, 'newfield_type', 'selectoption', 'Text', 'text');
NewFormItem($form, $section, 'newfield_type', 'selectoption', 'Date', 'reldate');
NewFormItem($form, $section, 'newfield_type', 'selectoption', 'List', 'multisearch');
NewFormItem($form, $section, 'newfield_type', 'selectend');
print "\n</td>";
print "\n<td>";
NewFormItem($form, $section, 'newfield_searchable', 'checkbox');
print "</td>";
print "\n<td>";
print submit($form, 'add', 'add', 'add');
print "\n</td>";
print "\n</tr>";

print "\n</table>";
endWindow();
buttons();
EndForm();

include_once("navbottom.inc.php");

?>