<?php

require_once("../inc/db.inc.php");
require_once("../inc/utils.inc.php");
require_once("../inc/memcache.inc.php");
require_once("../inc/DBMappedObject.php");
require_once("../inc/DBMappedObjectHelpers.php");
require_once("../inc/DBRelationMap.php");

date_default_timezone_set("US/Pacific");

$database = 'lcrrates'; //make cmdline?
$opts = array();
$directory = null;
$user = "root";
$password = "";
$host = "localhost";
$rollback = false;
array_shift($argv); //ignore this script
$argi = 0;
$skiparg = false;
$backup = null;
foreach ($argv as $arg) {
	if ($arg[0] == "-") {
		for ($x = 1; $x < strlen($arg); $x++) {
			switch ($arg[$x]) {
				case "f":
					$directory = $argv[$argi + 1];
					$skiparg = true;
					break;
				case "h":
					$host = $argv[$argi + 1];
					$skiparg = true;
					break;
				case "u":
					$user = $argv[$argi + 1];
					$skiparg = true;
					break;
				case "p":
					$password = $argv[$argi + 1];
					$skiparg = true;
					break;
				case "r":
					$rollback = $argv[$argi + 1];
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
	}
	$argi++;
}

$usage = "
Description:
This script will import carriers' LCR data into database. File name should be same as the table name and
the first line should be column names comma-separated. Example:

—————bandwidthratedrates.csv—————————
npanxx,lata,interstaterate,intrastaterate
201007,224,0.0015,0.0015
201032,224,0.0023,0.0023
....

Usage:
php lcrupdate.php -f <directory of csv files> [-r <rollback number>] -u <db username>  -p <db password> -h <db hostname>
-f specifies the directory of the CSV-coded LCR data files to load
-p specifies the password for the MySQL database user
-h specifies the hostname for the MySQL database we want to the data imported to (defaults to \"localhost\")
-u specifies the username for the MySQL database host (defaults to \"root\")
optional:
-r rolls back (rotates out) the most recently imported data if possible
	Backups are done as <tablename>_<date>_<number>. Thus, the rollback number is <date>_<number>
";


echo "Input: database:{$database} user:{$user} host:{$host} directory:{$directory} rollback(backup suffix):$rollback\n";

if (!$directory) {
	exit("No directory specified\n$usage");
}
if (!$host || !$user || !$password) {
	exit("ERROR: Please run the script with correct parameters.\n$usage");
}

$_dbcon = $db = DBConnect($host, $user, $password, $database);

try {
	//first get list of files
	$files = listFolderFiles($directory, "csv");
	if ($files && count($files) > 0) {
		if ($rollback) {
			echo "Rollback data...\n";
			foreach ($files as $f) {
				$tablename = strtolower(basename($f, ".csv"));
				rollbackTable($db, $tablename, "{$tablename}_{$rollback}");
			}
		} else {
			$backup = date("Ymd") . "_" . rand();
			foreach ($files as $f) {
				echo "Reading file: $f\n";
				$data = getCSVData($f);
				$tablename = strtolower(basename($f, ".csv"));
				if ($data && count($data["rows"]) > 0 && count($data["columns"]) > 0) {
					echo "updating table $tablename\n";
					updateTable($db, $tablename, $backup, $data["columns"], $data["rows"]);
				} else {
					echo "No data exist in file $f no column information found. Skipping table {$tablename}...\n";
				}
			}
		}
	} else {
		echo "No files found in directory $directory\n";
	}
} catch (Exception $e) {
	echo 'Caught exception: ', $e->getMessage(), "\n";
	echo mysql_error() . "\n";
	exit(-1);
}

DBClose();
exit();

/**
 * Update a table with given data
 * 
 * @param object $db database connection
 * @param string $tablename table name
 * @param string $backup backup number
 * @param array $columns column names
 * @param array $rows rows to insert
 */
function updateTable($db, $tablename, $backup, $columns, $rows) {
	$backuptable = "{$tablename}_{$backup}";
	echo "Updating table  $tablename number of rows:" . count($rows) . " columns:" . implode(",", $columns) . "\n";
	Query("begin", $db);
	//TODO: to be more efficient we could rename the original one to backup and create original table without data
	backupTable($db, $tablename, $backuptable);
	truncateTable($db, $tablename);
	insertData($db, $tablename, $columns, $rows);
	verifyData($db, $tablename, count($rows));
	Query("commit");
}

/**
 * rollback a table from its backup
 * 
 * @param object $db database connection
 * @param string $tablename table name
 * @param string $tablebackup backup table
 */
function rollbackTable($db, $tablename, $tablebackup) {
	echo "Creating rollback of previous transaction on table $tablename from $tablebackup\n";
	$temptable = "{$tablename}_temp" . date("Ymd") . "_" . rand();
	Query("begin", $db);
	//rename original to temp
	renameTable($db, $tablename, $temptable);
	//rename backupt to original
	renameTable($db, $tablebackup, $tablename);
	//should keep the backup? 
	renameTable($db, $temptable, $tablebackup);
	Query("commit");
}

/**
 * Backup table
 * 
 * object $db database connection
 * @param string $tablename table name
 * @param string $tablebackup backup table name
 */
function backupTable($db, $tablename, $tablebackup) {
	echo "Creating a backup of table $tablename  as $tablebackup\n";
	//drop if exists the backup one
	dropTable($db, $tablebackup);
	//create backup from existing name
	createTable($db, $tablename, $tablebackup, true);
}

/**
 * Drop table
 * 
 * @param object $db database connection
 * @param string $tablename table name
 */
function dropTable($db, $tablename) {
	echo "Droping table {$tablename}...\n";
	if (QuickUpdate("drop table if exists {$tablename}", $db) === false) {
		throw new DBException("Failed to drop table {$tablename}. SQL: drop table if exists {$tablename}");
	}
}

/**
 * Rename table
 * 
 * @param object $db database connection
 * @param string $tablename table to create from
 * @param string $destination table to create
 */
function renameTable($db, $tablename, $destination) {
	echo "Renaming table from $tablename to $destination\n";
	$query = "rename table {$tablename} to  {$destination}";
	if (QuickUpdate("rename table {$tablename} to  {$destination}", $db) === false) {
		throw new DBException("Failed to rename table. SQL: $query");
	}
}

/**
 * Create a table from existing table
 * 
 * @param object $db database connection
 * @param string $tablename table to create from
 * @param string $destination table to create
 */
function createTable($db, $tablename, $destination, $copydata = false) {
	echo "Creating table $destination from $tablename\n";
	$query = ($copydata) ? "create table {$destination} select * from {$tablename}" : "create table $destination like $tablename";
	if (QuickUpdate($query, $db) === false) {
		throw new DBException("Failed to create table. SQL: $query");
	}
}

/**
 * Truncate a table
 * 
 * @param object $db database connection
 * @param string $tablename table to truncate
 */
function truncateTable($db, $tablename) {
	echo "Truncating table  $tablename \n";
	if (QuickUpdate("truncate $tablename", $db) === false) {
		throw new DBException("Failed to truncate table $tablename. SQL:truncate $tablename");
	}
}

/**
 * Insert data into table
 * 
 * @param object $db database connection
 * @param string $tablename table name
 * @param array $columns column names
 * @param array $rows data rows
 */
function insertData($db, $tablename, $columns, $rows) {
	echo "Inserting data into  table  $tablename number of rows:" . count($rows) . " columns:" . implode(",", $columns) . "\n";
	$cols = implode(",", $columns);
	echo "columns=$cols\n";
	$batch = array();
	foreach ($rows as $row) {
		$batch[] = $row;
		if (count($batch) == 10000) {
			insertBatch($db, $tablename, $cols, $batch);
			$batch = array();
		}
	}
	if (count($batch) > 0) {
		insertBatch($db, $tablename, $cols, $batch);
	}
}

/**
 * 
 * @param object $db db connection
 * @param array $tablename table name
 * @param array $cols columns
 * @param array $rows rows
 * @throws DBException
 */
function insertBatch($db, $tablename, $cols, $rows) {
	$values = array();
	foreach ($rows as $row) {
		$fields = array();
		foreach ($row as $field) {
			$fields[] = $db->quote($field);
		}
		$values[] = "(" . implode(",", $fields) . ")";
	}

	$query = "insert ignore into {$tablename} ({$cols}) values " . implode(",", $values);
	Query("begin", $db);
	if (QuickUpdate($query, $db, $row) === false) {
		echo $query;
		throw new DBException("Failed to insert data into table $tablename.");
	}
	Query("commit", $db);
}

/**
 * make sure data is inserted properly
 * @param object $db connection
 * @param string $tablename tablename
 * @param integer $nrows number of rows
 * @throws DBException
 */
function verifyData($db, $tablename, $nrows) {
	$query = "select count(*) from $tablename";
	$results = QuickQuery($query, $db);
	if (!$results) {
		echo $query;
		throw new DBException("Failed to get count of data from table $tablename. SQL: $query");
	}
	if ($results != $nrows) {
		throw new DBException("Expected $nrows rows, but found $results in  $tablename. SQL: $query");
	}
}

/**
 * get CSV data
 * @param string $file file name
 * @return array data["rows"] rows of data and  data["columns"] names of columns 
 */
function getCSVData($file) {
	ini_set('auto_detect_line_endings', TRUE);
	$handle = fopen($file, 'r');
	$columns = fgetcsv($handle);
	$rows = array();
	while (($data = fgetcsv($handle)) !== FALSE) {
		$rows[] = $data;
	}
	ini_set('auto_detect_line_endings', FALSE);
	fclose($handle);
	return array("rows" => $rows, "columns" => $columns);
}

/**
 * Get list of file in a directory for given extension
 * 
 * @param string $dir directory
 * @param string $extension file extension ex: csv
 * @return array list fo files
 */
function listFolderFiles($dir, $extension) {
	$files = array();
	foreach (glob("{$dir}/*.{$extension}") as $file) {
		$files[] = $file;
	}
	return $files;
}

class DBException extends Exception {
	
}

?>