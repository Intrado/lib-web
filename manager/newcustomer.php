<?
include_once("common.inc.php");




if ($_POST['submit']) {


	if (!$_POST['name']) exit ("missing name");
	if (!$_POST['hostname']) exit ("missing url path");
	if ($_POST['code'] != "joplin555") exit("bad secret code");
	if ($_POST['password'] != $_POST['password']) exit("passwords do not match");
	if (strlen($_POST['inboundnumber']) > 0 && !ereg("[0-9]{10}",$_POST['inboundnumber'])) exit ("bad 800 number format");

	$query = "insert into customer (name,enabled,timezone,hostname,inboundnumber) values ('$_POST[name]',1,'$_POST[timezone]','$_POST[hostname]','$_POST[inboundnumber]')";
	QuickUpdate($query) or die( "ERROR:" . mysql_error() . " SQL:" . $query);
	$custid = mysql_insert_id();

	$query = "insert into access (name,customerid) values ('System Administrators',$custid)";
	QuickUpdate($query) or die( "ERROR:" . mysql_error());
	$accessid = mysql_insert_id();

	$query = "INSERT INTO `permission` (accessid,name,value) VALUES "
			. "($accessid, 'loginweb', '1'),"
			. "($accessid, 'manageprofile', '1'),"
			. "($accessid, 'manageaccount', '1'),"
			. "($accessid, 'manageaccount', '1'),"
			. "($accessid, 'managesystem', '1'),"
			. "($accessid, 'loginphone', '1'),"
			. "($accessid, 'startstats', '1'),"
			. "($accessid, 'startshort', '1'),"
			. "($accessid, 'starteasy', '1'),"
			. "($accessid, 'sendprint', '0'),"
			. "($accessid, 'callmax', '10'),"
			. "($accessid, 'sendemail', '1'),"
			. "($accessid, 'sendphone', '1'),"
			. "($accessid, 'sendmulti', '1'),"
			. "($accessid, 'createlist', '1'),"
			. "($accessid, 'createrepeat', '1'),"
			. "($accessid, 'createreport', '1'),"
			. "($accessid, 'datafields', 'f01|f02|f03'),"
			. "($accessid, 'maxjobdays', '7'),"
			. "($accessid, 'viewsystemreports', '1'),"
			. "($accessid, 'managesystemjobs', '1'),"
			. "($accessid, 'managemyaccount', '1'),"
			. "($accessid, 'viewcontacts', '1'),"
			. "($accessid, 'viewsystemactive', '1'),"
			. "($accessid, 'viewsystemrepeating', '1'),"
			. "($accessid, 'viewsystemcompleted', '1'),"
			. "($accessid, 'listuploadids', '1'),"
			. "($accessid, 'listuploadcontacts', '1'),"
			. "($accessid, 'setcallerid', '1'),"
			. "($accessid, 'blocknumbers', '1'),"
			. "($accessid, 'callblockingperms', 'editall'),"
			. "($accessid, 'metadata', '1'),"
			. "($accessid, 'managetasks', '1');"
		;
	QuickUpdate($query) or die( "ERROR:" . mysql_error() . " SQL:" . $query);

	$query = "INSERT INTO `user` (`accessid`, `login`, `password`, `customerid`, `firstname`, `lastname`, `enabled`, `deleted`) VALUES
		($accessid, '$_POST[user]', password('$_POST[password]'), $custid, 'System', 'Administrator', 1 ,0)";
	QuickUpdate($query) or die( "ERROR:" . mysql_error() . " SQL:" . $query);

	$query = "INSERT INTO `fieldmap` (`customerid`, `fieldnum`, `name`, `options`) VALUES
		($custid, 'f01', 'First Name', 'searchable,text'),
		($custid, 'f02', 'Last Name', 'searchable,text'),
		($custid, 'f03', 'Language', 'searchable,multisearch');
		";
	QuickUpdate($query) or die( "ERROR:" . mysql_error() . " SQL:" . $query);

	$query = "
		INSERT INTO `language` (`customerid`, `name`, `code`) VALUES
		($custid, 'English', ''),($custid, 'Spanish', '');
		";
	QuickUpdate($query) or die( "ERROR:" . mysql_error() . " SQL:" . $query);


	$query = "INSERT INTO `jobtype` (`customerid`, `name`, `priority`, `systempriority`, `deleted`) VALUES
			($custid, 'Emergency', 10000, 1, 0),
			($custid, 'Attendance', 20000, 2, 0),
			($custid, 'General', 30000, 3, 0)
		";
	QuickUpdate($query) or die( "ERROR:" . mysql_error() . " SQL:" . $query);


	exit("All done!");

} else {

function genpassword() {
	$digits = 6;
	$passwd = "";
	$chars = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyzabcdefghijklmnopqrstuvwxyz";
	while ($digits--) {
		$passwd .= $chars[mt_rand(0,strlen($chars)-1)];
	}
	return $passwd;
}

$password = genpassword();

?>


<html>
<body>
<form method="POST">
<table>
<tr><td>Customer display name: </td><td> <input name="name" type="text"></td></tr>
<tr><td>URL path name: </td><td><input name="hostname" type="text"> (should be >= 5 characters, try not to use acronyms exclusively)</td></tr>
<tr><td>800 inbound number: </td><td><input name="inboundnumber" type="text" maxlength="10"> (be sure this isn't used already!) </td></tr>
<tr><td>Admin username: </td><td><input name="user" type="text" value="admin"> (admin is ok)</td></tr>
<tr><td>Admin password: </td><td><input name="password" type="text" value="<?= $password ?>"> (generated automatically, be sure to write this down)</td></tr>
<tr><td>Password verify:</td><td><input name="password2" type="text" value="<?= $password ?>"></td></tr>

<tr><td>Reliance secret code: </td><td><input name="code" type="password"></td></tr>
<tr><td>Timezone: </td><td><select name="timezone">
<option value="US/Alaska">US/Alaska</option>
<option value="US/Aleutian">US/Aleutian</option>
<option value="US/Arizona">US/Arizona</option>
<option value="US/Central">US/Central</option>
<option value="US/East-Indiana">US/East-Indiana</option>
<option value="US/Eastern">US/Eastern</option>
<option value="US/Hawaii">US/Hawaii</option>
<option value="US/Indiana-Starke">US/Indiana-Starke</option>
<option value="US/Michigan">US/Michigan</option>
<option value="US/Mountain">US/Mountain</option>
<option value="US/Pacific">US/Pacific</option>
<option value="US/Samoa">US/Samoa</option>
</select></td></tr>

<tr><td colspan=2><input name="submit" type="submit"></td></tr>
</table>

</form>
</body>
</html>
<?
}
?>

