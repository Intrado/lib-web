<?
// redialer.schedulemanager executes this to populate job.thesql field

$SETTINGS = parse_ini_file("inc/settings.ini.php",true);
$IS_COMMSUITE = $SETTINGS['feature']['is_commsuite'];

require_once("inc/db.inc.php");
require_once("inc/DBMappedObject.php");
require_once("inc/DBRelationMap.php");
require_once("inc/utils.inc.php");
require_once("inc/date.inc.php");
require_once("obj/User.obj.php");
require_once("obj/Access.obj.php");
require_once("obj/Permission.obj.php");
require_once("obj/Job.obj.php");
require_once("obj/JobLanguage.obj.php");
require_once("obj/JobType.obj.php");
require_once("obj/Rule.obj.php");
require_once("obj/FieldMap.obj.php");
require_once("obj/SurveyQuestionnaire.obj.php");


$custid = $argv[1] +0;
if ($custid == 0) {
	echo "missing customer id, Usage: generatejobsql customerid jobid \n";
	exit(-1);
}

$jobid = $argv[2] +0;
if ($jobid == 0) {
	echo "missing job id, Usage: generatejobsql customerid jobid \n";
	exit(-1);
}

echo "generatejobsql for customerid=".$custid." jobid=".$jobid."\n";

$db['host'] = $SETTINGS['redialer']['host'];
$db['user'] = $SETTINGS['redialer']['user'];
$db['pass'] = $SETTINGS['redialer']['pass'];
if ($IS_COMMSUITE) {
	$db['db'] = $SETTINGS['redialer']['db'];
	/*CSDELETEMARKER_START*/
} else {
	$db['db'] = "c_".$custid;
	/*CSDELETEMARKER_END*/
}


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

if (!QuickQuery("select count(*) from job where id=".$jobid)) {
	echo("Error: job not found\n");
	exit(-1);
}

$job = new Job($jobid);
$job->generateSql();

echo "new sql is: " . $job->thesql . "\n";

$job->update();
?>