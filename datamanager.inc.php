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

function cleanedname($name) {
	// alphanumeric, space, underscore, pound (replace invalid chars with pound)
	return trim(preg_replace('/[^a-zA-Z0-9 _#]/', '#', $name));
}
function validate($name, $type, $allnamessofar) {
	global $VALID_TYPES;
	$isvalid = true;
	$cleanedname = cleanedname($name);
	if (strlen($cleanedname) < 1) {
		error("Please choose a field name that is at least one character long (alphanumeric, space, underscore, pound)");
		$isvalid = false;
	} else if (array_key_exists(strtolower($cleanedname), $allnamessofar)) {
		error("Please choose a unique field name. '$cleanedname' is already in use");
		$isvalid = false;
	} else if (!in_array($type, $VALID_TYPES)) {
		error("The field type, $type, is not valid");
		$isvalid = false;
	}
	return $isvalid;
}

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

if (isset($_GET['delete'])) {
	$id = DBSafe($_GET['delete']);
	$fm = new FieldMap($id);
	if (!$fm->isOptionEnabled('firstname') &&
		!$fm->isOptionEnabled('lastname') &&
		!$fm->isOptionEnabled('language') &&
		!$fm->isOptionEnabled('staff')) {
		// do not destroy required fields

		$fm->destroy();
	}
	redirect();
}

if (isset($_GET['clear'])) {
	$fieldnum = DBSafe($_GET['clear']);
	if ($fieldnum === FieldMap::getFirstNameField() ||
		$fieldnum === FieldMap::getLastNameField() ||
		$fieldnum === FieldMap::getLanguageField() ||
		$fieldnum === FieldMap::getStaffField()) {
		// do not clear data of required fields
		redirect();
	}

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
	$VALID_TYPES = array('text', 'reldate', 'multisearch', 'numeric', 'multisearch,language', 'multisearch,grade',
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
	$VALID_TYPES = array('multisearch', 'numeric', 'multisearch,staff', 'staff');
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

if (CheckFormSubmit($form, $section) || CheckFormSubmit($form, 'add')) {
	//check to see if formdata is valid
	if (CheckFormInvalid($form)) {
		error('Form was edited in another window, reloading data');
		$reloadform = true;
	} else {
		MergeSectionFormData($form, $section);
		if (CheckFormSection($form, $section)) {
			error('There was a problem trying to save your changes', 'Please verify that all required field information has been entered properly');
		} else {
			$isvalid = true; // are the fields all valid to save
			if (CheckFormSubmit($form, 'add') && strlen(cleanedname(DBSafe(GetFormData($form, $section, "newfield_name")))) == 0) {
				error("Please choose a field name that is at least one character long (alphanumeric, space, underscore, pound)");
				$isvalid = false;
			}

			// build list of all field names to check for duplicates (from other field types, plus those being edited)
			$othernames = QuickQueryList("select name from fieldmap where fieldnum not like '$dt'");
			$allnamessofar = array();
			foreach ($othernames as $othername) {
				$allnamessofar[strtolower($othername)] = $othername;
			}

			// if there is a new field to add, validate it
			$isadd = false;
			$name = DBSafe(GetFormData($form, $section, "newfield_name"));
			if ($isvalid && $name != "") {
				$isadd = true;
				$type = DBSafe(GetFormData($form, $section, 'newfield_type'));
				$isvalid = validate($name, $type, $allnamessofar);
				$allnamessofar[strtolower(cleanedname($name))] = $name;
			}

			// for each existing field, check for any modified values
			if ($isvalid) foreach ($FIELDMAPS as $field) {
				$fieldnum = $field->fieldnum;
				$name = DBSafe(GetFormData($form, $section, "name_$fieldnum"));
				$type = DBSafe(GetFormData($form, $section, "type_$fieldnum"));
				$isvalid = validate($name, $type, $allnamessofar);
				if (!$isvalid) break;
				$allnamessofar[strtolower(cleanedname($name))] = $name;
			}

			// if everything is valid, then save the changes
			if ($isvalid) {
				if ($isadd) {
					$newfield = new FieldMap();
					// Submit new item
					$newfield->name = cleanedname(DBSafe(GetFormData($form, $section, "newfield_name")));
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
					if (GetFormData($form, $section, "newfield_searchable"))
						$newfield->addOption('searchable');
					$newfield->addOption(GetFormData($form, $section, "newfield_type"));
					$newfield->update();
				}

				// update each existing field
				foreach($FIELDMAPS as $field) {
					$fieldnum = $field->fieldnum;
					$name = DBSafe(GetFormData($form, $section, "name_$fieldnum"));
					$type = DBSafe(GetFormData($form, $section, "type_$fieldnum"));
					$searchable = GetFormData($form, $section, "searchable_$fieldnum");

					if ($name !== null || $type !== null || $searchable !== null) {
						$updatefield = DBFind("FieldMap", "from fieldmap where fieldnum = '$fieldnum'");
						$updatefield->name = cleanedname($name);
						if ($fieldnum != FieldMap::getFirstNameField() &&
							$fieldnum != FieldMap::getLastNameField() &&
							$fieldnum != FieldMap::getLanguageField() &&
							$fieldnum != FieldMap::getSchoolField() &&
							$fieldnum != FieldMap::getGradeField() &&
							$fieldnum != FieldMap::getStaffField() ) {

							// only update options for non-specialfield
							if ($type !== null) $updatefield->updateFieldType($type);
							
							if ($searchable)
								$updatefield->addOption('searchable');
							else
								$updatefield->removeOption('searchable');
						}
						$updatefield->update();
					}
				}

				// redraw or redirect
				if ($isadd) {
					$FIELDMAPS = DBFindMany("FieldMap", "from fieldmap where fieldnum like '".$dt."' order by fieldnum");
					$reloadform = true;
				} else {
					redirect('settings.php');
				}
			}
		}
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
		} else if ($field->isOptionEnabled('numeric')) {
			$type = 'numeric';
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
$hover = "DataManager_Fields";
break;
case "group" :
$TITLE = "Group Field Definitions";
$hover = "DataManager_GFields";
break;
case "schedule" :
$TITLE = "Enrollment Field Definitions";
$hover = "DataManager_CFields";
break;
}

include_once("nav.inc.php");

NewForm($form);
buttons(submit($form, $section, 'Save'));
startWindow('Fields ' . help($hover), 'padding: 3px;');
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
					"List" => 'multisearch', 
					"Numeric" => 'numeric');

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
					<td><?= action_links(
							action_link(_L("Delete"),"cross","$datapage?delete=$field->id","return confirmDelete();"),
							action_link(_L("Clear Data"),"lightning","$datapage?clear=$fieldnum","return confirm('Are you sure you want to clear (erase) all data for this field?');")
							) ?></td>
<?
				break;
				case "group" :
?>
					<td><?= action_links(
							action_link(_L("Delete"),"cross","$datapage?delete=$field->id","return confirmDelete();"),
							action_link(_L("Clear Data"),"lightning","$datapage?clear=$fieldnum","return confirm('Are you sure you want to clear (erase) all data for this field?');")
							) ?></td>
<?
				break;
				case "schedule" :
?>
					<td><?= action_links(
							action_link(_L("Delete"),"cross","$datapage?delete=$field->id","return confirmDelete();")
							) ?></td>
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
<?
					$issearch = 'DISABLED';
					// firstname, lastname, language are always searchable, so checkbox disabled
					if ($fieldnum != $field->getFirstNameField() &&
						$fieldnum != $field->getLastNameField() &&
						$fieldnum != $field->getLanguageField()) {
							$issearch = '';
						}
					NewFormItem($form, $section, 'searchable_' . $fieldnum, 'checkbox', null, null, $issearch);
?>
					</td>
					<td>
<?
					// firstname, lastname, language are unremovable
					if ($fieldnum != $field->getFirstNameField() &&
						$fieldnum != $field->getLastNameField() &&
						$fieldnum != $field->getLanguageField()) {
?>
					<?= action_links(
							action_link(_L("Delete"),"cross","$datapage?delete=$field->id","return confirmDelete();"),
							action_link(_L("Clear Data"),"lightning","$datapage?clear=$fieldnum","return confirm('Are you sure you want to clear (erase) all data for this field?');")
							) ?>
<?
					}
					?></td><?
				break;
				case "group" :
?>
					<td><?= action_links(
							action_link(_L("Delete"),"cross","$datapage?delete=$field->id","return confirmDelete();"),
							action_link(_L("Clear Data"),"lightning","$datapage?clear=$fieldnum","return confirm('Are you sure you want to clear (erase) all data for this field?');")
							) ?></td>
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
