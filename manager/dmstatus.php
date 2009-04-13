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
	// customer Flex Appliance
	
	$custid = QuickQuery("select customerid from dm where id = ?", false, array($dmid));
	$custinfo = QuickQueryRow("select s.dbhost, c.dbusername, c.dbpassword from customer c inner join shard s on (c.shardid = s.id) where c.id = '$custid'");
	$custdb = DBConnect($custinfo[0], $custinfo[1], $custinfo[2], "c_$custid");
	if (!$custdb) {
		exit("Connection failed for customer: $custinfo[0], db: c_$custid");
	}
	$status = json_decode(QuickQuery("select poststatus from custdm where dmid=?", $custdb, array($dmid)));
	
} else {
	// system DM
	
	$status = json_decode(QuickQuery("select poststatus from dm where id=?", false, array($dmid)));
	//var_dump($status);
}

$systemstats = (array) $status[0];
$dispatchers = array();

foreach ($systemstats as $key => $value) {
	if (strpos($key, "comerr") === 0) {
		$dispatcher = substr($key, 7);
		$dispatchers[$dispatcher]['comerr'] = $value;
	}
	if (strpos($key, "comtimeout") === 0) {
		$dispatcher = substr($key, 11);
		$dispatchers[$dispatcher]['comtimeout'] = $value;
	}
}


$resourcedata = array();
foreach ($status as $row) {
	$row = (array) $row;
	if ($row['name'] == 'system') continue;
	$resourcedata[] = $row;
}
$resourcetitles = array(	"name" => "Name",
							"rtype" => "Type",
							"rstatus" => "State",
							"starttime" => "Start Time",
							"result" => "Result"
						);

//////////////////////////////////////////////////////////////////////
// DISPLAY

include_once("nav.inc.php");

echo "Current Status for: " . $dmName . "<BR>";

?>
<table width="100%"><tr><td>
<? include_once("../dmsysstats.inc.php"); ?>
</td><td valign="top">
<table>
<?

if (count($status) > 1) {
	showTable($resourcedata, $resourcetitles);
} else {
	echo "There are no active resources at this time.  The system is idle.<br>";
}

?>
</table>

</td></tr></table>

<?
include_once("navbottom.inc.php");
?>
