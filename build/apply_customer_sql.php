<?
/*
 * apply_customer_sql.php
 *
 * Description: execute the contents of an SQL script (delimited by $$$)
 * or individual SQL statements against one or many customer databases.
 * Automatically connects to authserver to find the respective shard
 * for each customer.
 *
 * You can include the integer for the current customer id in the query:
 * UPDATE mytable SET field = _$CUSTOMERID_;
 *
 * Supports optional output of query result sets.
 *
 * Supports optional "chunking" to apply large UPDATE or DELETE statements
 * incrementally. The purpose is to limit the scope of row locking and use
 * of the rollback segment during an update that affects many rows.
 * To use this feature, add the placeholder _$CHUNKIFY_ at the end of your
 * UPDATE or DELETE statement. This is not supported for INSERT statements.
 * Results are unpredictable when using joins or subqueries.
 *
 * Be careful when using chunking that your SQL fetches a different set of
 * rows on successive chunks, or else you will create an infinite loop.
 * For example, the below doesn't work because it will apply the same change
 * redundantly to the same chunk of rows every time, and will never progress.
 * UPDATE mytable SET field = 123 _$CHUNKIFY_;
 *
 * Whereas the below does work, because each successive chunk will match
 * only rows that haven't been updated yet. Therefore it will update all
 * rows eventually.
 * UPDATE mytable SET field = 123 WHERE field != 123 _$CHUNKIFY_;
 */

function help() {
	echo <<<HELP
Usage: php apply_customer_sql.php [ options... ] [ <sqlfile> ] { all | <cid1> ... <cidN> }

-c|--chunk-size <n>            Apply change in chunks of n rows (default: all rows in one chunk)
   --chunk-limit <n>           Stop after executing n chunks (default: unlimited)
   --chunk-delay-ms <ms>       Sleep for milliseconds after each chunk (default: 0)
-r|--runtime <seconds>         Stop after specified number of seconds (default: unlimited)
-e|--execute <sql>             Literal SQL statement, in lieu of using an input file
-f|--config <file>             MySQL config file for host, port user, password (default: "~/.my.cnf")
-h|--host <host>               Authserver host (default: "localhost")
-o|--output <format>           Output results of queries e.g. in CSV format (default: no output)
-p|--password <password>       Authserver password
-P|--port <port>               Authserver port (default: 3306)
-s|--shard <shardid>           Shard id (default: all)
-u|--user <user>               Authserver user (default: "root")
-v|--verbose                   Verbose output
-?|--help                      Print this help

HELP;
}

$chunkDelayMsTotal = 0;

function runSqlAgainstCustomer(array $customer, array $options) {
	global $chunkDelayMsTotal;

	printf("Executing against customer % 5d: ", $customer["id"]);

	$custDb = databaseConnection($customer);

	foreach ($options["sqlQueries"] as $sqlQuery) {
		if (!trim($sqlQuery)) {
			continue;
		}
		$sqlQuery = str_replace('_$CUSTOMERID_', $customer["id"], $sqlQuery);
		$chunkified = false;
		if ($options["chunkSize"]) {
			$limitClause = " limit {$options['chunkSize']}";
		} else {
			$limitClause = "";
		}
		$sqlQueryNew = str_replace('_$CHUNKIFY_', $limitClause, $sqlQuery);
		if ($options["chunkSize"] && $sqlQuery != $sqlQueryNew) {
			$chunkified = true;
			$sqlQuery = $sqlQueryNew;
		}
		if ($options["verbose"]) {
			echo "\nSQL = $sqlQuery\n";
		}
		$stmt = $custDb->prepare($sqlQuery);

		$chunkCount = 0;
		$totalRowCount = 0;
		$stmtStartTime = time();

		do {
			$stmt->execute();
			$rowCount = $stmt->rowCount();
			$totalRowCount += $rowCount;
			$chunkCount++;
			if ($rowCount) {
				if ($options["verbose"]) {
					echo "Chunk $chunkCount processed $rowCount rows.\n";
				} else {
					echo ".";
				}
				usleep($options["chunkDelayMs"] * 1000);
				$chunkDelayMsTotal += $options["chunkDelayMs"];
			}
			// stop processing chunks after a number of chunks.
			if ($options["chunkLimit"] && $chunkCount >= $options["chunkLimit"]) {
				break;
			}
			// stop processing chunks if we run out of runTime.
			if ($options["runTime"] && time() - $options["startTime"] > $options["runTime"]) {
				if ($options["verbose"]) {
					echo "Stopping because runtime exceeded.\n";
				}
				break;
			}
		} while ($chunkified && !$stmt->getColumnMeta(1) && $rowCount > 0);

		$stmtEndTime = time();
		$stmtElapsedTime = gmdate("H:i:s", $stmtEndTime - $stmtStartTime);
		if ($options["verbose"]) {
			echo "\nStatement ran $chunkCount chunks, processed total $totalRowCount rows, in $stmtElapsedTime.\n";
		}

		// optionally fetch and display output
		switch ($options["output"]) {
		case "csv":
		case "CSV":
			echo "\n";
			do {
				while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
					fputcsv(STDOUT, $row);
				}
			} while ($stmt->nextRowset());
			break;
		}
		// Allow the sqlQueries loop to continue even if runTime has
		// been exceeded, so we execute at least one chunk of all
		// sqlQueries against the current customer.
	}
	echo "\n";
	$custDb = NULL;
}

function parse_options($argv) {
	$options = array(
		"chunkSize" => 0,
		"chunkLimit" => null,
		"chunkDelayMs" => 100,
		"configfile" => getenv("HOME") . "/.my.cnf",
		"output" => false,
		"runTime" => null,
		"startTime" => time(),
		"verbose" => 0,
		"sqlQueries" => array(),
		"shardIds" => null,
		"dbparams" => array(
			"dbhost" => "localhost",
			"dbport" => "3306",
			"dbname" => "authserver",
			"dbusername" => "root",
			"dbpassword" => "asp123"
		)
	);

	$shortopts = "c:e:f:h:o:p:P:r:s:u:v?";
	$longopts = array(
		"chunk-size:",
		"chunk-limit:",
		"chunk-delay-ms:",
		"execute:",
		"config:",
		"host:",
		"output:",
		"password:",
		"port:",
		"runtime:",
		"shard:",
		"user:",
		"verbose",
		"help"
	);
	$getopts = getopt($shortopts, $longopts);

	$remainingArgv = $argv;
	array_shift($remainingArgv); // remove $0

	$flag_dbparams = array();

	foreach ($getopts as $flag => $value) {
		switch ($flag) {
		case "c":
		case "chunk-size":
			$options["chunkSize"] = (int)$value;
			array_shift($remainingArgv);
			array_shift($remainingArgv);
			break;
		case "chunk-limit":
			$options["chunkLimit"] = (int)$value;
			array_shift($remainingArgv);
			array_shift($remainingArgv);
			break;
		case "chunk-delay-ms":
			$options["chunkDelayMs"] = (int)$value;
			array_shift($remainingArgv);
			array_shift($remainingArgv);
			break;
		case "r":
		case "runtime":
			$options["runTime"] = $value;
			array_shift($remainingArgv);
			array_shift($remainingArgv);
			break;
		case "e":
		case "execute":
			$options["sqlQueries"][] = $value;
			array_shift($remainingArgv);
			array_shift($remainingArgv);
			break;
		case "f":
		case "config":
			$options["configfile"] = $value;
			array_shift($remainingArgv);
			array_shift($remainingArgv);
			break;
		case "o":
		case "output":
			$options["output"] = $value;
			array_shift($remainingArgv);
			array_shift($remainingArgv);
			break;
		case "h":
		case "host":
			$flag_dbparams["dbhost"] = $value;
			array_shift($remainingArgv);
			array_shift($remainingArgv);
			break;
		case "p":
		case "password":
			$flag_dbparams["dbpassword"] = $value;
			array_shift($remainingArgv);
			array_shift($remainingArgv);
			break;
		case "P":
		case "port":
			$flag_dbparams["dbport"] = (int)$value;
			array_shift($remainingArgv);
			array_shift($remainingArgv);
			break;
		case "s":
		case "shard":
			$options["shardIds"] = array_map("intval", explode(",", $value));
			array_shift($remainingArgv);
			array_shift($remainingArgv);
			break;
		case "u":
		case "user":
			$flag_dbparams["dbusername"] = $value;
			array_shift($remainingArgv);
			array_shift($remainingArgv);
			break;
		case "v":
		case "verbose":
			$options["verbose"]++;
			array_shift($remainingArgv);
			break;
		case "?":
		case "help":
			help();
			array_shift($remainingArgv);
			exit(0);
		}
	}

	if ($remainingArgv && $remainingArgv[0] && $remainingArgv[0][0] == '-') {
		echo "Unknown flag '{$remainingArgv[0]}'.\n\n";
		help();
		exit(1);
	}

	if (isset($options["configfile"]) && file_exists($options["configfile"])) {
		if ($config = parse_ini_file($options["configfile"], true)) {
			foreach ($config["client"] as $key => $value) {
				switch ($key) {
				case "user":
					$key = "username";
				case "host":
				case "port":
				case "password":
					$options["dbparams"]["db{$key}"] = $value;
				}
			}
		} else {
			die(__FUNCTION__ . ": Cannot read or parse config file \"{$options['configfile']}\"\n");
		}
	}
	// options on the command-line override both defaults and config file, if any
	$options["dbparams"] = array_merge($options["dbparams"], $flag_dbparams);

	return array($options, $remainingArgv);
}

function databaseConnection(array $dbParams) {
	try {
		$dsn = "mysql:";
		if (isset($dbParams["dbhost"])) {
			$dsn .= "host={$dbParams['dbhost']}";
		} else {
			$dsn .= "host=localhost";
		}
		if (isset($dbParams["dbport"])) {
			$dsn .= ";port={$dbParams['dbport']}";
		}
		if (isset($dbParams["dbname"])) {
			$dsn .= ";dbname={$dbParams['dbname']}";
		}
		$db = new PDO($dsn, $dbParams["dbusername"], $dbParams["dbpassword"],
			array(
				PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
				PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'UTF8'"
			)
		);
	} catch (PDOException $e) {
		error_log("Connection failed for $dsn");
		die($e->getMessage() . "\n");
	}
	return ($db);
}

list($options, $remainingArgv) = parse_options($argv);

// Load SQL statements from file
if (empty($options["sqlQueries"])) {
	$sqlFile = array_shift($remainingArgv);
	if (!$sqlFile) {
		die("Please specify the file with SQL you would like to execute, or use the --execute option.\n");
	}
	if (!file_exists($sqlFile)) {
		die("Cannot find the specified file \"$sqlFile\".\n");
	}
	$options["sqlQueries"] = explode("$$$", file_get_contents($sqlFile));
}

// Get SQL to query customer(s)
$params = array();
if (empty($remainingArgv) || array_search("all", $remainingArgv) !== false) {
	$query = "select c.id, s.dbhost, concat('c_', c.id) as dbname, s.dbusername, s.dbpassword
		from shard s inner join customer c on (c.shardid = s.id)
		where true ";
} else {
	$cidArray = array_filter(array_map("intval", $remainingArgv),
		function ($cid) {
			return $cid >= 1;
		});
	$query = "select c.id, s.dbhost, concat('c_', c.id) as dbname, s.dbusername, s.dbpassword
		from shard s inner join customer c on (c.shardid = s.id)
		where c.id in (" . implode(", ", array_fill(1, count($cidArray), "?")) . ") ";
	$params = array_merge($params, $cidArray);
}
if ($options["shardIds"]) {
	$sidArray = array_filter($options["shardIds"],
		function($sid) {
			return $sid >= 1 and $sid <= 8;
		});
	$query .= " and s.id in (" . implode(", ", array_fill(1, count($sidArray), "?")) . ") ";
	$params = array_merge($params, $sidArray);
}

$authDb = databaseConnection($options["dbparams"]);

$stmt = $authDb->prepare($query);
$stmt->execute(array_values($params));

$authDb = NULL;

while ($customer = $stmt->fetch(PDO::FETCH_ASSOC)) {
	runSqlAgainstCustomer($customer, $options);
	if ($options["runTime"] && time() - $options["startTime"] > $options["runTime"]) {
		if ($options["verbose"]) {
			echo "Finishing because runtime exceeded.\n\n";
		}
		break;
	}
}

$elapsedSeconds = time() - $options["startTime"];
echo "Completion time: " . gmdate("H:i:s", $elapsedSeconds) . "\n";
$delaySeconds = (int)($chunkDelayMsTotal / 1000);
if ($chunkDelayMsTotal) {
	echo "Delay time:      " . gmdate("H:i:s", $delaySeconds) . "\n";
}

