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
// Functions
////////////////////////////////////////////////////////////////////////////////

function newfield($f, $s, $name){

	$fieldlist = array("First Name" => FieldMap::getFirstNameField(), 
					"Last Name" => FieldMap::getLastNameField(), 
					"Language" => FieldMap::getLanguageField(), 
					"School" => FieldMap::getSchoolField(), 
					"Grade" => FieldMap::getGradeField());
	foreach($fieldlist as $index => $value){
		if($value) $count++;
	}
	if($count == count($fieldlist)) return true;

	NewFormItem($f, $s, 'predef_name', 'selectstart', NULL, NULL, "onchange=\"predef_name(this.value)\"");
	NewFormItem($f, $s, 'predef_name', 'selectoption'," -- Select a Category -- ", "");
	$count = count($fieldlist);
	foreach($fieldlist as $index => $value){
		if(!$value){
			NewFormItem($f, $s, 'predef_name', 'selectoption', $index, $index);
		}
	}
	NewFormItem($f, $s, 'predef_name', 'selectend');
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
		QuickUpdate("update person p use index (ownership) set `$fieldnum`=NULL ");
	}

	redirect();
}


$VALID_TYPES = array('text', 'reldate', 'multisearch', 'multisearch,language', 'multisearch,grade',
						'multisearch,school', 'text,firstname',  'text,lastname');
$FIELDMAPS = DBFindMany("FieldMap", "from fieldmap order by fieldnum");
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
			} else if (QuickQuery("select * from fieldmap where name = '$cleanedname'")) {
				error("Please choose a unique field name. This one is already in use.");
			} else if (!in_array($type, $VALID_TYPES)) {
				error("The field type, $type, is not valid");
			} else {
				$newfield = new FieldMap();
				// Submit new item
				$specialtype = GetFormData($form, $section, "newfield_specialtype");
				$newfield->name = $cleanedname;
				$newfield->fieldnum = "f" . GetFormData($form,$section,"newfield_fieldnum");
				$newfield->options = (GetFormData($form, $section, "newfield_searchable") ? 'searchable,' : '') .
										DBSafe(GetFormData($form, $section, "newfield_type") .
										($specialtype ? ',' . DBSafe($specialtype) : ''));
				if ($newfield->update()) {
					// Requery to get the newly inserted row
					$FIELDMAPS = DBFindMany("FieldMap", "from fieldmap order by fieldnum");
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
						$updatefield = DBFind("FieldMap", "from fieldmap where fieldnum = '$fieldnum'");
						$updatefield->name = $cleanedname;
						$updatefield->options = ($searchable ? 'searchable,' : '') . $type;
						$updatefield->update();
						// Requery to get the newly inserted row
						$FIELDMAPS = DBFindMany("FieldMap", "from fieldmap order by fieldnum");
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
$availablefields = array_diff($availablefields, QuickQueryList("select right(fieldnum,2) from fieldmap"));

if( $reloadform )
{
	ClearFormData($form);
	PutFormData($form, $section, 'newfield_type', 'text', 'text');

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

	PutFormData($form, $section, 'newfield_name', '', 'text', 1, 20, false); // This item is only required on an add operation
	PutFormData($form, $section, 'newfield_type', 'text', 'text');
	PutFormData($form, $section, 'newfield_searchable', '1', 'bool');
	PutFormData($form, $section, 'predef_name', "");
	PutFormData($form, $section, 'newfield_specialtype', '', 'text');

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
	
		$types = array("Text" => 'text',
					"Date" => 'reldate',
					"List" => 'multisearch');
					
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
			// These 3 items are read-only so display them in the else block
			if ($fieldnum != $field->getFirstNameField() &&
				$fieldnum != $field->getLastNameField() &&
				$fieldnum != $field->getLanguageField() &&
				$fieldnum != $field->getGradeField() &&
				$fieldnum !=  $field->getSchoolField()) {
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
				<td><? NewFormItem($form, $section, 'searchable_' . $fieldnum, 'checkbox'); ?></td>
				<td><a href='datamanager.php?delete=<?=$field->id?>' onclick="return confirmDelete();">Delete</a>&nbsp;|&nbsp;<a href='datamanager.php?clear=<?=$fieldnum?>' onclick="return confirm('Are you sure you want to clear (erase) all data for this field?');">Clear&nbsp;data</a></td>
<?
			} else {
?>
				<td><? NewFormItem($form, $section, "name_$fieldnum", 'text', '20');?></td>
<?	
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
?>
				<td><?=$type ?></td>
				<td><? NewFormItem($form, $section, 'searchable_' . $fieldnum, 'checkbox', null, null, 'DISABLED');?></td>
				<td><a href='datamanager.php?delete=<?=$field->id?>' onclick="return confirmDelete();">Delete</a>&nbsp;|&nbsp;<a href='datamanager.php?clear=<?=$fieldnum?>' onclick="return confirm('Are you sure you want to clear (erase) all data for this field?');">Clear&nbsp;data</a></td>
<?
			}
?>			
		</tr>
<?
		}
	}
	
	// Print extra row for adding new items
?>
	
	<tr>
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
		newfield($form, $section, 'newfield_name');
		NewFormItem($form, $section, 'newfield_name', 'text',20, '', 'id=newfield');
?> 
		</td>
		<td>
<?		
		NewFormItem($form, $section, 'newfield_type', 'selectstart', '', '', 'id=newfield_type');
		foreach($types as $text => $type)
			NewFormItem($form, $section, 'newfield_type', 'selectoption', $text, $type);
		NewFormItem($form, $section, 'newfield_type', 'selectend');
		NewFormItem($form, $section, 'newfield_specialtype', 'text', '20', '', 'id=specialtype'); 
?>
		</td>
		<td><? NewFormItem($form, $section, 'newfield_searchable', 'checkbox', '', '', 'id=newfield_searchable'); ?> </td>
		<td><? echo submit($form, 'add', 'add', 'add'); ?></td>
	</tr>
</table>
<?
endWindow();
buttons();
EndForm();

include_once("navbottom.inc.php");

////////////////////////////////////////////////////////////////////////////////
// Scripts
////////////////////////////////////////////////////////////////////////////////
?>
	<script>	
		hide('specialtype');
		
		function predef_name(name){
			specialtype = new getObj('specialtype').obj;
			type = new getObj('newfield_type').obj;
			type.style.display="none";
			searchable = new getObj('newfield_searchable').obj;
			searchable.checked=true;
			searchable.style.display="none";
			switch(name){
				case 'Language':
					new getObj('newfield').obj.value = 'Language';
					specialtype.value = 'language';
					type.value='multisearch';
					break;
				case 'Grade':
					new getObj('newfield').obj.value = 'Grade';
					specialtype.value = 'grade';
					type.value='multisearch';
					break;
				case 'School':
					new getObj('newfield').obj.value = 'School';
					specialtype.value = 'school';	
					type.value='multisearch';
					break;
				case 'Last Name':
					new getObj('newfield').obj.value = 'Last Name';
					specialtype.value = 'lastname';	
					type.value='text';
					break;
				case 'First Name':
					new getObj('newfield').obj.value = 'First Name';
					specialtype.value = 'firstname';	
					type.value='text';
					break;
				default:
					new getObj('newfield').obj.value = '';		
					type.value = 'text';
					type.style.display="block";
					specialtype.value = '';
					show('newfield_searchable');
					break;
			}	
		
		}


	</script>
