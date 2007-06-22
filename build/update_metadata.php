<?
if ($argc < 2)
	exit ("Please specify customerid");
	
$customerid = $argv[1];

$authhost="localhost:3306";
$authuser="root";
$authpass="";
$authdb="authserver";

$auth = mysql_connect($authhost, $authuser, $authpass)
			or die("Could not connect to auth: " . mysql_error($authdb));
mysql_select_db($authdb, $auth);

$query = "select c.id, s.dbhost, s.dbusername, s.dbpassword from shard s inner join customer c on (c.shardid = s.id) where c.id = '$customerid'";
$res = mysql_query($query, $auth);
$customer = mysql_fetch_row($res);

$custdb = mysql_connect($customer[1], $customer[2], $customer[3])
			or die("Could not connect to customer: " . mysql_error($custdb));
mysql_select_db("c_$customer[0]", $custdb)
			or die("Could not select customer db: " . mysql_error($custdb));
			
			
$query = "update fieldmap fm set fm.options=concat(fm.options, ',firstname') where fm.name = 'First Name'";
mysql_query($query, $custdb);
$query = "update fieldmap fm set fm.options=concat(fm.options, ',lastname') where fm.name = 'Last Name'";
mysql_query($query, $custdb);
$query = "update fieldmap fm set fm.options=concat(fm.options, ',grade') where fm.name = 'Grade'";
mysql_query($query, $custdb);
$query = "update fieldmap fm set fm.options=concat(fm.options, ',school') where fm.name = 'School'";
mysql_query($query, $custdb);
$query = "update fieldmap fm set fm.options=concat(fm.options, ',language') where fm.name = 'Language'";
mysql_query($query, $custdb);



?>