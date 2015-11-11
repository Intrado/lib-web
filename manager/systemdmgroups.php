<?
require_once("common.inc.php");
require_once("../inc/form.inc.php");
require_once("../inc/html.inc.php");
require_once("../inc/table.inc.php");
require_once("../inc/formatters.inc.php");
require_once("dbmo//authserver/DmGroup.obj.php");
include_once("../inc/memcache.inc.php");

if (!$MANAGERUSER->authorized("systemdm"))
	exit("Not Authorized");


// Action Handlers 
if (isset($_GET['delete'])) {
	$numDmsInGroup = QuickQuery("select count(1) from dm where dmgroupid=?",false,array($_GET['delete']));
	if ($numDmsInGroup == 0) {
		$dmgroupid = $_GET['delete'];
		$originalDmGroup = QuickQueryRow("select * from dmgroup where id = " . $dmgroupid, true, false, false);
		if ($originalDmGroup["dmGroupJmsProfileId"] > 0) {
			$otherDmsWithThisJmsProfile = QuickQueryMultiRow("select id from dmgroup where dmGroupJmsProfileId = ?", true, false, array($originalDmGroup["dmGroupJmsProfileId"]));
			// see if we are the only one using this JmsProfile remove it since we are not using it any more
			if (size($otherDmsWithThisJmsProfile) == 1) {
				$dmGroupJmsProfile = QuickQueryRow("select dgjp.* from dmgroupjmsprofile dgjp where dgjp.id = ?", true, false, array($originalDmGroup["dmGroupJmsProfileId"]));
				$otherJmsProfilesWithThisSetting = QuickQueryMultiRow("select id from dmgroupjmsprofile where dmJmsSettingId in (?,?) OR dispatcherJmsSettingId in (?,?)", true, false,
					array($dmGroupJmsProfile["dmJmsSettingId"], $dmGroupJmsProfile["dispatcherJmsSettingId"],$dmGroupJmsProfile["dmJmsSettingId"], $dmGroupJmsProfile["dispatcherJmsSettingId"]));
				QuickQuery("delete from dmgroupjmsprofile where id = ?", false, array($dmGroupJmsProfile["id"]));
				// see if this jmsProfile is the only one using these settings remove it since we are not using it any more.
				if (size($otherJmsProfilesWithThisSetting) == 1) {
					QuickQuery("delete from dmgroupjmssetting where id in (?,?)", false, array($dmGroupJmsProfile["dmJmsSettingId"],$dmGroupJmsProfile["dispatcherJmsSettingId"]));
				}
			}
		}
		QuickUpdate("delete from dmgroup where id=?", false, array($dmgroupid));

		notice(_L("Deleted DM Group"));
	} else {
		notice(_L("Can not delete DM Group because it has " . $numDmsInGroup . " associated DMs."));
	}
	redirect();
}

$viewoptions =  array();

// index 0 is dmid
function fmt_DMActions($row, $index){
	$actions = array();
	$dmgroupid = $row[0];
	$dmname = $row[1];

	$actions[] = action_link("Edit", "pencil","editdmgroup.php?dmgroupid=" . $dmgroupid);
	$actions[] = action_link("Delete", "cross","systemdmgroups.php?delete=" . $dmgroupid,"return confirm('Are you sure you want to delete DM Group " . addslashes($dmname) . "?');");
	return action_links($actions);
}

function fmt_dmgroupdispatchtype($row, $index){
	$dispatchtype = $row[$index];
	$dispatchtypes = array("system" => "System","customer" => "Customer");
	return isset($dispatchtypes[$dispatchtype])?$dispatchtypes[$dispatchtype]:"Unknown";
}

function fmt_dmgroupjmsprofile($row, $index){
	return isset($row[$index])?$row[$index]:"None";
}


function fmt_routetype($row, $index){
	$routetype = $row[$index];
	$routetypes = array("firstcall" => "Firstcall","lastcall" => "Lastcall","" => "Other");
	return isset($routetypes[$routetype])?$routetypes[$routetype]:"Unknown";
}

function fmt_dmgroupratemodel($row, $index){
	global $carrierRateModels;
	$carrierRateModelId = $row[$index];
	return isset($carrierRateModels[$carrierRateModelId])?$carrierRateModels[$carrierRateModelId]["name"]:"Unknown";
}

$data = array();
$dmGroupQuery = "select dmgroup.id, dmgroup.name, dmgroup.carrierRateModelId as carrierRateModelId, dmgroup.dmGroupJmsProfileId as dmGroupJmsProfileId,
 						dmgroupjmsprofile.name as jmsprofilename, dmgroup.dispatchType as dispathType, dmgroup.routeType as routeType, dmgroup.notes from dmgroup
                left join dmgroupjmsprofile on (dmgroup.dmGroupJmsProfileId = dmgroupjmsprofile.id)";
$result = Query($dmGroupQuery);
while($row = DBGetRow($result)) {
	$data[$row[0]] = $row;
}

$carrierRateModels = array();
$lcrConnection = DBConnect($SETTINGS['lcrdb']['host'], $SETTINGS['lcrdb']['user'], $SETTINGS['lcrdb']['pass'], $SETTINGS['lcrdb']['db']);
$result = Query("select * from carrierratemodel",$lcrConnection);
while($row = DBGetRow($result,true)) {
	$carrierRateModels[$row["id"]] = $row;
}

// Add field titles, leading # means it is sortable leading @ means it is hidden by default
$titles = array(0 => "#DM Group ID");
$titles[1] = "#Name";
$titles[5] = "@#Type";
$titles[2] = "#Rate Model";
$titles[4] = "#Jms Profile";
$titles[6] = "#Route Type";
$titles[7] = "#Notes";
$titles["actions"] = "Actions";

// Set hidden prefix from sticky session variable
setStickyColumns($titles,"systemdmgroups");

// Do not provide a checkbox to hide these columns.
$lockedTitles = array(0, "actions");

$filterTitles = array();

$formatters = array("actions" => "fmt_DMActions",
					4 => "fmt_dmgroupjmsprofile",
					5 => "fmt_dmgroupdispatchtype",
					2 => "fmt_dmgroupratemodel",
					6 => "fmt_routetype");

$filterFormatters = array();

/////////////////////////////
// Display
/////////////////////////////
$TITLE = _L("DM&nbsp;groups");
$PAGE = "dm:systemdmgroups";

include_once("nav.inc.php");

startWindow(_L('DM Groups'));

?>
<div class="feed_btn_wrap cf"><?= icon_button("Add Dm Group", "add",null,"editdmgroup.php?dmgroupid=new")   ?></div>
<?

if (count($data)) {
	?>
	<table>
	<tr width="100%">
	<td valign="top" align="left"><?
	// Show the column data hide/select check boxes.
	show_column_selector('dm_group_table', $titles, $lockedTitles,"systemdmgroups");
	?>
	</table>
	<table class="list sortable" id="dm_group_table">
	<?
		showTable($data, $titles, $formatters);
	?>
	</table>
	<script type="text/javascript">
		document.observe('dom:loaded', function() {
			// Append id to make javascript filter work
			var table = $('dm_group_table');
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
