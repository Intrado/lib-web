<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
require_once("inc/common.inc.php");
require_once("obj/Job.obj.php");
require_once("obj/Schedule.obj.php");
require_once("inc/form.inc.php");
require_once("inc/html.inc.php");
require_once("inc/table.inc.php");
require_once("inc/utils.inc.php");
require_once("inc/securityhelper.inc.php");
require_once("inc/formatters.inc.php");
require_once("obj/JobType.obj.php");
require_once("obj/Monitor.obj.php");


////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$USER->authorize('monitorevent')) {
	redirect('unauthorized.php');
}


////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

if (isset($_GET["delete"])) {
	if (userOwns("monitor",$_GET['delete'])) {
		Query("BEGIN");
		QuickUpdate("delete from monitorfilter where monitorid = ?",false,array($_GET['delete']));
		QuickUpdate("delete from monitor where id = ?",false,array($_GET['delete']));
		Query("COMMIT");
		notice(_L("Monitor deleted"));
	} else {
		notice(_L("Unable to delete monitor"));
	}
}

$query = "select m.id, m.type as monitortype, mf.type as filtertype, mf.val from monitor m left join monitorfilter mf on (mf.monitorid = m.id) where userid=?";
$result = Query($query,false,array($USER->id));
$data = array();
$users = array();


while ($row = DBGetRow($result,true)) {
	if (isset($data[$row["id"]])) {
		$item = $data[$row["id"]];
	} else {
		$item = array("id" => $row["id"], "type" => $row["monitortype"]);
	}

	$values = array();
	switch($row["filtertype"]) {
		case 'userid':
			$values = explode(",",$row["val"]);
			foreach ($values as $userid) {
				$users[$userid] = $userid;
			}
			break;
		case 'jobtypeid':
			$values = explode(",",$row["val"]);
			break;
	}
	$item["filters"][$row["filtertype"]] = $values;

	$data[$row["id"]] = $item;
}


// Get all users that are included in the filters but exclude the deleted users
if (count($users)) {
	$query = "select id, login from user where deleted=0 and login != 'schoolmessenger' and id in (" . DBParamListString(count($users)) . ")";
	$users = QuickQueryList($query,true,false,array_keys($users));
}

$jobtypes = JobType::getUserJobTypes();

$titles = array(
	"type" => "Event",
	"filters" => "Filters",
	"action" => "Action"
);
$formatters = array(
	"type" => "fmt_monitor_event",
	"filters" => "fmt_monitor_filter",
	"action" => "fmt_monitor_action"
);

////////////////////////////////////////////////////////////////////////////////
// Display Functions
////////////////////////////////////////////////////////////////////////////////
function fmt_monitor_event($row,$index) {
	$types = array(
		'job-active' => 'Jobs Submitted',
		'job-firstpass' => 'Jobs First Attempt Completed',
		'job-complete' => 'Jobs Completed'
	);
	return isset($types[$row[$index]])?$types[$row[$index]]:"Unknown";
}

function fmt_monitor_filter ($row,$index) {
	global $users,$jobtypes;
	
	$filterbyvalues = array();
	foreach($row[$index] as $filtertypes => $filtervalues) {
		switch($filtertypes) {
			case 'userid':
				$filterbyusers = array();
				foreach ($filtervalues as $userid) {
					if (isset($users[$userid]))
						$filterbyusers[] = $users[$userid];
				}
				$filterbyvalues[] = "Users: " . implode(",",$filterbyusers);
				break;
			case 'jobtypeid':		
				$fiterbyjobtypes = array();
				foreach ($filtervalues as $jobtypeid) {
					if (isset($jobtypes[$jobtypeid]))
						$fiterbyjobtypes[] = $jobtypes[$jobtypeid]->name;
				}
				$filterbyvalues[] =  "Job Types: " . implode(",",$fiterbyjobtypes);
				break;
		}
	}
	
	if (!in_array('userid',array_keys($row[$index])))
		$filterbyvalues[] = "Users: All";
	if (!in_array('jobtypeid',array_keys($row[$index])))
		$filterbyvalues[] = "Job Types: All";
	
	return implode(";",$filterbyvalues);
}

function fmt_monitor_action ($row,$index) {
	$actionlinks = array(
		action_link("Edit", "pencil","monitoredit.php?id={$row['id']}"),
		action_link("Delete", "cross","monitors.php?delete={$row['id']}","return confirmDelete();")
	);
	return action_links($actionlinks);
}
////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$PAGE = "system:monitors";
$TITLE = "Monitors";

include_once("nav.inc.php");

startWindow('Monitors');
echo "<div style='padding:10px'>" . icon_button("Add Monitor", "add",null,"monitoredit.php?id=new") . "</div><hr />";
echo '<table width="100%" cellpadding="3" cellspacing="1" class="list">';
showTable($data, $titles,$formatters);
echo "</table>";
endWindow();

include_once("navbottom.inc.php");
?>
