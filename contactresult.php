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
if (!$USER->authorize('createreport')) {
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

$f="fields";
$s="sort";
$reload = 0;

$phone = "";
$email = "";

$fields = DBFindMany("FieldMap", "from fieldmap where options not like '%firstname%' and options not like '%lastname%'");
foreach($fields as $key => $fieldmap){
	if(!$USER->authorizeField($fieldmap->fieldnum))
		unset($fields[$key]);
}
$firstname = DBFind("FieldMap", "from fieldmap where options like '%firstname%'");
$lastname = DBFind("FieldMap", "from fieldmap where options like '%lastname%'");


$orders = array("order1", "order2", "order3");


$options = isset($_SESSION['contacts']['options']) ? $_SESSION['contacts']['options'] : array();
$activefields = array();
$fieldlist = array();
foreach($fields as $field){
	// used in html
	$fieldlist[$field->fieldnum] = $field->name;
	
	// used in pdf
	if(isset($_SESSION['fields'][$field->fieldnum]) && $_SESSION['fields'][$field->fieldnum]){
		$activefields[] = $field->fieldnum; 
	}
}
$reportinstance = new ReportInstance();

$reportinstance->setFields($fieldlist);
$reportinstance->setActiveFields($activefields);

$pagestart = 0;
if(isset($_REQUEST['pagestart'])){
	$pagestart = $_REQUEST['pagestart'];
}
$options['pagestart'] = $pagestart;

$reportinstance->setParameters($options);
$reportgenerator = new ContactsReport();
$reportgenerator->reportinstance = $reportinstance;
$reportgenerator->userid = $USER->id;

if(isset($_REQUEST['csv']) && $_REQUEST['csv']){
	$reportgenerator->format = "csv";
} else if(isset($_REQUEST['pdf']) && $_REQUEST['pdf']){
	$reportgenerator->format = "pdf";
} else {
	$reportgenerator->format = "html";
}
if(CheckFormSubmit($f,$s))
{
	//check to see if formdata is valid
	if(CheckFormInvalid($f))
	{
		print '<div class="warning">Form was edited in another window, reloading data.</div>';
	}
	else
	{
		MergeSectionFormData($f, $s);
		//do check
		if( CheckFormSection($f, $s) ) {
			error('There was a problem trying to save your changes', 'Please verify that all required field information has been entered properly');
		} else {
			$orderquery = "";
			$orders = array("order1", "order2", "order3");
			foreach($orders as $order){
				$options[$order] = GetFormData($f, $s, $order);
				$_SESSION['contacts']['options'] = $options;
			}

			$_SESSION['contacts']['options'] = $options;
			redirect();
		}
	}
} else {
	$reload = 1;
}
if($reload){
	ClearFormData($f);
	$options = isset($_SESSION['contacts']['options']) ? $_SESSION['contacts']['options'] : array();
	foreach($orders as $order){
		PutFormData($f, $s, $order, isset($options[$order]) ? $options[$order] : "");
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


if($reportgenerator->format != "html"){
	if($reportgenerator->format == "pdf"){
		$name = secure_reportname();
		$params = createPdfParams($name);
		$reportgenerator->generate($params);

	
		header("Pragma: private");
		header("Cache-Control: private");
		header("Content-disposition: attachment; filename=$name");
		header("Content-type: application/pdf");	
		session_write_close();
		$fp = fopen($name, "r");
		while($line = fgets($fp)){
			echo $line;
		}
		unlink($name);
	} else {
		$reportgenerator->generate();
	}
} else {
	$PAGE = "system:contact search";
	$TITLE = "Contact Search";
	
	include_once("nav.inc.php");
	NewForm($f);
	
	buttons(button("back", "location.href='contactsearch.php'"),submit($f, $s, "refresh", "refresh"));
	startWindow("Display Options", "padding: 3px;");
	?>
	<table border="0" cellpadding="3" cellspacing="0" width="100%">
		<tr valign="top"><th align="right" class="windowRowHeader bottomBorder">Fields:</th>
			<td class="bottomBorder">
	<? 		
			select_metadata('searchresultstable', 5, $fields); 
	?>
			</td>
		</tr>
		<tr valign="top"><th align="right" class="windowRowHeader">Sort by:</th>
			<td>
				<table>
					<tr>
	<?
	
					foreach($orders as $order){
	?>
					<td>
	<?
						NewFormItem($f, $s, $order, 'selectstart');
						NewFormItem($f, $s, $order, 'selectoption', " -- Order --", "");
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
		<tr><th align="right" class="windowRowHeader">Output Format:</th>
			<td>
				<a href="contactresult.php?csv=1">CSV</a>&nbsp;|&nbsp;<a href="contactresult.php?pdf=1">PDF</a>
			</td>
		</tr>
	</table>
	<?
	endWindow();
	
	?>
	<br>
	<?
	
	$reportgenerator->generate();
	buttons();
	EndForm();
	include("navbottom.inc.php");
}
?>
