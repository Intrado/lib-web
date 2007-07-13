<?

function generateFields($tablealias){
	$fieldstring = "";
	$first = FieldMap::GetFirstNameField();
	$last = FieldMap::GetLastNameField();
	for($i=1; $i<=20; $i++){

		if($i<10){
			$num = "f0".$i;
		}else{
			$num = "f".$i;
		}
		if($num == $first || $num == $last){
			continue;
		} else{
			$fieldstring .= "," . $tablealias . "." . $num;
		}
	}
	return $fieldstring;
}

function select_metadata($tablename=null, $start=null, $fields){
	global $USER;
	if(isset($_SESSION['saved_report']) && $_SESSION['saved_report']){
		$saved = "true";
	} else {
		$saved = "false";
	}
?>
	<table border="0" cellpadding="2" cellspacing="1" class="list">
		<tr class="listHeader" align="left" valign="bottom">
<?
			foreach($fields as $field){
				?><td><?=$field->name;?></td><?
			}
?>
		</tr>
		<tr>
<?
			$count = 1;
			foreach($fields as $field){
				$fieldnum = $field->fieldnum;
				if($saved == "false"){
					$usersetting = DBFind("UserSetting", "from usersetting where name = '" . DBSafe($field->fieldnum) . "' and userid = '$USER->id'");
					$_SESSION['fields'][$fieldnum] = false;
					if($usersetting!= null){
						if($usersetting->value == "true"){
							$_SESSION['fields'][$fieldnum] = true;
						}
					}
				}
				?><td><div align="center">
				<?
					if(isset($_SESSION['fields'][$fieldnum]) && $_SESSION['fields'][$fieldnum]){
						$result = "<img src=\"img/checkbox-check.png\" onclick=\"dofieldbox(this,true,'$fieldnum', $saved);";
						$checked = "checked>";
					} else {
						$result = "<img src=\"img/checkbox-clear.png\" onclick=\"dofieldbox(this,false,'$fieldnum', $saved);";
						$checked = ">";
					}
					if($tablename == null && $start ==null){
						$result .= "\">";
					} else {
						$result .= "toggleHiddenField('$fieldnum'); setColVisability($tablename, $start+$count, new getObj('hiddenfield$fieldnum').obj.checked); \">";
					}
					echo $result;
					echo "<input style='display: none' type='checkbox' id='hiddenfield$fieldnum' " . $checked;
				?>
				</div></td><?
				$count++;
			}
?>
		</tr>
	</table>
<?
}

function getFieldMaps(){
	global $USER;
	$fields = DBFindMany("FieldMap", "from fieldmap where options not like '%firstname%' and options not like '%lastname%'");
	foreach($fields as $key => $fieldmap){
		if(!$USER->authorizeField($fieldmap->fieldnum))
			unset($fields[$key]);
	}
	return $fields;
}
?>