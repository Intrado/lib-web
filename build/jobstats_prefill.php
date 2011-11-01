<? 
// MUST RUN BEFORE ASP_8-1 upgrade
// Prefill customer jobstats table with best guess from reportcontact data, also fill job.activedate

$authhost = "127.0.0.1";
$authuser = "root";
$authpass = "asp123";


$usage = "
Description:
This script will compute jobstats for customers or entire shards databases
Usage:
php jobstats_prefill.php -a 
php jobstats_prefill.php -c <customerid> [<customerid> ...] 
php jobstats_prefill.php -s <shardid> [<shardid> ...] 

-a : run on everything
-s : shard mode, specific shards
-c : customer mode, specific customers
";

$opts = array();
$mode = false;
$ids = array();
array_shift($argv); //ignore this script
foreach ($argv as $arg) {
	if ($arg[0] == "-") {
		for ($x = 1; $x < strlen($arg); $x++) {
			switch ($arg[$x]) {
				case "a":
					$mode = "all";
					break;
				case "s":
					$mode = "shard";
					break;
				case "c":
					$mode = "customer";
					break;
				default:
					echo "Unknown option " . $arg[$x] . "\n";
				exit($usage);
			}
		}
	} else {
		$ids[] = $arg + 0;
	}
}

if (!$mode)
exit("No mode specified\n$usage");
if ($mode != "csimport" && $mode != "all" && count($ids) == 0)
exit("No IDs specified\n$usage");


$SETTINGS = parse_ini_file("../inc/settings.ini.php",true);

require_once("../inc/db.inc.php");
require_once("../inc/DBMappedObject.php");
require_once("../inc/DBRelationMap.php");
require_once("../inc/utils.inc.php");


date_default_timezone_set("America/Los_Angeles");

$updater = mt_rand();
echo "Updater id: $updater\n";

	//connect to authserver db
	echo "connecting to authserver db\n";
	$authdb = DBConnect($authhost,$authuser,$authpass,"authserver");

	$res = Query("select id,dbhost,dbusername,dbpassword from shard", $authdb)
	or exit(mysql_error());
	$shards = array();
	while ($row = DBGetRow($res, true))
	$shards[$row['id']] = DBConnect($row['dbhost'],$row['dbusername'],$row['dbpassword'], "aspshard")
	or exit(mysql_error());

	switch ($mode) {
		case "all": $optsql = ""; break;
		case "customer": $optsql = "and id in (" . implode(",",$ids) . ")"; break;
		case "shard": $optsql = "and shardid in (" . implode(",",$ids) . ")"; break;
	}

	$res = Query("select id, shardid, urlcomponent, dbusername, dbpassword from customer where 1 $optsql order by id", $authdb)
	or exit(mysql_error());
	$customers = array();
	while ($row = DBGetRow($res, true))
	$customers[$row['id']] = $row;

	foreach ($customers as  $customerid => $customer) {
		echo "customer $customerid - jobids ";
		$_dbcon = $db = $shards[$customer['shardid']];
		QuickUpdate("use c_$customerid",$db);
		update_customer($db, $customerid, $customer['shardid']);
	}



function updatePhonetimesForAttemptSequence($attempt, $sequence, $starttime, &$phonetimes) {
	if (!isset($phonetimes[$attempt]))
		$phonetimes[$attempt] = array();
	if (!isset($phonetimes[$attempt][$sequence]))
		$phonetimes[$attempt][$sequence] = array();
	if (!isset($phonetimes[$attempt][$sequence]['min']) || $starttime < $phonetimes[$attempt][$sequence]['min'])
		$phonetimes[$attempt][$sequence]['min'] = $starttime;
	if (!isset($phonetimes[$attempt][$sequence]['max']) || $starttime > $phonetimes[$attempt][$sequence]['max'])
		$phonetimes[$attempt][$sequence]['max'] = $starttime;
}
	
function update_customer($db, $customerid, $shardid) {
	global $updater;
	
	//only allow one instance of the updater per customer to run at a time
	//try to insert our updater code, it should either error out due to duplicate key, or return 1
	//indicating 1 row was modified.
	if (!QuickUpdate("insert into setting (name,value) values ('_dbjobstats_inprogress','$updater')",$db)) {
		echo "an upgrade is already in process, skipping\n";
		return;
	}
	
	$maxphones = QuickQuery("select value from setting where name = 'maxphones'");
	$maxemails = QuickQuery("select value from setting where name = 'maxemails'");
	$maxsms = QuickQuery("select value from setting where name = 'maxsms'");
	
	$loopcount = 1;
	//while ($loopcount <= 100) {
	while (true) {
		$loopcount++;
		// only backfill completed jobs, too complex to worry about active or cancelled
		$jobid = QuickQuery("select id from job where status = 'complete' and activedate is null limit 1");
		if (!$jobid)
			break;
		
		echo $jobid . ", ";
		
		$jobstats = array(); // key=name, value=value to fill jobstats table with jobid
		
		// for each job, single transaction
		Query("begin",$db);
	
		///////////////////
		//phone
		$maxcallattempts = QuickQuery("select value from jobsetting where name = 'maxcallattempts' and jobid = ?", null, array($jobid));
		
		$phonetimes = array(); // [attempt 0, 1, 2, ][sequence 0,1,2. ]['min'|'max'] = starttime
		
		$phoneattemptdata = QuickQueryMultiRow("select personid, sequence, attemptdata from reportcontact where jobid = ? and type = 'phone' and attemptdata is not null group by personid order by sequence", true, null, array($jobid));
		$workingPersonid = 0; // current personid working through their sequences
		$workingSequence = 0;
		foreach ($phoneattemptdata as $row) {
			if ($workingPersonid != $row['personid']) {
				$workingPersonid  = $row['personid'];
				$workingSequence = 0;
			} else {
				$workingSequence++;
			}
			for ($workingAttempt = 0; $workingAttempt < $maxcallattempts; $workingAttempt++) {
				$starttime = substr($row['attemptdata'], ($workingAttempt * 16), 13); // 16 chars per attempt, 13 chars length of starttime
				if (!$starttime)
					break; // no more attempts for this sequence
				updatePhonetimesForAttemptSequence($workingAttempt, $workingSequence, $starttime, $phonetimes);
			}
		}
		
		if (isset($phonetimes[0][0]['min']))
			$minstartphone = $phonetimes[0][0]['min']; // starting time of attempt=0, sequence=0
		else
			$minstartphone = null;
		
		foreach ($phonetimes as $attempt => $seqarray) {
			foreach ($seqarray as $seq => $minmaxarray) {
				$duration = ($minmaxarray['max'] - $minstartphone) / 1000;
				$jobstats["complete-seconds-phone-attempt-".$attempt."-sequence-".$seq] = $duration;
			}
		}
		
		
		
		///////////////////
		// email
		$minmax = QuickQueryRow("select min(starttime), max(starttime) from reportcontact where jobid = ? and type = 'email' and starttime is not null", false, null, array($jobid));
		$minstartemail = $minmax[0];
		$maxstartemail = $minmax[1];
	
		// if there is a min and max, then there was a complete first pass
		if ($minstartemail && $maxstartemail) {
			$duration = ($maxstartemail - $minstartemail) / 1000;
			for ($i=0; $i < $maxemails; $i++) {
				$jobstats["complete-seconds-email-attempt-0-sequence-" . $i] = $duration;
			}
		}
	
		////////////////
		// sms
		$minmax = QuickQueryRow("select min(starttime), max(starttime) from reportcontact where jobid = ? and type = 'sms' and starttime is not null", false, null, array($jobid));
		$minstartsms = $minmax[0];
		$maxstartsms = $minmax[1];
	
		// if there is a min and max, then there was a complete first pass
		if ($minstartsms && $maxstartsms) {
			$duration = ($maxstartsms - $minstartsms) / 1000;
			for ($i=0; $i < $maxsms; $i++) {
				$jobstats["complete-seconds-sms-attempt-0-sequence-" . $i] = $duration;
			}
		}
	
		/////////////////
		// activedate
	
		// create array of possible activedate, do not include null
		$a = array();
		if ($minstartphone != null)
			$a[] = $minstartphone;
		if ($minstartemail != null)
			$a[] = $minstartemail;
		if ($minstartsms != null)
			$a[] = $minstartsms;
	
		if (count($a) == 0) {
			// no starttime data, set to job startdate/time
			if (!QuickUpdate("update job set activedate = timestamp(startdate, starttime) where id = ?", null, array($jobid))) {
				echo "\nFailed to update job.activedate\n";
				return;
			}
		} else {
			$activedate = min($a) / 1000; // convert millis to secs
			// TODO timezone	
			if (!QuickUpdate("update job set activedate = from_unixtime(?) where id = ?", null, array($activedate, $jobid))) {
				echo "\nFailed to update job.activedate\n";
				return;
			}
		}
	
		// insert bulk jobstats
		$args = array();
		$query = "insert into jobstats (jobid, name, value) values ";
		foreach ($jobstats as $name => $value) {
			$query .= "(" . $jobid . ",?,?),";
			$args[] = $name;
			$args[] = $value;
		}
		$query = substr($query, 0, strlen($query)-1); // chop trailing comma
		if (count($args) > 0) {
			if (!QuickUpdate($query, null, $args)) {
				echo $query ."\nFailed to insert jobstats\n";
				return;
			}
		}
	
		// commit this job
		Query("commit",$db);
	} // end while loop
	
	// remove progress lock
	QuickUpdate("delete from setting where name='_dbjobstats_inprogress'",$db);
	
	echo "\n";
	
}

?>