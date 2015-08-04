<?
include_once("common.inc.php");
include_once("../inc/table.inc.php");
include_once("../inc/form.inc.php");
include_once("../inc/html.inc.php");
include_once("../inc/formatters.inc.php");

if (!$MANAGERUSER->authorized("systemdm"))
	exit("Not Authorized");

////////////////////////////////////////////////////////////////////////////////
// Action Handling
////////////////////////////////////////////////////////////////////////////////

if (isset($_GET['delete'])) {
	$lcrdbcon = DBConnect($SETTINGS['lcrdb']['host'], $SETTINGS['lcrdb']['user'], $SETTINGS['lcrdb']['pass'], $SETTINGS['lcrdb']['db']);
	QuickQuery("delete from carrierratemodelblock where id = ?", $lcrdbcon, array($_GET['delete']));
	notice("Pattern deleted.");
}

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////
$lcrdbcon = DBConnect($SETTINGS['lcrdb']['host'], $SETTINGS['lcrdb']['user'], $SETTINGS['lcrdb']['pass'], $SETTINGS['lcrdb']['db']);
$carrierRateModels = QuickQueryMultiRow("select id, name  from carrierratemodel", true, $lcrdbcon);

$carrierRateModelBlocks = QuickQueryMultiRow("
	select crmb.id as id, crm.name as carrierRateModelName, crmb.createdate as createdate, crmb.notes as notes, crmb.pattern as pattern
	from carrierratemodel crm
	inner join carrierratemodelblock crmb on (crmb.carrierRateModelId = crm.id)
	order by crm.name asc",true,$lcrdbcon);

$titles = array("id" => "Block ID",
				"carrierRateModelName" => "Rate Model Name",
				"pattern" => "Block Pattern",
				"createdate" => "Date Added",
				"notes" => "Notes",
				"actions" => "Actions");

$formatters = array("actions" => "fmt_dmgroupblock_actions");

////////////////////////////////////////////////////////////////////////////////
// Functions
////////////////////////////////////////////////////////////////////////////////

function fmt_dmgroupblock_actions($row, $index){
	$actions = array();
	$actions[] = action_link("Edit", "pencil","editdmgroupblock.php?crmbid=" . $row['id']);
	$actions[] = action_link("Un-Block", "lock_open","dmgroupblock.php?delete=" . $row['id']);
	return action_links($actions);
}

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$TITLE = _L('DM Group Blocking');
$PAGE = 'commsuite:dmblocking';

include("nav.inc.php");

startWindow($TITLE);

?>
<table class=list>
<?
showTable($carrierRateModelBlocks, $titles, $formatters);
?>
</table>
<?

endWindow();

include("navbottom.inc.php");
?>
