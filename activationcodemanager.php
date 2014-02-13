<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
include_once("inc/common.inc.php");
include_once("inc/form.inc.php");
include_once("inc/table.inc.php");
include_once("inc/html.inc.php");
include_once("inc/formatters.inc.php");
include_once("inc/date.inc.php");
include_once("obj/Person.obj.php");
include_once("obj/PeopleList.obj.php");
include_once("obj/ListEntry.obj.php");
include_once("obj/RenderedList.obj.php");
include_once("obj/Validator.obj.php");
require_once("obj/Form.obj.php");
include_once("obj/Rule.obj.php");
include_once("obj/FieldMap.obj.php");
require_once("obj/FormItem.obj.php");
require_once("obj/Phone.obj.php");
require_once("obj/Email.obj.php");
require_once("obj/Sms.obj.php");
require_once("inc/securityhelper.inc.php");
include_once("ruleeditform.inc.php");
require_once("inc/rulesutils.inc.php");
require_once("obj/FormRuleWidget.fi.php");
require_once("obj/SectionWidget.fi.php");
require_once("obj/ValSections.val.php");
require_once("obj/ValRules.val.php");
require_once("inc/reportutils.inc.php");
require_once("inc/list.inc.php");

include_once("obj/Address.obj.php");
include_once("obj/Language.obj.php");
include_once("obj/JobType.obj.php");

//require_once("inc/reportgeneratorutils.inc.php");
//require_once("obj/ReportGenerator.obj.php");
//require_once("obj/ReportInstance.obj.php");
require_once("obj/UserSetting.obj.php");
//require_once("inc/rulesutils.inc.php");
//require_once("obj/PortalReport.obj.php");

require_once("obj/RenderedList.obj.php");
require_once("obj/RenderedListCM.obj.php");


////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////

if (!getSystemSetting("_hasportal", false) || !$USER->authorize('portalaccess')) {
	redirect('unauthorized.php');
}

////////////////////////////////////////////////////////////////////////////////
// Action/Request Processing
////////////////////////////////////////////////////////////////////////////////

//handle list search mode switches (contactsearchformdata.inc.php)
if (isset($_GET['listsearchmode'])) {

	if ($_GET['listsearchmode'] == "rules" && !isset($_SESSION['listsearch']['rules'])) {
		unset($_SESSION['listsearch']); //defaults to rules mode with no search criteria
	}
	
	if ($_GET['listsearchmode'] == "individual" && !isset($_SESSION['listsearch']['individual'])) {
		$_SESSION['listsearch'] = array ("individual" => array ("quickaddsearch" => ''));
	}
	
	if ($_GET['listsearchmode'] == "sections" && !isset($_SESSION['listsearch']['sectionx'])) {
		$_SESSION['listsearch'] = array ("sectionids" => array ());
	}
	
	if ($_GET['listsearchmode'] == "showall" && !isset($_SESSION['listsearch']['showall'])) {
		$_SESSION['listsearch'] = array("showall" => true);
	}
}

if (isset($_GET['hideactivecodes']))
	$_SESSION['hideactivecodes'] = $_GET['hideactivecodes'] == "true" ? true : false;

if (isset($_GET['hideassociated']))
	$_SESSION['hideassociated'] = $_GET['hideassociated'] == "true" ? true : false;

// if csv download, else html
if (isset($_GET['csv']) && $_GET['csv'])
	$csv = true;
else
	$csv = false;

// basic rendered list initialization
$renderedlist = new RenderedListCM();
$extrawheresql = "";
if (isset($_SESSION['hideactivecodes']) && $_SESSION['hideactivecodes']) {
	$extrawheresql .= " and not exists (select * from portalpersontoken ppt where ppt.personid = p.id and ppt.expirationdate >= curdate()) ";
}
if (isset($_SESSION['hideassociated']) && $_SESSION['hideassociated']) {
	$extrawheresql .= " and not exists (select * from portalperson pp2 where pp2.personid = p.id) ";
}

$renderedlist->setExtraWhereSql($extrawheresql);

$pagelimit = 100;
$renderedlist->pagelimit = $pagelimit;

$canGenerateBulkTokens = $USER->authorize('generatebulktokens');

// FORM DATA

$checkHideActiveCodes = (!empty($_SESSION['hideactivecodes'])) ? 'checked' : '';
$checkHideAssociated = (!empty($_SESSION['hideassociated'])) ? 'checked' : '';

$buttons = array(
	icon_button(_L('Download CSV'), "disk",null,"activationcodemanager.php/report.csv?csv=true")
);
if ($canGenerateBulkTokens) {
	$buttons[] = icon_button("Generate Activation Codes", "key_go", "if(confirmGenerate()) window.location='activationcodebulkgenerate.php'", "activationcodebulkgenerate.php");
}

$buttons[] = icon_button(_L('Back to Contacts'),"fugue/arrow_180",null,"contacts.php");

$redirectpage = "activationcodemanager.php";

$additionalformdata = array();
$additionalformdata["filter"] = array(
	"label" => _L("Filter"),
	"control" => array("FormHtml", "html" => "
		<div><input type='checkbox' id='checkboxHideActiveCodes' onclick='location.href=\"?hideactivecodes=\" + this.checked' $checkHideActiveCodes><label for='checkboxHideActiveCodes'>"._L('Hide people with unexpired codes')."</label></div>
		<div><input type='checkbox' id='checkboxHideAssociated' onclick='location.href=\"?hideassociated=\" + this.checked' $checkHideAssociated><label for='checkboxHideAssociated'>"._L('Hide people with Contact Manager accounts')."</label></div>
	"),
	"helpstep" => 2
);

$disablerenderedlistajax = true;
include_once("contactsearchformdata.inc.php");

// Prepare RenderedList options
$validsortfields = array("pkey" => "ID#");
foreach (FieldMap::getAuthorizedFieldMapsLike("f") as $fieldmap) {
	$validsortfields[$fieldmap->fieldnum] = $fieldmap->name;
}

$ordering = isset($_SESSION['showlistorder']) ? $_SESSION['showlistorder'] : array(array("f02", false),array("f01",false));
for ($x = 0; $x < 3; $x++) {
	if (!isset($_GET["sort$x"]))
		continue;
	if ($_GET["sort$x"] == "")
		unset($ordering[$x]);
	else if (isset($validsortfields[$_GET["sort$x"]])) {
		$ordering[$x] = array($_GET["sort$x"],isset($_GET["desc$x"]));
	}
}
$_SESSION['showlistorder'] = $ordering = array_values($ordering); //remove gaps

$pagestart = (isset($_GET['pagestart']) ? $_GET['pagestart'] + 0 : 0);
$renderedlist->setPageOffset($pagestart);
$renderedlist->orderby = $ordering;

$data = $renderedlist->getPageData();
$total = $renderedlist->getTotal();

// find if any active tokens in display results
$hasactivecodes = false;
$personsql = $renderedlist->getPersonSql(false);
if ($personsql != "") {
	$personids = QuickQueryList($personsql); // TODO page this
	if (count($personids) > 0) {
		$query = "select 1 from portalpersontoken where exists (select * from portalpersontoken where personid in (".repeatWithSeparator("?", ",", count($personids)).") and token is not null and expirationdate > curdate() limit 1)";
		$hasactivecodes = QuickQuery($query, false, $personids);
	}
}

////////////////////////////////////////////////////////////////////////////////
// Functions
////////////////////////////////////////////////////////////////////////////////
	
/**
 * similar to list.inc.php function
 */
function showRenderedListTableCM($data, $total, $pagestart, $pagelimit, $validsortfields, $ordering) {
	static $tableidcounter = 1;
	
	$titles = array();
	$formatters = array();
	$repeatedcolumns = array(6); // contact manager accounts
	$groupby = 0; //personid

	$titles[1] = "Unique ID";
	$formatters[1] = "fmt_persontip";
	
	$titles[2] = FieldMap::getName(FieldMap::getFirstNameField());
	$titles[3] = FieldMap::getName(FieldMap::getLastNameField());
	
	$titles[4] = "Activation Code";
	$formatters[4] = "fmt_activation_code";
	
	$titles[5] = "Expiration Date";
	
	$titles[6] = "Contact Manager Account(s)";
	
	$titles[7] = getSystemSetting("organizationfieldname","Organization");
	
	//after that, show F fields, then G fields
	//optional F fields start at index 8 (skip f01, f02)
	//save some data for field show/hide tool
	
	//show field togglers, reuse whats in reportutils.inc.php (needs to be refactored)
	//FIXME UGLY HACK: need to set global session var to control behavior of this function
	//FIXME UGLY HACK: this function also has the side effect of loading $_SESSION['report']['fields'] display prefs
	$_SESSION['saved_report'] = false; //this causes checkbox states to be loaded/saved in userprefs
	
	$tableid = "renderedlist". $tableidcounter++;
	$optionalfields = array_merge(FieldMap::getOptionalAuthorizedFieldMapsLike('f'), FieldMap::getAuthorizedFieldMapsLike('g'));
	$optionalfieldstart = 6; //table col of last non optional field
	
?>
	<table border="0" width="100%">
	<tr>
		<td>
<?
		showSortMenu($validsortfields,$ordering);
?>		
		</td>
		<td>
<?
		show_field_visibility_selector($tableid, $optionalfields, $optionalfieldstart);
?>		
		</td>
<?

	//now use session display prefs to set up titles and whatnot for the optional fields
	$i = 8;
	foreach ($optionalfields as $field) {
		//add a formatter for language field
		if ($field->fieldnum == FieldMap::getLanguageField())
			$formatters[$i] = "fmt_languagecode";
		
		if (isset($_SESSION['fieldvisibility'][$field->fieldnum]))
			$titles[$i++] = $field->name;
		else
			$titles[$i++] = "@" . $field->name;
	}
?>
		<td halign="right">
<?
		showPageMenu($total,$pagestart,$pagelimit);
?>		
		</td>
	</tr>
	</table>
<?	
	

	echo '<table id="'.$tableid.'" width="100%" cellpadding="3" cellspacing="1" class="list">';
	showTable($data, $titles, $formatters, $repeatedcolumns, $groupby);
	echo "\n</table>";
	showPageMenu($total,$pagestart,$pagelimit);
}


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


////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
if ($csv) {
	$optionalfields = array_merge(FieldMap::getOptionalAuthorizedFieldMapsLike('f'), FieldMap::getAuthorizedFieldMapsLike('g'));

	$titles = array();
	$titles[1] = "Unique ID";
	$titles[2] = FieldMap::getName(FieldMap::getFirstNameField());
	$titles[3] = FieldMap::getName(FieldMap::getLastNameField());
	$titles[4] = "Activation Code";
	$titles[5] = "Expiration Date";
	//$titles[6] = "Contact Manager Account(s)";
	$titles[7] = getSystemSetting("organizationfieldname","Organization");
	//now use session display prefs to set up titles and whatnot for the optional fields
	$i = 8;
	foreach ($optionalfields as $field) {
		if (isset($_SESSION['report']['fields'][$field->fieldnum]) && $_SESSION['report']['fields'][$field->fieldnum])
			$titles[$i++] = $field->name;
	}
	
	header("Pragma: private");
	header("Cache-Control: private");
	header("Content-disposition: attachment; filename=report.csv");
	header("Content-type: application/vnd.ms-excel");

	session_write_close();//WARNING: we don't keep a lock on the session file, any changes to session data are ignored past this point
	
	// write column titles
	echo '"' . implode('","', $titles) . '"';
	echo "\r\n";

	$pagesize = 1000;
	$renderedlist->pagelimit = $pagesize;

	$pageoffset = 0;
	$renderedlist->setPageOffset($pageoffset);
	$data = $renderedlist->getPageData(true);
	while (count($data) > 0) {
		// write out the rows of data
		foreach ($data as $row) {
			// index=4 is code, index=5 is expirationdate
			if ($row[4]) {
				if (strtotime($row[5]) < strtotime("now")) {
					$row[4] = "Expired";
				}
			}
			if ($row[5]) {
				$row[5] = date("m/d/Y", strtotime($row[5]));
			}

			$displaydata = array($row[1], $row[2], $row[3], $row[4], $row[5], $row[7]);
		
			$i = 8;
			foreach ($optionalfields as $field) {
				if (isset($_SESSION['report']['fields'][$field->fieldnum]) && $_SESSION['report']['fields'][$field->fieldnum])
					$displaydata[] = $row[$i++];
				else
					$i++;
			}
		
			echo '"' . implode('","', $displaydata) . '"';
			echo "\r\n";
		}

		$pageoffset += $pagesize;
		$renderedlist->setPageOffset($pageoffset);
		$data = $renderedlist->getPageData();
	}
	
} else {
	$PAGE = "system:contacts";
	$TITLE = _L("Activation Code Manager");

	include_once("nav.inc.php");
?>
	<script type="text/javascript">
		<? Validator::load_validators(array("ValSections", "ValRules")); ?>

		function rulewidget_add_rule(event) {
			$('listsearch_ruledata').value = Object.toJSON(event.memo.ruledata);
			form_submit(event, 'addrule');
		}

		function rulewidget_delete_rule(event) {
			$('listsearch_ruledata').value = event.memo.fieldnum;
			form_submit(event, 'deleterule');
		}

		document.observe('dom:loaded', function() {
			if (window.ruleWidget) {
				ruleWidget.delayActions = true;
				ruleWidget.container.observe('RuleWidget:AddRule', rulewidget_add_rule);
				ruleWidget.container.observe('RuleWidget:DeleteRule', rulewidget_delete_rule);
			}
		});

		function confirmGenerate () {
		<?
			if (count($data)) {
				if ($hasactivecodes) {
					$str = addslashes(_L("Some activation codes exist in this list.  Are you sure you want to overwrite them?"));
					echo "
						return confirm('$str');
					";
				} else {
					$str = addslashes(_L("Are you sure you want to generate activation codes for these people?"));
					echo "
						return confirm('$str');
					";
				}
			} else {
				$str = addslashes(_L("There are no people in this list."));
				echo "
					window.alert('$str');
					return false;
				";
			}
		?>
		}
		
	</script>
<?

	startWindow("Contact Search", "padding: 3px;");

	echo $form->render();

	endWindow();

	startWindow("Search Results");

	if ($hassomesearchcriteria)
		showRenderedListTableCM($data, $total, $pagestart, $pagelimit, $validsortfields, $ordering);
	else
		echo "<h2>Select some search options to begin.</h2>";

	endWindow();

	include_once("navbottom.inc.php");
}
?>
