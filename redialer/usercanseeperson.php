<?
// Call PHP User.canSeePerson(personId) from Java for CS API
// returns {"canSee":"1"} 1 is true, 0 is false

setlocale(LC_ALL, 'en_US.UTF-8');
mb_internal_encoding('UTF-8');

require_once("../inc/db.inc.php");
require_once("../inc/utils.inc.php");
require_once("../inc/memcache.inc.php");
require_once("../inc/DBMappedObject.php");
require_once("../inc/DBMappedObjectHelpers.php");
require_once("../inc/DBRelationMap.php");
require_once("../inc/date.inc.php");
require_once("../obj/Person.obj.php");
require_once("../obj/User.obj.php");
require_once("../obj/Access.obj.php");
require_once("../obj/Permission.obj.php");
require_once("../obj/Rule.obj.php");
require_once("../obj/FieldMap.obj.php");
require_once("../obj/Organization.obj.php");
require_once("../obj/Section.obj.php");


$custid = $argv[1] +0;
if ($custid == 0) {
	echo "missing customer id, Usage: usercanseeperson customerid userid personid dbhost:port dbname dbuser dbpass \n";
	exit(-1);
}

$userid = $argv[2] +0;
if ($userid == 0) {
	echo "missing user id, Usage: usercanseeperson customerid userid personid dbhost:port dbname dbuser dbpass \n";
	exit(-1);
}

$personid = $argv[3] +0;
if ($personid == 0) {
	echo "missing person id, Usage: usercanseeperson customerid userid personid dbhost:port dbname dbuser dbpass \n";
	exit(-1);
}

//echo "usercanseeperson for customerid=$custid userid=$userid personid=$personid \n";

// gather database connection info
$db['host'] = $argv[4];
$db['db'] = $argv[5];
$db['user'] = $argv[6];
@ $db['pass'] = $argv[7]; // password is last argument in case it is blank


// 	now connect to the customer database
global $_dbcon;
$_dbcon = DBConnect($db['host'], $db['user'], $db['pass'], $db['db']);
if (!$_dbcon) {
	echo("Problem connecting to MySQL server at " . $db['host'] . "\n");
	exit(-1);
}

if (!QuickQuery("select count(*) from user where id=".$userid)) {
	echo("Error: user not found\n");
	exit(-1);
}

if (!QuickQuery("select count(*) from person where id=".$personid)) {
	echo("Error: person not found\n");
	exit(-1);
}

$timezone = QuickQuery("select value from setting where name='timezone'");
if($timezone){
	@date_default_timezone_set($timezone);
	QuickUpdate("set time_zone='" . $timezone . "'");
}

$USER = new User($userid);

$canSeePerson = $USER->canSeePerson($personid);

echo json_encode(array("canSee" => ($canSeePerson) ? "1" : "0"));

exit(0); // success
?>
