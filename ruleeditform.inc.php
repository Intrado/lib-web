			<table border="0" cellspacing="3" cellpadding="2" class="border" width="50%">
<?
function showmode($type) {
	global $RULEMODE, $fieldmap;
	return $fieldmap->isOptionEnabled($type) && ($RULEMODE[$type]);
}

//get all possible rules
$fieldmapnames = FieldMap::getAuthorizedMapNames();

$fieldmaps = FieldMap::getAuthorizedFieldMaps();

$rulemap = array();
if(is_array($RULES)) {
	foreach ($RULES as $rule) {
		$rulemap[$rule->fieldnum][] = $rule;
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

		foreach($rulemap[$fieldnum] as $rule) {

			echo '<tr><td class="border">' . htmlentities($fieldname) . '</td>';

			if(showmode("text")) {
				echo '<td class="border" nowrap>' . array_search($rule->op, $RULE_OPERATORS) . '</td><td class="border">' . ($rule->val ? $rule->val : '&nbsp;') . '</td>';
			} elseif(showmode("reldate")) {
				echo '<td class="border" nowrap>is</td><td class="border">' . $RELDATE_OPTIONS[$rule->val] . '</td>';
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
					$formattedvalues = "&nbsp;";
				echo $formattedvalues;
				echo '</td>';
			} elseif(showmode("classschedule")) {

				//TODO

			}

			echo '<td>';
			echo button('delete', NULL, '?deleterule=' . $rule->id);
			echo "</td></tr>\n";
		}
	}
}

////////////////////////////////////////////////////////////////////////////////
// Field Select
////////////////////////////////////////////////////////////////////////////////

//set the select so that it will show the correct form and hide the others
//when a value is selected, show that form part for that field

echo "<tr><td valign=top>";

$extrahtml = "onchange=\"hide(ruleselected);
						ruleselected='rule_'+this.value;
						show(ruleselected);
						hide(typeselected);
						typeselected = 'operator_'+ruletypes[this.selectedIndex];
						show(typeselected);
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
foreach($RULE_OPERATORS as $name => $code)
	if($code != 'in')
		NewFormItem($f,$s,"newruleoperator_text","selectoption",$name,$code);
NewFormItem($f,$s,"newruleoperator_text","selectend");
echo '</div>';

//Reldate Operators
echo '<div id="operator_reldate" style="display: none;">';
PutFormData($f,$s,"newrulelogical_reldate","and");
PutFormData($f,$s,"newruleoperator_reldate","reldate");
echo "is";
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

		if(showmode("text")) {
			PutFormData($f,$s,"newrulevalue_" . $fieldnum,"","text");
			NewFormItem($f,$s,"newrulevalue_" . $fieldnum,"text",20);

		} elseif(showmode("reldate")) {
			PutFormData($f,$s,"newrulevalue_" . $fieldnum,"","array",array_keys($RELDATE_OPTIONS));
			NewFormItem($f, $s, "newrulevalue_" . $fieldnum, "selectstart");
			foreach($RELDATE_OPTIONS as $name => $value) {
				NewFormItem($f, $s, "newrulevalue_" . $fieldnum, "selectoption",$value,$name);
			}
			NewFormItem($f, $s, "newrulevalue_" . $fieldnum, "selectend");

		} elseif(showmode("multisearch")) {

			//skip any field that has a rule defined for it
			if (isset($rulemap[$fieldnum])) {
				echo "</div>\n";
				continue;
			}

			$limit = DBFind('Rule', "from rule inner join userrule on rule.id = userrule.ruleid where userid = $USER->id and fieldnum = '$fieldnum'");
			$limitsql = $limit ? $limit->toSQL(false,"value") : "";
			$query = "select value from persondatavalues
						where fieldnum='$fieldnum' $limitsql order by value";

			$values = QuickQueryList($query);
			PutFormData($f,$s,"newrulevalue_" . $fieldnum,"","array",$values);
			if (count($values) > 1) {
				NewFormItem($f,$s,"newrulevalue_" . $fieldnum,"selectmultiple",5,@array_combine($values,$values));
			} else {
				NewFormSelect($f,$s,"newrulevalue_" . $fieldnum,@array_combine($values,$values));
			}
		}

		echo "</div>\n";
	}
}
echo '</td><td>';
NewFormItem($f,$s,"Add","image", 'add');
echo "</tr>";

?>
			</table>