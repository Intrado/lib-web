<?

// SET THESE to your authserver host and database. may optionally pass as CLI arguments.
$authhost = "";
$authuser = "";
$authpass = "";

/*
 * Starting in 11.1, we will use a database table 'dbupgrade' to indicate what version the DB is running.
 * Support for all commsuite databases is provided.
 * 
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
//rev is mainly for internal dev where we may have already deployed that version, but made some changes (rev starts at 1)
// CUSTOMER databases
$versions = array (
	"cs" => array (
		"7.1.5/0",	//this is the assumed version when no _dbversion exists. it is special
		"7.5/14",
		"7.6/1",	//rev 1 is always the first complete revision (not zero)
		"7.7/6",
		"7.8/7",
		"8.0/6",
		"8.1/11",
		"8.2/12",
		"8.3/12",
		"9.1/4",
		"9.2/1",
		"9.3/1",
		"9.4/2",
		"9.5/3",
		"9.6/1",
		"9.7/5",
		"10.0/6",
		"10.1/14",
		"10.2/4",
		"10.3/10",
		"11.0/8",
		"11.1/14"
		//etc
	),
	
	"tai" => array (
		"0.1/11",
		"1.2/2",
		"1.3/1",
		"1.4/1",
		"1.5/6"
		//etc
	)
	
);

// non-Customer databases
$dbReleaseVersion = "11.1"; // version to update databases to if no revision changes for individual db, implies revision value of 1
$dbversions = array (
	"authserver" => array (
		"11.0/2"
	),
	
	"aspshard" => array (
		"11.0/1",
		"11.1/4"
	),
	
	"deviceservice" => array (
		"11.1/10"
	),
	
	"disk" => array (
		"11.0/1"
	),
	
	"infocenter" => array (
		"11.0/1",
		"11.1/1"
	),
	
	"lcrrates" => array (
		"11.0/2"
	),
	
	"pagelink" => array (
		"11.0/1"
	),
	
	"portalauth" => array (
		"11.0/2",
		"11.1/2"
	)
);

$usage = "
Description:
This script will upgrade customers or entire shards databases, or all/any databases found in authserver.dbupgradehost table.
Usage:
php upgrade_databases.php -a 
php upgrade_databases.php -c <customerid> [<customerid> ...] 
php upgrade_databases.php -s <shardid> [<shardid> ...] 
php upgrade_databases.php -e 
php upgrade_databases.php -d [all] <databasename> [<databasename> ...] 
Optional: [-h hostname] [-u username] [-p password]

-h : hostname of authserver
-u : username of authserver
-p : password of authserver
-a : all customers
-c : customer mode, specific customers only
-s : shard mode, specific shards customers only
-e : everything mode, all databases and all customers
-d : database mode, specific databases (may have multiple hosts per database, example: aspshard). Special case 'all' to skip upgrading customers and only upgrade all other databases.
";


$opts = array();
$mode = false;
$ids = array();
array_shift($argv); //ignore this script
$argi = 0;
$skiparg = false;
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
				case "e":
					$mode = "everything";
					break;
				case "d":
					$mode = "database";
					break;
				case "h":
					$authhost = $argv[$argi + 1];
					$skiparg = true;
					break;
				case "u":
					$authuser = $argv[$argi + 1];
					$skiparg = true;
					break;
				case "p":
					$authpass = $argv[$argi + 1];
					$skiparg = true;
					break;
				default:
					echo "Unknown option " . $arg[$x] . "\n";
					exit($usage);
			}
		}
	} else if ($skiparg) {
		// skip this arg, already read by the -X arg that used it
		$skiparg = false;
	} else {
		if ($mode == "database") {
			$ids[] = $arg; // string ids
		} else {
			$ids[] = $arg + 0; // int ids
		}
	}
	$argi++;
}

if (!$mode)
	exit("No mode specified\n$usage");
if ($mode != "all" && $mode != "everything" && count($ids) == 0)
	exit("No IDs specified\n$usage");

if (!$authhost || !$authuser || !$authpass)
	exit("ERROR: This script is not properly configured, please add connection info\n$usage");


$SETTINGS = parse_ini_file("../inc/settings.ini.php",true);

date_default_timezone_set("US/Pacific"); //to keep php from complaining. TODO move to manager settings.

require_once("dbupgrade_authserver/dbupgrade_authserver.php");
require_once("dbupgrade_aspshard/dbupgrade_aspshard.php");
require_once("dbupgrade_deviceservice/dbupgrade_deviceservice.php");
require_once("dbupgrade_disk/dbupgrade_disk.php");
require_once("dbupgrade_infocenter/dbupgrade_infocenter.php");
require_once("dbupgrade_lcrrates/dbupgrade_lcrrates.php");
require_once("dbupgrade_pagelink/dbupgrade_pagelink.php");
require_once("dbupgrade_portalauth/dbupgrade_portalauth.php");

require_once("../inc/db.inc.php");
require_once("../inc/utils.inc.php");
require_once("../inc/memcache.inc.php");
require_once("../inc/DBMappedObject.php");
require_once("../inc/DBMappedObjectHelpers.php");
require_once("../inc/DBRelationMap.php");

$updater = mt_rand();
echo "Updater id: $updater\n";


//connect to authserver db
echo "connecting to authserver db\n";
$authdb = DBConnect($authhost,$authuser,$authpass,"authserver");

if ($mode == "database" && $ids[0] == "all") {
	// skip customers
} else { // upgrade customers
$res = Query("select id,dbhost,dbusername,dbpassword from shard", $authdb)
	or exit(mysql_error());
$shards = array();
while ($row = DBGetRow($res, true))
	$shards[$row['id']] = DBConnect($row['dbhost'],$row['dbusername'],$row['dbpassword'], "aspshard")
		or exit(mysql_error());

switch ($mode) {
	case "all": $optsql = ""; break;
	case "everything": $optsql = ""; break;
	case "database": $optsql = "and 0"; break;
	case "customer": $optsql = "and id in (" . implode(",",$ids) . ")"; break;
	case "shard": $optsql = "and shardid in (" . implode(",",$ids) . ")"; break;
}

$res = Query("select id, shardid, urlcomponent, dbusername, dbpassword from customer where 1 $optsql order by id", $authdb)
	or exit(mysql_error());
$customers = array();
while ($row = DBGetRow($res, true))
	$customers[$row['id']] = $row;


// for each customer database to upgrade
foreach ($customers as  $customerid => $customer) {
	echo "doing $customerid \n";
	$_dbcon = $db = $shards[$customer['shardid']];
	QuickUpdate("use c_$customerid",$db);

	foreach ($versions as $product => $productversions) {

		$targetversion = $productversions[count($productversions)-1];
		list($targetversion, $targetrev) = explode("/",$targetversion);
		
		switch ($product) {
			case "cs" :
				//technically we should check if they have commsuite, but this is always true right now
				update_customer($db, $customerid, $customer['shardid']);
				break;
			case "tai":
				$hastai = QuickQuery("show tables like 'tai_topic'");
				if ($hastai) {
					update_taicustomer($db, $customerid, $customer['shardid']);
				}
				break;
		}
	}
} // end loop customers
} // end if upgrade customers

// for each other database to upgrade
if ($mode == "everything" || $mode == "database") {
	if ($mode == "database" && $ids[0] != "all")
		$optsql = "and dbname in ('" . implode("','",$ids) . "')";
	else
		$optsql = "";
	// lookup all hosts and databases to be upgraded
	$res = Query("select dbname, dbhost, dbusername, dbpassword from dbupgradehost where 1 $optsql order by id", $authdb)
		or exit(mysql_error());
	$dbupgradehosts = array();
	while ($row = DBGetRow($res, true)) {
		$dbupgradehosts[] = $row;
	}
	
	foreach ($dbupgradehosts as $dbupgradehost) {
		$dbname = $dbupgradehost['dbname'];
		echo "\ndoing $dbname\n";
		$_dbcon = $db = DBConnect($dbupgradehost['dbhost'], $dbupgradehost['dbusername'], $dbupgradehost['dbpassword'], $dbupgradehost['dbname']);
		update_namedDb($db, $dbname);
	}
}

function update_namedDb($db, $dbname) {
	global $dbversions;
	global $dbReleaseVersion;
	global $updater;
	
	Query("begin", $db);
	
	// find current version
	$version = QuickQuery("select version from dbupgrade where id = ?", $db, array($dbname));
	if ($version == null) {
		Query("commit", $db);
		echo "MISSING DBUPGRADE TABLE! unable to upgrade $dbname\n";
		return;
	}
	list($currentversion, $currentrev) = explode("/", $version);
	echo("current version $version\n");
	// find if needs update
	// find latest ver/rev for specific database, compare with dbReleaseVersion
	list($targetversion, $targetrev) = explode("/", $dbversions[$dbname][count($dbversions[$dbname]) -1]);

	// if current version is up to date
	if (($targetversion < $dbReleaseVersion && $currentversion == $dbReleaseVersion) ||
		($targetversion == $dbReleaseVersion && $targetrev == $currentrev && $currentversion == $dbReleaseVersion)) {
		// no changes to apply
		Query("commit", $db);
		echo "already up to date, skipping upgrade $dbname\n";
		return;
	}
	// check in progress status
	$inprogress = QuickQuery("select status from dbupgrade where id = ?", $db, array($dbname));
	if ($inprogress == "none") {
		// ok to continue
		QuickUpdate("update dbupgrade set status = ? where id = ?", $db, array($updater, $dbname));
		Query("commit", $db);
		Query("begin", $db);
	} else {
		// another process is running, skip this database
		Query("rollback", $db);
		echo "an upgrade is already in progress, skipping\n";
		return;
	}
	
	// if no updates for db, be sure to update to latest release
	if ($targetversion < $dbReleaseVersion && $currentversion == $targetversion && $currentrev == $targetrev) {
		QuickUpdate("update dbupgrade set version = ?, lastUpdateMs = (UNIX_TIMESTAMP() * 1000), status = 'none' where id = ?", $db, array("$dbReleaseVersion/1", $dbname));
		Query("commit", $db);
		echo ("update to release version $dbReleaseVersion/1 \n");
		return;
	}
	
	// apply revision changes
	apply_rev($db, $dbname, $currentversion, $currentrev);
	
	// after all revisions are applied, check again if the release version is greater than the target
	if ($targetversion < $dbReleaseVersion) {
		QuickUpdate("update dbupgrade set version = ?, lastUpdateMs = (UNIX_TIMESTAMP() * 1000) where id = ?", $db, array("$dbReleaseVersion/1", $dbname));
	}
	
	QuickUpdate("update dbupgrade set status = 'none' where id = ?", $db, array($dbname));
	Query("commit", $db);
}

// $db database connection
// $dbname name of database
// $version current version of db
// $rev current rev of db
function apply_rev($db, $dbname, $version, $rev) {
	global $dbversions;
	
	// for each version, upgrade to the next
	$foundstartingversion = false;
	foreach ($dbversions[$dbname] as $vr) {
		list($targetversion, $targetrev) = explode("/",$vr); //WARNING: $targetversion and $targetrev are used outside of for loop
		// skip past versions, find our current version to start the upgrade
		if (!$foundstartingversion && $version > $targetversion) {
			continue;
		}

		if ($version == $targetversion)
			$foundstartingversion = true;
	
		//check to see that we are already on the latest rev, then skip upgrading current version, go to next version
		if ($version == $targetversion && $rev == $targetrev) {
			continue;
		}
	
		echo "upgrading $dbname from $version/$rev to $targetversion/$targetrev\n";
	
	
		/* if we are looking at same major version, check for revs
		 * otherwise skip to next target version and start at rev zero (since we obviously moved major versions)
		* in either case we run the targetversion upgrade script, difference is same version we use current rev,
		* different version we set rev to zero (to indicate that upgrade should take it to rev1)
		*/
	
		if ($version != $targetversion) {
			$rev = 0;
		}
		
		switch ($dbname) {
			case "authserver":
				apply_authserver($targetversion, $rev, $db);
				break;
			case "aspshard":
				apply_aspshard($targetversion, $rev, $db);
				break;
			case "lcrrates":
				apply_lcrrates($targetversion, $rev, $db);
				break;
			case "deviceservice":
				apply_deviceservice($targetversion, $rev, $db);
				break;
			case "disk":
				apply_disk($targetversion, $rev, $db);
				break;
			case "infocenter":
				apply_infocenter($targetversion, $rev, $db);
				break;
			case "portalauth":
				apply_portalauth($targetversion, $rev, $db);
				break;
			case "pagelink":
				apply_pagelink($targetversion, $rev, $db);
				break;
			default:
				echo("Unsupported database named: $dbname , skipping\n");
		}
		
		$version = $targetversion;
		$rev = $targetrev;
	} // end for each version
	
	if ($foundstartingversion !== false) {
		// upgrade success
		QuickUpdate("update dbupgrade set version = ?, lastUpdateMs = (UNIX_TIMESTAMP() * 1000) where id = ?", $db, array("$targetversion/$targetrev", $dbname));
	} // else nothing to apply
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
		echo "already up to date, skipping commsuite upgrade\n";
		return;
	}
	
	//only allow one instance of the updater per customer to run at a time
	$inprogress_value = QuickQuery("select value from setting where name = '_dbupgrade_inprogress' for update");
	if (!$inprogress_value) {
		// ok to continue, this is for older instances where _dbupgrade_inprogress was created and deleted each upgrade. It is now persistent
		QuickUpdate("insert into setting (name, value) values ('_dbupgrade_inprogress', '$updater')", $db);
		Query("commit", $db);
		Query("begin", $db);
	} else if ($inprogress_value == "none") {
		// ok to continue
		QuickUpdate("update setting set value = '$updater' where name = '_dbupgrade_inprogress'", $db);
		Query("commit", $db);
		Query("begin", $db);
	} else {
		// another process is running, skip this customer
		Query("rollback", $db);
		echo "an upgrade is already in progress, skipping\n";
		return;
	}
	
	// require the necessary version upgrade scripts
	//TODO nice to rename "upgrades" folder to "dbupgrade_customer" for consistency with "dbupgrade_*" folders
	require_once("upgrades/db_7-5.php");
	require_once("upgrades/db_7-6.php");
	require_once("upgrades/db_7-7.php");
	require_once("upgrades/db_7-8.php");
	require_once("upgrades/db_8-0.php");
	require_once("upgrades/db_8-1.php");
	require_once("upgrades/db_8-2.php");
	require_once("upgrades/db_8-3.php");
	require_once("upgrades/db_9-1.php");
	require_once("upgrades/db_9-2.php");
	require_once("upgrades/db_9-3.php");
	require_once("upgrades/db_9-4.php");
	require_once("upgrades/db_9-5.php");
	require_once("upgrades/db_9-6.php");
	require_once("upgrades/db_9-7.php");
	require_once("upgrades/db_10-0.php");
	require_once("upgrades/db_10-1.php");
	require_once("upgrades/db_10-2.php");
	require_once("upgrades/db_10-3.php");
	require_once("upgrades/db_11-0.php");
	require_once("upgrades/db_11-1.php");


	// for each version, upgrade to the next
	$foundstartingversion = false;
	foreach ($versions["cs"] as $vr) {
		list($targetversion, $targetrev) = explode("/",$vr); //WARNING: $targetversion and $targetrev are used outside of for loop
		// skip past versions, find our current version to start the upgrade
		if (!$foundstartingversion && $version != $targetversion)
			continue;
			
		if ($version == $targetversion && $rev <= $targetrev)
			$foundstartingversion = true;
		
		//check to see that we are already on the latest rev, then skip upgrading current version, go to next version
		if ($version == $targetversion && $rev == $targetrev)
			continue;

		echo "upgrading commsuite customer from $version/$rev to $targetversion/$targetrev\n";
		
		
		/* if we are looking at same major version, check for revs
		 * otherwise skip to next target version and start at rev zero (since we obviously moved major versions)
		 * in either case we run the targetversion upgrade script, difference is same version we use current rev, 
		 * different version we set rev to zero (to indicate that upgrade should take it to rev1)
		 */
		
		if ($version != $targetversion)
			$rev = 0;
			
		switch ($targetversion) {
			case "7.5":
				if (!upgrade_7_5($rev, $shardid, $customerid, $db)) {
					exit("Error upgrading DB");
				}
				break;
			case "7.6":
				if (!upgrade_7_6($rev, $shardid, $customerid, $db)) {
					exit("Error upgrading DB");
				}
				break;
			case "7.7":
				if (!upgrade_7_7($rev, $shardid, $customerid, $db)) {
					exit("Error upgrading DB");
				}
				break;
			case "7.8":
				if (!upgrade_7_8($rev, $shardid, $customerid, $db)) {
					exit("Error upgrading DB");
				}
				break;
			case "8.0":
				if (!upgrade_8_0($rev, $shardid, $customerid, $db)) {
					exit("Error upgrading DB");
				}
				break;
			case "8.1":
				if (!upgrade_8_1($rev, $shardid, $customerid, $db)) {
					exit("Error upgrading DB");
				}
				break;
			case "8.2":
				if (!upgrade_8_2($rev, $shardid, $customerid, $db)) {
					exit("Error upgrading DB");
				}
				break;
			case "8.3":
				if (!upgrade_8_3($rev, $shardid, $customerid, $db)) {
					exit("Error upgrading DB");
				}
				break;
			case "9.1":
				if (!upgrade_9_1($rev, $shardid, $customerid, $db)) {
					exit("Error upgrading DB");
				}
				break;
			case "9.2":
				if (!upgrade_9_2($rev, $shardid, $customerid, $db)) {
					exit("Error upgrading DB");
				}
				break;
			case "9.3":
				if (!upgrade_9_3($rev, $shardid, $customerid, $db)) {
					exit("Error upgrading DB");
				}
				break;
			case "9.4":
				if (!upgrade_9_4($rev, $shardid, $customerid, $db)) {
					exit("Error upgrading DB");
				}
				break;
			case "9.5":
				if (!upgrade_9_5($rev, $shardid, $customerid, $db)) {
					exit("Error upgrading DB");
				}
				break;
			case "9.6":
				if (!upgrade_9_6($rev, $shardid, $customerid, $db)) {
					exit("Error upgrading DB");
				}
				break;
			case "9.7":
				if (!upgrade_9_7($rev, $shardid, $customerid, $db)) {
					exit("Error upgrading DB");
				}
				break;
			case "10.0":
				if (!upgrade_10_0($rev, $shardid, $customerid, $db)) {
					exit("Error upgrading DB");
				}
				break;
			case "10.1":
				if (!upgrade_10_1($rev, $shardid, $customerid, $db)) {
					exit("Error upgrading DB");
				}
				break;
			case "10.2":
				if (!upgrade_10_2($rev, $shardid, $customerid, $db)) {
					exit("Error upgrading DB");
				}
				break;
			case "10.3":
				if (!upgrade_10_3($rev, $shardid, $customerid, $db)) {
					exit("Error upgrading DB");
				}
				break;
			case "11.0":
				if (!upgrade_11_0($rev, $shardid, $customerid, $db)) {
					exit("Error upgrading DB");
				}
				break;
			case "11.1":
				if (!upgrade_11_1($rev, $shardid, $customerid, $db)) {
					exit("Error upgrading DB");
				}
				break;
		}
		
		$version = $targetversion;
		$rev = $targetrev;
	}
	
	if ($foundstartingversion !== false) { 
		// upgrade success
		//NOTE: never apply any SQL directly in this file, it is not safe to it will function across versions or are even appropriate
		//apply_sql("../db/update_SMAdmin_access.sql", $customerid, $db);
		
		if (QuickQuery("select value from setting where name = '_dbversion' and organizationid is null")) {
			QuickUpdate("update setting set value = '$targetversion/$targetrev' where name = '_dbversion' and organizationid is null", $db);
		} else {
			QuickUpdate("insert into setting (organizationid, name, value) values (null, '_dbversion', '$targetversion/$targetrev')", $db);
		}
	} else {
		//TODO ERROR !!!! running ancient upgrade_databases on newer db? didnt find current version
	}
	QuickUpdate("update setting set value = 'none' where name = '_dbupgrade_inprogress'", $db);
	Query("commit", $db);
	
	echo "\n";
}


function update_taicustomer($db, $customerid, $shardid) {
	global $versions;
	global $targetversion;
	global $targetrev;
	global $updater;

	Query("begin",$db);

	$version = QuickQuery("select value from setting where name='_dbtaiversion' for update",$db);
	if ($version == null)
	$version = "0.1/0"; //assume last known version with no _dbversion support

	list($version, $rev) = explode("/",$version);

	if ($version === $targetversion && $rev == $targetrev) {
		Query("commit",$db);
		echo "already up to date, skipping tai upgrade\n";
		return;
	}

	//only allow one instance of the updater per customer to run at a time
	$inprogress_value = QuickQuery("select value from setting where name = '_dbtaiupgrade_inprogress' for update");
	if (!$inprogress_value) {
		// ok to continue
		QuickUpdate("insert into setting (name, value) values ('_dbtaiupgrade_inprogress', '$updater')", $db);
		Query("commit", $db);
		Query("begin", $db);
	} else if ($inprogress_value == "none") {
		// ok to continue
		QuickUpdate("update setting set value = '$updater' where name = '_dbtaiupgrade_inprogress'", $db);
		Query("commit", $db);
		Query("begin", $db);
	} else {
		// another process is running, skip this customer
		Query("rollback", $db);
		echo "an upgrade is already in progress, skipping\n";
		return;
	}
	

	// require the necessary version upgrade scripts
	require_once("taiupgrades/db_0-1.php");
	require_once("taiupgrades/db_1-2.php");
	require_once("taiupgrades/db_1-3.php");
	require_once("taiupgrades/db_1-4.php");
	require_once("taiupgrades/db_1-5.php");

	// for each version, upgrade to the next
	$foundstartingversion = false;
	foreach ($versions["tai"] as $vr) {
		list($targetversion, $targetrev) = explode("/",$vr); //WARNING: $targetversion and $targetrev are used outside of for loop
		// skip past versions, find our current version to start the upgrade
		if (!$foundstartingversion && $version != $targetversion)
			continue;
			
		if ($version == $targetversion && $rev <= $targetrev)
			$foundstartingversion = true;
		
		//check to see that we are already on the latest rev, then skip upgrading current version, go to next version
		if ($version == $targetversion && $rev == $targetrev)
			continue;
		
		echo "upgrading tai from $version/$rev to $targetversion/$targetrev\n";
		
		
		/* if we are looking at same major version, check for revs
		 * otherwise skip to next target version and start at rev zero (since we obviously moved major versions)
		* in either case we run the targetversion upgrade script, difference is same version we use current rev,
		* different version we set rev to zero (to indicate that upgrade should take it to rev1)
		*/
		
		if ($version != $targetversion)
			$rev = 0;
			
		switch ($targetversion) {
			case "0.1":
				if (!tai_upgrade_0_1($rev, $shardid, $customerid, $db)) {
					exit("Error upgrading DB; Shard: $shardid, Customer: $customerid, Rev: " . $rev);
				}
				break;
			case "1.2":
				if (!tai_upgrade_1_2($rev, $shardid, $customerid, $db)) {
					exit("Error upgrading DB; Shard: $shardid, Customer: $customerid, Rev: " . $rev);
				}
				break;
			case "1.3":
				if (!tai_upgrade_1_3($rev, $shardid, $customerid, $db)) {
					exit("Error upgrading DB; Shard: $shardid, Customer: $customerid, Rev: " . $rev);
				}
				break;
			case "1.4":
				if (!tai_upgrade_1_4($rev, $shardid, $customerid, $db)) {
					exit("Error upgrading DB; Shard: $shardid, Customer: $customerid, Rev: " . $rev);
				}
				break;
			case "1.5":
				if (!tai_upgrade_1_5($rev, $shardid, $customerid, $db)) {
					exit("Error upgrading DB; Shard: $shardid, Customer: $customerid, Rev: " . $rev);
				}
				break;
		}

		$version = $targetversion;
		$rev = $targetrev;
	}

	if ($foundstartingversion !== false) {
		// upgrade success
		apply_sql("../db/tai_update_SMAdmin_access.sql", $customerid, $db);
		if (QuickQuery("select value from setting where name = '_dbtaiversion' and organizationid is null")) {
			QuickUpdate("update setting set value = '$targetversion/$targetrev' where name = '_dbtaiversion' and organizationid is null", $db);
		} else {
			QuickUpdate("insert into setting (organizationid, name, value) values (null, '_dbtaiversion', '$targetversion/$targetrev')", $db);
		}
	} else {
		//TODO ERROR !!!! running ancient upgrade_databases on newer db? didnt find current version
	}
	QuickUpdate("update setting set value = 'none' where name = '_dbtaiupgrade_inprogress'", $db);
	Query("commit", $db);

	echo "\n";
}

// apply to customer db
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
					exit("Error running query, check dberrors.txt for info\n");
				}
			}
		}
	}
		
	Query("commit",$custdb);	
	Query("begin",$custdb);	
}

// apply to non-customer db
function apply_sql_db ($filename, $db, $specificrev = false) {

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
				$res = Query($sqlquery,$db);
				if (!$res) {
					exit("Error running query, check dberrors.txt for info\n");
				}
			}
		}
	}

	Query("commit",$db);
	Query("begin",$db);
}

?>
