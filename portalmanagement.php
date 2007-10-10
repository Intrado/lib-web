<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
require_once("inc/common.inc.php");
include_once("inc/securityhelper.inc.php");
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

////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
/*
if (!$USER->authorize('PortalManagement')) {
	redirect('unauthorized.php');
}
*/

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

$RULES = false;
if(isset($options['rules']) && $options['rules']){
	$RULES = $options['rules'];
}

$fields = FieldMap::getOptionalAuthorizedFieldMaps();

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
$reportgenerator->format = "html";

$f = "person";
$s = "all";
$reloadform = 0;

if(CheckFormSubmit($f,$s) || CheckFormSubmit($f, 'showall') || CheckFormSubmit($f, 'search') || CheckFormSubmit($f, 'refresh') || CheckFormSubmit($f, 'generate')){
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
				if(GetFormData($f, $s, "radioselect") == "person"){
					unset($options['rules']);
					$options['pkey'] = GetFormData($f, $s, 'pkey');
				} else {
					unset($options['pkey']);
					
					if($rule = getRuleFromForm($f, $s)){
						if(!isset($options['rules']))
							$options['rules'] = array();
						$options['rules'][] = $rule;
						$rule->id = array_search($rule, $options['rules']);
						$options['rules'][$rule->id] = $rule;
					}
				}
				foreach($options as $index => $option){
					if($option == "")
						unset($options[$index]);
				}
				$_SESSION['portal']['options'] = $options;
				if(CheckFormSubmit($f, 'generate')){
					$reportinstance->setParameters($options);
					$reportgenerator->reportinstance = $reportinstance;
					$reportgenerator->generateQuery();
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
	}
} else {
	$reloadform = 1;
}
if($reloadform){
	ClearFormData($f);
	$options = isset($_SESSION['portal']['options']) ? $_SESSION['portal']['options'] : array();

	if(isset($options['pkey']))
		$radio = "person";
	else
		$radio = "criteria";
	PutFormData($f, $s, "radioselect", $radio);
	PutFormData($f, $s, 'pkey', isset($options['pkey']) ? $options['pkey'] : "", 'text');

	putRuleFormData($f, $s);
}

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
$PAGE = "admin:portalmanagement";
$TITLE = "Portal Management";

include_once("nav.inc.php");

NewForm($f);
buttons(submit($f, 'refresh', 'Refresh'), submit($f, 'showall','Show All Contacts'), submit($f, "generate", "Generate Tokens"));
startWindow("Contact Search", "padding: 3px;");

if(isset($options['pkey'])){
	$singlepersondisplay = '';
	$searchbardisplay = 'style="display:none"';
} else {
	$singlepersondisplay = 'style="display:none"';
	$searchbardisplay = '';
}
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
						<table border="0" cellpadding="3" cellspacing="0" width="100%" id="searchcriteria" <?=$searchbardisplay?> >
							<tr>
								<td>
								<?
									//$RULES is declared above
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
						<table id="singleperson" <?=$singlepersondisplay?> >
							<tr><td>Person ID: </td><td><? NewFormItem($f, $s, 'pkey', 'text', '15'); ?></td><td><?=submit($f,'search', 'Search')?></td></tr>
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
</table>
	<br>
	<?

if(isset($options['pkey']) || (isset($options['rules']) && $options['rules'] != "") || isset($options['showall'])){
	$reportgenerator->generate();
}
buttons();
endWindow();
EndForm();

include_once("navbottom.inc.php");


////////////////////////////////////////////////////////////////////////////////
// Functions
////////////////////////////////////////////////////////////////////////////////
//data is an array of arrays 
//if there is an array inside a subarray of data ex array[][][], extract it and place
//into own row.
//basically try to an array of array of arrays into an array of arrays that has
//holes
function flattenData($data){
	$newdata = array();
	$count = 0;
	$colcount=0;
	$rowcount=0;
	$maxrowcount = 0;
	$newline = false;
	foreach($data as $row){
		$newdata[$count] = array_fill(0, count($row), "");
		foreach($row as $item){
			if(is_array($item)){
				$newline = true;
				foreach($item as $subitem){
					if(!isset($newdata[$count+$rowcount]) || !is_array($newdata[$count+$rowcount]))
						$newdata[$count+$rowcount] = array_fill(0, count($row), "");
					$newdata[$count+$rowcount][$colcount] = $subitem;
					$rowcount++;
				}
			} else {
				$newdata[$count][$colcount] = $item;
			}
			$colcount++;
			if($maxrowcount < $rowcount)
				$maxrowcount = $rowcount;
			$rowcount=0;
		}
		$colcount=0;
		if($newline)
			$count += $maxrowcount;
		else
			$count++;
		$maxrowcount = 0;
	}
	return $newdata;
}
?>