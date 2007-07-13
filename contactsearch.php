<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
require_once("inc/common.inc.php");
include_once("inc/securityhelper.inc.php");
require_once("inc/table.inc.php");
require_once("inc/html.inc.php");
require_once("inc/form.inc.php");
require_once("inc/utils.inc.php");
require_once("inc/reportutils.inc.php");
require_once("inc/formatters.inc.php");
require_once("obj/FieldMap.obj.php");
require_once("inc/date.inc.php");
require_once("obj/UserSetting.obj.php");


////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$USER->authorize('createreport')) {
	redirect('unauthorized.php');
}


////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////


$fields = DBFindMany("FieldMap", "from fieldmap where options not like '%firstname%' and options not like '%lastname%'");
foreach($fields as $key => $fieldmap){
	if(!$USER->authorizeField($fieldmap->fieldnum))
		unset($fields[$key]);
}
$firstname = DBFind("FieldMap", "from fieldmap where options like '%firstname%'");
$lastname = DBFind("FieldMap", "from fieldmap where options like '%lastname%'");

if(isset($_REQUEST['clear']) && $_REQUEST['clear']){
	unset($_SESSION['contacts']['options']);
	$_SESSION['saved_report'] = false;
	redirect();
}
$options = isset($_SESSION['contacts']['options']) ? $_SESSION['contacts']['options'] : array();

unset($_SESSION['contactrules']);
if(isset($options['rules']) && $options['rules'] != ""){
	$rules = explode("||", $options['rules']);
	foreach($rules as $rule){
		if($rule != ""){
			$rule = explode(";", $rule);
			$newrule = new Rule();
			$newrule->logical = $rule[0];
			$newrule->op = $rule[1];
			$newrule->fieldnum = $rule[2];
			$newrule->val = $rule[3];
			if(isset($_SESSION['contactrules']) && is_array($_SESSION['contactrules']))
				$_SESSION['contactrules'][] = $newrule;
			else 
				$_SESSION['contactrules'] = array($newrule);
			$newrule->id = array_search($newrule, $_SESSION['contactrules']);
			$_SESSION['contactrules'][$newrule->id] = $newrule;
		}
	}
}

if(isset($_GET['deleterule'])) {
	unset($_SESSION['contactrules'][(int)$_GET['deleterule']]);
	$options['rules'] = explode("||", $options['rules']);
	$options['rules'][(int)$_GET['deleterule']] = "";
	$options['rules'] = implode("||", $options['rules']);
	$_SESSION['contacts']['options'] = $options;
	if(!count($_SESSION['contactrules']))
		$_SESSION['contactrules'] = false;
	redirect();
}

$f = "person";
$s = "search";
$reload = 0;
$orders = array("order1", "order2", "order3");

if(CheckFormSubmit($f,$s) || CheckFormSubmit($f, 'submit')){
	//check to see if formdata is valid
	if(CheckFormInvalid($f))
	{
		error('Form was edited in another window, reloading data');
		$reloadform = 1;
	}
	else
	{
		MergeSectionFormData($f, $s);
		if( CheckFormSection($f, $s) ) {
			error('There was a problem trying to save your changes', 'Please verify that all required field information has been entered properly');
		} else {
			$options['reporttype']="contacts";
			$options['personid'] = GetFormData($f, $s, 'personid');
			$options['phone']= GetFormData($f, $s, 'phone');
			$options['email'] = GetFormData($f, $s, 'email');
			foreach($orders as $order){
				$options[$order] = GetFormData($f, $s, $order);
			}
			$options['rules'] = isset($options['rules']) ? explode("||", $options['rules']) : array();
			$fieldnum = GetFormData($f,$s,"newrulefieldnum");
			if ($fieldnum != "") {
				$type = GetFormData($f,$s,"newruletype");

				if ($type == "text")
					$logic = "and";
				else
					$logic = GetFormData($f,$s,"newrulelogical_$type");

				if ($type == "multisearch")
					$op = "in";
				else
					$op = GetFormData($f,$s,"newruleoperator_$type");

				$value = GetFormData($f,$s,"newrulevalue_" . $fieldnum);
				if (count($value) > 0) {
					$rule = new Rule();
					$rule->logical = $logic;
					$rule->op = $op;
					$rule->val = ($type == 'multisearch' && is_array($value)) ? implode("|",$value) : $value;
					$rule->fieldnum = $fieldnum;
					if(isset($_SESSION['contactrules']) && is_array($_SESSION['contactrules']))
						$_SESSION['contactrules'][] = $rule;
					else
						$_SESSION['contactrules'] = array($rule);
					$rule->id = array_search($rule, $_SESSION['contactrules']);
					
					$options['rules'][$rule->id] = implode(";", array($rule->logical, $rule->op, $rule->fieldnum, $rule->val));
				}
			}
			$options['rules'] = implode("||", $options['rules']);
			$_SESSION['contacts']['options'] = $options;
			if(CheckFormSubmit($f, 'submit')){
				redirect("contactresult.php");
			}
		}
	}
} else {
	$reload = 1;
}

if($reload){
	ClearFormData($f);
	$options = isset($_SESSION['contacts']['options']) ? $_SESSION['contacts']['options'] : array();
	PutFormData($f, $s, 'personid', isset($options['personid']) ? $options['personid'] : "", 'text');
	PutFormData($f, $s, 'phone', isset($options['phone']) ? $options['phone'] : "", 'phone', "7", "10");
	PutFormData($f, $s, 'email', isset($options['email']) ? $options['email'] : "", 'email');
	foreach($orders as $order){
		if($order == "order1"){
			PutFormData($f, $s, $order, isset($options[$order]) ? $options[$order] : "pkey");
		} else {
			PutFormData($f, $s, $order, isset($options[$order]) ? $options[$order] : "");
		}
	}

	PutFormData($f,$s,"newrulefieldnum","");
	PutFormData($f,$s,"newruletype","text","text",1,50);
	PutFormData($f,$s,"newrulelogical_text","and","text",1,50);
	PutFormData($f,$s,"newrulelogical_multisearch","and","text",1,50);
	PutFormData($f,$s,"newruleoperator_text","sw","text",1,50);
	PutFormData($f,$s,"newruleoperator_multisearch","in","text",1,50);
}
	


////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$PAGE = "system:contact search";
$TITLE = "Contact Search";

include_once("nav.inc.php");

NewForm($f);
buttons(submit($f, 'submit', 'search', 'search'));
startWindow("Contact Search", "padding: 3px;"); 
?>
<table border="0" cellpadding="3" cellspacing="0" width="100%">
	<tr valign="top"><th align="right" class="windowRowHeader bottomBorder">Search:</th>
		<td class="bottomBorder">
			<table border="0" cellpadding="3" cellspacing="0" width="100%">
				<tr>
					<td>
						<table>
							<tr><td>Person ID: </td><td><? NewFormItem($f, $s, 'personid', 'text', '15'); ?></td></tr>
							<tr><td>Phone Number: </td><td><? NewFormItem($f, $s, 'phone', 'text', '12'); ?></td></tr>
							<tr><td>Email Address: </td><td><? NewFormItem($f, $s, 'email', 'text', '100'); ?></td></tr>
						</table>
					</td>
				</tr>
				<tr>
					<td><br>
						<? 
							if(!isset($_SESSION['contactrules']) || is_null($_SESSION['contactrules']))
								$_SESSION['contactrules'] = false;
							
							$RULES = &$_SESSION['contactrules'];
							$RULEMODE = array('multisearch' => true, 'text' => true, 'reldate' => true);
							
							include("ruleeditform.inc.php");
						?>
					<br></td>
				</tr>
			</table>
		</td>
	</tr>
	<tr valign="top"><th align="right" class="windowRowHeader bottomBorder">Fields:</th>
		<td class="bottomBorder">
<? 		
			select_metadata('searchresultstable', 4, $fields);
?>
		</td>
	</tr>	
	<tr valign="top"><th align="right" class="windowRowHeader bottomBorder">Sort by:</th>
		<td class="bottomBorder">
			<table>
				<tr>
<?
				foreach($orders as $order){
?>
				<td>
<?
					NewFormItem($f, $s, $order, 'selectstart');
					NewFormItem($f, $s, $order, 'selectoption', " -- Not Selected --", "");
					NewFormItem($f, $s, $order, 'selectoption', "Person ID", "pkey");
					NewFormItem($f, $s, $order, 'selectoption', $firstname->name, $firstname->fieldnum);
					NewFormItem($f, $s, $order, 'selectoption', $lastname->name, $lastname->fieldnum);
					foreach($fields as $field){
						NewFormItem($f, $s, $order, 'selectoption', $field->name, $field->fieldnum);
					}
					NewFormItem($f, $s, $order, 'selectend');
?>
				</td>
<?
				}
?>
				</tr>
			</table>
		</td>
	</tr>
</table>

<?
buttons();
endWindow();
EndForm();

include_once("navbottom.inc.php");
?>
