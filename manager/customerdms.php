<?
require_once("common.inc.php");
require_once("../inc/form.inc.php");
require_once("../inc/html.inc.php");
require_once("../inc/table.inc.php");
require_once("../inc/formatters.inc.php");

if (!$MANAGERUSER->authorized("editdm"))
	exit("Not Authorized");

$custtxt = "";

if(isset($_GET['resetDM']) || isset($_GET['update'])){
	if(isset($_GET['resetDM'])){
		$dmid = $_GET['resetDM'] + 0;
		$command = "reset";
	} else if(isset($_GET['update'])){
		$dmid = $_GET['update'] + 0;
		$command = "update";
	}
	$dmrow = QuickQueryRow("select name, command from dm where id = " . $dmid);
	if($dmrow[1] != ""){
?>
	<script>
		window.location="customerdms.php";
	</script>
<?
	}
	QuickUpdate("update dm set command = '" . $command ."' where id = " . $dmid);
?>
	<script>
		window.location="customerdms.php";
	</script>
<?
}

$queryextra = "";

if(isset($_GET['clear'])){
	unset($_SESSION['customerid']);
	redirect();
}
if (isset($_GET['cid'])) {
	if ($_GET['cid']) {
		$queryextra = " AND dm.customerid in (";
		foreach (explode(",", $_GET['cid']) as $cid)
			$queryextra .= "'". DBSafe($cid) . "',";
			
		$queryextra = substr($queryextra, 0, -1) . ") ";
	}
}

if (isset($_POST['showmatch'])) {
	if (isset($_POST['custtxt']) && trim($_POST['custtxt'])) {
		$custtxt = escapehtml(trim($_POST['custtxt']));
		$queryextra = " and c.urlcomponent like '%" . DBSafe(trim($_POST['custtxt'])) . "%'";
	}
}

if(isset($_SESSION['customerid'])){
	$queryextra = " and dm.customerid = " . $_SESSION['customerid'] . " ";
}

if(isset($_GET['showdisabled'])) {
	$showingDisabledDMs = true;
	$queryextra .= " and s_dm_enabled.value = '0' ";
} else {
	$showingDisabledDMs = false;
	$queryextra .= " and (s_dm_enabled.value = '1' or s_dm_enabled.value is null) ";
}

if(isset($_GET['showall'])) {
	$showingDisabledDMs = false;
	$queryextra = "";
}

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
	$url =  '<a href="editdm.php?dmid=' . $row[0] . '" title="Edit"><img src="mimg/s-edit.png" border=0></a>&nbsp;' .
			'<a href="dmstatus.php?dmid=' . $row[0] . '" title="Status"><img src="mimg/s-rdms.png" border=0></a>&nbsp;' .
			'<a href="#" onclick="if(confirm(\'Are you sure you want to reset DM ' . addslashes($row[3]) . '?\')) window.location=\'customerdms.php?resetDM=' . $row[0] . '\'" title="Reset"><img src="mimg/s-restart.png" border=0></a>&nbsp;' .
			'<a href="#" onclick="if(confirm(\'Are you sure you want to update DM ' . addslashes($row[3]) . '?\')) window.location=\'customerdms.php?update=' . $row[0] . '\'" title="Update"><img src="mimg/s-update.png" border=0></a>&nbsp;' .
			'<a href="dmupload.php?dmid=' . $row[0] . '" title="Upload DatFile"><img src="mimg/s-dat.png" border=0></a>';
	return $url;
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
	$data[] = $row;

if ($data) {
	// First, get a list of every shard, $shardinfo[], indexed by ID, storing dbhost, dbusername, and dbpassword.
	$result = Query("select id, dbhost, dbusername, dbpassword, name from shard order by id");
	$shardinfo = array();
	while($row = DBGetRow($result)){
		$shardinfo[$row[0]] = array($row[1], $row[2], $row[3], $row[4]);
	}
	
	// Connect to each customer's shard and retrieve dmmethod
	$custdb;
	foreach($data as $dataPos => $cust) {
		if ($cust[1] + 0 > 0) {
			try {
				$dsn = 'mysql:dbname=c_'.$cust[1].';host='.$shardinfo[$cust[15]][0];
				$custdb = new PDO($dsn, $shardinfo[$cust[15]][1], $shardinfo[$cust[15]][2]);
				$custdb->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
			} catch (PDOException $e) {
				die("Could not connect to customer database: ".$e->getMessage());
			}
			Query("use c_" . $cust[1], $custdb);
			$query = "select value from setting where name = '_dmmethod' limit 1";
			if ($custdb)
				$data[$dataPos]['dmmethod'] = QuickQuery($query, $custdb);
		} else {
			$data[$dataPos]['dmmethod'] = '';
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
$titles[17] = "@#Notes";
$titles["actions"] = "Actions";

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

<form method="POST" action="customerdms.php">
<table>
	<tr>
		<td valign="top">
			<table border="0" cellpadding="2" cellspacing="1" class="list">
				<tr class="listHeader" align="left" valign="bottom">
					<td>
						Search (can match partial urls)
					</td>
				</tr>
				<tr>
					<td valign="top">
						<table>
							<tr>
								<td valign="top" align="left">
									Cust URL:
								</td>
								<td>
									<input type="text" name="custtxt" id="custtxt" value="<?=$custtxt?>" size="20" maxlength="50" />
								</td>
							</tr>
							<tr>
								<td colspan="2">
									<input type="submit" name="showmatch" id="showmatch" value="Search" />   
								</td>
							</tr>
						</table>
					</td>
				</tr>
			</table>
		</td>
		<td valign="top">
			<?
			// show the row data filters
			show_row_filter('customer_dm_table', $data, $titles, $filterTitles, $filterFormatters);
			
			?>
		</td>
	</tr>
</table>
<a href='customerdms.php?showall=1'>Show All DMs</a> 
<? 
if($showingDisabledDMs) {
	?><a href='customerdms.php'>Show Enabled DMs</a><? 
} else {
	?><a href='customerdms.php?showdisabled=1'>Show Disabled DMs</a><?
}
?>
</form>
<?

// Show the column data hide/select check boxes.
show_column_selector('customer_dm_table', $titles, $lockedTitles);

?>
<table class="list sortable" id="customer_dm_table">
<?
	showTable($data, $titles, $formatters);
?>
</table>
<script language="javascript">
	var table = new getObj('customer_dm_table').obj;
	var trows = table.rows;
	for (var i = 0, length = trows.length; i < length; i++) {
		trows[i].id = 'row'+i;
	}
</script>
<?
if(file_exists("dmbuild.txt")){
?>
	<div>Latest Version: <?=file_get_contents("dmbuild.txt");?></div>
<?
}
include_once("navbottom.inc.php");
?>
