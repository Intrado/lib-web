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
require_once("Service.obj.php");

////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$MANAGERUSER->authorized("manageserver"))
	exit("Not Authorized");

////////////////////////////////////////////////////////////////////////////////
// Action/Request Processing
////////////////////////////////////////////////////////////////////////////////
if (isset($_GET['serverid'])) {
	$_SESSION['servicelist'] = array();
	$_SESSION['servicelist']['serverid'] = $_GET['serverid'] + 0;
	redirect();
}

if (isset($_GET['id']) && isset($_GET['delete'])) {
	QuickQuery("BEGIN");
	QuickUpdate("delete from serviceattribute where serviceid = ?", false, array($_GET['id']));
	QuickUpdate("delete from service where id = ?", false, array($_GET['id']));
	QuickQuery("COMMIT");
	redirect();
}

if (isset($_GET['id']) && isset($_GET['restart'])) {
	/*
	$jolokiaProxy = $SETTINGS['servermanagement']['jmxproxy'];
	$server = new Server($_GET['id']);
	if ($server->id) {
		$name = escapeshellarg($server->hostname);
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
	*/
}
////////////////////////////////////////////////////////////////////////////////
// Formatters
////////////////////////////////////////////////////////////////////////////////
function fmt_notes($row, $index){
	// TODO: mouse over display
	return '<div style="max-height:70px; overflow:auto;">'. escapehtml($row[$index]). '</div>';
}

function fmt_type($row,$index) {
	$types = Service::getTypes();
	if ($row[$index] && isset($types[$row[$index]])) {
		return $types[$row[$index]];
	} else
	return '<div style="float:left;">Missing/Invalid type!</div>';
}

function fmt_runmode($row,$index) {
	$modes = Service::getRunModes();
	if ($row[$index] && isset($modes[$row[$index]])) {
		return $modes[$row[$index]];
	} else
		return '<div style="float:left;">Missing/Invalid runmode!</div>';
}

function fmt_actions($row,$index) {
	$actionlinks = array();
	$actionlinks[] = action_link("Edit", "application_edit","serviceedit.php?id=$row[0]");
	$actionlinks[] = action_link("Delete", "application_delete","servicelist.php?id=$row[0]&delete","return confirmDelete();");
	$actionlinks[] = action_link("Restart", "application_key","servicelist.php?id=$row[0]");
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
		$html .= escapehtml($output). "<br>";
	$html .= '</div>';
	return $html;
}
////////////////////////////////////////////////////////////////////////////////
// Data
////////////////////////////////////////////////////////////////////////////////
if (isset($_SESSION['servicelist']['serverid']))
	$serverid = $_SESSION['servicelist']['serverid'];
else
	$serverid = false;

$server = new Server($serverid);

if (!$server->hostname)
	exit("Bad/Missing server id!");
	
// TODO: commsuite service status field
$titles = array("1" => "Type",
		"2" => "Mode",
		"actions" => "Actions",
		"3" => "Notes");

$formatters = array("1" => "fmt_type",
		"2" => "fmt_runmode",
		"3" => "fmt_notes",
		"actions" => "fmt_actions");

$data = QuickQueryMultiRow("select id, type, runmode, notes from service where serverid = ?", false, false, array($server->id));

$cmdtitles = array("name" => "Hostname",
		"retval" => "Status",
		"output" => "Output");

$cmdformatters = array("retval" => "fmt_retval",
		"output" => "fmt_cmdoutput");

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
include_once("nav.inc.php");

if (isset($_SESSION['servicelist']['restart'])) {
	startWindow(_L('Command Status'));
	?><table>
	<?
	showTable($_SESSION['servicelist']['restart'], $cmdtitles, $cmdformatters);
	?></table>
	<?
	endWindow();
	unset($_SESSION['servicelist']['restart']);
}
startWindow(_L('%s Service List', $server->hostname));
?><table>
<?
showTable($data, $titles, $formatters);
?></table>
<?
endWindow();
startWindow(_L('Actions'));
button_bar(
	icon_button("New Service", "add", null, "servicenew.php?serverid=". $server->id),
	icon_button("Server List", "arrow_undo", null, "serverlist.php"));
endWindow();
include_once("navbottom.inc.php");
?>