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

if (isset($SETTINGS['lcrdb'])) {
	$lcrdbcon = DBConnect($SETTINGS['lcrdb']['host'], $SETTINGS['lcrdb']['user'], $SETTINGS['lcrdb']['pass'], $SETTINGS['lcrdb']['db']);

	if (isset($_GET['delete'])) {
		QuickQuery("delete from carrierratemodelblock where id = ?", $lcrdbcon, array($_GET['delete']));
		notice("Pattern deleted.");
	}

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////
	$carrierRateModels = QuickQueryMultiRow("select id, name  from carrierratemodel", true, $lcrdbcon);

	$carrierRateModelBlocks = QuickQueryMultiRow("
		select 
			crmb.id as id,
			crmb.carrierRateModelClassname as carrierRateModelClassname,
			crmb.createdate as createdate,
			crmb.notes as notes,
			crmb.pattern as pattern
		from
			carrierratemodelblock crmb
		order by
			crmb.carrierRateModelClassname,
			crmb.pattern asc
	", true, $lcrdbcon);
} else {
	$carrierRateModels = $carrierRateModelBlocks = array();
}

$titles = array(
	"id" => "Block ID",
	"carrierRateModelClassname" => "Rate Model Name",
	"pattern" => "Block Pattern",
	"createdate" => "Date Added",
	"notes" => "Notes",
	"actions" => "Actions"
);

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
$PAGE = 'dm:dmblocking';

include("nav.inc.php");

startWindow($TITLE);

?>
<div class="feed_btn_wrap cf"><?= icon_button("Add Dm Group Block", "add",null,"editdmgroupblock.php?crmbid=new")   ?></div>
<?

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
