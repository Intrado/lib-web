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
require_once("inc/reportgeneratorutils.inc.php");
require_once("inc/formatters.inc.php");
require_once("obj/FieldMap.obj.php");
require_once("obj/Phone.obj.php");
require_once("obj/Email.obj.php");
require_once("obj/ReportGenerator.obj.php");
require_once("obj/ReportInstance.obj.php");
require_once("obj/UserSetting.obj.php");
require_once("obj/Rule.obj.php");
require_once("inc/date.inc.php");
require_once("obj/ContactsReport.obj.php");
require_once("obj/Person.obj.php");


////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$USER->authorize('viewcontacts')) {
	redirect('unauthorized.php');
}

////////////////////////////////////////////////////////////////////////////////
// Functions
////////////////////////////////////////////////////////////////////////////////

function fmt_phone_contact ($phone) {
	if (strlen($phone) == 10)
		return "(" . substr($phone,0,3) . ")&nbsp;" . substr($phone,3,3) . "-" . substr($phone,6,4);
	else if (strlen($phone) == 7)
		return  substr($phone,0,3) . "-" . substr($phone,3,4);
	else
		return $phone;
}

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////


$f = "person";
$s = "all";

$reload = 0;
$ordercount = 3;
$ordering = ContactsReport::getOrdering();

$fields = FieldMap::getOptionalAuthorizedFieldMaps();

if(isset($_REQUEST['clear']) && $_REQUEST['clear']){
	unset($_SESSION['contacts']['options']);
	$_SESSION['saved_report'] = false;
}
$options = isset($_SESSION['contacts']['options']) ? $_SESSION['contacts']['options'] : array("reporttype" => "contacts");

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
	if(!isset($options['rules'])){
		if(count($_SESSION['contactrules']) > 0){
			$_SESSION['contactrules'] = false;
		}
		redirect();
	}
	$options['rules'] = explode("||", $options['rules']);
	unset($options['rules'][(int)$_GET['deleterule']]);
	if(count($options['rules']) == 0){
		unset($options['rules']);
	} else {
		$options['rules'] = implode("||", $options['rules']);
	}
	$_SESSION['contacts']['options'] = $options;
	if(!count($_SESSION['contactrules']))
		$_SESSION['contactrules'] = false;
	redirect();
}

$activefields = array();
$fieldlist = array();
foreach($fields as $field){
	// used in pdf
	if(isset($_SESSION['fields'][$field->fieldnum]) && $_SESSION['fields'][$field->fieldnum]){
		$activefields[] = $field->fieldnum;
	}
}
$options['activefields'] = $activefields;
$reportinstance = new ReportInstance();

$pagestart = 0;
if(isset($_REQUEST['pagestart'])){
	$pagestart = $_REQUEST['pagestart'];
}
$options['pagestart'] = $pagestart;

$reportinstance->setParameters($options);
$reportgenerator = new ContactsReport();
$reportgenerator->reportinstance = $reportinstance;
$reportgenerator->userid = $USER->id;
$reportgenerator->format = "html";

if(CheckFormSubmit($f,$s) || CheckFormSubmit($f, 'showall') || CheckFormSubmit($f, 'search') || CheckFormSubmit($f, 'refresh')){
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

			if(CheckFormSubmit($f, "showall")){
				$options = array('reporttype' => "contacts");
				for($i=1; $i<=$ordercount; $i++){
					$options["order$i"] = GetFormData($f, $s, "order$i");
				}
				$options['showall'] = true;
				$_SESSION['contacts']['options'] = $options;
				redirect();
			} else {
				$options['reporttype']="contacts";
				if(CheckFormSubmit($f, 'search') || CheckFormSubmit($f, $s))
					unset($options['showall']);
				if(GetFormData($f, $s, "radioselect") == "person"){
					unset($options['rules']);
					$options['personid'] = GetFormData($f, $s, 'personid');
					$options['phone']= GetFormData($f, $s, 'phone');
					$options['email'] = GetFormData($f, $s, 'email');
				} else {
					unset($options['personid']);
					unset($options['phone']);
					unset($options['email']);
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
				}
				for($i=1; $i<=$ordercount; $i++){
					$options["order$i"] = GetFormData($f, $s, "order$i");
				}
				foreach($options as $index => $option){
					if($option == "")
						unset($options[$index]);
				}
				$_SESSION['contacts']['options'] = $options;
				redirect();
			}
		}
	}
} else {
	$reload = 1;
}

if($reload){
	ClearFormData($f);
	$options = isset($_SESSION['contacts']['options']) ? $_SESSION['contacts']['options'] : array();

	if(isset($options['personid']) || isset($options['phone']) || isset($options['email']))
		$radio = "person";
	else
		$radio = "criteria";
	PutFormData($f, $s, "radioselect", $radio);
	PutFormData($f, $s, 'personid', isset($options['personid']) ? $options['personid'] : "", 'text');
	PutFormData($f, $s, 'phone', isset($options['phone']) ? $options['phone'] : "", 'phone', "7", "10");
	PutFormData($f, $s, 'email', isset($options['email']) ? $options['email'] : "", 'email');
	for($i=1;$i<=$ordercount;$i++){
		$order="order$i";
		if($i==1){
			PutFormData($f, $s, $order, isset($options[$order]) ? $options[$order] : "p.pkey");
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

$PAGE = "system:contacts";
$TITLE = "Contact Database";

include_once("nav.inc.php");

NewForm($f);
buttons(submit($f, 'refresh', 'Refresh'), submit($f, 'showall','Show All Contacts'));
startWindow("Contact Search", "padding: 3px;");
?>
<table border="0" cellpadding="3" cellspacing="0" width="100%">
	<tr valign="top"><th align="right" class="windowRowHeader bottomBorder">Search:</th>
		<td class="bottomBorder">
			<table border="0" cellpadding="3" cellspacing="0" width="100%">
				<tr>
					<td>
						<table>
							<tr>
								<td><? NewFormItem($f, $s, "radioselect", "radio", null, "criteria", "onclick='hide(\"singleperson\"); show(\"searchcriteria\")'");?> By Criteria</td>
								<td><? NewFormItem($f, $s, "radioselect", "radio", null, "person", "onclick='hide(\"searchcriteria\"); show(\"singleperson\")'");?> By Person</td>
							</tr>
						</table>
					</td>
				</tr>
				<tr>
					<td>
						<table border="0" cellpadding="3" cellspacing="0" width="100%" id="searchcriteria">
							<tr>
								<td>
								<?
									if(!isset($_SESSION['contactrules']) || is_null($_SESSION['contactrules']))
										$_SESSION['contactrules'] = false;

									$RULES = &$_SESSION['contactrules'];
									$RULEMODE = array('multisearch' => true, 'text' => true, 'reldate' => true);

									include("ruleeditform.inc.php");
								?>
								</td>
							</tr>
						</table>
					</td>
				</tr>
				<tr>
					<td>
						<table id="singleperson">
							<tr><td>Person ID: </td><td><? NewFormItem($f, $s, 'personid', 'text', '15'); ?></td></tr>
							<tr><td>Phone Number: </td><td><? NewFormItem($f, $s, 'phone', 'text', '12'); ?></td></tr>
							<tr><td>Email Address: </td><td><? NewFormItem($f, $s, 'email', 'text', '100'); ?></td></tr>
							<tr><td><?=submit($f,'search', 'Search')?></td></tr>
						</table>
					</td>
				</tr>
			</table>
		</td>
	</tr>
	<tr valign="top"><th align="right" class="windowRowHeader bottomBorder">Display Fields:</th>
		<td class="bottomBorder">
	<?
			select_metadata('searchresultstable', 5, $fields);
	?>
		</td>
	</tr>
	<tr valign="top"><th align="right" class="windowRowHeader bottomBorder">Sort By:</th>
		<td class="bottomBorder">
<?
			selectOrderBy($f, $s, $ordercount, $ordering);
?>
		</td>
	</tr>
</table>
<script>
	<?
		if(isset($options['personid'])|| isset($options['phone']) || isset($options['email'])){
			?>hide("searchcriteria");<?
		} else {
			?>hide("singleperson");<?
		}
	?>
</script>

	<br>
	<?

if(isset($options['personid']) || isset($options['phone']) || isset($options['email']) || (isset($options['rules']) && $options['rules'] != "") || isset($options['showall'])){
	$reportgenerator->generate();
}
buttons();
endWindow();
EndForm();

include_once("navbottom.inc.php");
?>
