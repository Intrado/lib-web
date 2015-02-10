<?
// redialer.schedulemanager executes this to gather the list of recipients

setlocale(LC_ALL, 'en_US.UTF-8');
mb_internal_encoding('UTF-8');

require_once("../inc/db.inc.php");
require_once("../inc/utils.inc.php");
require_once("../inc/memcache.inc.php");
require_once("../inc/DBMappedObject.php");
require_once("../inc/DBMappedObjectHelpers.php");
require_once("../inc/DBRelationMap.php");
require_once("../inc/date.inc.php");
require_once("../obj/User.obj.php");
require_once("../obj/Access.obj.php");
require_once("../obj/Permission.obj.php");
require_once("../obj/Job.obj.php");
require_once("../obj/JobType.obj.php");
require_once("../obj/Rule.obj.php");
require_once("../obj/FieldMap.obj.php");
require_once("../obj/SurveyQuestionnaire.obj.php");
require_once("../obj/Organization.obj.php");
require_once("../obj/Section.obj.php");
require_once("../obj/PeopleList.obj.php");
require_once("../obj/RenderedList.obj.php");
require_once("../obj/RenderedRecipient.obj.php");


$custid = $argv[1] +0;
if ($custid == 0) {
	echo "missing customer id, Usage: generaterecipientlist customerid listid dbhost:port dbname dbuser dbpass \n";
	exit(-1);
}

$listid = $argv[2] +0;
if ($listid == 0) {
	echo "missing list id, Usage: generaterecipientlist customerid listid dbhost:port dbname dbuser dbpass \n";
	exit(-1);
}

//echo "generaterecipientlist for customerid=".$custid." listid=".$listid."\n";

// gather database connection info
$db['host'] = $argv[3];
$db['db'] = $argv[4];
$db['user'] = $argv[5];
@ $db['pass'] = $argv[6]; // password is last argument in case it is blank


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

$recipientList = array(); // key=index, value=object(recipientpersonid, targetpersonid)

$list = new PeopleList($listid);
$USER = new User($list->userid);


$renderedlist = new RenderedList2();
$renderedlist->initWithList($list);
$recipientList = $renderedlist->getRecipientList();

echo json_encode($recipientList);

exit(0); // success
?>
