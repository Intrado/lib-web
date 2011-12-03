<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
require_once("common.inc.php");
require_once("../inc/form.inc.php");
require_once("../inc/html.inc.php");
require_once("../inc/utils.inc.php");
require_once("../inc/table.inc.php");
require_once("Server.obj.php");

////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$SETTINGS['servermanagement']['manageservers'] || !$MANAGERUSER->authorized("manageserver"))
	exit("Not Authorized");

////////////////////////////////////////////////////////////////////////////////
// Action/Request Processing
////////////////////////////////////////////////////////////////////////////////
if (isset($_GET['id']) && isset($_GET['delete'])) {
	QuickQuery("BEGIN");
	// TODO: get and delete all services for this server
	QuickUpdate("delete from serversetting where serverid = ?", false, array($_GET['id']));
	QuickUpdate("delete from server where id = ?", false, array($_GET['id']));
	QuickQuery("COMMIT");
	redirect();
}

////////////////////////////////////////////////////////////////////////////////
// Formatters
////////////////////////////////////////////////////////////////////////////////
function fmt_notes($row, $index){
	// TODO: mouse over display
	return '<div style="max-height:70px; overflow:auto;">'. escapehtml($row[$index]). '</div>';
}
		
function fmt_runmode($row,$index) {
	$modes = Server::getRunModes();
	if ($row[$index] && isset($modes[$row[$index]])) {
		return $modes[$row[$index]];
	} else
		return '<div style="float:left;">Missing/Invalid runmode!</div>';
}

function fmt_actions($row,$index) {
	$actionlinks = array();
	$actionlinks[] = action_link("Edit", "application_edit","serveredit.php?id=$row[0]");
	$actionlinks[] = action_link("Delete", "application_delete","serverlist.php?id=$row[0]&delete","return confirmDelete();");
	$actionlinks[] = action_link("Services", "application_key","servicelist.php?serverid=$row[0]");
	return action_links($actionlinks);
}

function fmt_retval($row, $index) {
	if ($row[$index] == 0)
		return '<div style="color:green;">Successful!</div>';
	else
		return '<div style="color:red;">Failed!</div>';
}

////////////////////////////////////////////////////////////////////////////////
// Data
////////////////////////////////////////////////////////////////////////////////

// TODO: commsuite service status field
$titles = array("1" => "Hostname",
		"2" => "Run Mode",
		"4" => "Service Props",
		"actions" => "Actions",
		"3" => "Notes");

$formatters = array("2" => "fmt_runmode",
		"3" => "fmt_notes",
		"actions" => "fmt_actions");

$data = QuickQueryMultiRow("select s.id, s.hostname, s.runmode, s.notes, 
		(select group_concat(runmode, ':', type separator ', ') 
			from service 
			where serverid = s.id) as services 
		from server s", false, false, array());

$cmdtitles = array("hostname" => "Hostname",
		"retval" => "Status",
		"output" => "Output");

$cmdformatters = array("retval" => "fmt_retval");

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
include_once("nav.inc.php");

if (isset($_SESSION['servicebulkrestart'])) {
	startWindow(_L('Command Status'));
	?><table>
	<?
	showTable($_SESSION['servicebulkrestart'], $cmdtitles, $cmdformatters);
	?></table>
	<?
	endWindow();
	unset($_SESSION['servicebulkrestart']);
}
startWindow(_L('Server List'));
?><table>
<?
showTable($data, $titles, $formatters);
?></table>
<?
endWindow();
startWindow(_L('Actions'));
button_bar(
	icon_button("New Server", "add", null, "serveredit.php?id=new"),
	icon_button("Bulk Restart", "cog_go", null, "servicebulkrestart.php"));
endWindow();
include_once("navbottom.inc.php");
?>