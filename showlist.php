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
require_once("inc/list.inc.php");

////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$USER->authorize('createlist') && !($USER->authorize("subscribe") && userCanSubscribe('list'))) {
	redirect('unauthorized.php');
}

////////////////////////////////////////////////////////////////////////////////
// Action/Request Processing
////////////////////////////////////////////////////////////////////////////////

//get the message to edit from the request params or session
if (isset($_GET['id'])) {
	$listid = $_GET['id'] + 0;

	if (isSubscribed("list",$listid))
		$_SESSION['previewlistid'] = $listid;
	if (userOwns("list",$listid)) {
		$_SESSION['previewlistid'] = $listid;
		if ($USER->authorize('createlist'))
			$_SESSION['listid'] = $listid; //if the user owns the list and can edit lists, additionally set the listid so that the "in list" checkboxes work
	}
	$_SESSION['listreferer'] = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'list.php';
	redirect("showlist.php" . (isset($_GET["iframe"])?"?iframe=true":""));
}

handle_list_checkbox_ajax(); //for handling check/uncheck from the list

// If the session expired while the user was previewing a list, then the user logged back in, then the app redirects here,
// but with a depopulated $_SESSION. In that case, redirect the user back to the lists page to choose a list again.
if (!isset($_SESSION['previewlistid'])) {
	redirect("lists.php");
}

$list = new PeopleList($_SESSION['previewlistid']);

if($list->type == 'alert')
	redirect('unauthorized.php');

$renderedlist = new RenderedList2();
$renderedlist->initWithList($list);
$renderedlist->pagelimit = 100;



////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$PAGE = "notifications:lists";
$TITLE = 'List Preview: ' . escapehtml($list->name);

include_once("nav.inc.php");

if (!isset($_GET["iframe"]))
	startWindow("Preview");

$buttons = array();
if (!isset($_GET["iframe"])) {
	$buttons[] = icon_button(_L("Done"),"tick",null,$_SESSION['listreferer']);
}
$buttons[] = icon_button(_L("Refresh"),"arrow_refresh",null, $_SERVER["REQUEST_URI"]);

call_user_func_array('buttons', $buttons);

showRenderedListTable($renderedlist, $list, !isset($_GET["iframe"]));

if (!isset($_GET["iframe"]))
	endWindow();

include_once("navbottom.inc.php");
?>