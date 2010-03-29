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
require_once("list.inc.php");

////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////

if (!$USER->authorize('viewcontacts')) {
	redirect('unauthorized.php');
}

////////////////////////////////////////////////////////////////////////////////
// Action/Request Processing
////////////////////////////////////////////////////////////////////////////////

if (isset($_GET['clear']))
	$_SESSION['listsearch'] = array();
	
if (isset($_GET['showall']))
	$_SESSION['listsearch'] = array("showall" => true);


////////////////////////////////////////////////////////////////////////////////
// Form Data
////////////////////////////////////////////////////////////////////////////////

$renderedlist = new RenderedList2();
$renderedlist->pagelimit = 100;

$buttons = array(
	submit_button(_L('Refresh'),"refresh","arrow_refresh"),
	icon_button(_L('Show All Contacts'),"tick",null,"contacts.php?showall")
);
if (getSystemSetting("_hasportal", false) && $USER->authorize('portalaccess'))
	$buttons[] = icon_button("Manage Activation Codes", "tick", null, "activationcodemanager.php");

$redirectpage = "contacts.php";

include_once("contactsearchformdata.inc.php");

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$PAGE = "system:contacts";
$TITLE = "Contact Database";

include_once("nav.inc.php");

require_once("script/contactsearch.js.php");

startWindow("Search Options");

echo $form->render();

endWindow();

startWindow("Search Results");

if ($hassomesearchcriteria)
	showRenderedListTable($renderedlist);
else
	echo "<h2>Select some search options to begin.</h2>";

endWindow();

require_once("navbottom.inc.php");
?>
