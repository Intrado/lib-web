<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
require_once("inc/common.inc.php");
include_once("inc/securityhelper.inc.php");
require_once("obj/Job.obj.php");
require_once("obj/JobType.obj.php");
require_once("inc/table.inc.php");
require_once("inc/html.inc.php");
require_once("inc/form.inc.php");
require_once("inc/utils.inc.php");
require_once("inc/formatters.inc.php");
require_once("obj/Phone.obj.php");
require_once("obj/ReportInstance.obj.php");
require_once("obj/UserSetting.obj.php");
require_once("obj/FieldMap.obj.php");
require_once("inc/date.inc.php");

////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$USER->authorize('createreport')) {
	redirect('unauthorized.php');
}

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

$f = "report";
$s = "personnotify";
$reload = 0;

$_SESSION['saved_report'] = false;


$jobtypes = DBFindMany("JobType", "from jobtype");
$fields = DBFindMany("FieldMap", "from fieldmap where options not like '%firstname%' and options not like '%lastname%'");
foreach($fields as $key => $fieldmap){
	if(!$USER->authorizeField($fieldmap->fieldnum))
		unset($fields[$key]);
}
$firstname = DBFind("FieldMap", "from fieldmap where options like '%firstname%'");
$lastname = DBFind("FieldMap", "from fieldmap where options like '%lastname%'");

$orders = array("order1", "order2", "order3");
$results = array("A" => "Answered",
					"M" => "Machine",
					"N" => "No Answer",
					"B" => "Busy",
					"F" => "Failed",
					"X" => "Disconnected");	


$undelivered = false;
if(isset($_REQUEST['callsreport']) && $_REQUEST['callsreport']){
	$_SESSION['undelivered'] = false;
	$_SESSION['reporttype'] = 'callsreport';
} else if(isset($_REQUEST['undelivered']) && $_REQUEST['undelivered'] ){
	$undelivered = true;
	$chosenresults = array("N","B","F","X");
	$options['result'] = $chosenresults;
	$_SESSION['undelivered'] = true;
	$_SESSION['reporttype'] = 'undelivered';
} else if(isset($_REQUEST['emergency']) && $_REQUEST['emergency']){
	$_SESSION['undelivered'] = false;
	$_SESSION['reporttype'] = 'emergency';
	$_SESSION['systempriority'] = 1;
} else if(isset($_REQUEST['attendance']) && $_REQUEST['attendance']){
	$_SESSION['undelivered'] = false;
	$_SESSION['reporttype'] = 'attendance';
	$_SESSION['systempriority'] = 2;
}

if(isset($_REQUEST['clear']) && $_REQUEST['clear']){
	unset($_SESSION['report']['options']);
	redirect();
}

$options = isset($_SESSION['report']['options']) ? $_SESSION['report']['options'] : array();

$options['reporttype'] = $_SESSION['reporttype'];
$options['systempriority'] = isset($_SESSION['systempriority']) ? $_SESSION['systempriority'] : null;
$_SESSION['report']['options'] = $options;

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
	$_SESSION['report']['options'] = $options;
	if(!count($_SESSION['contactrules']))
		$_SESSION['contactrules'] = false;
	redirect();
}

if(CheckFormSubmit($f,$s) || CheckFormSubmit($f,'search')){
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
		} else if((GetFormData($f, $s, 'date_start') != "") && !strtotime(GetFormData($f, $s, 'date_start'))){
			error("The start date is in an invalid format");
		} else if((GetFormData($f, $s, 'date_end') != "") && !strtotime(GetFormData($f, $s, 'date_end'))){
			error("The end date is in an invalid format");
		} else {
			$options['personid'] = GetFormData($f, $s, 'personid');
			$options['phone'] = GetFormData($f, $s, 'phone');
			$options['email'] = GetFormData($f, $s, 'email');
			$options['date_start'] = GetFormData($f, $s, 'date_start');
			$options['date_end'] = GetFormData($f, $s, 'date_end');
			$options['priority'] = GetFormData($f, $s, 'priority')+0;
			$result = GetFormData($f, $s, "result");
			if($result)
				$options['result'] = "'" . implode("','", $result) . "'";
			
			foreach($orders as $order)
				$options[$order] = GetFormData($f, $s, $order);

			$options['reldate'] = GetFormData($f, $s, "relativedate");
			if(GetFormData($f, $s, "relativedate") == "xdays")
				$options['lastxdays'] = GetFormData($f, $s, "lastxdays");


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
			$_SESSION['report']['options'] = $options;
			if(CheckFormSubmit($f,'search')){
				redirect("reportcallsresult.php");
			}
		}
	}
} else {
	$reload = 1;
}


if($reload){
	ClearFormData($f);
	$options = isset($_SESSION['report']['options']) ? $_SESSION['report']['options'] : array();
	PutFormData($f, $s, 'personid', isset($options['personid']) ? $options['personid'] : "", 'text');
	PutFormData($f, $s, 'phone', isset($options['phone']) ? $options['phone'] : "", 'phone', "7", "10");
	PutFormData($f, $s, 'email',isset($options['email']) ? $options['email'] : "", 'email');
	PutFormData($f, $s, 'date_start', isset($options['date_start']) ? $options['date_start'] : "", 'text');
	PutFormData($f, $s, 'date_end', isset($options['date_end']) ? $options['date_end'] : "", 'text');
	PutFormData($f, $s, 'priority', isset($options['priority']) ? $options['priority'] : "");
	PutFormData($f, $s, 'result', isset($options['result']) ? $options['result'] : array(), "array", array_keys($results));
	PutFormData($f, $s, "relativedate", isset($options['reldate']) ? $options['reldate'] : "");
	PutFormData($f, $s, 'xdays', isset($options['lastxdays']) ? $options['lastxdays'] : "", "number");
	foreach($orders as $order){
		PutFormData($f, $s, $order, isset($_SESSION[$order]) ? $_SESSION[$order] : "");
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

$PAGE = "reports:reports";

switch($_SESSION['reporttype']){
	case 'undelivered':
		$TITLE = "UnDelivered Calls";
		break;
	case 'attendance':
		$TITLE = "Attendance Calls";
		break;
	case 'emergency':
		$TITLE = "Emergency Calls";
		break;
	default:
		$TITLE = "Calls Report";
}

include_once("nav.inc.php");

NewForm($f);
buttons(submit($f, 'search', 'search', 'search'));
startWindow("Person Notification Search", "padding: 3px;");

?>
<table border="0" cellpadding="3" cellspacing="0" width="100%">
	<tr valign="top"><th align="right" class="windowRowHeader bottomBorder">Search:</th>
	<td class="bottomBorder">
		<table>
		<tr><td>Person ID: </td><td><? NewFormItem($f, $s, 'personid', 'text', '20'); ?></td></tr>
		<tr><td>Phone Number: </td><td><? NewFormItem($f, $s, 'phone', 'text', '12'); ?></td></tr>
		<tr><td>Email Address: </td><td><? NewFormItem($f, $s, 'email', 'text', '30'); ?></td></tr>
		<tr><td>Date Range: </td><td><? NewFormItem($f, $s, 'date_start', 'text', '20'); ?></td><td> To: </td><td><? NewFormItem($f, $s, 'date_end', 'text', '20'); ?></td></tr>
		<tr><td>Relative Date: </td>
			<td><?
				NewFormItem($f, $s, 'relativedate', 'selectstart', null, null, "onchange='new getObj(\"xdays\").obj.disabled=(this.value!=\"xdays\")'");
				NewFormItem($f, $s, 'relativedate', 'selectoption', ' -- None -- ', '');
				NewFormItem($f, $s, 'relativedate', 'selectoption', 'Yesterday', 'yesterday');
				NewFormItem($f, $s, 'relativedate', 'selectoption', 'Last Work Day', 'workday');
				NewFormItem($f, $s, 'relativedate', 'selectoption', 'Last X Days', 'xdays');
				NewFormItem($f, $s, 'relativedate', 'selectend');
				NewFormItem($f, $s, 'xdays', 'text', '3', null, "id='xdays' disabled");
				?>
			</td>
		</tr>
		<tr><td>Priority: </td>
			<td><?
				NewFormItem($f, $s, 'priority', 'selectstart');
				NewFormItem($f, $s, 'priority', 'selectoption', ' -- All -- ', '');
				foreach($jobtypes as $jobtype){
					NewFormItem($f, $s, 'priority', 'selectoption', $jobtype->name, $jobtype->id);
				}
				NewformItem($f, $s, 'priority', 'selectend');
			?></td>
		</tr>
<?
		if(isset($undelivered) && !$undelivered){
?>
		<tr>
			<td>Call Result:</td>
			<td>
				<?
					NewFormItem($f, $s, 'result', 'selectmultiple',  "6", $results);
				?>
			</td>
		</tr>
<?
		}
?>
		</table>
		<table border="0" cellpadding="3" cellspacing="0" width="100%">
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
	<tr valign="top"><th align="right" class="windowRowHeader bottomBorder">Fields:</th>
		<td class="bottomBorder">
<? 		
			select_metadata(null, null, $fields);
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
	<tr><td><? buttons(); ?></td></tr>
</table>
<?
endWindow();
EndForm();

include_once("navbottom.inc.php");
?>
