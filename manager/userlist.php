<?
include_once("common.inc.php");
include_once("../obj/User.obj.php");
include_once("../obj/Access.obj.php");
include_once("../obj/Permission.obj.php");
include_once("../inc/table.inc.php");

if (!$MANAGERUSER->authorized("users"))
	exit("Not Authorized");
	
$customerid = $_GET["customer"] + 0;

$cust = QuickQueryRow("select s.dbhost, s.dbusername, s.dbpassword, c.urlcomponent from customer c inner join shard s on (c.shardid = s.id) where c.id = '$customerid'");


////////////////////////////////////////////////////////////////////////////////
// formatters
////////////////////////////////////////////////////////////////////////////////

function fmt_lastlogin($row, $index){
	$lastlogin = strtotime($row[$index]);
	if ($lastlogin !== -1 && $lastlogin !== false && $lastlogin != "0000-00-00 00:00:00")
		$lastlogin = date("M j, Y g:i a",$lastlogin);
	else
		$lastlogin = "- Never -";

	return $lastlogin;
}

function fmt_jobcount($row, $index){
	global $custdb, $MANAGERUSER;
	$jobcount = QuickQuery("SELECT COUNT(*) FROM job WHERE job.userid = '" . $row[3] . "'
							AND job.status = 'active'", $custdb);
	$dmmethod = "system";
	if ($row[12] != 'asp')
		$dmmethod = "customer";
							
	if ($MANAGERUSER->authorized("activejobs"))
		$link = "<a href=\"customeractivejobs.php?user=" . $row['3'] . "&" . $dmmethod . "&cid=" . $row[0] . "\">" . $jobcount . "</a>";
	else
		$link = $jobcount;
	return $link;
}

function fmt_custurl($row, $index){
	global $MANAGERUSER;
	if ($MANAGERUSER->authorized("logincustomer"))
		return escapehtml($row[2]) . " (<a href='customerlink.php?id=" . $row[0] ."' target=\"_blank\">" . escapehtml($row[1]) . "</a>)";
	else
		return escapehtml($row[2] . " (" . $row[1] . ")");
}


////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$TITLE = _L('Customer User List');
$PAGE = 'commsuite:customers';

include_once("nav.inc.php");

startWindow($TITLE);

if($custdb = DBConnect($cust[0], $cust[1], $cust[2], "c_$customerid")){
	$displayname = QuickQuery("select value from setting where name = 'displayname'", $custdb);
	$dmmethod = QuickQuery("select value from setting where name = '_dmmethod'", $custdb);
	$custinfo = array($customerid, $displayname, $cust[3]);
	$result = Query("select u.id, u.login, u.firstname, u.lastname, u.lastlogin, u.phone, u.email, a.name, u.aremail from user u left join access a on (u.accessid = a.id) where enabled=1 AND deleted=0", $custdb);
	$users = array();
	while($row = DBGetRow($result)){
		$users[] = array_merge($custinfo, $row, array($dmmethod));
	}
}


$titles = array("0" => "Customer ID",
				"1" => "Customer Name",
				"url" => "Customer URl",
				"3" => "User ID",
				"4" => "User Name",
				"5" => "Last Name",
				"6" => "First Name",
				"7" => "Last Login",
				"activejobs" => "Active Jobs",
				"10" => "Access Profile",
				"8" => "Phone",
				"9" => "Email",
				"11" => "AutoReport Email");

$formatters = array("url" => "fmt_custurl",
					"7" => "fmt_lastlogin",
					"activejobs" => "fmt_jobcount");

?>
<table border=1>
<?
	showTable($users, $titles, $formatters);
?>
</table>
<?

endWindow();

include_once("navbottom.inc.php");

?>
