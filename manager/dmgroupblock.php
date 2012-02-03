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
	QuickQuery("delete from dmgroupblock where id = ?", false, array($_GET['delete']));
	notice("Pattern deleted.");
}

if (isset($_POST['dmgroup']) && isset($_POST['newpattern'])) {
	$dmgroupid = $_POST['dmgroup'];
	$pattern = $_POST['newpattern'];
	if ($dmgroupid && $pattern) {
		QuickQuery("insert into dmgroupblock (dmgroupid, pattern) values (?,?)", false, array($dmgroupid, $pattern));
		notice($pattern . " has been inserted.");
	} else {
		notice("Couldn't insert pattern.");
	}
	
}
////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

$dmgroups = QuickQueryMultiRow("select id, carrier, state from dmgroup", true, false);

$dmgroupblocks = QuickQueryMultiRow("
	select dmg.id as dmgid, dmg.carrier as dmgcarrier, dmg.state dmgstate, dmgb.id as patternid, dmgb.pattern as pattern
	from dmgroup dmg
	inner join dmgroupblock dmgb on (dmgb.dmgroupid = dmg.id)
	order by dmg.id",true,false);

$titles = array("dmgid" => "DM Group ID",
				"dmgcarrier" => "Carrier",
				"dmgstate" => "State",
				"pattern" => "Block Pattern",
				"actions" => "Actions");

$formatters = array("actions" => "fmt_dmgroupblock_actions");

////////////////////////////////////////////////////////////////////////////////
// Functions
////////////////////////////////////////////////////////////////////////////////

//index 5 is status
function fmt_dmgroupblock_actions($row, $index){
	return action_links(action_link("Un-Block", "lock_open","dmgroupblock.php?delete=" . $row['patternid']));
}

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
include("nav.inc.php");

?>
<form action="dmgroupblock.php" method="post">
<div style="border:1px solid black;padding:3px;float:left;">
	Block a new pattern?<br>
	<div style="float:left">
		<select id="dmgroup" name="dmgroup">
			<option value="0">--Select One--</option>
		<?
		foreach ($dmgroups as $dmgroup) {
			echo '<option value="'.$dmgroup['id'].'">'.escapehtml($dmgroup['carrier']. " - ". $dmgroup['state']).'</option>';
		}?>
		</select>
		<input type=text id="newpattern" name="newpattern" />
	</div>
	<?=submit_button("Add", "submit", "add")?>
	<div style="clear:both;"></div>
</div>
<div style="clear:both;"></div>
</form>
<table class=list>
<?
showTable($dmgroupblocks, $titles, $formatters);
?>
</table>
<?
include("navbottom.inc.php");
?>