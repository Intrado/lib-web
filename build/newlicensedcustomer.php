<?
require_once("../manager/managerutils.inc.php");
require_once("../inc/utils.inc.php");

$inboundnumber  = '';
$maxphones = 3;
$maxemails = 2;
$maxsms = 1;
$retry = 5;
$surveyurl = '';
$displayname = '';
$timezone = '';
$defaultproductname = 'SchoolMessenger';
$inboundnumber = '';
$callerid = '';
$areacode = '';
$autoname = '';
$autoemail = '';
$renewaldate = '';
$callspurchased = '';
$managernote = '';
$hassms = false;
$hasportal = false;
$hassurvey = true;
$hascallback = false;
$timeslice = 450;
$customerurl = 'default';
$shard = 'shard1';
$defaultbrand = 'SchoolMessenger';
$logofile = @file_get_contents("../img/logo_small.gif");

$timezones = array(	"US/Alaska",
					"US/Aleutian",
					"US/Arizona",
					"US/Central",
					"US/East-Indiana",
					"US/Eastern",
					"US/Hawaii",
					"US/Indiana-Starke",
					"US/Michigan",
					"US/Mountain",
					"US/Pacific",
					"US/Samoa"	);

if (!$authdb = mysql_connect('localhost', $argv[1], $argv[2]))
	die("Could not connect to authserver database");

mysql_select_db('authserver',$authdb);

if (QuickQuery("SELECT COUNT(*) FROM customer", $authdb))
	die("Customer(s) already exists.");

//choose shard info based on selection
$query = "select id, dbhost, dbusername, dbpassword from shard where name = '$shard'";
if (!$shardinfo = QuickQueryRow($query, true, $authdb))
	die("Could not find shard info");
$shardid = $shardinfo['id'];
$shardhost = $shardinfo['dbhost'];
$sharduser = $shardinfo['dbusername'];
$shardpass = $shardinfo['dbpassword'];

$dbpassword = genpassword();

while (!trim($displayname)) {
	echo("Customer Display Name: ");
	$displayname = trim(fgets(STDIN));
}

while (!trim($timezone)) {
	echo("Customer Time Zone: ");
	$timezone = trim(fgets(STDIN));
	if (!in_array($timezone, $timezones)) {
		echo "\nINVALID Timezone! See list...\n";
		foreach ($timezones as $tz)
			echo "   $tz\n";
		$timezone = '';
	}

}

while (!trim($callerid)) {
	echo("Default Caller ID: ");
	$callerid = trim(fgets(STDIN));
	if (strlen($callerid) !== 10) {
		echo "\nINVALID Callerid!\n";
		$callerid = '';
	}
}

$query = "insert into customer (urlcomponent, shardid, dbpassword, enabled) values
	('" . DBSafe($customerurl, $authdb) . "','$shardid', '$dbpassword', '1')";
QuickUpdate($query, $authdb) or die("failed to insert customer into auth server");

$customerid = mysql_insert_id();

$newdbname = "c_$customerid";
QuickUpdate("update customer set dbusername = '" . $newdbname . "' where id = '" . $customerid . "'", $authdb);

$newdb = mysql_connect($shardhost, $sharduser, $shardpass)
	or die("Failed to connect to DBHost $shardhost : " . mysql_error($newdb));
QuickUpdate("create database $newdbname DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci",$newdb)
	or die ("Failed to create new DB $newdbname : " . mysql_error($newdb));
mysql_select_db($newdbname,$newdb)
	or die ("Failed to connect to DB $newdbname : " . mysql_error($newdb));

QuickUpdate("drop user '$newdbname'", $newdb); //ensure mysql credentials match our records, which it won't if create user fails because the user already exists
QuickUpdate("create user '$newdbname' identified by '$dbpassword'", $newdb);
QuickUpdate("grant select, insert, update, delete, create temporary tables, execute on $newdbname . * to '$newdbname'@'localhost'", $newdb);

$tablequeries = explode("$$$",file_get_contents("../db/customer.sql"));
$tablequeries = array_merge($tablequeries, explode("$$$",file_get_contents("../db/createtriggers.sql")));
foreach ($tablequeries as $tablequery) {
	if (trim($tablequery)) {
		$tablequery = str_replace('_$CUSTOMERID_', $customerid, $tablequery);
		Query($tablequery,$newdb)
			or die ("Failed to execute statement \n$tablequery\n\nfor $newdbname : " . mysql_error($newdb));
	}
}

createSMUserProfile($newdb);

$query = "INSERT INTO `fieldmap` (`fieldnum`, `name`, `options`) VALUES
	('f01', 'First Name', 'searchable,text,firstname'),
	('f02', 'Last Name', 'searchable,text,lastname'),
	('f03', 'Language', 'searchable,multisearch,language'),
	('c01', 'Staff ID', 'searchable,multisearch,staff')";
QuickUpdate($query, $newdb) or die( "ERROR:" . mysql_error() . " SQL:" . $query);

$query = "INSERT INTO `language` (`name`) VALUES
	('English'),
	('Spanish')";
QuickUpdate($query, $newdb) or die( "ERROR: " . mysql_error() . " SQL:" . $query);

$query = "INSERT INTO `jobtype` (`name`, `systempriority`, `info`, `issurvey`, `deleted`) VALUES
	('Emergency', 1, 'Emergencies Only', 0, 0),
	('Attendance', 2, 'Attendance', 0, 0),
	('General', 3, 'General Announcements', 0, 0),
	('Survey', 3, 'Surveys', 1, 0)";

QuickUpdate($query, $newdb) or die( "ERROR: " . mysql_error() . " SQL:" . $query);

$query = "INSERT INTO `jobtypepref` (`jobtypeid`,`type`,`sequence`,`enabled`) VALUES
	(1,'phone',0,1),
	(1,'email',0,1),
	(1,'sms',0,1),
	(2,'phone',0,1),
	(2,'email',0,1),
	(2,'sms',0,1),
	(3,'phone',0,1),
	(3,'email',0,1),
	(3,'sms',0,1),
	(4,'phone',0,1),
	(4,'email',0,1),
	(4,'sms',0,0)";

QuickUpdate($query, $newdb) or die( "ERROR: " . mysql_error() . " SQL:" . $query);

$surveyurl = $SETTINGS['feature']['customer_url_prefix'] . "/" . $customerurl . "/survey/";
$query = "INSERT INTO `setting` (`name`, `value`) VALUES
	('maxphones', '$maxphones'),
	('maxemails', '$maxemails'),
	('maxsms', '$maxsms'),
	('retry', '$retry'),
	('disablerepeat', '0'),
	('surveyurl', '" . DBSafe($surveyurl, $newdb) . "'),
	('displayname', '" . DBSafe($displayname, $newdb) . "'),
	('timezone', '" . DBSafe($timezone, $newdb) . "'),
	('callerid', '$callerid'),
	('defaultareacode', '$areacode'),
	('autoreport_replyname', ''),
	('autoreport_replyemail', ''),
	('_maxusers', 'unlimited'),
	('_hassms', '$hassms'),
	('_hasportal', '$hasportal'),
	('_hassurvey', '$hassurvey'),
	('_hascallback', '$hascallback'),
	('_timeslice', '$timeslice'),
	('loginlockoutattempts', '5'),
	('logindisableattempts', '0'),
	('loginlockouttime', '5'),
	('_brandtheme', '3dblue'),
	('_brandprimary', '26477D'),
	('_brandtheme1', '89A3CE'),
	('_brandtheme2', '89A3CE'),
	('_brandratio', '.3'),
	('_logoclickurl', 'http://'),
	('_supportemail', 'support@schoolmessenger.com'),
	('_supportphone', '8009203897'),
	('emaildomain', ''),
	('_dmmethod', 'cs'),
	('_productname', '" . DBSafe($defaultproductname, $newdb) ."')";

QuickUpdate($query, $newdb) or die( "ERROR: " . mysql_error() . " SQL:" . $query);

$query = "INSERT INTO `ttsvoice` (`language`, `gender`) VALUES
	('english', 'male'),
	('english', 'female'),
	('spanish', 'male'),
	('spanish', 'female')";
QuickUpdate($query, $newdb) or die( "ERROR: " . mysql_error() . " SQL: " . $query);

// Brand/LOGO Info

if($logofile && $defaultbrand != "Other"){
	$query = "INSERT INTO `content` (`contenttype`, `data`) VALUES
				('image/gif', '" . base64_encode($logofile) . "');";
	QuickUpdate($query, $newdb) or die( "ERROR: " . mysql_error() . " SQL: " . $query);
	$logoid = mysql_insert_id();

	$query = "INSERT INTO `setting` (`name`, `value`) VALUES
				('_logocontentid', '" . $logoid . "')";
	QuickUpdate($query, $newdb) or die( "ERROR: " . mysql_error() . " SQL: " . $query);
}

QuickUpdate("INSERT INTO content (contenttype, data) values
			('image/gif', '" . base64_encode(file_get_contents("../img/classroom_girl.jpg")) . "')",$newdb);
$loginpicturecontentid = mysql_insert_id($newdb);

$query = "INSERT INTO `setting` (`name`, `value`) VALUES
			('_loginpicturecontentid', '" . $loginpicturecontentid . "')";
QuickUpdate($query, $newdb) or die( "ERROR: " . mysql_error() . " SQL: " . $query);

?>