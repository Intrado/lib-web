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
include_once("obj/Address.obj.php");
include_once("obj/Language.obj.php");
include_once("obj/JobType.obj.php");
require_once("inc/utils.inc.php");
require_once("inc/reportgeneratorutils.inc.php");
require_once("obj/FieldMap.obj.php");
//require_once("obj/ReportGenerator.obj.php");
//require_once("obj/ReportInstance.obj.php");
require_once("obj/UserSetting.obj.php");
//require_once("obj/ContactsReport.obj.php");
require_once("obj/RenderedList.obj.php");
require_once("inc/list.inc.php");

////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////

if (!$USER->authorize('viewcontacts')) {
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


////////////////////////////////////////////////////////////////////////////////
// Form Data
////////////////////////////////////////////////////////////////////////////////

$renderedlist = new RenderedList2();
$renderedlist->pagelimit = 100;

$buttons = array();
if (getSystemSetting("_hasportal", false) && $USER->authorize('portalaccess'))
	$buttons[] = icon_button("Manage Activation Codes", "key_go", null, "activationcodemanager.php");

$redirectpage = "contacts.php";

include_once("contactsearchformdata.inc.php");

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$PAGE = "system:contacts";
$TITLE = "Contact Database";

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
	</script>
<?

startWindow("Search Options");

echo $form->render();

endWindow();

startWindow("Search Results");
?>
<div id="renderedlistcontent">
<? 
if ($hassomesearchcriteria)
	showRenderedListTable($renderedlist);
else
	echo "<h2>Select some search options to begin.</h2>";
?>
</div>
<?
	
endWindow();

require_once("navbottom.inc.php");
?>
