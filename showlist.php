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
require_once("obj/Person.obj.php");
require_once("obj/FieldMap.obj.php");
require_once("obj/Rule.obj.php");
require_once("obj/PeopleList.obj.php");
require_once("obj/RenderedList.obj.php");
require_once("obj/Phone.obj.php");

////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$USER->authorize('createlist')) {
	redirect('unauthorized.php');
}


////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////
//get the message to edit from the request params or session
if (isset($_GET['id'])) {
	setCurrentList($_GET['id']);
	$_SESSION['listreferer'] = $_SERVER['HTTP_REFERER'];
	redirect();
}


////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$PAGE = "notifications:lists";
$TITLE = 'List Preview: ' . htmlentities(QuickQuery("select name from list where id = $_SESSION[listid]"));

include_once("nav.inc.php");

buttons(button('Refresh', '', $_SERVER["REQUEST_URI"] ), button("Done","",$_SESSION['listreferer']));


$starttime = microtime_float();

//TODO these need to come from a form or something

//now show the data

$list = new PeopleList($_SESSION['listid']);

$renderedlist = new RenderedList($list);
$renderedlist->mode = "preview";
$renderedlist->pagelimit = 500;
$showpagemenu = true;

startWindow("Preview " . help('ShowList_Preview'));

//list.inc.php expects renderedlist, showpagemenu to be set
include("list.inc.php"); //expects $renderedlist to be set
endWindow();

$endtime = microtime_float();
buttons(button('Refresh', '', $_SERVER["REQUEST_URI"] ), button("Done","","list.php"));

include_once("navbottom.inc.php");
?>
