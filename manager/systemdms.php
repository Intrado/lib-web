<?
require_once("common.inc.php");
require_once("../inc/form.inc.php");
require_once("../inc/html.inc.php");
require_once("../inc/table.inc.php");
require_once("../inc/formatters.inc.php");

if (!$MANAGERUSER->authorized("systemdm"))
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
		window.alert('That DM already had a command queued.  New command queued instead.');
		window.location="systemdms.php";
	</script>
<?
	}
	QuickUpdate("update dm set command = '" . $command ."' where id = " . $dmid);
	redirect();
}

$queryextra = "";

if(isset($_GET['showdisabled'])) {
	$showingDisabledDMs = true;
	$queryextra .= " and s_dm_enabled.value = '0' ";
} else {
	$showingDisabledDMs = false;
	$queryextra .= " and s_dm_enabled.value = '1' ";
}

if(isset($_GET['showall'])) {
	$showingDisabledDMs = false;
	$queryextra = "";
}

// index 0 is dmid
function fmt_DMActions($row, $index){
	$url =  '<a href="editdm.php?dmid=' . $row[0] . '" title="Edit"><img src="img/s-edit.png" border=0></a>&nbsp;' .
			'<a href="dmstatus.php?dmid=' . $row[0] . '" title="Status"><img src="img/s-rdms.png" border=0></a>&nbsp;' .
			'<a href="#" onclick="if(confirm(\'Are you sure you want to reset DM ' . addslashes($row[3]) . '?\')) window.location=\'systemdms.php?resetDM=' . $row[0] . '\'" title="Reset"><img src="img/s-restart.png" border=0></a>&nbsp;';
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


	if ($row[4] != "active") {
		$problems[] = "Not Authorized";
	} else {
		if (!ip4HostIsInNetwork($row[3],$row[2]))
			$problems[] = "IP Mismatch";

		if ($row[5]/1000 < time() - 30)
			$problems[] = "DM Lost Connection";
	}

	if (count($problems))
		return "<div style=\"background-color:red\">" . implode(", ", $problems) . "</div>";
	else
		return "OK";
}

function fmt_dmstatus_nohtml($row,$index, $usehtml=true) {
	$problems = array();


	if ($row[4] != "active") {
		$problems[] = "Not Authorized";
	} else {
		if (!ip4HostIsInNetwork($row[3],$row[2]))
			$problems[] = "IP Mismatch";

		if ($row[5]/1000 < time() - 30)
			$problems[] = "DM Lost Connection";
	}

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
	//index 13 is poststatus
	if (!isset($row[13]) || $row[13] == "")
		return ""; // unauth dm has no poststatus
		
	$data = json_decode($row[13]);
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
$query = "select dm.id, dm.name, dm.authorizedip, dm.lastip,
			dm.enablestate, dm.lastseen, dm.version, dm.dmuuid, dm.command, s_telco_calls_sec.value as telco_calls_sec, 
			s_telco_type.value as telco_type, s_delmech_resource_count.value as delmech_resource_count,
			s_telco_inboundtoken.value as telco_inboundtoken,
			poststatus
			from dm dm
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
			where dm.type = 'system'
			" . $queryextra . "
			order by dm.name";
$result = Query($query);
$data = array();
$restotal = 0;
$resactout = 0;
$resactin = 0;
while($row = DBGetRow($result)){
	$data[] = $row;
	
	$poststatus = json_decode($row[13]);
	$poststatus = $poststatus[0];
	
	$restotal += $poststatus->restotal;
	$resactout += $poststatus->resactout;
	$resactin += $poststatus->resactin;
}

//scale to 0-100
$respctout = $restotal ? $resactout/$restotal * 100 : 0;
$respctin = $restotal ? $resactin/$restotal * 100 : 0;

// Add field titles, leading # means it is sortable leading @ means it is hidden by default
$titles = array(0 => "#DM ID");
$titles[1] = "#Name";
$titles[2] = "#Authorized IP";
$titles[3] = "#Last IP";
$titles[5] = "#Last Seen";
$titles[4] = "#Auth";
$titles["status"] = "#Status";
$titles[6] = "#Version";
$titles[9] = "#Calls/Sec";
$titles[10] = "@#Type";
$titles[11] = "Resources";
$titles[12] = "@#Inbound";
$titles[7] = "@#DM UUID";
$titles[8] = "@#Cmd";
$titles["actions"] = "Actions";

// Do not provide a checkbox to hide these columns.
$lockedTitles = array(0, "status", "actions", 2, 3);

$filterTitles = array(4,"status",6);

$formatters = array("actions" => "fmt_DMActions",
					"status" => "fmt_dmstatus",
					5 => "fmt_lastseen",
					4 => "fmt_state",
					11 => "fmt_resources");

$filterFormatters = array("status" => "fmt_dmstatus_nohtml",4 => "fmt_state");
/////////////////////////////
// Display
/////////////////////////////

include_once("nav.inc.php");

?>
<form method="POST" action="systemdms.php">
<?
// show the row data filters
show_row_filter('customer_dm_table', $data, $titles, $filterTitles, $filterFormatters);

?>

<a href='systemdms.php?showall=1'>Show All DMs</a> 
<? 
if($showingDisabledDMs) {
	?><a href='systemdms.php'>Show Enabled DMs</a><? 
} else {
	?><a href='systemdms.php?showdisabled=1'>Show Disabled DMs</a><?
}
?>
</form>
<?

// Show the column data hide/select check boxes.
show_column_selector('customer_dm_table', $titles, $lockedTitles);

?>

<div style="float: left;">Total resources: <?= ($resactin + $resactout) ?>/<?= $restotal?></div>
<div style="float: left; margin-left: 10px; width: 100px; height: 16px; border: 1px solid black;">
	<div style="float: left; width: <?=$respctout?>px; height: 16px; background: #00BBFF;"></div>
	<div style="float: left; width: <?=$respctin?>px; height: 16px; background: #FF00BB;"></div>
</div>


<table class="list sortable" id="customer_dm_table" width="100%">
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
?>

Resource legend:
<span style="background: #00BBFF;">outbound</span>
<span style="background: #FF00BB;">inbound</span>

<?
include_once("navbottom.inc.php");
?>
