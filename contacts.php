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
require_once("inc/rulesutils.inc.php");
include_once("ruleeditform.inc.php");

////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$USER->authorize('viewcontacts')) {
	redirect('unauthorized.php');
}

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

if(isset($_GET['clear']) && $_GET['clear']){
	unset($_SESSION['contacts']['options']);
	$_SESSION['saved_report'] = false;
	redirect();
}
$options = isset($_SESSION['contacts']['options']) ? $_SESSION['contacts']['options'] : array("reporttype" => "contacts");

if(isset($_GET['deleterule'])) {
	if(isset($options['rules'])){
		unset($options['rules'][$_GET['deleterule']]);
		if(!count($options['rules']))
			unset($options['rules']);
	}
	$_SESSION['contacts']['options'] = $options;
	redirect();
}

$RULES = false;
if(isset($options['rules']) && $options['rules']){
	$RULES = $options['rules'];
}

$ordercount = 3;
$ordering = ContactsReport::getOrdering();

$ffields = FieldMap::getOptionalAuthorizedFieldMapsLike('f');
$gfields = FieldMap::getOptionalAuthorizedFieldMapsLike('g');
$fields = $ffields + $gfields;

$activefields = array();
$fieldlist = array();
foreach($fields as $field){
	// used in pdf
	if(isset($_SESSION['report']['fields'][$field->fieldnum]) && $_SESSION['report']['fields'][$field->fieldnum]){
		$activefields[] = $field->fieldnum;
	}
}
$options['activefields'] = implode(",",$activefields);
$reportinstance = new ReportInstance();

$pagestart = 0;
if(isset($_GET['pagestart'])){
	$pagestart = $_GET['pagestart'];
}
$options['pagestart'] = $pagestart;

$reportinstance->setParameters($options);
$reportgenerator = new ContactsReport();
$reportgenerator->reportinstance = $reportinstance;
$reportgenerator->userid = $USER->id;
$reportgenerator->format = "html";

$searchby = "criteria";


$f = "person";
$s = "all";
$reloadform = 0;

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
		
		TrimFormData($f, $s, 'personid');
		TrimFormData($f, $s, 'phone');
		TrimFormData($f, $s, 'email');
		
		$radio = GetFormData($f, $s, "radioselect");
		if ($radio == "criteria" || GetFormData($f, $s, "showall") || GetFormData($f, $s, "refresh")) {
			$searchby = "criteria";
			PutFormData($f, $s, 'personid',"", 'text');
			PutFormData($f, $s, 'phone',"", 'phone', "7", "10");
			PutFormData($f, $s, 'email',"", 'email');				
		} elseif ($radio == "person"){
			$searchby = "person";
		}
		
		if( CheckFormSection($f, $s) ) {
			error('The search field could not be processed', 'Please verify that all required field information has been entered properly');					
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
				if($radio == "person"){
					unset($options['rules']);
					$options['personid'] = GetFormData($f, $s, 'personid');
					$options['phone']= Phone::parse(GetFormData($f, $s, 'phone'));
					$options['email'] = GetFormData($f, $s, 'email');
				} else {
					unset($options['personid']);
					unset($options['phone']);
					unset($options['email']);
					PutFormData($f, $s, 'personid',"", 'text');
					PutFormData($f, $s, 'phone',"", 'phone', "7", "10");
					PutFormData($f, $s, 'email',"", 'email');
					
					if($rule = getRuleFromForm($f, $s)){
						if(!isset($options['rules']))
							$options['rules'] = array();
						$options['rules'][] = $rule;
						$rule->id = array_search($rule, $options['rules']);
						$options['rules'][$rule->id] = $rule;
					}
					$reloadform = 1;
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
	$reloadform = 1;
}

if($reloadform){
	ClearFormData($f);
	$options = isset($_SESSION['contacts']['options']) ? $_SESSION['contacts']['options'] : array();

	if(isset($options['personid']) || isset($options['phone']) || isset($options['email'])) {
		$radio = "person";
		$searchby = "person";
	} else {
		$radio = "criteria";
		$searchby = "criteria";
		
	}
	PutFormData($f, $s, "radioselect", $radio);
	PutFormData($f, $s, 'personid', isset($options['personid']) ? $options['personid'] : "", 'text');
	PutFormData($f, $s, 'phone', isset($options['phone']) ? Phone::format($options['phone']) : "", 'phone', "7", "10");
	PutFormData($f, $s, 'email', isset($options['email']) ? $options['email'] : "", 'email');
	for($i=1;$i<=$ordercount;$i++){
		$order="order$i";
		if($i==1){
			PutFormData($f, $s, $order, isset($options[$order]) ? $options[$order] : "p.pkey");
		} else {
			PutFormData($f, $s, $order, isset($options[$order]) ? $options[$order] : "");
		}
	}

	putRuleFormData($f, $s);
}

////////////////////////////////////////////////////////////////////////////////
// Functions
////////////////////////////////////////////////////////////////////////////////


////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$PAGE = "system:contacts";
$TITLE = "Contact Database";

include_once("nav.inc.php");

NewForm($f);
buttons(submit($f, 'refresh', 'Refresh'), submit($f, 'showall','Show All Contacts'), (getSystemSetting("_hasportal", false) && $USER->authorize('portalaccess') ? button("Manage Activation Codes", null, "activationcodemanager.php") : null) );
startWindow("Contact Search" . help('ContactDatabase_ContactSearch'), "padding: 3px;");
?>
<table border="0" cellpadding="3" cellspacing="0" width="100%">
	<tr valign="top"><th align="right" class="windowRowHeader bottomBorder">Search:</th>
		<td class="bottomBorder">
			<table border="0" cellpadding="3" cellspacing="0" width="100%">
				<tr>
					<td>
						<table>
							<tr>
								<td><? NewFormItem($f, $s, "radioselect", "radio", null, "criteria", "onclick='$(\"singleperson\").hide(); $(\"searchcriteria\").show()'");?> By Criteria</td>
								<td><? NewFormItem($f, $s, "radioselect", "radio", null, "person", "onclick='$(\"searchcriteria\").hide(); $(\"singleperson\").show()'");?> By Person</td>
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
									//$RULES is declared above
									$RULEMODE = array('multisearch' => true, 'text' => true, 'reldate' => true, 'numeric' => true);

									drawRuleTable($f, $s, false, true, true, true);

								?>
								</td>
							</tr>
						</table>
					</td>
				</tr>
				<tr>
					<td>
						<table id="singleperson">
							<tr><td>Person ID: </td><td><? NewFormItem($f, $s, 'personid', 'text', 15, 255); ?></td></tr>
							<tr><td>Phone Number: </td><td><? NewFormItem($f, $s, 'phone', 'text', '15'); ?></td></tr>
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
		if($searchby == "person"){
			?>$("searchcriteria").hide();<?
		} else {
			?>$("singleperson").hide();<?
		}
	?>
</script>
<?
endWindow();

if(isset($options['personid']) || isset($options['phone']) || isset($options['email']) || (isset($options['rules']) && $options['rules'] != "") || isset($options['showall'])){
	$reportgenerator->generate();
}
buttons();
EndForm();

?>
<script SRC="script/calendar.js"></script>
<?

include_once("navbottom.inc.php");
?>
