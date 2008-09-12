<?
// SHARED GUI CODE between F-Field, G-Field, and C-Field definitions


////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$USER->authorize('metadata')) {
	redirect('unauthorized.php');
}

////////////////////////////////////////////////////////////////////////////////
// Functions
////////////////////////////////////////////////////////////////////////////////


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
		QuickUpdate("update person p use index (ownership) set `$fieldnum`=NULL ");
	}
	if (ereg("^g[0-9]{2}$",$fieldnum)) {
		QuickUpdate("delete from groupdata where fieldnum=".substr($fieldnum, 1));
	}
	redirect();
}


switch ($DATATYPE) {
case "person" :
	$VALID_TYPES = array('text', 'reldate', 'multisearch', 'multisearch,language', 'multisearch,grade',
						 'text,firstname', 'text,lastname', 'grade', 'firstname', 'lastname', 'language',
						 'multisearch,school', 'school');
	$numfields = 20;
	$dt = "f%";
break;
case "group" :
	$VALID_TYPES = array('multisearch', 'multisearch,school', 'school');
	$numfields = 10;
	$dt = "g%";
break;
case "schedule" :
	$VALID_TYPES = array('multisearch', 'multisearch,staff', 'staff');
	$numfields = 10;
	$dt = "c%";
break;
}


$FIELDMAPS = DBFindMany("FieldMap", "from fieldmap where fieldnum like '".$dt."' order by fieldnum");
$availablefields = array();
for ($x = 1; $x <= $numfields; $x++)
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
			} else if (QuickQuery("select count(*) from fieldmap where name = '$cleanedname'")) {
				error("Please choose a unique field name. This one is already in use.");
			} else if (!in_array($type, $VALID_TYPES)) {
				error("The field type, $type, is not valid");
			} else if(count($FIELDMAPS) < 20){
				$newfield = new FieldMap();
				// Submit new item
				$specialtype = GetFormData($form, $section, "newfield_specialtype");
				$newfield->name = $cleanedname;
				switch ($DATATYPE) {
				case "person" :
					$temp = "f";
				break;
				case "group" :
					$temp = "g";
				break;
				case "schedule" :
					$temp = "c";
				break;
				}
				$newfield->fieldnum = $temp . GetFormData($form,$section,"newfield_fieldnum");
				$newfield->options = (GetFormData($form, $section, "newfield_searchable") ? 'searchable,' : '') .
										DBSafe(GetFormData($form, $section, "newfield_type") .
										($specialtype ? ',' . DBSafe($specialtype) : ''));

				if ($newfield->update()) {
					// Requery to get the newly inserted row
					$FIELDMAPS = DBFindMany("FieldMap", "from fieldmap where fieldnum like '".$dt."' order by fieldnum");
				} else {
					error("Uknown database error: unable to add new field data");
				}
			}
		} else { // Error check then submit changes
			foreach($FIELDMAPS as $field) {
				$fieldnum = $field->fieldnum;
				if ($fieldnum != FieldMap::getFirstNameField() &&
					$fieldnum != FieldMap::getLastNameField() &&
					$fieldnum != FieldMap::getLanguageField() &&
					$fieldnum != FieldMap::getSchoolField() &&
					$fieldnum != FieldMap::getGradeField() &&
					$fieldnum != FieldMap::getStaffField() )
					{
					$name = DBSafe(GetFormData($form, $section, "name_$fieldnum"));
					$type = DBSafe(GetFormData($form, $section, "type_$fieldnum"));
					$searchable = GetFormData($form, $section, "searchable_$fieldnum");

					// Check that new name contains only alphanumerics, underscores, and spaces
					$cleanedname = preg_replace('/[^\w ]/', '#', $name);
					PutFormData($form, $section, 'newfield_name', $cleanedname);
					if (!preg_match("/\w/", $cleanedname)) { // Find at least one alphanumeric or underscore character
						error("Please choose a field name that is at least one alphanumeric character long");
					} else if (!in_array($type, $VALID_TYPES)) {
						error("The field type, $type, is not valid");
					} else if ($name !== null || $type !== null || $searchable !== null) {
						$updatefield = DBFind("FieldMap", "from fieldmap where fieldnum = '$fieldnum'");
						$updatefield->name = $cleanedname;
						$updatefield->options = ($searchable ? 'searchable,' : '') . $type;
						$updatefield->update();
						// Requery to get the newly inserted row
						$FIELDMAPS = DBFindMany("FieldMap", "from fieldmap where fieldnum like '".$dt."' order by fieldnum");
					}
				} else {
					$name = DBSafe(GetFormData($form, $section, "name_$fieldnum"));
					$type = DBSafe(GetFormData($form, $section, "type_$fieldnum"));
					$cleanedname = preg_replace('/[^\w ]/', '#', DBSafe(GetFormData($form, $section, "name_$fieldnum")));
					PutFormData($form, $section, 'newfield_name', $cleanedname);
					if (!preg_match("/\w/", $cleanedname)) { // Find at least one alphanumeric or underscore character
						error("Please choose a field name that is at least one alphanumeric character long");
					} else if (!in_array($type, $VALID_TYPES)) {
						error("The field type, $type, is not valid");
					} else if ($name !== null || $type !== null || $searchable !== null) {
						$updatefield = DBFind("FieldMap", "from fieldmap where fieldnum = '$fieldnum'");
						$updatefield->name = $cleanedname;
						$updatefield->update();
						// Requery to get the newly inserted row
						$FIELDMAPS = DBFindMany("FieldMap", "from fieldmap where fieldnum like '".$dt."' order by fieldnum");
					}
				}
			}
			redirect('settings.php');
		}

		$reloadform = true;
	}
} else {
	$reloadform = true;
}

//load this after possibly saving a new field
$availablefields = array_diff($availablefields, QuickQueryList("select right(fieldnum,2) from fieldmap where fieldnum like '".$dt."'"));

if( $reloadform )
{
	ClearFormData($form);


	foreach($FIELDMAPS as $field) {
		$fieldnum = $field->fieldnum;
		$name = $field->name;
		$searchable = $field->isOptionEnabled('searchable');
		if ($field->isOptionEnabled('language')) {
			$type = 'language';
		} else if ($field->isOptionEnabled('school')) {
			$type = 'school';
		} else if ($field->isOptionEnabled('grade')) {
			$type = 'grade';
		} else if ($field->isOptionEnabled('staff')) {
			$type = 'staff';
		} else if ($field->isOptionEnabled('firstname')) {
			$type = 'firstname';
		} else if ($field->isOptionEnabled('lastname')) {
			$type = 'lastname';
		} else if ($field->isOptionEnabled('text')) {
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
	if(count($FIELDMAPS) < $numfields){
		PutFormData($form, $section, 'newfield_type', 'text', 'text');
		PutFormData($form, $section, 'newfield_name', '', 'text', 1, 20, false); // This item is only required on an add operation
		PutFormData($form, $section, 'newfield_type', 'text', 'text');
		PutFormData($form, $section, 'newfield_searchable', '1', 'bool');

		PutFormData($form,$section,"newfield_fieldnum","array",$availablefields);
	}
}



////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$PAGE = "admin:settings";

switch ($DATATYPE) {
case "person" :
$TITLE = "Field Definitions";
break;
case "group" :
$TITLE = "Group Field Definitions";
break;
case "schedule" :
$TITLE = "Enrollment Field Definitions";
break;
}

include_once("nav.inc.php");

NewForm($form);
buttons(submit($form, $section, 'Save'));
startWindow('Fields ' . help('DataManager_Fields'), 'padding: 3px;');
?>

<table cellpadding="3" cellspacing="1" class="list" width="100%">
	<tr class="listHeader">
		<th align="left">Field</th><th align="left">Name</th><th align="left">Type</th>
<?		if ($DATATYPE == "person") {
?>
		<th align="left">Searchable</th>
<?		}
?>
		<th align="left">Actions</th>
	</tr>
<?
	$alt = 0;


switch ($DATATYPE) {
case "person" :
	$types = array("Text" => 'text',
					"Date" => 'reldate',
					"List" => 'multisearch');

		if(!FieldMap::getName(FieldMap::getFirstNameField()))
			$types["First Name"] = 'text,firstname';
		if(!FieldMap::getName(FieldMap::getLastNameField()))
			$types["Last Name"] = 'text,lastname';
		if(!FieldMap::getName(FieldMap::getLanguageField()))
			$types["Language"] = 'multisearch,language';
		if(!FieldMap::getGradeField())
			$types["Grade"] = 'multisearch,grade';
		if(!FieldMap::getSchoolField())
			$types["School"] = 'multisearch,school';
break;
case "group" :
		$types = array("List" => 'multisearch');

		if(!FieldMap::getSchoolField())
			$types["School"] = 'multisearch,school';
break;
case "schedule" :
		$types = array("List" => 'multisearch');

		if(!FieldMap::getStaffField())
			$types["Staff ID"] = 'multisearch,staff';
break;
}

	if (count($FIELDMAPS) > 0) {

		foreach ($FIELDMAPS as $field) {
			echo ++$alt % 2 ? '<tr>' : '<tr class="listAlt">';
?>
			<td>
<?
				$fieldnum = $field->fieldnum;
				$num = substr($fieldnum, 1) + 0;
				echo $num;
?>
			</td>
<?

switch ($DATATYPE) {
case "person" :
	$datapage = "persondatamanager.php";
break;
case "group" :
	$datapage = "groupdatamanager.php";
break;
case "schedule" :
	$datapage = "scheduledatamanager.php";
break;
}

			// These 5 items are special cases
			if ($fieldnum != $field->getFirstNameField() &&
				$fieldnum != $field->getLastNameField() &&
				$fieldnum != $field->getLanguageField() &&
				$fieldnum != $field->getGradeField() &&
				$fieldnum != $field->getSchoolField() &&
				$fieldnum != $field->getStaffField() ) {
?>
				<td><? NewFormItem($form, $section, "name_$fieldnum", 'text', '20'); ?></td>
				<td>
<?
					NewFormItem($form, $section, 'type_' . $fieldnum, 'selectstart', '', 'id=type_'.$fieldnum);
					foreach($types as $text => $type)
						NewFormItem($form, $section, 'type_' . $fieldnum, 'selectoption', $text, $type);
					NewFormItem($form, $section, 'type_' . $fieldnum, 'selectend');
?>
				</td>
<?				switch ($DATATYPE) {
				case "person" :
?>
					<td>
					<?NewFormItem($form, $section, 'searchable_' . $fieldnum, 'checkbox');?>
					</td>
					<td><a href='<?=$datapage?>?delete=<?=$field->id?>' onclick="return confirmDelete();">Delete</a>&nbsp;|&nbsp;<a href='<?=$datapage?>?clear=<?=$fieldnum?>' onclick="return confirm('Are you sure you want to clear (erase) all data for this field?');">Clear&nbsp;data</a></td>
<?
				break;
				case "group" :
?>
					<td><a href='<?=$datapage?>?delete=<?=$field->id?>' onclick="return confirmDelete();">Delete</a>&nbsp;|&nbsp;<a href='<?=$datapage?>?clear=<?=$fieldnum?>' onclick="return confirm('Are you sure you want to clear (erase) all data for this field?');">Clear&nbsp;data</a></td>
<?
				break;
				case "schedule" :
?>
					<td><a href='<?=$datapage?>?delete=<?=$field->id?>' onclick="return confirmDelete();">Delete</a></td>
<?
				break;
				}
			} else {
?>
				<td><? NewFormItem($form, $section, "name_$fieldnum", 'text', '20');?></td>
				<td><?=ucfirst(GetFormData($form, $section, "type_$fieldnum")); ?></td>

<?				switch ($DATATYPE) {
				case "person" :
?>
					<td>
					<?NewFormItem($form, $section, 'searchable_' . $fieldnum, 'checkbox', null, null, 'DISABLED');?>
					</td>
					<td><a href='<?=$datapage?>?delete=<?=$field->id?>' onclick="return confirmDelete();">Delete</a>&nbsp;|&nbsp;<a href='<?=$datapage?>?clear=<?=$fieldnum?>' onclick="return confirm('Are you sure you want to clear (erase) all data for this field?');">Clear&nbsp;data</a></td>
<?
				break;
				case "group" :
?>
					<td><a href='<?=$datapage?>?delete=<?=$field->id?>' onclick="return confirmDelete();">Delete</a>&nbsp;|&nbsp;<a href='<?=$datapage?>?clear=<?=$fieldnum?>' onclick="return confirm('Are you sure you want to clear (erase) all data for this field?');">Clear&nbsp;data</a></td>
<?
				break;
				case "schedule" :
					// staffID field is unremovable
				break;
				}
			}
?>
		</tr>
<?
		}
	}

	// Print extra row for adding new items
	// only if they have more fields to use
	if(count($FIELDMAPS) < $numfields){
		echo ++$alt % 2 ? '<tr>' : '<tr class="listAlt">';
?>
		<td>
<?
		NewFormItem($form, $section, 'newfield_fieldnum', 'selectstart');
		foreach ($availablefields as $field)
			NewFormItem($form, $section, 'newfield_fieldnum', 'selectoption', $field , $field);
		NewFormItem($form, $section, 'newfield_fieldnum', 'selectend');
?>
		</td>
		<td>
<?
		NewFormItem($form, $section, 'newfield_name', 'text',20, '', 'id=newfield');
?>
		</td>
		<td>
<?
		NewFormItem($form, $section, 'newfield_type', 'selectstart', '', '', 'id=newfield_type');
		foreach($types as $text => $type)
			NewFormItem($form, $section, 'newfield_type', 'selectoption', $text, $type);
		NewFormItem($form, $section, 'newfield_type', 'selectend');
?>
		</td>
<?		if ($DATATYPE == "person") {
?>
		<td><? NewFormItem($form, $section, 'newfield_searchable', 'checkbox', '', '', 'id=newfield_searchable'); ?> </td>
<?		}
?>
		<td><? echo submit($form, 'add', 'Add'); ?></td>
	</tr>
<?
	}
?>
</table>
<?
endWindow();
buttons();
EndForm();

include_once("navbottom.inc.php");
