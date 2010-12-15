<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
require_once("inc/common.inc.php");
require_once("inc/form.inc.php");
require_once("inc/table.inc.php");
require_once("inc/html.inc.php");
require_once("inc/formatters.inc.php");
require_once("inc/date.inc.php");
require_once("inc/securityhelper.inc.php");
require_once("inc/rulesutils.inc.php");
require_once("obj/Person.obj.php");
require_once("obj/PeopleList.obj.php");
require_once("obj/ListEntry.obj.php");
require_once("obj/RenderedList.obj.php");
require_once("obj/Validator.obj.php");
require_once("obj/Form.obj.php");
require_once("obj/Rule.obj.php");
require_once("obj/FieldMap.obj.php");
require_once("obj/FormItem.obj.php");
require_once("obj/Phone.obj.php");
require_once("obj/Email.obj.php");
require_once("obj/Sms.obj.php");
require_once("obj/FormRuleWidget.fi.php");
require_once("obj/SectionWidget.fi.php");
require_once("obj/ValSections.val.php");
require_once("obj/ValRules.val.php");
require_once("obj/Address.obj.php");
require_once("obj/Language.obj.php");
require_once("obj/JobType.obj.php");
require_once("obj/UserSetting.obj.php");


require_once("inc/reportutils.inc.php"); //used by list.inc.php
require_once("inc/list.inc.php");
require_once("ruleeditform.inc.php");


////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$USER->authorize('createlist')) {
	redirect('unauthorized.php');
}

////////////////////////////////////////////////////////////////////////////////
// Action/Request Processing
////////////////////////////////////////////////////////////////////////////////

//get the list to edit from the request params or session
if (isset($_GET['id'])) {
	setCurrentList($_GET['id']);
	unset($_SESSION['listsearch']);
	$_SESSION['listreferer'] = $_SERVER['HTTP_REFERER'];
	redirect();
}

if (isset($_GET['showall']))
	$_SESSION['listsearch'] = array("showall" => true);

handle_list_checkbox_ajax(); //for handling check/uncheck from the list

$list = new PeopleList($_SESSION['listid']);

////////////////////////////////////////////////////////////////////////////////
// Form Data
////////////////////////////////////////////////////////////////////////////////

$renderedlist = new RenderedList2();
$renderedlist->pagelimit = 100;

// buttons must be defined before include 'contactsearchformdata.inc'
$buttons = array(
	submit_button(_L('Refresh'),"refresh","arrow_refresh"),
);
$buttons[] = icon_button(_L('Show All Contacts'),"application_view_list",null,"search.php?showall");
$buttons[] = icon_button(_L('Done'),"tick",null, isset($_SESSION['listreferer']) ? $_SESSION['listreferer'] : "list.php");

// variable for page redirect, used by include 'contactsearchformdata.inc'
$redirectpage = "search.php";

include_once("contactsearchformdata.inc.php");

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$PAGE = "notifications:lists";
$TITLE = "List Search: " . escapehtml($list->name);
require_once("nav.inc.php");

?>
	<script src="script/contactsearch.js.php" type="text/javascript"></script>

	<script type="text/javascript">
		<? Validator::load_validators(array("ValSections", "ValRules")); ?>

		document.observe('dom:loaded', function() {
			ruleWidget.delayActions = true;
			ruleWidget.container.observe('RuleWidget:AddRule', rulewidget_add_rule);
			ruleWidget.container.observe('RuleWidget:DeleteRule', rulewidget_delete_rule);
		
<?
			if (isset($_SESSION['listsearch']['individual']))
				echo 'choose_search_by_person();';
			else if (isset($_SESSION['listsearch']['sectionids']))
				echo 'choose_search_by_sections();';
			else 
				echo 'choose_search_by_rules();';
?>
		});
	</script>
<?

startWindow("Search Options");

echo $form->render();

endWindow();

startWindow("Search Results");

if ($hassomesearchcriteria)
	showRenderedListTable($renderedlist, $list);
else
	echo "<h2>Select some search options to begin.</h2>";

endWindow();

require_once("navbottom.inc.php");
?>
