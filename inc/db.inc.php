<?

function DBDebug($query, $dbcon) {
	global $SETTINGS;
	static $initdblog = false;
	static $logfp;

	if (!$SETTINGS['feature']['log_db_queries'])
		return;

	$dbinfo = "";
	if ($dbcon) {
		// get the dbcon IP address
		$dbinfo = $dbcon->getAttribute(constant("PDO::ATTR_CONNECTION_STATUS"));
		if ($dbinfo == null) $dbinfo = "";
	}

	$cid = 0;
	if (isset($_SESSION['_dbcid']))
		$cid = $_SESSION['_dbcid'];

	if (!$initdblog) {
		list($usec,$sec) = explode(" ", microtime());
		$logfp = fopen($SETTINGS['feature']['log_dir'] . "dblog.txt","a");
		fwrite($logfp,"\n=== t:" . $cid . " ================ " . date("Y-m-d H:i:") . " ================ " . sprintf("%.3f",$usec + ($sec %60)) . ", ===================\n");
		$initdblog = true;
	}
	fwrite($logfp,"--- t:" . $cid . " " . $dbinfo . " --------------------------\n$query\n");
}

//wrap mysql query, and optionally log query errors
function DBQueryWrapper($dbcon, $query, $args=false) {
	global $SETTINGS, $CUSTOMERURL, $USER;

	static $initdblog = false;
	static $logfp;
	
	if (isset($SETTINGS['feature']['query_trace']) && $SETTINGS['feature']['query_trace']) {
		//prepend a comment with trace info to the query
		
		//the first frame is the original caller
		$frame = array_pop(debug_backtrace());
		//Add the PHP source location, customer, and user info.
		$query_header = "/* File: {$frame['file']}\t"
						."Line: {$frame['line']}\t"
						."Function: {$frame['function']}\t"
						."Customer: {$CUSTOMERURL}\t"
						."Username: " . str_replace(array("*/","\t","\n","\0"), "", $USER->login)
						."*/";
		//TODO add more debug vars
		//ie:
//		foreach($keys as $x => $key) { 
//			$val = $this->get($key); 
//			if($val) {
//				$key = strtolower(str_replace(array(": ","\t","\n","\0"), "", $key));
//				$val = str_replace(array("\t","\n","\0"), "", $val); /* all other chars are safe in comments */
//				$query_header .= "\t{$key}: {$val}";
//			}
//		}
		$query = $query_header . "\n" . $query;
	}	

	if ($args) {
		$stmt = $dbcon->prepare($query);
		$queryok = $stmt->execute($args);
	} else {
		$queryok = true;
		$stmt = $dbcon->query($query);
		if ($stmt == null) $queryok = false;
	}

	if (!$queryok && $SETTINGS['feature']['log_db_errors']) {
		if ($args)
			$errInfo = $stmt->errorInfo();
		else
			$errInfo = $dbcon->errorInfo();
		
		if ($errInfo[2] == null)
			$detail = "unknown";
		else
			$detail = $errInfo[2];
			
		if (!$initdblog) {
			$logfp = fopen($SETTINGS['feature']['log_dir'] . "dberrors.txt","a");
			$initdblog = true;
		}
		list($usec,$sec) = explode(" ", microtime());
		$temp = $dbcon->query("select connection_id()");

		$cid = 0;
		if ($temp != null)
			$cid = $temp->fetchColumn();
		$errorstr = "\n" . date("Y-m-d H:i:") . sprintf("%.3f",$usec + ($sec %60)) . " t:" . $cid . " e:" . $errInfo[0] . " " . $detail . " q:" . preg_replace('/\s\s+/', ' ',$query);

		fwrite($logfp, $errorstr);
	}
	
	if ($queryok)
		return $stmt;
	return false;
}

function DBClose () {
	global $_dbcon;
	$_dbcon = null;
}

function DBSafe ($string, $dbconnect=false) {
	global $_dbcon;
	$connection = $dbconnect ? $dbconnect : $_dbcon;

	$quoted = $connection->quote($string);
	// remove quotes from start/end, we just need the escaped string
	return substr($quoted, 1, strlen($quoted)-2);
}

function DBEscapeLikeWildcards ($string) {
	return str_replace("_","\\_",str_replace("%","\\%",$string));
}

function Query ($query, $dbconnect=false, $args=false) {
	global $_dbcon;
	$connection = $dbconnect ? $dbconnect : $_dbcon;
	DBDebug($query, $connection);

	return DBQueryWrapper($connection, $query, $args);
}

function QuickQuery ($query, $dbconnect=false, $args=false) {
	global $_dbcon;
	$val = false;
	$connection = $dbconnect ? $dbconnect : $_dbcon;
	DBDebug($query, $connection);

	if ($result = DBQueryWrapper($connection, $query, $args)) {
		if ($row = $result->fetch(PDO::FETCH_NUM)) {
			$val = $row[0];
		}
		$result = null;
	}

	return $val;
}

function QuickUpdate ($query, $dbconnect=false, $args=false) {
	global $_dbcon;
	$connection = $dbconnect ? $dbconnect : $_dbcon;
	DBDebug($query, $connection);

	if ($res = DBQueryWrapper($connection, $query, $args)) {
		return $res->rowCount();
	}
	return false;
}

function QuickQueryRow ($query, $assoc=false, $dbconnect = false, $args=false) {
	global $_dbcon;
	$connection = $dbconnect ? $dbconnect : $_dbcon;
	DBDebug($query, $connection);

	$row = false;
	if ($result = DBQueryWrapper($connection, $query, $args))
		$row = $result->fetch($assoc ? PDO::FETCH_ASSOC : PDO::FETCH_NUM);

	return $row;
}

function QuickQueryMultiRow ($query, $assoc = false, $dbconnect = false, $args = false) {
	global $_dbcon;
	$connection = $dbconnect ? $dbconnect : $_dbcon;
	DBDebug($query, $connection);

	$list = array();
	if ($result = DBQueryWrapper($connection, $query, $args)) {
		while ($row = $result->fetch($assoc ? PDO::FETCH_ASSOC : PDO::FETCH_NUM))
			$list[] = $row;
	}

	return $list;
}

function QuickQueryList ($query, $pair = false, $dbconnect = false, $args = false) {
	global $_dbcon;
	$list = array();
	$connection = $dbconnect ? $dbconnect : $_dbcon;
	DBDebug($query, $connection);

	if ($result = DBQueryWrapper($connection, $query, $args)) {
		while ($row = $result->fetch(PDO::FETCH_NUM)) {
			if ($pair)
				$list[$row[0]] = $row[1];
			else
				$list[] = $row[0];
		}
	}
	return $list;
}

function DBGetRow ($result, $assoc = false) {
	return $result->fetch($assoc ? PDO::FETCH_ASSOC : PDO::FETCH_NUM);
}

function DBConnect($host, $user, $pass, $database) {
	if (strpos($host,":") !== false) {
		list($host,$port) = explode(":",$host);
		$dsn = 'mysql:dbname='.$database.';host='.$host.';port='.$port;
	} else
		$dsn = 'mysql:dbname='.$database.';host='.$host;
	try {
		$custdb = new PDO($dsn, $user, $pass);
		$custdb->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
		$setcharset = "SET character_set_results = 'utf8', character_set_client = 'utf8', character_set_connection = 'utf8', character_set_database = 'utf8', character_set_server = 'utf8'";
		$custdb->query($setcharset);
		return $custdb;
	} catch (PDOException $e) {
		error_log("Problem connecting to MySQL database: " . $database . " args: ($host, $user, xxxxx, $database) error:" . $e->getMessage());
		return false;
	}
}

function DBParamListString ($count) {
	if ($count > 1)
		return "?" . str_repeat(",?",$count-1);
	else if ($count == 1)
		return "?";
	else
		return "";
}

?>
