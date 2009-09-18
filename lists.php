<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
include_once("inc/common.inc.php");
include_once("inc/securityhelper.inc.php");
include_once("obj/PeopleList.obj.php");
require_once("inc/table.inc.php");
require_once("inc/utils.inc.php");
include_once("inc/form.inc.php");
include_once("inc/html.inc.php");
include_once("inc/formatters.inc.php");


////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$USER->authorize('createlist')) {
	redirect('unauthorized.php');
}

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

$_SESSION['previewfrom'] = 'lists.php';

if (isset($_GET['delete'])) {
	$deleteid = $_GET['delete'] + 0;
	if (isset($_SESSION['listid']) && $_SESSION['listid'] == $deleteid)
		$_SESSION['listid'] = NULL;
	if (userOwns("list",$deleteid)) {
		$list = new PeopleList($deleteid);
		//QuickUpdate("delete from listentry where listid='$deleteid'");
		QuickUpdate("update list set deleted=1 where id=?", false, array($list->id));
		notice(_L("The list, %s, is now deleted.", escapehtml($list->name)));
	} else {
		notice(_L("You do not have permission to delete this list."));
	}
	redirect();
}

////////////////////////////////////////////////////////////////////////////////
// Display Functions
////////////////////////////////////////////////////////////////////////////////


function fmt_actions ($obj,$name) {
	return action_links (
		action_link("Edit", "pencil", "list.php?id=$obj->id"),
		action_link("Preview", "application_view_list", "showlist.php?id=$obj->id"),
		action_link("Delete", "cross", "lists.php?delete=$obj->id", "return confirmDelete();")
	);
}

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////


$PAGE = "notifications:lists";
$TITLE = "List Builder";

include_once("nav.inc.php");

$data = DBFindMany("PeopleList",", (name +0) as foo from list where userid=$USER->id and deleted=0 order by foo,name");
$titles = array(	"name" => "#List Name",
					"description" => "#Description",
					"lastused" => "Last Used",
					"Actions" => "Actions"
					);


startWindow('My Lists&nbsp;' . help('Lists_MyLists'));

button_bar(button('Create New List', NULL,"list.php?id=new") . help('Lists_AddList'));



showObjects($data, $titles,array("Actions" => "fmt_actions", "lastused" => "fmt_obj_date"), false,  true);
endWindow();


include_once("navbottom.inc.php");
