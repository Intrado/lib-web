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

//get the list to edit from the request params or session
if (isset($_GET['id'])) {
	setCurrentList($_GET['id']);
	
	// NOTE: maintaing previous behavior while removing errors from httpd log files. See bug:4605
	$referer = (isset($_SERVER['HTTP_REFERER'])?$_SERVER['HTTP_REFERER']:NULL);
	$_SESSION['listreferer'] = $referer;
	redirect();
}

handle_list_checkbox_ajax(); //for handling check/uncheck from the list

$list = new PeopleList($_SESSION['listid']);

////////////////////////////////////////////////////////////////////////////////
// Form Data
////////////////////////////////////////////////////////////////////////////////

$renderedlist = new RenderedList2();
$renderedlist->pagelimit = 100;

// buttons must be defined before include 'contactsearchformdata.inc'
$buttons = array(
	icon_button(_L('Done'),"tick",null, isset($_SESSION['listreferer']) ? $_SESSION['listreferer'] : "list.php")
);

// variable for page redirect, used by include 'contactsearchformdata.inc'
$redirectpage = "search.php";

include_once("contactsearchformdata.inc.php");

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$PAGE = "notifications:lists";
$TITLE = "List Search: " . escapehtml($list->name);
require_once("nav.inc.php");


//load validator for rules, handle rule add/delete to form submit (contactsearchformdata.inc.php)
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
	showRenderedListTable($renderedlist, $list);
else
	echo "<h2>Select some search options to begin.</h2>";
?>
</div>
<?
endWindow();

require_once("navbottom.inc.php");
?>
