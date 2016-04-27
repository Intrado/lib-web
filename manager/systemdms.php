<?
const MIN_FRESHNESS = 60;
require_once("common.inc.php");
require_once("../inc/form.inc.php");
require_once("../inc/html.inc.php");
require_once("../inc/table.inc.php");
require_once("../inc/formatters.inc.php");
require_once("dbmo//authserver/DmGroup.obj.php");
include_once("../inc/memcache.inc.php");

if (!$MANAGERUSER->authorized("systemdm")) {
	exit("Not Authorized");
}


// Action Handlers 
if (isset($_GET['resetDM'])) {
	if (null != QuickQuery("select command from dm where id=?", false,array($_GET['resetDM']))) {
		notice(_L('DM already had a command queued.  New command queued instead.'));
	}
	QuickUpdate("update dm set command='reset' where id=?", false,array($_GET['resetDM']));
	notice(_L("Reset queued"));
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



$viewoptions =  array(
	"enablestate" => "active",
	"enabled" => "true"
);


if(isset($_REQUEST['enablestate']))
	$viewoptions["enablestate"] = $_REQUEST['enablestate'];

if(isset($_REQUEST['enabled']))
	$viewoptions["enabled"] = $_REQUEST['enabled']=="true";


$enablestates = array("all","new","active","disabled","deleted");
if (!in_array($viewoptions["enablestate"],$enablestates))
	$viewoptions["enablestate"] = 'active';

$queryextra = "";
if ($viewoptions["enablestate"] != "all")
	$queryextra .= " and dm.enablestate = '{$viewoptions["enablestate"]}' ";

if ($viewoptions["enabled"] == "false")
	$queryextra .= " and s_dm_enabled.value = '0' ";
else if ($viewoptions["enabled"] == "true")
	$queryextra .= " and (s_dm_enabled.value = '1' or s_dm_enabled.value is null) ";


// index 0 is dmid
function fmt_DMActions($row, $index){
	$actions = array();
	$dmid = $row[0];
	$dm = urlencode($row[1]);

	$actions[] = action_link("Edit", "pencil","editdm.php?dmid=" . $dmid);
	$actions[] = action_link("Status", "fugue/globe","dmstatus.php?dmid=" . $dmid);
	$actions[] = action_link("Reset", "fugue/burn","systemdms.php?resetDM=" . $dmid, "return confirm('Are you sure you want to reset DM " . addslashes($row[3]) . "?');");
	$actions[] = action_link("Graph", "phone", "aspcallsbytimebyday.php?dm=$dm");
	if ($row[4] != "deleted") {
		$actions[] = action_link("Delete", "cross","systemdms.php?delete=" . $dmid,"return confirm('Are you sure you want to delete DM " . addslashes($row[3]) . "?');");
	} else {
		$actions[] = action_link("Undelete", "arrow_left","systemdms.php?undelete=" . $dmid,"return confirm('Are you sure you want to undelete DM " . addslashes($row[3]) . "?');");
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


	if ($row[4] != "active") {
		$problems[] = "Not Authorized";
	} else {
		if (!ip4HostIsInNetwork($row[3], $row[2])) {
			$problems[] = "IP Mismatch";
		}

		if ($row[17] > MIN_FRESHNESS) {
			$problems[] = "DM Lost Connection";
		}
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
		if (!ip4HostIsInNetwork($row[3],$row[2])) {
			$problems[] = "IP Mismatch";
		}

		if ($row[17] > MIN_FRESHNESS) {
			$problems[] = "DM Lost Connection";
		}
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
function fmt_routetype($row, $index){
	global $dmgroups;
	$dmgroupid = $row[15];
	$routetypes = array("firstcall" => "Firstcall","lastcall" => "Lastcall","" => "Other");
	$routetype = isset($dmgroups[$dmgroupid])?$dmgroups[$dmgroupid]["routeType"]:"";
	return isset($routetypes[$routetype])?$routetypes[$routetype]:"Unknown";
}
function fmt_dmgroupname($row, $index){
	global $dmgroups;
	$dmgroupid = $row[15];
	return isset($dmgroups[$dmgroupid])?$dmgroups[$dmgroupid]["name"]:"";
}
function fmt_dmgroupratemodel($row, $index){
	global $dmgroups,$carrierRateModels;
	$dmgroupid = $row[15];
	$carrierRateModelId = isset($dmgroups[$dmgroupid])?$dmgroups[$dmgroupid]["carrierRateModelId"]:"";
	return isset($carrierRateModels[$carrierRateModelId])?$carrierRateModels[$carrierRateModelId]["name"]:"Unknown";
}

function fmt_dmgroupcarrier($row, $index){
	global $dmgroups;
	$dmgroupid = $row[15];
	return isset($dmgroups[$dmgroupid])?$dmgroups[$dmgroupid]["carrier"]:"";
}

function fmt_dmgroupstate($row, $index){
	global $dmgroups;
	$dmgroupid = $row[15];
	return isset($dmgroups[$dmgroupid])?$dmgroups[$dmgroupid]["state"]:"";
}

$dmgroups = array();
$dmGroupQuery = "select dmgroup.id, dmgroup.name, dmgroup.routeType, dmgroup.carrierRateModelId from dmgroup";
$result = Query($dmGroupQuery);
while($row = DBGetRow($result,true)) {
	$dmgroups[$row['id']] = $row;
}

$carrierRateModels = array();
if (isset($SETTINGS['lcrdb'])) {
	$lcrdbcon = DBConnect($SETTINGS['lcrdb']['host'], $SETTINGS['lcrdb']['user'], $SETTINGS['lcrdb']['pass'], $SETTINGS['lcrdb']['db']);
	$result = Query("select id,name from carrierratemodel",$lcrdbcon);
	while($row = DBGetRow($result,true)) {
		$carrierRateModels[$row['id']] = $row;
	}
}
$dms = array();
$query = "select dm.id, dm.name, dm.authorizedip, dm.lastip,
			dm.enablestate, dm.lastseen, dm.version, dm.dmuuid, dm.command, s_telco_calls_sec.value as telco_calls_sec, 
			s_telco_type.value as telco_type, s_delmech_resource_count.value as delmech_resource_count,
			s_telco_inboundtoken.value as telco_inboundtoken, '' as poststatus, dm.routetype, dm.dmgroupid, dm.notes,
			now() - from_unixtime(dm.lastseen/1000) as freshness
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
			$queryextra
			order by dm.dmgroupid, lpad(dm.name,50,' ')";
$result = Query($query);
$data = array();
$restotal = 0;
$resactout = 0;
$resactin = 0;

$memcaches = array();
if (isset($SETTINGS['memcache']) && isset($SETTINGS['memcache']['hosts'])) {
	foreach ((array)$SETTINGS['memcache']['hosts'] as $host) {
		$memcache = new Memcache();
		$memcache->addserver($host);
		$memcaches[] = $memcache;
	}
}

while($row = DBGetRow($result)){
	$data[$row[0]] = $row;

	//fake some blank data when the api is unavailable
	$poststatus = "[{\"restotal\":0, \"resactout\": 0, \"resactin\":0}]";
	$cachedpoststatus = false;
	// since we don't have memcached and java is putting things in cache using a different hash
	// method the only way to find the key is to try both memcache servers.  While this is wonky
	// it works so what the heck.  Finally, for voipin's java compresses the value in a way that
	// again prevents memcache() from reading it, so for those we end up falling back to curl.
	// Still overall its WAY faster than using http for all dms.
	foreach ($memcaches as $memcache) {
		try {
			$cachedpoststatus = $memcache->get("dmpoststatus/" . $row[1]);
		} catch (Exception $e) {
			// nothing
		}
		if ($cachedpoststatus !== false) {
			break;
		}
	}
	if ($cachedpoststatus === false) {
		$managerApi = "https://{$_SERVER['SERVER_NAME']}:{$_SERVER['SERVER_PORT']}/manager/api/2";
		$dmUuid = $row[7];
		$url = "{$managerApi}/deliverymechanisms/{$dmUuid}";
		if (($fh = fopen($url, "r")) !== false) {
			$apidata = stream_get_contents($fh);
			fclose($fh);
			if ($apidata) {
				$dmdata = json_decode($apidata);
				if (isset($dmdata->postStatus)) {
					$poststatus = $dmdata->postStatus;
				}
			}
		}
	} else {
		$poststatus = $cachedpoststatus;
	}

	$data[$row[0]][13] = $poststatus;
	$poststatus = json_decode($poststatus);
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
$titles["dmgroupname"] = "#Dm Group Name";
$titles["ratemodel"] = "@#Rate Model";
$titles["routetype"] = "@#Route Type";
$titles[16] = "#Notes";
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
					11 => "fmt_resources",
					"dmgroupname" => "fmt_dmgroupname",
					"ratemodel" => "fmt_dmgroupratemodel",
					"routetype" => "fmt_routetype");

$filterFormatters = array("status" => "fmt_dmstatus_nohtml",4 => "fmt_state");
/////////////////////////////
// Display
/////////////////////////////
$TITLE = _L("System&nbsp;DMs");
$PAGE = "dm:systemdms";

include_once("nav.inc.php");

startWindow(_L('System DMs'));

?>
<form id="viewoptions" method="GET" action="systemdms.php">
<table>
<tr>
	<td>
	Displaying: 
	</td>
	<td>
	<select name="enablestate" id='enablestate' onchange="this.form.submit();">
		<option value='all' <?=($viewoptions["enablestate"]=='all')?"selected":""?>>Show All</option>
		<option value='new' <?=($viewoptions["enablestate"]=='new')?"selected":""?>>New</option>
		<option value='active' <?=($viewoptions["enablestate"]=='active')?"selected":""?>>Active</option>
		<option value='disabled' <?=($viewoptions["enablestate"]=='disabled')?"selected":""?>>Disabled</option>
		<option value='deleted' <?=($viewoptions["enablestate"]=='deleted')?"selected":""?>>Deleted</option>
	</select>
	<select name="enabled" id='enabled' onchange="this.form.submit();">
		<option value='all' <?=($viewoptions["enabled"]== "all")?"selected":""?>>All</option>
		<option value='true' <?=($viewoptions["enabled"]=="true")?"selected":""?>>Enabled</option>
		<option value='false' <?=($viewoptions["enabled"]=="false")?"selected":""?>>Disabled</option>
	</select>
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
		});
	</script>
	<?
} else {
	echo "<div class='destlabel'><img src='img/largeicons/information.jpg' align='middle'> " . _L("No Records Found") . "</div>";
}
endWindow();

include_once("navbottom.inc.php");
?>
