<?
// redialer.schedulemanager executes this to populate job.thesql field


require_once("../inc/db.inc.php");
require_once("../inc/DBMappedObject.php");
require_once("../inc/DBRelationMap.php");
require_once("../inc/utils.inc.php");
require_once("../inc/date.inc.php");
require_once("../obj/User.obj.php");
require_once("../obj/Access.obj.php");
require_once("../obj/Permission.obj.php");
require_once("../obj/Job.obj.php");
require_once("../obj/JobLanguage.obj.php");
require_once("../obj/JobType.obj.php");
require_once("../obj/Rule.obj.php");
require_once("../obj/FieldMap.obj.php");
require_once("../obj/SurveyQuestionnaire.obj.php");


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
$_dbcon = mysql_connect($db['host'], $db['user'], $db['pass']);
if (!$_dbcon) {
	echo("Problem connecting to MySQL server at " . $db['host'] . " error:" . mysql_error() . "\n");
	exit(-1);
}
if (!mysql_select_db($db['db'])) {
	echo("Problem selecting database for " . $db['host'] . " error:" . mysql_error() . "\n");
	exit(-1);
}

if (!mysql_set_charset("utf8",$db['db'])) {
	echo("Problem selecting charset for " . $db['host'] . " error:" . mysql_error() . "\n");
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


$job = new Job($jobid);
$job->generateSql();

echo "new sql is: " . $job->thesql . "\n";

$job->update();

echo "job updated, assume success \n";

exit(0); // success
?>