<?
//settings

//authserver db info
$authhost = "";
$authuser = "";
$authpass = "";

if (!$authhost || !$authuser || !$authpass)
	exit("ERROR: This script is not properly configured, please add connection info\n");

// interupt file, stops execution. Will wait till current move operation completes
// go to working directory and execute: touch <stopfilename>
$stopfile = "stop_shard_move_customer";

// where is the createtriggers sql file?
$createtriggerssql = "../db/createtriggers.sql";
if (!file_exists($createtriggerssql))
	die("Cannot find createtriggers sql file: $createtriggerssql\n");

if ($argc < 4)
	exit("need args: sourceshard destshard customerid [customerid] [customerid] ...\n");

array_shift($argv); //ignore this script

$srcshard = array_shift($argv); //remember on the asp shard2 actually has shardid=1
$destshard = array_shift($argv);

$customerids = $argv;

echo "\nMoving customerids: [" . implode(", ", $customerids) . "] from shard id $srcshard to shard id $destshard\n\npress enter to continue";

fgets(STDIN);

//----------------------------------------------------------------------

$SETTINGS = parse_ini_file("../inc/settings.ini.php", true);

require_once("../inc/db.inc.php");
require_once("../inc/utils.inc.php");
require_once("../inc/memcache.inc.php");
require_once("../inc/DBMappedObject.php");
require_once("../inc/DBMappedObjectHelpers.php");
require_once("../inc/DBRelationMap.php");
require_once("../manager/managerutils.inc.php");

//----------------------------------------------------------------------


//connect to authserver db, and each shard
echo "connecting to authserver db\n";
$authdb = DBConnect($authhost, $authuser, $authpass, "authserver");

echo "connecting to dest shard\n";
$query = "select dbhost,dbusername,dbpassword,isfull from shard where id=$destshard";
list($desthost, $destuser, $destpass, $destisfull) = QuickQueryRow($query, false, $authdb) or die("Can't query shard info:" . errorinfo($authdb));

// check destination shard to see if it is set to full
if ($destisfull)
	die("Destination shard says it's full. Check authserver->shard->isfull for destination shard.\n");

$destsharddb = DBConnect($desthost, $destuser, $destpass, "aspshard") or die("Can't connect to shard:" . $desthost);

echo "connecting to source shard\n";
$query = "select dbhost,dbusername,dbpassword from shard where id=$srcshard";
list($srchost, $srcuser, $srcpass) = QuickQueryRow($query, false, $authdb) or die("Can't query shard info:" . errorinfo($authdb));
$srcsharddb = DBConnect($srchost, $srcuser, $srcpass, "aspshard") or die("Can't connect to shard:" . $srchost);

$successful = array();
$failures = array();
try {
	// for each customer id
	foreach ($customerids as $customerid) {
		// check for stop file, if it exists. terminate
		if (file_exists($stopfile)) {
			echo "\nStop file found: $stopfile\nTerminating execution!\n";
			break;
		}
		try {
			echo "\n=============== Attempting to move customerid: $customerid ===================\n";

			//ensure shard connections are set to aspshard database
			$srcsharddb->query("use aspshard");
			$destsharddb->query("use aspshard");

			//sanity checks
			echo "doing sanity checks\n";

			//customer has active jobs
			$query = "select count(*) from qjob where customerid=$customerid and status in ('processing', 'procactive', 'active', 'cancelling')";
			if (QuickQuery($query, $srcsharddb))
				throw new ContinuableException("There are active jobs! customerid: $customerid\n");

			//max last login
			if (QuickQuery("select max(lastlogin) > now() - interval 1 hour from c_$customerid.user where login != 'schoolmessenger'", $srcsharddb))
				throw new ContinuableException("A user has logged in less than an hour ago! customerid: $customerid\n");

			//customer db exists on source
			if (!QuickQuery("show databases like 'c_$customerid'", $srcsharddb))
				throw new ContinuableException("Customer database doesn't exist on source shard customerid: $customerid\n");

			//customer db already exists on dest
			if (QuickQuery("show databases like 'c_$customerid'", $destsharddb))
				throw new ContinuableException("Customer database already exists on target shard customerid: $customerid\n");

			//----------------------------------------------------------------------

			//backup the existing customer db
			$backupfilename = "c_$customerid.xfer.sql";
			$cmd = "nice mysqldump -h $srchost -u $srcuser -p$srcpass --quick --single-transaction --skip-triggers c_$customerid > $backupfilename";
			echo "Backing up customer data to: $backupfilename\n";
			$result = exec($cmd, $output, $retval);

			if ($retval != 0)
				throw new ContinuableException("Problem backing up data for transfer\ncustomerid: $customerid\n" . implode("\n", $output));

			// dump all messagelink for this customer into the transfer file
			echo "adding all messagelink records to transfer file\n";

			if (!$fp = fopen($backupfilename, 'a'))
				throw new ContinuableException("Unable to open transfer file for writing: $backupfilename\n");

			$query = "use aspshard;\n";
			if (!fwrite($fp, $query)) {
				fclose($fp);
				throw new ContinuableException("Failed to write to transfer file : $query\n");
			}

			$query = "select * from messagelink where customerid=?";
			if ($res = Query($query, $srcsharddb, array($customerid))) {
				while ($row = $res->fetch(PDO::FETCH_ASSOC)) {
					$query = "insert ignore into messagelink (`customerid`,`jobid`,`personid`,`createtime`,`code`) values (" . $row['customerid'] . "," . $row['jobid'] . "," . $row['personid'] . ",'" . $row['createtime'] . "','" . $row['code'] . "');\n";
					if (!fwrite($fp, $query)) {
						fclose($fp);
						throw new ContinuableException("Failed to write to transfer file : $query\n");
					}
				}
			}

			$query = "select * from importalert where customerid=?";
			if ($res = Query($query, $srcsharddb, array($customerid))) {
				while ($row = $res->fetch(PDO::FETCH_ASSOC)) {
					// set nullable fields to null and not empty string
					$valalerttime = ($row['alerttime'] === null) ? "null" : "'" . $row['alerttime'] . "'";
					$valnotified = ($row['notified'] === null) ? "null" : "'" . $row['notified'] . "'";
					$valnotes = ($row['notes'] === null) ? "null" : "'" . $row['notes'] . "'";

					$query = "insert ignore into importalert (`customerid`, `importalertruleid`, `type`, `importname`, `name`, `operation`, `testvalue`, `actualvalue`, `alerttime`, `notified`, `notes`, `acknowledged`)
				values (" . $row['customerid'] . "," . $row['importalertruleid'] . ",'" . $row['type'] . "','" . $row['importname'] . "','" . $row['name'] . "','" . $row['operation'] . "'," . $row['testvalue'] . "," . $row['actualvalue'] . "," . $valalerttime . "," . $valnotified . "," . $valnotes . "," . $row['acknowledged'] . ");\n";
					if (!fwrite($fp, $query)) {
						fclose($fp);
						throw new ContinuableException("Failed to write to transfer file : $query\n");
					}
				}
			}

			if (!fclose($fp))
				throw new ContinuableException("Failed to close transfer file : $backupfilename\n");

			//create a db, user, etc for the customer database on the shard
			echo "creating destination DB\n";
			$newdbname = "c_$customerid";
			$newpass = genpassword();
			$limitedusername = "c_" . $customerid . "_limited";
			$limitedpassword = genpassword();

			$query = "create database $newdbname DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci";
			if (!QuickUpdate($query, $destsharddb))
				throw new FatalException("Failed to create new DB $newdbname : " . errorinfo($destsharddb));

			if (!$destsharddb->query("use " . $newdbname))
				throw new FatalException("Failed select db $newdbname : " . errorinfo($destsharddb));

			//TODO should we back these up?????
			//ensure mysql credentials match our records, which it won't if create user fails because the user already exists
			$query = "select user from mysql.user where user='?'";
			if ($res = Query($query, $destsharddb,array($newdbname))) {
				if ($res->fetch(PDO::FETCH_ASSOC)) {
					QuickUpdate("drop user '$newdbname'", $destsharddb);
				}
			}

			$query = "select user from mysql.user where user='?'";
			if ($res = Query($query, $destsharddb,array($limitedusername))) {
				if ($res->fetch(PDO::FETCH_ASSOC)) {
					QuickUpdate("drop user '$limitedusername'", $destsharddb);
				}
			}

			// create new db user for the customer on destination shard
			QuickUpdate("create user '$newdbname' identified by '$newpass'", $destsharddb);
			QuickUpdate("grant select, insert, update, delete, create temporary tables, execute on $newdbname . * to '$newdbname'", $destsharddb);

			//load customer db into new shard
			$cmd = "nice mysql -h $desthost -u $destuser -p$destpass c_$customerid < $backupfilename";
			echo("loading customer data from: $backupfilename\n");
			$result = exec($cmd, $output, $retval);
			if ($retval != 0)
				throw new ContinuableCleanableException("Problem loading transfer data\ncustomerid: $customerid\n" . implode("\n", $output));

			// create the limited user
			// FIXME: if this fails for any reason this die's which is VERY odd for an API to exit
			// what is interesting is that this is also called from createnewcustomer() which is also an API which
			// performs die's on error.  It inturn is called by customeredit.php which is a manager page.
			// clearly this should be fixed.
			createLimitedUser($limitedusername, $limitedpassword, $newdbname, $destsharddb);

			//verify the tables by doing a checksum
			$srcsharddb->query("use c_$customerid");
			$destsharddb->query("use c_$customerid");

			$customertables = QuickQueryList("show tables", false, $srcsharddb);

			echo "Check tables: ";
			$anyerrors = 0;
			$skipchecktables = array("custdm");
			foreach ($customertables as $table) {
				if (in_array($table, $skipchecktables))
					continue;
				echo "$table ";
				if (verifyTable($customerid,$srcsharddb, $destsharddb, $table)) {
					echo "OK, ";
				} else {
					echo "\n##### HASH MISMATCH #####\nTrying to verify customerid: $customerid table: $table\n";
					$anyerrors++;
				}
			}

			if ($anyerrors)
				throw new ContinuableCleanableException("################## HASH MISMATCH DETECTED##################\n\nTrying to verify customerid: $customerid");

			// create triggers
			// NOTE we need to create triggers before we copy all shard jobs in case one starts processing
			echo("\nCreate triggers\n");
			$triggers = explode("$$$", file_get_contents($createtriggerssql));
			if (count($triggers) == 0)
				throw new FatalException("Failed to read trigger definition\ncustomerid: $customerid\n");

			if (false === QuickUpdate("START TRANSACTION", $destsharddb))
				throw new FatalException("cannot start transaction\ncustomerid: $customerid\n");

			foreach ($triggers as $trigger) {
				if (trim($trigger)) {
					$trigger = str_replace('_$CUSTOMERID_', $customerid, $trigger);
					$rowcount = QuickUpdate($trigger, $destsharddb);
					if ($rowcount === false)
						throw new FatalException("Failed to create trigger by executing statement \n$trigger\n\nfor c_$customerid : " . errorinfo($destsharddb));
				}
			}

			if (false === QuickUpdate("COMMIT", $destsharddb))
				throw new FatalException("failed to commit transaction\ncustomerid: $customerid\n");

			//----------------------------------------------------------------------
			// copy job/schedule/reportsubscription to shard

			echo "copying shard records\n";

			$timezone = QuickQuery("select value from setting where name='timezone'", $destsharddb);

			// reportsubscription
			echo("Copy reportsubscriptions\n");
			if (false === QuickUpdate("START TRANSACTION", $destsharddb))
				throw new FatalException("cannot start transaction");

			$query = "INSERT INTO aspshard.qreportsubscription (id, customerid, userid, type, daysofweek, dayofmonth, time, timezone, nextrun, email) select id, " . $customerid . ", userid, type, daysofweek, dayofmonth, time, '" . $timezone . "', nextrun, email from reportsubscription";
			$rowcount = QuickUpdate($query, $destsharddb);
			if ($rowcount === false)
				throw new ContinuableCleanableException("Failed to execute statement \n$query\n\nfor c_$customerid : " . errorinfo($destsharddb));

			if (false === QuickUpdate("COMMIT", $destsharddb))
				throw new FatalException("failed to commit transaction\ncustomerid: $customerid\n");

			if (false === QuickUpdate("START TRANSACTION", $destsharddb))
				throw new FatalException("cannot start transaction\ncustomerid: $customerid\n");

			// repeating job
			$query = "INSERT INTO aspshard.qjob (id, customerid, userid, scheduleid, timezone, startdate, enddate, starttime, endtime, status)" .
				" select id, " . $customerid . ", userid, scheduleid, '" . $timezone . "', startdate, enddate, starttime, endtime, 'repeating' from job where status='repeating'";
			$rowcount = QuickUpdate($query, $destsharddb);
			if ($rowcount === false)
				throw new ContinuableCleanableException("Failed to execute statement \n$query\n\nfor c_$customerid : " . errorinfo($destsharddb));

			// schedule
			$query = "INSERT INTO aspshard.qschedule (id, customerid, daysofweek, time, nextrun, timezone) select id, " . $customerid . ", daysofweek, time, nextrun, '" . $timezone . "' from schedule";
			$rowcount = QuickUpdate($query, $destsharddb);
			if ($rowcount === false)
				throw new ContinuableCleanableException("Failed to execute statement \n$query\n\nfor c_$customerid : " . errorinfo($destsharddb));

			// future job
			$query = "INSERT INTO aspshard.qjob (id, customerid, userid, scheduleid, timezone, startdate, enddate, starttime, endtime, status)" .
				" select id, " . $customerid . ", userid, scheduleid, '" . $timezone . "', startdate, enddate, starttime, endtime, 'scheduled' from job where status='scheduled'";
			$rowcount = QuickUpdate($query, $destsharddb);
			if ($rowcount === false)
				throw new ContinuableCleanableException("Failed to execute statement \n$query\n\nfor c_$customerid : " . errorinfo($destsharddb));

			if (false === QuickUpdate("COMMIT", $destsharddb))
				throw new FatalException("failed to commit transaction\ncustomerid: $customerid\n");


			//----------------------------------------------------------------------

			echo "Transfer successful. setting authserver records and removing old database\n";

			$srcsharddb->query("use aspshard"); //ensure src shard connection is set to aspshard database

			//update authserver to point to new customer info
			//update shardid and password
			$query = "update customer set dbusername='" . DBSafe($newdbname, $authdb) . "', limitedusername='" . DBSafe($limitedusername, $authdb) . "', limitedpassword='" . DBSafe($limitedpassword, $authdb) . "', shardid=$destshard, dbpassword='" . DBSafe($newpass, $authdb) . "' where id=$customerid";
			if (QuickUpdate($query, $authdb) === false)
				throw new FatalException("Problem updating customer table:" . errorinfo($authdb) . "\ncustomerid: $customerid\n");

			//remove this customer's shard data from old shard
			cleanupCustomer($customerid,$srcsharddb);

			// Made it! Customer moved successfully!
			// done, for each customer id
			$successful[] = $customerid;
		} catch (ContinuableCleanableException $e) {
			$failures[$customerid] = $e->getMessage();
			echo "Continuable error: " . $e->getMessage() . "\n";
			cleanupCustomer($customerid,$destsharddb);
		} catch (ContinuableException $e) {
			$failures[$customerid] = $e->getMessage();
			echo "Continuable error: " . $e->getMessage() . "\n";
		}
	}
} catch (FatalException $e) {
	$failures[$customerid] = $e->getMessage();
	echo "Fatal error: " . $e->getMessage() . "\n";
}

reportresults($customerids, $successful, $failures);

// report successful customer moves
function reportresults($customerids, $successful, $failures)
{
	$logfile = 'results-' . time();
	if ($successful) {
		echo "Successfully moved customer ids: [" . implode(" ", $successful) . "]\n";
		echo "/-------------------------------------------------------------\\\n";
		echo "| Dont forget to restart authserver, redialer, dispatchers    |\n";
		echo "| as they cache connection info                               |\n";
		echo "\\-------------------------------------------------------------/\n\n";
	} else {
		echo "Moved no customers successfully\n";
	}
	if ($failures) {
		$file = fopen($logfile, 'w');
		echo "Failed customer ids: [" . implode(" ", array_keys($failures)) . "]\n";
		fwrite($file, "-------------------ERRORS--------------------\n");
		foreach ($failures as $customerid => $error) {
			fwrite($file, "customerid:$customerid error:$error\n");
		}

		fwrite($file, "-------------------Failed customers--------------------\n");
		fwrite($file, "Failed customer ids: [" . implode(" ", array_keys($failures)) . "]\n");
		fwrite($file, "-------------------------------------------------------\n");
		fclose($file);
	}

	// check if we skipped any (that is if the sum of failures and successfull is not the same as customerids)
	$skipped = array_diff($customerids, $successful, array_keys($failures));
	if ($skipped) {
		$file = fopen($logfile, 'w');
		echo "Skipped customer ids: [" . implode(" ", $skipped) . "]\n";
		fwrite($file, "-------------------Skipped customers--------------------\n");
		fwrite($file, "Skipped customer ids: [" . implode(" ", $skipped) . "]\n");
		fwrite($file, "-------------------------------------------------------\n");
		fclose($file);

	}

	if (count($successful) == count($customerids)) {
		echo "\nAll customer ids moved successfully!\n";
	} else {
		echo "\nSome customer ids were NOT moved!\n";
		echo "Check log file \"" . $logfile . "\" for details.\n\n";
	}
}

function verifyTable($customerid, $db1, $db2, $table)
{
	$tableType = QuickQuery("select table_type from information_schema.tables where table_schema='c_$customerid' and table_name='aspsmsblock'",$db1);
	if ($tableType == "VIEW") {
		return true;
	}
	$fields = QuickQueryList("describe $table", false, $db1);
	$fieldlist = "`" . implode("`, `", $fields) . "`";
	$query = "select md5(group_concat(md5(concat_ws('#',$fieldlist)))) from $table";

	$hash1 = QuickQuery($query, $db1);
	$hash2 = QuickQuery($query, $db2);

	if ($hash1 === false || $hash2 === false)
		echo "Problem trying to hash tables:" . $query;

	return $hash1 == $hash2;
}

function cleanupCustomer($customerid, $db)
{
	$shardtablearray = array("importalert", "importqueue", "qjobperson", "smsjobtask", "emailjobtask", "qjobtask", "specialtaskqueue", "qreportsubscription", "qschedule", "qjob", "messagelink");

	echo "deleting shard records:";

	$db->query("use aspshard"); //ensure connection is set to aspshard database

	foreach ($shardtablearray as $t) {
		echo ".";
		$query = "delete from " . $t . " where customerid=$customerid";
		if (QuickUpdate($query, $db) === false) {
			throw new FatalException("Failed to execute statement \n$query\n\n : " . errorinfo($db) . "\n");
		}
	}
	echo "\n";

	//drop customer user
	$query = "drop user c_$customerid";
	if (QuickUpdate($query, $db) === false)
		throw new FatalException("Problem dropping old customer user:" . errorinfo($db) . "\ncustomerid: $customerid\n");
	//drop limited customer user
	$query = "drop user c_" . $customerid . "_limited";
	if (QuickUpdate($query, $db) === false)
		throw new FatalException("Problem dropping old limited customer user:" . errorinfo($db) . "\ncustomerid: $customerid\n");
	//drop customer db
	echo "Dropping database\n";
	$query = "drop database c_$customerid";
	if (QuickUpdate($query, $db) === false)
		throw new FatalException("Problem dropping old customer db:" . errorinfo($db) . "\ncustomerid: $customerid\n");


}

function errorinfo($dbcon)
{
	$errInfo = $dbcon->errorInfo();
	$err = $errInfo[0];
	if (!isset($errInfo[2]))
		$detail = "unknown";
	else
		$detail = $errInfo[2];
	return $err . " : " . $detail;
}

class FatalException extends Exception {  }
class ContinuableException extends Exception {  }
class ContinuableCleanableException extends Exception {  }

?>
