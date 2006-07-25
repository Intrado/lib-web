<?

////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
include_once("inc/common.inc.php");
include_once("obj/Job.obj.php");
include_once("obj/Schedule.obj.php");
include_once("inc/form.inc.php");
include_once("inc/html.inc.php");
require_once("inc/table.inc.php");
require_once("inc/utils.inc.php");
require_once("inc/securityhelper.inc.php");
require_once("inc/formatters.inc.php");
require_once("obj/Import.obj.php");


////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$USER->authorize('managetasks')) {
	redirect('unauthorized.php');
}

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

$IMPORTS = DBFindMany("Import", "from import where customerid = $USER->customerid and ownertype != 'user' order by id");

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$PAGE = "admin:taskmanager";
$TITLE = "Data Import Manager";

include_once("nav.inc.php");

startWindow('System Imports ' . help('Tasks_SystemTasks', NULL, 'blue'), 'padding: 3px;');
button_bar(button('addnewimport', null, "task.php?id=new"));

print '<table width="100%" cellpadding="3" cellspacing="1" class="list">';
echo '<tr class="listHeader">';
echo "<th>Name</th><th>Description</th><th>Type</th><th>Next Scheduled Run</th><th>Status</th><th>File</th><th></th>";
echo "</tr>\n";

$alt = 0;
if (count($IMPORTS) > 0) {
	foreach ($IMPORTS as $import) {
		echo ++$alt % 2 ? '<tr>' : '<tr class="listAlt">';

		echo "\n<td>";
		echo $import->name;
		echo "</td>";

		echo "\n<td>";
		echo $import->description;
		echo "</td>";

		echo "\n<td>";
		echo ($import->updatemethod == "updateonly" ? "Update Only" : ucfirst($import->updatemethod));
		echo "</td>";

		echo "\n<td>";
		echo fmt_nextrun($import);
		echo "</td>";

		echo "\n<td>";
		echo $import->status;
		echo "</td>";

		echo "\n<td>";
		echo (is_readable($import->path) && is_file($import->path) ? "Exists" : "Not Found");
		echo "</td>";

		echo "\n<td>";
		echo "<a href=task.php?run=$import->id>Run&nbsp;Now</a>&nbsp;|&nbsp;<a href=task.php?id=$import->id>Edit</a>&nbsp;|&nbsp;<a href=taskmap.php?id=$import->id>Map&nbsp;Fields</a>&nbsp;|&nbsp;<a href=\"task.php?delete=$import->id\" onclick=\"return confirmDelete();\">Delete</a>";
		echo "</td>";
	}

		echo "</tr>\n";
}

print "\n</table>";
endWindow();

include_once("navbottom.inc.php");


function fmt_nextrun ($obj) {
	$nextrun = QuickQuery("select nextrun from schedule where id=$obj->scheduleid");
	if ($nextrun == null) {
		$nextrun = "- Never -";
	} else {
		$nextrun = date("F jS, Y h:i a", strtotime($nextrun));
	}
	return $nextrun;
}
?>