<?
require_once("../inc/db.inc.php");

/* Used to make sure the data for a particular customer table
 * on the slave is correctly synched with the same table on
 * the master.
 * 
 * - Nickolas Heckman
 */

//authserver db info
$authhost = "10.25.25.50:3306";
$authuser = "root";
$authpass = "asp123";

// how long should we wait for the slave replication to catch up?
// this shouldn't be too small, especially if your checking large tables.
$slaveCatchupSeconds = 120;

if ($argc < 3) {
	die("Missing arguments\n".
		"Usage: php <this script> customerid tablename [tablename] [tablename]...\n");
}

// shift out all the arguments (first one is the script)
array_shift($argv);
$customerid = array_shift($argv) + 0;
$tables = array();
foreach ($argv as $table)
	$tables[] = $table;

if ($customerid <= 0) {
	die("You have supplied an invalid customer id: '$customerid'\nCannot continue...\n");
}

// connect to authserver to get the master and slave db info
$authdb = DBConnect($authhost,$authuser,$authpass,"authserver");
$query = "select s.dbhost, s.dbusername, s.dbpassword, s.readonlyhost 
		from shard s inner join customer c on (s.id = c.shardid) where c.id=$customerid";
list($dbhost,$dbuser,$dbpass,$readonlyhost) = QuickQueryRow($query, false, $authdb) or die("Can't query shard info: " . errorinfo($authdb). "\n");

// Check host info...
if (!$dbhost || !$dbuser || !$dbpass || !$readonlyhost) {
	die("There is something wrong with your shard info.\n".
		"One or more of the following fields are invalid:\n".
		"\tdbhost, dbusername, dbpassword, readonlyhost\n");
}

// Connect to the master and the slave
if (!$masterdb = DBConnect($dbhost,$dbuser,$dbpass,"c_". $customerid))
	die("Problem connecting to master database.\n");
// Connect to the master and the slave
if (!$slavedb = DBConnect($readonlyhost,$dbuser,$dbpass,"c_". $customerid))
	die("Problem connecting to master database.\n");

// To check that the tables are equivalent we must do the following
// 1. Check how far behind slave replication is on the slave. If it is way behind we bail out
// 2. Create a table on the master to write hashes into
// 3. Hash tables and store the value
// 4. Wait for slave to catch up
// 5. Compare hashes between the master and slave
// 6. Clean up the hash storage table

// 1. Check slave replication
echo "Checking slave replication status...\n";
$slavestatus = QuickQueryRow("show slave status", true, $slavedb) or die("Cannot query slave status: ". errorinfo($slavedb));
if ($slavestatus["Slave_IO_Running"] != "Yes" || $slavestatus["Slave_SQL_Running"] != "Yes" || $slavestatus["Seconds_Behind_Master"] > 10) {
	die("Slave is not in a state where we can reliably check it against the Master DB\n".
		"One or more of the following is not true:\n".
		"Slave_IO_Running, Slave_SQL_Running, Seconds_Behind_Master <= 10\n");
}
echo "Slave replication is OK\n";

try {
	// 2. Create hash storage table
	echo "Creating hash storage table...\n";
	$query = "CREATE TABLE verify_table_hash (
				tablename VARCHAR( 24 ) NOT NULL ,
				hash VARCHAR( 32 ) NOT NULL
				) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_bin";
	if (!Query($query, $masterdb)) {
		throw new Exception("Cannot create verify_table_hash table on master: ". errorinfo($masterdb));
	}
	
	// 3. Hash tables and store the values
	echo "Creating hashes of requested tables on the master";
	foreach ($tables as $table) {
		echo ".";
		if (!$fields = QuickQueryList("describe `$table`", false, $masterdb))
			throw new Exception("Problem getting field list for table: '$table', ". errorinfo($masterdb));
			
		$fieldlist = "`" . implode("`, `",$fields) . "`";
		$query = "insert into verify_table_hash 
				select ? as tablename, md5(group_concat(md5(concat_ws('#',$fieldlist)))) as hash from `$table` ";
		
		if (!Query($query, $masterdb, array($table)))
			throw new Exception("Problem inserting hash for table: '$table', ". errorinfo($masterdb));
	}
	echo "\n";
	
	// 4. Check slave for hash values
	echo "Waiting for slave to catch up.";
	$tables_query_args = array();
	foreach ($tables as $table)
		$tables_query_args[] = '?';
	$isSlaveReady = false;
	$query = "select count(*) from verify_table_hash where tablename in (". implode(",", $tables_query_args). ")";
	for ($i = $slaveCatchupSeconds; $i > 0; $i--) {
		if (QuickQuery($query, $slavedb, $tables) != count($tables)) {
			echo (".");
			sleep(1);
		} else {
			$isSlaveReady = true;
			break;
		}
	}
	echo "\n";
	// are all table hashes ready?
	if (!$isSlaveReady) {
		echo "WARNING: Some table hashes were not found on the slave. It may be too slow or has lost synchronization.\n";
	}
	
	// 5. Compare hash of table from both slave and master
	echo "Comparing table hashes...\n";
	if (!$masterhashes = QuickQueryList("select tablename, hash from verify_table_hash", true, $masterdb))
		throw new Exception("Problem querying verify_table_hash table on master: ". errorinfo($masterdb));
		
	if (!$slavehashes = QuickQueryList("select tablename, hash from verify_table_hash", true, $slavedb))
		throw new Exception("Problem querying verify_table_hash table on slave: ". errorinfo($slavedb));
	
	foreach ($masterhashes as $mastertable => $masterhash) {
			echo "============= Table '$mastertable' ===============\n";
		if (isset($slavehashes[$mastertable]) && $slavehashes[$mastertable] == $masterhash) {
			echo "MATCH!\n";
		} else {
			echo "############# HASH MISMATCH DETECTED ##############\n".
				"The hash between master and slave do NOT match!\n";
		}
	}
	
	// 6. Clean up hash table
	if (!Query("DROP TABLE verify_table_hash", $masterdb)) {
		throw new Exception("Problem dropping verify_table_hash table on master: ". errorinfo($masterdb));
	}
	
	
} catch (Exception $e) {
	echo $e;
	// clean up verify table hash table
	if (!Query("DROP TABLE verify_table_hash", $masterdb)) {
		echo "Problem dropping verify_table_hash table after exception: ". errorinfo($masterdb). "\n";
	}
}

// This method copied from the shard_move_customer.php script
function errorinfo($dbcon) {
	$errInfo = $dbcon->errorInfo();
	$err = $errInfo[0];
	if (!isset($errInfo[2]))
		$detail = "unknown";
	else
		$detail = $errInfo[2];
	return $err . " : " . $detail;
}

?>