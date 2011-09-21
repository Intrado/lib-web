<?
require_once("common.inc.php");
require_once("../inc/form.inc.php");
require_once("../inc/html.inc.php");
require_once("../inc/table.inc.php");
require_once("../inc/formatters.inc.php");

if (!$MANAGERUSER->authorized("systemdm"))
	exit("Not Authorized");


// Action Handlers 
if (isset($_GET['resetDM'])) {
	if (!QuickQuery("select command from dm where id=?", false,array($_GET['resetDM']))) {
		notice(_L('DM already had a command queued.  New command queued instead.'));
	}
	QuickUpdate("update dm set command='reset' where id=?", false,array($_GET['resetDM']));
	notice(_L("Reset queued"));
	redirect();
} else if (isset($_GET['delete'])) {
	$enablestate = QuickQuery("select enablestate from dm where id=?",false,array($_GET['delete']));
	if ($enablestate != "disabled") {
		notice(_L("Unable to delete dm"));
		redirect();
	}
	QuickUpdate("update dm set enablestate='deleted' where id=?",false,array($_GET['delete']));
	notice(_L("Deleted DM"));
	redirect();
}


$queryextra = "";
$viewoptions = 'all';
if (isset($_POST['submit']) && $_POST['submit'] == "showmatch") {
	if(isset($_POST['view'])) {
		$viewoptions = $_POST['view'];
		switch($viewoptions) {
			case "enabled":
				$queryextra .= " and (s_dm_enabled.value = '1' or s_dm_enabled.value is null) ";
				break;
			case "disabled":
				$queryextra .= " and s_dm_enabled.value = '0' and dm.enablestate != 'deleted'";
				break;
			case "deleted":
				$queryextra .= " and dm.enablestate = 'deleted'";
				break;
			case "all":
			default:
				$queryextra .= " and dm.enablestate != 'deleted'";
				$viewoptions = 'all';
				break;
		}
	}
} else {
	$queryextra .= " and dm.enablestate != 'deleted'";
}


// index 0 is dmid
function fmt_DMActions($row, $index){
	$actions = array();
	$dmid = $row[0];
	$actions[] = action_link("Edit", "pencil","editdm.php?dmid=" . $dmid);
	$actions[] = action_link("Status", "fugue/globe","dmstatus.php?dmid=" . $dmid);
	$actions[] = action_link("Reset", "fugue/burn","customerdms.php?resetDM=" . $dmid, "return confirm('Are you sure you want to reset DM " . addslashes($row[3]) . "?');");
	if ($row[4] == "disabled") {
		$actions[] = action_link("Delete", "cross","customerdms.php?delete=" . $dmid,"return confirm('Are you sure you want to delete DM " . addslashes($row[3]) . "?');");
	}
	return "<div id='actions_$dmid' style='display:none;'>" .  str_replace("&nbsp;|&nbsp;","<br />",action_links($actions)) . "</div>
				<img id='actionlink_$dmid' src='img/icons/fugue/gear.png' alt='tools' />";
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
			poststatus,
			dm.notes
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
	$data[$row[0]] = $row;
	
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
$titles[14] = "#Notes";
$titles["actions"] = "Actions";

// Set hidden prefix from sticky session variable
setStickyColumns($titles,"systemdms");

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
<form id="viewoptions" method="POST" action="systemdms.php">
<table>
<tr>
	<td>
	<select name="view" id='view'>
		<option value='all' <?=($viewoptions=='all')?"selected":""?>>Show All</option>
		<option value='enabled' <?=($viewoptions=='enabled')?"selected":""?>>Enabled</option>
		<option value='disabled' <?=($viewoptions=='disabled')?"selected":""?>>Disabled</option>
		<option value='deleted' <?=($viewoptions=='deleted')?"selected":""?>>Deleted</option>
	</select>
	</td><td><div id="searchbutton">
	<?= submit_button("View","showmatch","magnifier")?>
	</div>
	</td>	
</tr>
</table>
</form>

<hr />
<?
if (count($data)) {
	?>
	<table>
	<tr><td>
	<?
	// show the row data filters
	show_row_filter('customer_dm_table', $data, $titles, $filterTitles, $filterFormatters);
	?></td><td valign="top"><?
	// Show the column data hide/select check boxes.
	show_column_selector('customer_dm_table', $titles, $lockedTitles,"systemdms");
	
	?>
	<div style="margin: 10px;">Total resources: <?= ($resactin + $resactout) ?>/<?= $restotal?>
	&nbsp;&nbsp;|&nbsp;&nbsp;
	Resource legend:
		<span style="background: #00BBFF;">outbound</span>
		<span style="background: #FF00BB;">inbound</span>
	</div>
	<div style="margin-left:10px;float: left; width: 300px; height: 30px; border: 1px solid black;">
			<div style="float: left; width: <?=$respctout * 3?>px; height: 30px; background: #00BBFF;"></div>
			<div style="float: left; width: <?=$respctin * 3?>px; height: 30px; background: #FF00BB;"></div>
	</div>
	</td>
	</tr>
	</table>
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

			// Add tooltips
			var dmids = <?= json_encode(array_keys($data))?>;
			dmids.each(function (dmid) {				
				$('actionlink_' + dmid).tip = new Tip('actionlink_' + dmid, $('actions_' + dmid).innerHTML, {
					style: 'protogrey',
					radius: 4,
					border: 4,
					hideOn: false,
					hideAfter: 0.5,
					stem: 'topRight',
					hook: {  target: 'bottomLeft', tip: 'topRight'  },
					width: 'auto',
					offset: { x: 3, y: -3 }
				});
			});
		});
	</script>
	<?
} else {
	echo "<div class='destlabel'><img src='img/largeicons/information.jpg' align='middle'> " . _L("No Records Found") . "<div>";
}
include_once("navbottom.inc.php");
?>
