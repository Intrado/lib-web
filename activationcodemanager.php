<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
require_once("inc/common.inc.php");
require_once("inc/securityhelper.inc.php");
require_once("inc/table.inc.php");
require_once("inc/html.inc.php");
require_once("inc/form.inc.php");
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
require_once("obj/Person.obj.php");
require_once("inc/rulesutils.inc.php");
require_once("obj/PortalReport.obj.php");
require_once("ruleeditform.inc.php");


////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////

if (!getSystemSetting("_hasportal", false) || !$USER->authorize('portalaccess')) {
	redirect('unauthorized.php');
}


////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////


if(isset($_GET['clear']) && $_GET['clear']){
	unset($_SESSION['portal']['options']);
	$_SESSION['saved_report'] = false;
	redirect();
}

$options = isset($_SESSION['portal']['options']) ? $_SESSION['portal']['options'] : array("reporttype" => "portal");

if(isset($_GET['deleterule'])) {
	if(isset($options['rules'])){
		unset($options['rules'][$_GET['deleterule']]);
		if(!count($options['rules']))
			unset($options['rules']);
	}
	$_SESSION['portal']['options'] = $options;
	redirect();
}

if(isset($_GET['hideactivecodes'])){
	if($_GET['hideactivecodes'] == "true"){
		$options['hideactivecodes'] = 1;
	} else {
		$options['hideactivecodes'] = 0;
	}
	$_SESSION['portal']['options'] = $options;
	redirect();
}

if(isset($_GET['hideassociated'])){
	if($_GET['hideassociated'] == "true"){
		$options['hideassociated'] = 1;
	} else {
		$options['hideassociated'] = 0;
	}
	$_SESSION['portal']['options'] = $options;
	redirect();
}

if($USER->authorize('generatebulktokens')){
	if(isset($_GET['generate'])){
		$reportinstance = new ReportInstance();
		$reportgenerator = new PortalReport();
		$reportinstance->setParameters($options);
		$reportgenerator->reportinstance = $reportinstance;
		$reportgenerator->generateQuery();
		if($reportgenerator->query){
			$result = Query($reportgenerator->query);
			$data = array();
			while($row = DBGetRow($result)){
				$data[] = $row[1];
			}
			generatePersonTokens($data);
		}
		redirect();
	}
}

$RULES = false;
if(isset($options['rules']) && $options['rules']){
	$RULES = $options['rules'];
}

$fields = FieldMap::getOptionalAuthorizedFieldMaps() + FieldMap::getOptionalAuthorizedFieldMapsLike('g');

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
	$pagestart = $_GET['pagestart']+0;
}
$options['pagestart'] = $pagestart;

$reportinstance->setParameters($options);
$reportgenerator = new PortalReport();
$reportgenerator->reportinstance = $reportinstance;
$reportgenerator->userid = $USER->id;
if(isset($_GET['csv']) && $_GET['csv']){
	$reportgenerator->format = "csv";
} else {
	$reportgenerator->format = "html";
}
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
		if( CheckFormSection($f, $s) ) {
			error('There was a problem trying to save your changes', 'Please verify that all required field information has been entered properly');
		} else {

			if(CheckFormSubmit($f, "showall")){
				$options = array('reporttype' => "portal");
				$options['showall'] = true;
				$_SESSION['portal']['options'] = $options;
				redirect();
			} else {
				$options['reporttype']="portal";
				if(CheckFormSubmit($f, 'search') || CheckFormSubmit($f, $s))
					unset($options['showall']);

				if($rule = getRuleFromForm($f, $s)){
					if(!isset($options['rules']))
						$options['rules'] = array();
					$options['rules'][] = $rule;
					$rule->id = array_search($rule, $options['rules']);
					$options['rules'][$rule->id] = $rule;
				}

				foreach($options as $index => $option){
					if($option == "")
						unset($options[$index]);
				}
				$options['hideactivecodes'] = GetFormData($f, $s, "hideactivecodes");
				$options['hideassociated'] = GetFormData($f, $s, "hideassociated");
				$_SESSION['portal']['options'] = $options;
				redirect();
			}
		}
	}
} else {
	$reloadform = 1;
}

if($reloadform){
	ClearFormData($f);
	$options = isset($_SESSION['portal']['options']) ? $_SESSION['portal']['options'] : array();

	putRuleFormData($f, $s);
	PutFormData($f, $s, "hideactivecodes", isset($options['hideactivecodes']) ? $options['hideactivecodes'] : 0, "bool", 0, 1);
	PutFormData($f, $s, "hideassociated", isset($options['hideassociated']) ? $options['hideassociated'] : 0, "bool", 0, 1);
}

////////////////////////////////////////////////////////////////////////////////
// Functions
////////////////////////////////////////////////////////////////////////////////

//index 4 is token
//index 5 is expiration date
function fmt_activation_code($row, $index){
	if($row[$index]){
		if(strtotime($row[5]) < strtotime("today")){
			return "Expired";
		}
	}
	return $row[$index];
}

if($reportgenerator->format == "csv"){
	$reportgenerator->generate();
} else {

	////////////////////////////////////////////////////////////////////////////////
	// Display
	////////////////////////////////////////////////////////////////////////////////
	$PAGE = "system:contacts";
	$TITLE = "Activation Code Manager";

	include_once("nav.inc.php");

	NewForm($f);
	if($USER->authorize('generatebulktokens')){
		$reportgenerator->generateQuery();
		$query = $reportgenerator->testquery;
		if($query != ""){
			$result = QuickQuery($query);
		} else {
			$result = false;
		}
		if($result){
			$extrajs = "if(confirmGenerateActive())";
		} else {
			$extrajs = "if(confirmGenerate())";
		}
		buttons(button("Back", null, "contacts.php"), submit($f, 'refresh', 'Refresh'), submit($f, 'showall','Show All Contacts'), button("Generate Activation Codes", $extrajs . " window.location='?generate=1'"));
	} else {
		buttons(button("Back", null, "contacts.php"), submit($f, 'refresh', 'Refresh'), submit($f, 'showall','Show All Contacts'));
	}
	startWindow("Contact Search", "padding: 3px;");

	?>
	<table border="0" cellpadding="3" cellspacing="0" width="100%">
		<tr valign="top"><th align="right" class="windowRowHeader bottomBorder">Search:</th>
			<td class="bottomBorder">
				<table border="0" cellpadding="3" cellspacing="0" width="100%">
					<tr>
						<td>
							<table border="0" cellpadding="3" cellspacing="0" width="100%" id="searchcriteria">
								<tr>
									<td>
									<?
										//$RULES is declared above
										$RULEMODE = array('multisearch' => true, 'text' => true, 'reldate' => true, 'numeric' => true);

										//include("ruleeditform.inc.php");
										drawRuleTable($f, $s, false, true, true, true);

									?>
									</td>
								</tr>
							</table>
						</td>
					</tr>
				</table>
			</td>
		</tr>
		<tr valign="top"><th align="right" class="windowRowHeader bottomBorder">Display Fields:</th>
			<td class="bottomBorder">
		<?
				select_metadata('portalresultstable', 5, $fields);
		?>
			</td>
		</tr>
		<tr valign="top"><th align="right" class="windowRowHeader bottomBorder">Filter:</th>
			<td class="bottomBorder">
				<table>
					<tr><td><? NewFormItem($f, $s, "hideactivecodes", "checkbox", NULL, NULL, 'onclick="location.href=\'?hideactivecodes=\' + this.checked"') ?>Hide people with unexpired codes</td></tr>
					<tr><td><? NewFormItem($f, $s, "hideassociated", "checkbox", NULL, NULL, 'onclick="location.href=\'?hideassociated=\' + this.checked"') ?>Hide people with Contact Manager accounts</td></tr>
				</table>
			</td>
		</tr>
		<?if((isset($options['rules']) && $options['rules'] != "") || isset($options['showall'])){?>
			<tr>
				<th align="right" class="windowRowHeader bottomBorder">Output Format:</th>
				<td class="bottomBorder"><a href="activationcodemanager.php/report.csv?csv=true">CSV</a></td>
			</tr>
		<?}?>
	</table>
		<?
	endWindow();

	if((isset($options['rules']) && $options['rules'] != "") || isset($options['showall'])){
		$reportgenerator->generate();
	}
	buttons();
	EndForm();
?>
<script SRC="script/calendar.js"></script>
<?
	include_once("navbottom.inc.php");
?>
<script>
	function confirmGenerate(){
<? if($reportgenerator->reporttotal > 0){ ?>
		return confirm("Are you sure you want to generate activation codes for these people?");
<? } else { ?>
		window.alert("There are no people in this list.");
		return false;
<? } ?>
	}
	function confirmGenerateActive(){
		return confirm("Some activation codes exist in this list.  Are you sure you want to overwrite them?");
	}
</script>
<?
}
?>
