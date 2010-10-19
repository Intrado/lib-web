<?

// NOTE in 'csimport' mode these must be set to the host info with the 'csimport' database
$authhost = "127.0.0.1";
$authuser = "root";
$authpass = "";

/*
 * Starting in 7.5, we will use a customer setting "_dbversion" to indicate what version
 * that customer DB is running. This script will evolve to upgrade databases 
 * from 7.1.X to 7.5 and later versions.
 * 
 * When a new upgrade version is added, a switch case statement is added here, and appropriate
 * code is added to upgrades/db_<version>.php  (avoid periods in file names for my sanity please)
 * 
 * That script will be run and may apply other sql files. Naming convention should follow:
 * db_<version>[_<desc>].sql
 * 
 * Example: upgrades/db_7-5.php; upgrades/db_7-5_pre.sql; upgrades/db_7-5_post.sql
 * 
 * Utility functions will be added in the future to support finer grain control, perhaps by 
 * build or revision, allowing QA and Dev to apply small diffs within the same major version.
 * 
 * This script is designed to run in parallel on multiple customers at a time. Write upgrades
 * as thread-safe as possible. Non schema updates that may cause deadlocks or other temporary 
 * errors for whatever reason should be designed to be re-runable. Ex, use a setting "_upgrade_stage"
 * to indicate which stage of the upgrade process is complete.
 * 
 */

//list supported versons here in order of upgrade
//format is major.minor.point/revision
//rev is mainly for internal dev where we may have already deployed that version, but made some changes (rev starts at 0)
$versions = array (
	"7.1.5/0",
	"7.5/14",
	"7.6/0"
	//7.6.1
	//8.0
	//etc
);

$targetversion = $versions[count($versions)-1];
list($targetversion, $targetrev) = explode("/",$targetversion);

$usage = "
Description:
This script will upgrade customers or entire shards databases
Usage:
php upgrade_database.php -a 
php upgrade_database.php -c <customerid> [<customerid> ...] 
php upgrade_database.php -s <shardid> [<shardid> ...] 
php upgrade_database.php -i

-a : run on everything
-s : shard mode, specific shards
-c : customer mode, specific customers
-i : commsuite import mode, upgrade 7.1.5 commsuite-flex migration to 7.5 on 'csimport' database
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
				case "i":
					$mode = "csimport";
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

$updater = mt_rand();
echo "Updater id: $updater\n";

//connect to authserver db
echo "connecting to authserver db\n";
$authdb = DBConnect($authhost,$authuser,$authpass,"authserver");

// if $mode == 'csimport' skip the authserver/shard/customer lookup and simply connect to 'csimport' database
if ($mode == 'csimport') {
	$_dbcon = $db = $authdb;
	QuickUpdate("use csimport",$db);
	
	// customerid=0, shardid=0 just dummy placeholders that are not really used
	update_customer($db, 0, 0);
	
	// set systemmessages in time order
	QuickUpdate("update systemmessages set modifydate = now() + interval id second", $db);
} else {
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
		echo "doing $customerid ";
		$_dbcon = $db = $shards[$customer['shardid']];
		QuickUpdate("use c_$customerid",$db);
		update_customer($db, $customerid, $customer['shardid']);
	}
}


function update_customer($db, $customerid, $shardid) {
	global $versions;
	global $targetversion;
	global $targetrev;
	global $updater;
	
	Query("begin",$db);

	$version = QuickQuery("select value from setting where name='_dbversion' for update",$db);
	if ($version == null)
		$version = "7.1.5/0"; //assume last known version with no _dbversion support
	
	list($version, $rev) = explode("/",$version);
	
	if ($version === $targetversion && $rev == $targetrev) {
		Query("commit",$db);
		echo "already up to date, skipping\n";
		return;
	}
	
	//only allow one instance of the updater per customer to run at a time
	//try to insert our updater code, it should either error out due to duplicate key, or return 1
	//indicating 1 row was modified.
	if (QuickUpdate("insert into setting (name,value) values ('_dbupgrade_inprogress','$updater')",$db)) {
		Query("commit",$db);
		Query("begin",$db);
	} else {
		Query("rollback",$db);
		echo "an upgrade is already in process, skipping\n";
		return;
	}

	
	// require the necessary version upgrade scripts	
	require_once("upgrades/db_7-5.php");
	require_once("upgrades/db_7-6.php");

	// for each version, upgrade to the next
	$foundstartingversion = false;
	foreach ($versions as $vr) {
		list($v, $r) = explode("/",$vr);
		// skip past versions, find our current version to start the upgrade
		if (!$foundstartingversion && $version != $v)
			continue;
		$foundstartingversion = true;
		
		// skip version if already on max revision
		if ($r != $rev || $r == 0) {
			echo "upgrading from $version/$rev to $v/$r\n";
		
			if ("7.5" == $v) {
				if (!upgrade_7_5($rev, $shardid, $customerid, $db)) {
					exit("Error upgrading DB");
				}
			} else if ("7.6" == $v) {
				if (!upgrade_7_6($rev, $shardid, $customerid, $db)) {
					exit("Error upgrading DB");
				}
			}
			$rev = 0; // reset for next version
		}
		$version = $v; // set the current version
	}
	// upgrade success
	QuickUpdate("insert into setting (name,value) values ('_dbversion','$targetversion/$targetrev') on duplicate key update value=values(value)", $db);
	
	QuickUpdate("delete from setting where name='_dbupgrade_inprogress'",$db);
	Query("commit",$db);
	echo "\n";
}

function apply_sql ($filename, $customerid, $custdb, $specificrev = false) {
	
	$allsql = file_get_contents($filename);
	
	//check revisions
	$revs = preg_split("/-- \\\$rev ([0-9]+)/",$allsql,-1, PREG_SPLIT_DELIM_CAPTURE);
	
	$currev = 1;
	foreach ($revs as $revsql) {
		
		//if we got just numbers, must be revision version
		if (preg_match("/^[0-9]+\$/",$revsql)) {
			$currev = $revsql + 0;
			continue;
		}
		
		//skip blanks, older revs
		if (trim($revsql) == "" || $specificrev && $currev != $specificrev) {
			continue;
		}
		
		$sqlqueries = explode("$$$",$revsql);
		foreach ($sqlqueries as $sqlquery) {
			if (trim($sqlquery)){
				echo ".";
				$sqlquery = str_replace('_$CUSTOMERID_', $customerid, $sqlquery);
				$res = Query($sqlquery,$custdb);
				if (!$res) {
					exit("Error running query, check dberrors.txt for info");
				}
			}
		}
	}
		
	Query("commit",$custdb);	
	Query("begin",$custdb);	
}

?>
