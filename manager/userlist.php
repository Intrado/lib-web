<?
include_once("common.inc.php");
include_once("../obj/User.obj.php");
include_once("../obj/Access.obj.php");
include_once("../obj/Permission.obj.php");
include_once("../inc/table.inc.php");

$customerid = $_GET["customer"] + 0;

$custquery = Query("select s.dbhost, s.dbusername, s.dbpassword, c.urlcomponent from customer c inner join shard s on (c.shardid = s.id) where c.id = '$customerid'");
$cust = mysql_fetch_row($custquery);

////////////////////////////////////////////////////////////////////////////////
// formatters
////////////////////////////////////////////////////////////////////////////////

function fmt_lastlogin($row, $index){
	$lastlogin = strtotime($row[$index]);
	if ($lastlogin !== -1 && $lastlogin !== false && $lastlogin != "0000-00-00 00:00:00")
		$lastlogin = date("M j, g:i a",$lastlogin);
	else
		$lastlogin = "- Never -";

	return $lastlogin;
}

function fmt_jobcount($row, $index){
	global $custdb;
	$jobcount = QuickQuery("SELECT COUNT(*) FROM job WHERE job.userid = '" . $row[2] . "'
							AND job.status = 'active'", $custdb);
	$link = "<a href=\"customeractivejobs.php?user=" . $row['2'] . "\">" . $jobcount . "</a>";
	return $link;
}

function fmt_accssname($row, $index){
	global $custdb;
	$accessname = QuickQuery("select name from access where id = '" . $row[9] ."'", $custdb);
	return $accessname;
}

function fmt_custurl($row, $index){

	$url = "<a href=\"customerlink.php?id=" . $row[0] ."\" target=\"_blank\">" . $row[2] . "</a>";
	return $url;
}


////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

include_once("nav.inc.php");

if($custdb = DBConnect($cust[0], $cust[1], $cust[2], "c_$customerid")){
	$displayname = QuickQuery("select value from setting where name = 'displayname'", $custdb);
	$custinfo = array($customerid, $displayname, $cust[3]);
	$result = Query("select id, login, firstname, lastname, lastlogin, phone, email, accessid from user where enabled=1 AND deleted=0", $custdb);
	$users = array();
	while($row = DBGetRow($result)){
		$users[] = array_merge($custinfo, $row);
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
				"access" => "Access Profile",
				"8" => "Phone",
				"9" => "Email");

$formatters = array("url" => "fmt_custurl",
					"7" => "fmt_lastlogin",
					"activejobs" => "fmt_jobcount",
					"access" => "fmt_accssname");

?>
<table border=1>
<?
	showTable($users, $titles, $formatters);
?>
</table>
<?
include_once("navbottom.inc.php");

?>