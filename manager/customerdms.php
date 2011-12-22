<?
require_once("common.inc.php");
require_once("../inc/form.inc.php");
require_once("../inc/html.inc.php");
require_once("../inc/table.inc.php");
require_once("../inc/formatters.inc.php");

if (!$MANAGERUSER->authorized("editdm"))
	exit("Not Authorized");


// Action Handlers 
if(isset($_GET['clear'])){
	unset($_SESSION['customerid']);
	redirect();
} else if (isset($_GET['resetDM'])) {
	if (null != QuickQuery("select command from dm where id=?", false,array($_GET['resetDM']))) {
		notice(_L('DM already had a command queued.  New command queued instead.'));
	}
	QuickUpdate("update dm set command='reset' where id=?", false,array($_GET['resetDM']));
	notice(_L("Reset queued"));
	redirect();
} else if (isset($_GET['update'])) {
	if (null != QuickQuery("select command from dm where id=?", false,array($_GET['update']))) {
		notice(_L('DM already had a command queued.  New command queued instead.'));
	}
	QuickUpdate("update dm set command='update' where id=?", false,array($_GET['update']));
	notice(_L("Update queued"));
	redirect();
} else if (isset($_GET['delete'])) {
	QuickUpdate("update dm set enablestate='deleted' where id=?",false,array($_GET['delete']));
	notice(_L("Deleted DM"));
	redirect();
} else if (isset($_GET['undelete'])) {
	QuickUpdate("update dm set enablestate='disabled' where id=?",false,array($_GET['undelete']));
	notice(_L("Undeleted DM"));
	redirect();
}


$queryextra = "";
if (isset($_GET['cid'])) {
	if ($_GET['cid']) {
		$queryextra = " AND dm.customerid in (";
		foreach (explode(",", $_GET['cid']) as $cid)
			$queryextra .= "'". DBSafe($cid) . "',";
			
		$queryextra = substr($queryextra, 0, -1) . ") ";
	}
}

$custtxt = "";
$viewoption = 'enabled';

if (isset($_REQUEST['custtxt']) && trim($_REQUEST['custtxt'])) {
	$custtxt = escapehtml(trim($_REQUEST['custtxt']));
	$queryextra = " and c.urlcomponent like '%" . DBSafe(trim($_REQUEST['custtxt'])) . "%'";
}

$viewoptions =  array(
	"enablestate" => "active",
	"enabled" => "true"
);

if(isset($_REQUEST['enablestate']))
	$viewoptions["enablestate"] = $_REQUEST['enablestate'];

if(isset($_REQUEST['enabled']))
	$viewoptions["enabled"] = $_REQUEST['enabled'];


$enablestates = array("all","new","active","disabled","deleted");
if (!in_array($viewoptions["enablestate"],$enablestates))
	$viewoptions["enablestate"] = 'active';

if ($viewoptions["enablestate"] != "all")
	$queryextra .= " and dm.enablestate = '{$viewoptions["enablestate"]}' ";

if ($viewoptions["enabled"] == "false")
	$queryextra .= " and s_dm_enabled.value = '0' ";
else if ($viewoptions["enabled"] == "true")
	$queryextra .= " and (s_dm_enabled.value = '1' or s_dm_enabled.value is null) ";


//index 2 is customer id
//index 1 is customer url
function fmt_customerUrl($row, $index){
	$url = "";
	if($row[2])
		$url = "<a href=\"customerlink.php?id=" . $row[1] ."\" target=\"_blank\">" . $row[2] . "</a>";
	return $url;
}

// index 0 is dmid
function fmt_DMActions($row, $index){
	$actions = array();
	$dmid = $row[0];
	$actions[] = action_link("Edit", "pencil","editdm.php?dmid=" . $dmid);
	$actions[] = action_link("Status", "fugue/globe","dmstatus.php?dmid=" . $dmid);
	$actions[] = action_link("Reset", "fugue/burn","customerdms.php?resetDM=" . $dmid, "return confirm('Are you sure you want to reset DM " . addslashes($row[3]) . "?');");
	$actions[] = action_link("Update", "application_go","customerdms.php?update=" . $dmid, "return confirm('Are you sure you want to update DM " . addslashes($row[3]) . "?');");
	$actions[] = action_link("Upload", "folder","dmupload.php?dmid=" . $dmid);
	if ($row[6] != "deleted") {
		$actions[] = action_link("Delete", "cross","customerdms.php?delete=" . $dmid,"return confirm('Are you sure you want to delete DM " . addslashes($row[3]) . "?');");
	} else {
		$actions[] = action_link("Undelete", "arrow_left","customerdms.php?undelete=" . $dmid,"return confirm('Are you sure you want to undelete DM " . addslashes($row[3]) . "?');");
	}
	return action_links($actions);
}

function fmt_state($row, $index){

	switch ($row[$index]) {
		case "active":
			return "Authorized";
		case "new":
			return "New";
		case "disabled":
			return "Unauthorized";
	}
}

function fmt_dmstatus($row,$index) {
	$problems = array();


	if ($row[6] != "active") {
		$problems[] = "Not Authorized";
	} else {
		if (!ip4HostIsInNetwork($row[5],$row[4]))
			$problems[] = "IP Mismatch";

		if ($row[7]/1000 < time() - 30)
			$problems[] = "DM Lost Connection";

		if ($row[1] == null || $row[1] <= 0)
			$problems[] = "Invalid Customer ID";
	}

	if ($row['dmmethod'] == 'asp')
		$problems[] = "Customer set to Hosted";

	if (count($problems))
		return "<div style=\"background-color:red\">" . implode(", ", $problems) . "</div>";
	else
		return "OK";
}

function fmt_dmstatus_nohtml($row,$index, $usehtml=true) {
	$problems = array();

	if ($row[6] != "active") {
		$problems[] = "Not Authorized";
	} else {
		if (!ip4HostIsInNetwork($row[5],$row[4]))
			$problems[] = "IP Mismatch";

		if ($row[7]/1000 < time() - 30)
			$problems[] = "DM Lost Connection";

		if ($row[1] == null || $row[1] <= 0)
			$problems[] = "Invalid Customer ID";
	}
	
	if ($row['dmmethod'] == 'asp')
		$problems[] = "Customer set to Hosted";

	if (count($problems))
		return implode(", ", $problems);
	else
		return "OK";
}

function fmt_lastseen($row, $index){
	$output = date("Y-m-d G:i:s", $row[$index]/1000);
	if($row[$index]/1000 > strtotime("now") - (1800) && $row[$index]/1000 < strtotime("now")-600){
		$output = "<div style=\"background-color:yellow\">" . $output . "</div>";
	} else if($row[$index]/1000 < strtotime("now") - (1800)){
		$output = "<div style=\"background-color:red\">" . $output . "</div>";
	}
	return $output;
}

function fmt_resources ($row,$index) {
	//index 16 is poststatus
	if (!isset($row[16]) || $row[16] == "")
		return ""; // unauth dm has no poststatus
		
	$data = json_decode($row[16]);
	$data = $data[0];
	
	$restotal = $data->restotal;
	$resactout = $data->restotal ? $data->resactout/$restotal * 100 : 0;
	$resactin = $data->restotal ? $data->resactin/$restotal * 100 : 0;
	
	$used = $data->resactout + $data->resactin;
	
	$str = $used .'/'. $row[$index] . 
		'<div style="float: right; width: 100px; height: 16px; border: 1px solid black;">
			<div style="float: left; width: '.$resactout.'px; height: 16px; background: #00BBFF;"></div>
			<div style="float: left; width: '.$resactin.'px; height: 16px; background: #FF00BB;"></div>
		</div>';
	
	return $str;
}

$dms = array();
$query = "select dm.id, dm.customerid, c.urlcomponent, dm.name, dm.authorizedip, dm.lastip,
			dm.enablestate, dm.lastseen, dm.version, dm.dmuuid, dm.command, s_telco_calls_sec.value as telco_calls_sec, 
			s_telco_type.value as telco_type, s_delmech_resource_count.value as delmech_resource_count,
			s_telco_inboundtoken.value as telco_inboundtoken, c.shardid, dm.poststatus, dm.notes
			from dm dm
			left join customer c on (c.id = dm.customerid)
			left join dmsetting s_telco_calls_sec on 
					(dm.id = s_telco_calls_sec.dmid 
					and s_telco_calls_sec.name = 'telco_calls_sec')
			left join dmsetting s_telco_type on 
					(dm.id = s_telco_type.dmid 
					and s_telco_type.name = 'telco_type')
			left join dmsetting s_delmech_resource_count on 
					(dm.id = s_delmech_resource_count.dmid 
					and s_delmech_resource_count.name = 'delmech_resource_count')
			left join dmsetting s_telco_inboundtoken on 
					(dm.id = s_telco_inboundtoken.dmid 
					and s_telco_inboundtoken.name = 'telco_inboundtoken')
			left join dmsetting s_dm_enabled on 
					(dm.id = s_dm_enabled.dmid 
					and s_dm_enabled.name = 'dm_enabled')			
			where dm.type = 'customer'
			" . $queryextra . "
			order by dm.customerid, dm.name";
$result = Query($query);
$data = array();
while($row = DBGetRow($result))
	$data[$row[0]] = $row;

if ($data) {
	// First, get a list of every shard, $shardinfo[], indexed by ID, storing dbhost, dbusername, and dbpassword.
	$result = Query("select id, dbhost, dbusername, dbpassword, name from shard order by id");
	$shardinfo = array();
	while($row = DBGetRow($result)){
		$shardinfo[$row[0]] = array($row[1], $row[2], $row[3], $row[4]);
	}
	
	// Connect to each customer's shard and retrieve dmmethod
	$custdb;
	foreach($data as $dmid => $cust) {
		if ($cust[1] + 0 > 0) {
			try {
				$dsn = 'mysql:dbname=c_'.$cust[1].';host='.$shardinfo[$cust[15]][0];
				$custdb = new PDO($dsn, $shardinfo[$cust[15]][1], $shardinfo[$cust[15]][2]);
				$custdb->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
			} catch (PDOException $e) {
				die("Could not connect to customer database: ".$e->getMessage());
			}
			Query("use c_" . $cust[1], $custdb);
			if ($custdb) {
				$query = "select value from setting where name = '_dmmethod' limit 1";
				$data[$dmid]['dmmethod'] = QuickQuery($query, $custdb);
				
				$query = "select dmid,name,telco_type,poststatus,notes from custdm where dmid = ?";
				$custdminfo = QuickQueryRow($query,true,$custdb, array($dmid));
				if ($custdminfo) {
					$data[$dmid][3] = $custdminfo["name"];
					$data[$dmid][12] = $custdminfo["telco_type"];
					$data[$dmid][16] = $custdminfo["poststatus"];
					$data[$dmid][17] = $custdminfo["notes"];
				}
			}
		} else {
			$data[$dmid]['dmmethod'] = '';
		}
		
	}
}

// Add field titles, leading # means it is sortable leading @ means it is hidden by default
$titles = array(0 => "#DM ID");
$titles[1] = "#Cust ID";
$titles[2] = "#Customer URL";
$titles[3] = "#Name";
$titles[4] = "#Authorized IP";
$titles[5] = "#Last IP";
$titles[7] = "#Last Seen";
$titles[6] = "#Auth";
$titles["status"] = "#Status";
$titles[8] = "#Version";
$titles[11] = "@#Calls/Sec";
$titles[12] = "@#Type";
$titles[13] = "Resources";
$titles[14] = "@#Inbound";
$titles[9] = "@#DM UUID";
$titles[10] = "@#Cmd";
$titles[17] = "#Notes";
$titles["actions"] = "Actions";

// Set hidden prefix from sticky session variable
setStickyColumns($titles,"customerdms");

// Do not provide a checkbox to hide these columns.
$lockedTitles = array(0, "status", "actions", 2, 3);

$filterTitles = array(6,"status",8,12);

$formatters = array(2 => "fmt_customerUrl",
					"actions" => "fmt_DMActions",
					"status" => "fmt_dmstatus",
					7 => "fmt_lastseen",
					6 => "fmt_state",
					13 => "fmt_resources");

$filterFormatters = array("status" => "fmt_dmstatus_nohtml",6 => "fmt_state");
/////////////////////////////
// Display
/////////////////////////////

include_once("nav.inc.php");

?>
<form method="GET" name="viewoptions" id="viewoptions" action="customerdms.php">
<table>
<tr>
	<td>
	Displaying: 
	</td>
	<td>
	<select name="enablestate" id='enablestate' onchange="blankFieldSubmit();this.form.submit();">
		<option value='all' <?=($viewoptions["enablestate"]=='all')?"selected":""?>>Show All</option>
		<option value='new' <?=($viewoptions["enablestate"]=='new')?"selected":""?>>New</option>
		<option value='active' <?=($viewoptions["enablestate"]=='active')?"selected":""?>>Active</option>
		<option value='disabled' <?=($viewoptions["enablestate"]=='disabled')?"selected":""?>>Disabled</option>
		<option value='deleted' <?=($viewoptions["enablestate"]=='deleted')?"selected":""?>>Deleted</option>
	</select>
	<select name="enabled" id='enabled' onchange="blankFieldSubmit();this.form.submit();">
		<option value='all' <?=($viewoptions["enabled"]== "all")?"selected":""?>>All</option>
		<option value='true' <?=($viewoptions["enabled"]=="true")?"selected":""?>>Enabled</option>
		<option value='false' <?=($viewoptions["enabled"]=="false")?"selected":""?>>Disabled</option>
	</select>
	</td>
	<td>
	<input type="text" name="custtxt" id="custtxt" value="<?=$custtxt?>" size="40" maxlength="50" />
	</td>
</tr>
</table>
</form>

<hr />
<?
if (count($data)) {
	// show the row data filters
	show_row_filter('customer_dm_table', $data, $titles, $filterTitles, $filterFormatters);
	// Show the column data hide/select check boxes.
	show_column_selector('customer_dm_table', $titles, $lockedTitles,"customerdms");
	
	?>
	<table class="list sortable" id="customer_dm_table">
	<?
	showTable($data, $titles, $formatters);
	?>
	</table>
	
	<script type="text/javascript">
		document.observe('dom:loaded', function() {
			// Append id to make javascript filter work
			var table = $('customer_dm_table');
			var trows = table.rows;
			for (var i = 0, length = trows.length; i < length; i++) {
				trows[i].id = 'row'+i;
			}
		});
	</script>
	Resource legend:
	<span style="background: #00BBFF;">outbound</span>
	<span style="background: #FF00BB;">inbound</span>
	<?
} else {
	echo "<div class='destlabel'><img src='img/largeicons/information.jpg' align='middle'> " . _L("No Records Found") . "<div>";
}

?>
<script type="text/javascript">
	blankFieldValue('custtxt', 'Search Customer URL');

	function blankFieldSubmit() {
		if ($('custtxt').getStyle('color') == 'gray') {
			$('custtxt').value = "";
		}
	}
</script>

<?
include_once("navbottom.inc.php");
?>
