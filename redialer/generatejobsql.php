<?
// redialer.schedulemanager executes this to populate job.thesql field

setlocale(LC_ALL, 'en_US.UTF-8');

require_once("../inc/db.inc.php");
require_once("../inc/utils.inc.php");
require_once("../inc/memcache.inc.php");
require_once("../inc/DBMappedObject.php");
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


$custid = $argv[1] +0;
if ($custid == 0) {
	echo "missing customer id, Usage: generatejobsql customerid jobid dbhost:port dbname dbuser dbpass \n";
	exit(-1);
}

$jobid = $argv[2] +0;
if ($jobid == 0) {
	echo "missing job id, Usage: generatejobsql customerid jobid dbhost:port dbname dbuser dbpass \n";
	exit(-1);
}

//echo "generatejobsql for customerid=".$custid." jobid=".$jobid."\n";

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

if (!QuickQuery("select count(*) from job where id=".$jobid)) {
	echo("Error: job not found\n");
	exit(-1);
}

$timezone = QuickQuery("select value from setting where name='timezone'");
if($timezone){
	@date_default_timezone_set($timezone);
	QuickUpdate("set time_zone='" . $timezone . "'");
}

$thesql = array(); // key=listid, value=thesql

// generate thesql for every list in this job
$job = new Job($jobid);

$USER = new User($job->userid);

$joblists = DBFindMany('PeopleList', "from list l inner join joblist jl on (jl.listid=l.id) where jl.jobid=$jobid","l");
foreach ($joblists as $joblist) {
	$renderedlist = new RenderedList2();
	$renderedlist->initWithList($joblist);
	$sql = $renderedlist->getPersonSql(false,false);
	$thesql[$joblist->id] = $sql;
}

if (count($thesql) == 0) {
	//echo("Error: no lists for thesql\n");
	//exit(-1);
	$thesql[-1] = "0"; // NOTE bug in json if $thesql[0] using 0 as a key
}

echo json_encode($thesql);

exit(0); // success
?>
