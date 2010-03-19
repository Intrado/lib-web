<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
require_once("inc/common.inc.php");
include_once("inc/securityhelper.inc.php");
require_once("inc/table.inc.php");
require_once("inc/utils.inc.php");
require_once("inc/html.inc.php");
require_once("inc/date.inc.php");
require_once("inc/formatters.inc.php");
require_once("obj/Person.obj.php");
require_once("obj/FieldMap.obj.php");
require_once("obj/Rule.obj.php");
require_once("obj/PeopleList.obj.php");
require_once("obj/RenderedList.obj.php");
require_once("obj/Phone.obj.php");
require_once("obj/Language.obj.php");
require_once("obj/UserSetting.obj.php");


require_once("inc/reportutils.inc.php"); //used by list.inc.php
require_once("list.inc.php");

////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$USER->authorize('createlist')) {
	redirect('unauthorized.php');
}

////////////////////////////////////////////////////////////////////////////////
// Action/Request Processing
////////////////////////////////////////////////////////////////////////////////

//get the message to edit from the request params or session
if (isset($_GET['id'])) {
	setCurrentList($_GET['id']);
	$_SESSION['listreferer'] = $_SERVER['HTTP_REFERER'];
	redirect();
}

handle_list_checkbox_ajax(); //for handling check/uncheck from the list


$list = new PeopleList($_SESSION['listid']);

if($list->type == 'alert')
	redirect('unauthorized.php');

$renderedlist = new RenderedList2();
$renderedlist->initWithList($list);
$renderedlist->pagelimit = 100;



////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$PAGE = "notifications:lists";
$TITLE = 'List Preview: ' . escapehtml(QuickQuery("select name from list where id = $_SESSION[listid]"));

include_once("nav.inc.php");

buttons(button('Refresh', '', $_SERVER["REQUEST_URI"] ), button("Done","",$_SESSION['listreferer']));

startWindow("Preview");

showRenderedListTable($renderedlist, $list);

endWindow();

buttons(button('Refresh', '', $_SERVER["REQUEST_URI"] ), button("Done","","list.php"));

include_once("navbottom.inc.php");
?>