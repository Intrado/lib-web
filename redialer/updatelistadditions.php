<?
// CSAPIv2 calls this to create new list additions

setlocale(LC_ALL, 'en_US.UTF-8');
mb_internal_encoding('UTF-8');

require_once("../inc/db.inc.php");
require_once("../inc/utils.inc.php");
require_once("../inc/memcache.inc.php");
require_once("../inc/DBMappedObject.php");
require_once("../inc/DBRelationMap.php");
require_once("../inc/date.inc.php");
require_once("../obj/User.obj.php");
require_once("../obj/Access.obj.php");
require_once("../obj/Permission.obj.php");
require_once("../obj/Rule.obj.php");
require_once("../obj/FieldMap.obj.php");
require_once("../obj/Organization.obj.php");
require_once("../obj/PeopleList.obj.php");
require_once("../obj/Person.obj.php");


$custid = $argv[1] +0;
if ($custid == 0) {
	echo "missing customer id, Usage: updatelistadditions customerid listid doReplace dbhost:port dbname dbuser dbpass \nStdin array of pkeys\n";
	exit(-1);
}

$listid = $argv[2] +0;
if ($listid == 0) {
	echo "missing list id, Usage: updatelistadditions customerid listid doReplace dbhost:port dbname dbuser dbpass \nStdin array of pkeys\n";
	exit(-1);
}

$doReplace = $argv[3];

// read pkeys from stdin, could be 100k or more
//$pkeys = explode(",", trim(fgets(STDIN)));

$pkeys = array();
while (FALSE !== ($line = trim(fgets(STDIN)))) {
//echo "got a line " . $line . "\n";
	if (strlen($line) == 0) break;
	$pkeys[] = $line;
}

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

if (!QuickQuery("select count(*) from list where id=".$listid)) {
	echo("Error: list not found\n");
	exit(-1);
}

$timezone = QuickQuery("select value from setting where name='timezone'");
if($timezone){
	@date_default_timezone_set($timezone);
	QuickUpdate("set time_zone='" . $timezone . "'");
}

$list = new PeopleList($listid);

$USER = new User($list->userid);

// add the people
if ($doReplace)
	$numpeople = $list->updateManualAddByPkeys($pkeys);
else
	$numpeople = $list->createManualAddByPkeys($pkeys);
	
$result['numpeople'] = $numpeople + 0;
// success if all people added, else warning
if ($numpeople == count($pkeys))
	$result["resultcode"] = "success";
else {
	$result["resultcode"] = "warning"; // somple people skipped, not added to list
	$result["resultdescription"] = "Some people may have been skipped.";
}

echo json_encode($result);

exit(0); // success
?>
