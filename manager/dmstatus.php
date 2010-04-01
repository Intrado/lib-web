<?

include_once("common.inc.php");
include_once("../inc/form.inc.php");
include_once("../inc/table.inc.php");
include_once("../obj/Phone.obj.php");
include_once("../inc/html.inc.php");
$dmType = '';

if (!$MANAGERUSER->authorized("editdm") && !$MANAGERUSER->authorized("systemdm"))
	exit("Not Authorized");

if(isset($_GET['dmid'])){
	$dmid = $_GET['dmid']+0;
	$dmType = QuickQuery("select type from dm where id = " . $dmid);
	if(!QuickQuery("select count(*) from dm where id = " . $dmid) || 
			!(($MANAGERUSER->authorized("editdm") && $dmType == "customer") ||
			($MANAGERUSER->authorized("systemdm") && $dmType == "system"))){
		echo "Invalid DM, or not authorized to edit this DM.";
		exit();
	}
	$_SESSION['dmid'] = $dmid;
	redirect();
} else {
	$dmid = $_SESSION['dmid'];
	$dmType = QuickQuery("select type from dm where id = ?", false, array($dmid));
	$dmName = QuickQuery("select name from dm where id = ?", false, array($dmid));
}

if ($dmType == "customer") {
	// customer SmartCall Appliance
	
	$custid = QuickQuery("select customerid from dm where id = ?", false, array($dmid));
	$custinfo = QuickQueryRow("select s.dbhost, c.dbusername, c.dbpassword from customer c inner join shard s on (c.shardid = s.id) where c.id = '$custid'");
	$custdb = DBConnect($custinfo[0], $custinfo[1], $custinfo[2], "c_$custid");
	if (!$custdb) {
		exit("Connection failed for customer: $custinfo[0], db: c_$custid");
	}
	$jsonstats = QuickQuery("select poststatus from custdm where dmid=?", $custdb, array($dmid));
	
} else {
	// system DM
	
	$jsonstats = QuickQuery("select poststatus from dm where id=?", false, array($dmid));
}


include_once("../dmstatusdata.inc.php");


//////////////////////////////////////////////////////////////////////
// DISPLAY

include_once("nav.inc.php");

?>
<script type='text/javascript' src='../script/dmstatus.js'></script>
<?

echo "Current Status for: " . $dmName . "<BR>";

if ($status == NULL) {
	echo "There is no status available at this time.";
} else {
?>
<table width="100%"><tr><td valign="top">
<?
echo "<BR><b>SYSTEM STATISTICS</b><BR><BR>";
//startWindow("System Statistics");
echo "<div id='systemstats'>";
	foreach($statusgroups as $groupname => $groupdata) {
		showStatusGroup($groupname, $groupdata);
	}
echo "</div>";
//endWindow();
?>
</td><td valign="top">
<?
//startWindow("Active Resources");
echo "<BR><b>ACTIVE RESOURCES</b><BR><BR>";
echo "<div id='activeresources'></div>";
//endWindow();

//startWindow("Completed Resources");
echo "<BR><b>COMPLETED RESOURCES</b><BR><BR>";
echo "<div id='completedresources'></div>";
//endWindow();
?>
</td></tr></table>
<?
} // end else status is not null

include_once("navbottom.inc.php");
?>
