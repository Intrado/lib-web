<?

function showmode($type) {
	global $RULEMODE, $fieldmap;
	return $fieldmap->isOptionEnabled($type) && ($RULEMODE[$type]);
}

function drawRuleTable($f, $s, $readonly = false, $withffields = true, $withgfields = true, $withcfields = true) {
global $RULES, $fieldmap, $USER, $RULE_OPERATORS, $RELDATE_OPTIONS;

?>
<table border="0" cellspacing="3" cellpadding="2" class="border" width="50%">
<?


//get all possible rules
$sepval = array();
$temp = FieldMap::getSeparatorFieldMap(1);
$sepval[$temp->fieldnum] = $temp;
$sepval2 = array();
$temp = FieldMap::getSeparatorFieldMap(2);
$sepval2[$temp->fieldnum] = $temp;
$ffields = array();
if ($withffields) $ffields = FieldMap::getAuthorizedFieldMapsLike('f');
$gfields = array();
if ($withgfields) $gfields = FieldMap::getAuthorizedFieldMapsLike('g');
if (count($gfields) > 0) $gfields = $sepval + $gfields;
$cfields = array();
if ($withcfields) $cfields = FieldMap::getAuthorizedFieldMapsLike('c');
if (count($cfields)) $cfields = $sepval2 + $cfields;
$fieldmaps = $ffields + $gfields + $cfields; // GUI preffered order


$rulemap = array();
$unusedrules = array();
if(is_array($RULES)) {
	foreach ($RULES as $rule) {
		$rulemap[$rule->fieldnum][] = $rule;
		$unusedrules[$rule->fieldnum] = true;
	}
}

////////////////////////////////////////////////////////////////////////////////
// Existing Rules
////////////////////////////////////////////////////////////////////////////////
//make a form part for each of the defined rules
foreach ($fieldmaps as $fieldmap) {
	//only show if searchable and multisearch for rules
	if ($fieldmap->isOptionEnabled("searchable")) {

		$fieldname = $fieldmap->name;
		$fieldnum = $fieldmap->fieldnum;

		//only show an entry for fields that have a rule defined for them
		if (!isset($rulemap[$fieldnum]))
			continue;

		unset($unusedrules[$fieldnum]);

		foreach($rulemap[$fieldnum] as $rule) {

			echo '<tr><td class="border">' . escapehtml($fieldname) . '</td>';

			if(showmode("text")) {
				echo '<td class="border" nowrap>' . $RULE_OPERATORS['text'][$rule->op] . '</td><td class="border">' . ($rule->val != "" ? $rule->val : '&nbsp;') . '</td>';
			} elseif(showmode("reldate")) {

				echo '<td class="border" nowrap>' . $RULE_OPERATORS['reldate'][$rule->op] . '</td>';
				if ($rule->op == "reldate") {
					echo '<td class="border">' . $RELDATE_OPTIONS[$rule->val] . '</td>';
				} else if ($rule->op == "date_range" || $rule->op == "reldate_range") {
					$values = explode("|",$rule->val);
					echo '<td class="border">' . $values[0] . ' and ' . $values[1] . '</td>';
				} else {
					echo '<td class="border">' . $rule->val . '</td>';
				}
			} elseif(showmode("multisearch")) {
				if ($rule->logical == "and") {
								echo '<td class="border" nowrap>is</td>';
				} else {
								echo '<td class="border" nowrap>is NOT</td>';
				}
				echo '<td class="border">';
				$values = explode("|",$rule->val);
				$formattedvalues = implode(", ",$values);
				if ($formattedvalues == "")
					$formattedvalues = " ";
				echo escapehtml($formattedvalues);
				echo '</td>';
			} elseif(showmode("numeric")) {
				//TODO
				echo '<td class="border" nowrap>' . $RULE_OPERATORS['numeric'][$rule->op] . '</td>';
				if ($rule->op == "num_range") {
					$values = explode("|",$rule->val);
					echo '<td class="border">' . $values[0] . ' and ' . $values[1] . '</td>';
				} else {
					echo '<td class="border">' . $rule->val . '</td>';
				}
			}
if (!$readonly) {
			echo '<td>';
			echo button('Delete', NULL, '?deleterule=' . $rule->id);
			echo "</td>";
}
			echo "</tr>\n";
		}
	}
}

if (!$readonly) {

if (count($unusedrules) > 0) {
	echo '<tr><td class="border" colspan="4" style="color: red;">WARNING: Some rules are not visible due to security restrictions or system configuration.</td></tr>';
}

////////////////////////////////////////////////////////////////////////////////
// Field Select
////////////////////////////////////////////////////////////////////////////////

//set the select so that it will show the correct form and hide the others
//when a value is selected, show that form part for that field

echo "<tr><td valign=top>";

$extrahtml = "onchange=\"Element.hide(ruleselected);
						ruleselected = (this.value == -1) ? 'rule_' : 'rule_'+this.value;
						Element.show(ruleselected);
						Element.hide(typeselected);
						typeselected = 'operator_'+ruletypes[this.selectedIndex];
						Element.show(typeselected);
						new getObj('newruletype').obj.value=ruletypes[this.selectedIndex];\"";

PutFormData($f,$s,"newrulefieldnum", "-1");
NewFormItem($f,$s,"newrulefieldnum","selectstart",NULL,NULL,$extrahtml);

NewFormItem($f,$s,"newrulefieldnum","selectoption"," -- Select a Field -- ","-1");

$typemap = array();

//make a list of all undefined rules
foreach ($fieldmaps as $fieldmap) {
	//only show if searchable and multisearch for rules
	if ($fieldmap->isOptionEnabled("searchable")) {

		$fieldname = $fieldmap->name;
		$fieldnum = $fieldmap->fieldnum;
		//skip any field that has a rule defined for it

		if (isset($rulemap[$fieldnum]))
			continue;

		if(showmode("text")) {
			$typemap[] = 'text';
			NewFormItem($f,$s,"newrulefieldnum","selectoption",$fieldname,$fieldnum);
		} elseif(showmode("reldate")) {
			$typemap[] = 'reldate';
			NewFormItem($f,$s,"newrulefieldnum","selectoption",$fieldname,$fieldnum);
		} elseif(showmode("multisearch")) {
			$typemap[] = 'multisearch';
			$extrahtml = "";
			if ($fieldmap->isOptionEnabled("disabled")) $extrahtml = "disabled=\"disabled\"";
			NewFormItem($f,$s,"newrulefieldnum","selectoption",$fieldname,$fieldnum,$extrahtml);
		} else if(showmode("numeric")) {
			$typemap[] = 'numeric';
			NewFormItem($f,$s,"newrulefieldnum","selectoption",$fieldname,$fieldnum);
		}
	}
}
NewFormItem($f,$s,"newrulefieldnum","selectend");

echo '<script>var ruleselected = "rule_"; var typeselected = "operator_"; var ruletypes = new Array(\'\',\'' . implode("','", $typemap) . '\');</script>';
echo '</td><td valign=top>';


////////////////////////////////////////////////////////////////////////////////
// Operator Select
////////////////////////////////////////////////////////////////////////////////
PutFormData($f,$s,"newruletype","");
NewFormItem($f,$s,"newruletype","hidden","",NULL,'id="newruletype"');

echo '<div id="operator_">&nbsp;</div>';

//Text Operators
echo '<div id="operator_text" style="display: none;">';
NewFormItem($f,$s,"newrulelogical_text","hidden","and");
NewFormItem($f,$s,"newruleoperator_text","selectstart");
foreach($RULE_OPERATORS['text'] as $code => $name)
	NewFormItem($f,$s,"newruleoperator_text","selectoption",$name,$code);
NewFormItem($f,$s,"newruleoperator_text","selectend");
echo '</div>';

//Reldate Operators
echo '<div id="operator_reldate" style="display: none;">';
PutFormData($f,$s,"newrulelogical_reldate","and");
PutFormData($f,$s,"newruleoperator_reldate","reldate");

$extrahtml = "onchange=\"setDependentVisibility(this.form,'reldate_reldate',this.value == 'reldate'); "
					."setDependentVisibility(this.form,'reldate_val2',this.value == 'eq' || this.value == 'date_range'); "
					."setDependentVisibility(this.form,'reldate_val3',this.value == 'date_range'); "
					."setDependentVisibility(this.form,'reldate_val4',this.value == 'date_offset' || this.value == 'reldate_range'); "
					."setDependentVisibility(this.form,'reldate_val5',this.value == 'reldate_range');\"";

NewFormItem($f,$s,"newrulelogical_reldate","hidden","and");
NewFormItem($f,$s,"newruleoperator_reldate","selectstart",NULL,NULL,$extrahtml);
foreach($RULE_OPERATORS['reldate'] as $code => $name)
	NewFormItem($f,$s,"newruleoperator_reldate","selectoption",$name,$code);
NewFormItem($f,$s,"newruleoperator_reldate","selectend");
echo '</div>';

//Multisearch Operators
echo '</div><div id="operator_multisearch" style="display: none;">';
NewFormItem($f,$s,"newrulelogical_multisearch","selectstart");
NewFormItem($f,$s,"newrulelogical_multisearch","selectoption","is","and");
NewFormItem($f,$s,"newrulelogical_multisearch","selectoption","is NOT","and not");
NewFormItem($f,$s,"newrulelogical_multisearch","selectend");
PutFormData($f,$s,"newruleoperator_multisearch","in");
NewFormItem($f,$s,"newruleoperator_multisearch","hidden","in");
echo '</div>';

//Numeric Operators

$extrahtml = "onchange=\"setDependentVisibility(this.form,'numeric_range_field',this.value=='num_range') ;\"";

echo '<div id="operator_numeric" style="display: none;">';
NewFormItem($f,$s,"newrulelogical_numeric","hidden","and");
NewFormItem($f,$s,"newruleoperator_numeric","selectstart",NULL,NULL,$extrahtml);
foreach($RULE_OPERATORS['numeric'] as $code => $name)
	NewFormItem($f,$s,"newruleoperator_numeric","selectoption",$name,$code);
NewFormItem($f,$s,"newruleoperator_numeric","selectend");
echo '</div>';

echo '</td><td>';

////////////////////////////////////////////////////////////////////////////////
// Input Values
////////////////////////////////////////////////////////////////////////////////

//make hidden form parts for each of the rules
echo '<div id="rule_">&nbsp;</div>';
foreach ($fieldmaps as $fieldmap) {
	//only show if searchable and multisearch for rules
	if ($fieldmap->isOptionEnabled("searchable")) {

	$fieldname = $fieldmap->name;
	$fieldnum = $fieldmap->fieldnum;

		echo '<div id="rule_' . $fieldnum . '" style="display: none">';

		if (showmode("text")) {
			PutFormData($f,$s,"newrulevalue_" . $fieldnum,"","text");
			if (in_array($fieldnum, array("f01","f02"))) {
				NewFormItem($f,$s,"newrulevalue_" . $fieldnum,"text",20,50);
			} else {
				NewFormItem($f,$s,"newrulevalue_" . $fieldnum,"text",20,255);
			}

		} else if (showmode("reldate")) {
			echo '<table border=0 cellpadding=0 cellspacing=0><tr><td nowrap><div dependson="reldate_reldate">';
			PutFormData($f,$s,"newrulevalue_" . $fieldnum,"","array",array_keys($RELDATE_OPTIONS));
			NewFormItem($f, $s, "newrulevalue_" . $fieldnum, "selectstart");
			foreach($RELDATE_OPTIONS as $name => $value) {
				NewFormItem($f, $s, "newrulevalue_" . $fieldnum, "selectoption",$value,$name);
			}
			NewFormItem($f, $s, "newrulevalue_" . $fieldnum, "selectend");

			echo '</div></td><td nowrap><div dependson="reldate_val2" style="display: none;">';
			PutFormData($f,$s,"newrulevalue2_" . $fieldnum,"","text");
			NewFormItem($f,$s,"newrulevalue2_" . $fieldnum,"text",10,20,"onfocus=\"this.select();lcs(this,true,true)\" onclick=\"event.cancelBubble=true;this.select();lcs(this,true,true)\"");
			echo '</div></td><td nowrap><div dependson="reldate_val3" style="display: none;">&nbsp;and&nbsp;';
			PutFormData($f,$s,"newrulevalue3_" . $fieldnum,"","text");
			NewFormItem($f,$s,"newrulevalue3_" . $fieldnum,"text",10,20,"onfocus=\"this.select();lcs(this,true,true)\" onclick=\"event.cancelBubble=true;this.select();lcs(this,true,true)\"");
			echo '</div></td><td nowrap><div dependson="reldate_val4" style="display: none;">';
			PutFormData($f,$s,"newrulevalue4_" . $fieldnum,"","numeric");
			NewFormItem($f,$s,"newrulevalue4_" . $fieldnum,"text",5,5);
			echo '</div></td><td nowrap><div dependson="reldate_val5" style="display: none;">&nbsp;and&nbsp;';
			PutFormData($f,$s,"newrulevalue5_" . $fieldnum,"","numeric");
			NewFormItem($f,$s,"newrulevalue5_" . $fieldnum,"text",5,5);
			echo '</div></td></tr></table>';

		} else if (showmode("multisearch")) {

			//skip any field that has a rule defined for it
			if (isset($rulemap[$fieldnum])) {
				echo "</div>\n";
				continue;
			}

			$limit = DBFind('Rule', "from rule inner join userrule on rule.id = userrule.ruleid where userid = $USER->id and fieldnum = '$fieldnum'");
			$limitsql = $limit ? $limit->toSQL(false, "value", false, true) : "";
			$query = "select value from persondatavalues
						where fieldnum='$fieldnum' $limitsql order by value";

			$values = QuickQueryList($query);
			if(!count($values))
				$values = array(0 => "");
			PutFormData($f,$s,"newrulevalue_" . $fieldnum,"","array",$values);
			if (count($values) > 1) {
				NewFormItem($f,$s,"newrulevalue_" . $fieldnum,"selectmultiple",5,@array_combine($values,$values));
			} else {
				$arrayvalues = @array_combine($values,$values);
				if($arrayvalues === false){
					$arrayvalues = array(0 => "");
				}
				NewFormSelect($f,$s,"newrulevalue_" . $fieldnum,$arrayvalues);
			}
		} else if (showmode("numeric")) {
			echo '<table border=0 cellpadding=0 cellspacing=0><tr><td nowrap>';
			PutFormData($f,$s,"newrulevalue_" . $fieldnum,"","text");
			NewFormItem($f,$s,"newrulevalue_" . $fieldnum,"text",10,20);
			echo '</td><td nowrap><div dependson="numeric_range_field" style="display: none;">&nbsp;and&nbsp;';
			PutFormData($f,$s,"newrulevalue2_" . $fieldnum,"","text");
			NewFormItem($f,$s,"newrulevalue2_" . $fieldnum,"text",10,20);
			echo '</div></td></tr></table>';
		}

		echo "</div>\n";
	}
}
echo '</td><td>';

echo submit($f,$s,"Add");

echo "</tr>";
} // end readonly

echo "</table>";

} // end function drawRuleTable
?>
