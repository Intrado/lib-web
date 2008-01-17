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

if (isset($_GET['delete'])) {
	$deleteid = DBSafe($_GET['delete']);
	if (isset($_SESSION['listid']) && $_SESSION['listid'] == $deleteid)
		$_SESSION['listid'] = NULL;
	if (userOwns("list",$deleteid)) {
		//QuickUpdate("delete from listentry where listid='$deleteid'");
		QuickUpdate("update list set deleted=1 where id='$deleteid'");
	}
	redirect();
}

////////////////////////////////////////////////////////////////////////////////
// Display Functions
////////////////////////////////////////////////////////////////////////////////


function fmt_actions ($obj,$name) {
	return '<a href="list.php?id=' . $obj->id . '">Edit</a>&nbsp;|'
		. '&nbsp;<a href="showlist.php?id=' . $obj->id . '">Preview</a>&nbsp;|'
		. '&nbsp;<a href="lists.php?delete=' . $obj->id . '" onclick="return confirmDelete();">Delete</a>';
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


startWindow('My Lists&nbsp;' . help('Lists_MyLists'), 'padding: 3px;');

button_bar(button('Create New List', NULL,"list.php?id=new") . help('Lists_AddList'));



showObjects($data, $titles,array("Actions" => "fmt_actions", "lastused" => "fmt_obj_date"), count($data) > 10,  true);
endWindow();


include_once("navbottom.inc.php");
