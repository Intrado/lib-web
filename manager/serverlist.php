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
if (!$MANAGERUSER->authorized("manageserver"))
	exit("Not Authorized");

////////////////////////////////////////////////////////////////////////////////
// Action/Request Processing
////////////////////////////////////////////////////////////////////////////////
if (isset($_GET['id']) && isset($_GET['delete'])) {
	QuickQuery("BEGIN");
	QuickUpdate("delete from serversetting where serverid = ?", false, array($_GET['id']));
	QuickUpdate("delete from server where id = ?", false, array($_GET['id']));
	QuickQuery("COMMIT");
}

if (isset($_GET['id']) && isset($_GET['csrestart'])) {
	$jolokiaProxy = "http://localhost:8085/jolokia";
	$server = new Server($_GET['id']);
	if ($server->id) {
		$name = escapeshellarg($server->name);
		$port = escapeshellarg($server->getSetting("commsuitejmxport",3100));
		$cmd = "jmx4perl $jolokiaProxy --target service:jmx:rmi://$name:$port/jndi/rmi://$name:$port/jmxrmi ".
			"exec org.tanukisoftware.wrapper:type=WrapperManager restart 2>&1";
		$shelloutput = exec($cmd, $cmdoutput, $cmdretval);
		$_SESSION['csrestart'] = array();
		$_SESSION['csrestart'][] = array(
			'name' => $name,
			'cmd' => $cmd,
			'retval' => $cmdretval,
			'shelloutput' => $shelloutput,
			'output' => $cmdoutput);
		redirect();
	}
}
////////////////////////////////////////////////////////////////////////////////
// Formatters
////////////////////////////////////////////////////////////////////////////////
function fmt_notes($row, $index){
	// TODO: mouse over display
	return '<div style="max-height:70px; overflow:auto;">'. escapehtml($row[$index]). '</div>';
}
		
function fmt_production($row,$index) {
	if ($row[$index])
		return '<div style="float:left; background-color:green;">Yes</div>';
	else
		return '<div style="float:left;">No</div>';
}

function fmt_actions($row,$index) {
	$actionlinks = array();
	$actionlinks[] = action_link("Edit", "application_edit","serveredit.php?id=$row[0]");
	$actionlinks[] = action_link("Delete", "application_delete","serverlist.php?id=$row[0]&delete","return confirmDelete();");
	
	// if it has the commsuite service, allow restarting it.
	if ($row[4])
		$actionlinks[] = action_link("CS Restart", "application_go","serverlist.php?id=$row[0]&csrestart");
	
	return action_links($actionlinks);
}

function fmt_retval($row, $index) {
	if ($row[$index] == 0)
		return '<div style="color:green;">Successful!</div>';
	else
		return '<div style="color:red;">Failed!</div>';
}

function fmt_cmdoutput($row, $index) {
	$html = '<div style="max-height:70px; overflow:auto;">';
	foreach ($row[$index] as $output)
		$html .= $output;
	$html .= '</div>';
	return $html;
}
////////////////////////////////////////////////////////////////////////////////
// Data
////////////////////////////////////////////////////////////////////////////////

// TODO: commsuite service status field
$titles = array("1" => "#Name",
		"3" => "#In Production",
		"actions" => "Actions",
		"2" => "Notes");

$formatters = array("2" => "fmt_notes",
		"3" => "fmt_production",
		"actions" => "fmt_actions");

$data = QuickQueryMultiRow("
	select s.id, s.name, s.notes, s.production,
		(select value from serversetting where serverid = s.id and name = 'commsuitejmxport') as hascommsuite
	from server s",
	false, false, array());

$cmdtitles = array("name" => "Name",
		"retval" => "Status",
		"output" => "Output");

$cmdformatters = array("retval" => "fmt_retval",
		"output" => "fmt_cmdoutput");

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
include_once("nav.inc.php");

if (isset($_SESSION['csrestart'])) {
	startWindow(_L('Command Status'));
	?><table>
	<?
	showTable($_SESSION['csrestart'], $cmdtitles, $cmdformatters);
	?></table>
	<?
	endWindow();
	unset($_SESSION['csrestart']);
}
startWindow(_L('Server List'));
?><table>
<?
showTable($data, $titles, $formatters);
?></table>
<?
endWindow();
startWindow(_L('Actions'));
echo icon_button("New Server", "add", null, "serveredit.php");
endWindow();
include_once("navbottom.inc.php");
?>