<?

// $jsonstats variable must be set



$status = json_decode($jsonstats);
$systemstats = $status[0];

$dispatchers = array();
foreach ($systemstats as $key => $value) {
	if (strpos($key, "comerr") === 0) {
		$dispatcher = substr($key, 7);
		$dispatchers[] = $dispatcher;
	}
}

$statusgroups = array();
$statusgroups["General"] = array();
$statusgroups["General"]['dmenabled'] = "Active State:";
$statusgroups["General"]['startuptime'] = "System Startup Time:";
$statusgroups["General"]['currenttime'] = "Current System Time:";
$statusgroups['General']['sysrunning'] = "System has been Running:";
$statusgroups["General"]['clockresetcount'] = "Total Clock Resets:";

foreach ($dispatchers as $dname) {
	$groupname = "Communication Activity - " . $dname;

	$statusgroups[$groupname] = array();
	$statusgroups[$groupname]['comerr-'.$dname] = "Connection Failures:";
	$statusgroups[$groupname]['comtimeout-'.$dname] = "Connection Timeouts:";
	$statusgroups[$groupname]['comreadtimeout-'.$dname] = "Read Timeouts:";
}

$statusgroups["Resource Allocation"] = array();
$statusgroups["Resource Allocation"]['resactout'] = "Active Outbound:";
$statusgroups["Resource Allocation"]["residleout"] = "Idle Outbound:";
$statusgroups["Resource Allocation"]["resactin"] = "Active Inbound:";
$statusgroups["Resource Allocation"]["residlein"] = "Idle Inbound:";
$statusgroups["Resource Allocation"]["resthrotsched"] = "Throttled by Schedule:";
$statusgroups["Resource Allocation"]["resthroterr"] = "Throttled due to Congestion:";
$statusgroups["Resource Allocation"]["restotal"] = "Total Resources:";

$statusgroups['Call Results'] = array();
$statusgroups['Call Results']['A'] = "Answered:";
$statusgroups['Call Results']['M'] = "Machine:";
$statusgroups['Call Results']['B'] = "Busy:";
$statusgroups['Call Results']['N'] = "No Answer:";
$statusgroups['Call Results']['X'] = "Disconnect:";
$statusgroups['Call Results']['F'] = "Unknown:";
$statusgroups['Call Results']['TB'] = "Trunk Busy:";
$statusgroups['Call Results']['failures'] = "Other:";
$statusgroups['Call Results']['inboundcompletedcount'] = "Inbound:";
$statusgroups['Call Results']['dialtime'] = "Total Ring Time:";
$statusgroups['Call Results']['billtime'] = "Total Call Time:";

$statusgroups['Cache Information'] = array();
$statusgroups['Cache Information']['cachelocation'] = "Location:";
$statusgroups['Cache Information']['cachemax'] = "Max Size:";
$statusgroups['Cache Information']['cachesize'] = "Current Size:";
$statusgroups['Cache Information']['cachefilecount'] = "Current File Count:";
$statusgroups['Cache Information']['cachedelcount'] = "Deleted File Count:";
$statusgroups['Cache Information']['cachehit'] = "Total Hits:";
$statusgroups['Cache Information']['cachemiss'] = "Total Misses:";

$statusgroups['Download Audio Content'] = array();
$statusgroups['Download Audio Content']['getcontentapicount'] = "Total Downloads:";
$statusgroups['Download Audio Content']['getcontentapitime'] = "Total Time:";
$statusgroups['Download Audio Content']['getcontentapiavg'] = "Average Time:";

$statusgroups['Upload Audio Content'] = array();
$statusgroups['Upload Audio Content']['putcontentapicount'] = "Total Uploads:";
$statusgroups['Upload Audio Content']['putcontentapitime'] = "Total Time:";
$statusgroups['Upload Audio Content']['putcontentapiavg'] = "Average Time:";

$statusgroups['Text-to-Speech Content'] = array();
$statusgroups['Text-to-Speech Content']['ttsapicount'] = "Total Downloads:";
$statusgroups['Text-to-Speech Content']['ttsapitime'] = "Total Time:";
$statusgroups['Text-to-Speech Content']['ttsapiavg'] = "Average Time:";

$statusgroups['Continue Task Request'] = array();
$statusgroups['Continue Task Request']['continueapicount'] = "Total Requests:";
$statusgroups['Continue Task Request']['continueapitime'] = "Total Time:";
$statusgroups['Continue Task Request']['continueapiavg'] = "Average Time:";

$statusgroups['System Details'] = array();
$statusgroups['System Details']['uptime'] = "Uptime:";
$statusgroups['System Details']['diskspace'] = "Disk Usage:";
$statusgroups['System Details']['memory'] = "Memory Usage:";


///////////////////////////////////////
// FUNCTIONS

// data is divID-label pairs
function showStatusGroup($grouptitle, $data) {
	echo '<b>' . $grouptitle . '</b>';
	echo '<table width="100%">';
	foreach ($data as $div => $label) {
		echo '<tr><td width="20%">';
		echo $label;
		echo '</td><td><div id=\'' . $div . '\'></div></td></tr>';
	}
	echo '</table>';
}

//////////////////////////////////////
// AJAX

if (isset($_GET['ajax'])) {
	header("Content-type: application/json");
	echo $jsonstats;
	exit;
}

?>
