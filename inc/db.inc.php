<?

function DBDebug($query) {
	global $SETTINGS;
	global $_dbcon;
	static $initdblog = false;
	static $logfp;

	if (!$SETTINGS['feature']['log_db_queries'])
		return;

	$cid = 0;
	if (isset($_SESSION['_dbcid']))
		$cid = $_SESSION['_dbcid'];

	if (!$initdblog) {
		list($usec,$sec) = explode(" ", microtime());
		$logfp = fopen($SETTINGS['feature']['log_dir'] . "dblog.txt","a");
		fwrite($logfp,"\n=== t:" . $cid . " ================ " . date("Y-m-d H:i:") . " ================ " . sprintf("%.3f",$usec + ($sec %60)) . ", ===================\n");
		$initdblog = true;
	}
	fwrite($logfp,"--- t:" . $cid . " --------------------------\n$query\n");
}

//wrap mysql query, and optionally log query errors
function DBQueryWrapper($dbcon, $query, $args=false) {
	global $SETTINGS;

	static $initdblog = false;
	static $logfp;

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
	if ($dbconnect)
		$connection = $dbconnect;
	else
		$connection = $_dbcon;
	$quoted = $connection->quote($string);
	// remove quotes from start/end, we just need the escaped string
	return substr($quoted, 1, strlen($quoted)-2);
}

function Query ($query, $dbconnect=false, $args=false) {
	DBDebug($query);
	global $_dbcon;
	if ($dbconnect)
		return DBQueryWrapper($dbconnect, $query, $args);
	else
		return DBQueryWrapper($_dbcon, $query, $args);
}

function QuickQuery ($query, $dbconnect=false, $args=false) {
	DBDebug($query);
	global $_dbcon;
	$val = false;
	if ($dbconnect)
		$connection = $dbconnect;
	else
		$connection = $_dbcon;
	if ($result = DBQueryWrapper($connection, $query, $args)) {
		if ($row = $result->fetch(PDO::FETCH_NUM)) {
			$val = $row[0];
		}
		$result = null;
	}

	return $val;
}

function QuickUpdate ($query, $dbconnect=false, $args=false) {
	DBDebug($query);
	global $_dbcon;
	if ($dbconnect)
		$connection = $dbconnect;
	else
		$connection = $_dbcon;
	if ($res = DBQueryWrapper($connection, $query, $args)) {
		return $res->rowCount();
	}
	return false;
}

function QuickQueryRow ($query, $assoc=false, $dbconnect = false, $args=false) {
	DBDebug($query);
	global $_dbcon;
	if ($dbconnect)
		$connection = $dbconnect;
	else
		$connection = $_dbcon;
	$row = false;
	if ($result = DBQueryWrapper($connection, $query, $args)) {
		if ($assoc)
			$row = $result->fetch(PDO::FETCH_ASSOC);
		else
			$row = $result->fetch(PDO::FETCH_NUM);

		$result = null;
	}

	return $row;
}

function QuickQueryMultiRow ($query, $assoc = false, $dbconnect = false, $args = false) {
	DBDebug($query);
	global $_dbcon;
	$list = array();
	$i = 0;
	if ($dbconnect)
		$connection = $dbconnect;
	else
		$connection = $_dbcon;
	$row = false;
	if ($result = DBQueryWrapper($connection, $query, $args)) {
		if ($assoc)
			$row = $result->fetch(PDO::FETCH_ASSOC);
		else
			$row = $result->fetch(PDO::FETCH_NUM);
		while ($row) {
			$list[$i++] = $row;
			if ($assoc)
				$row = $result->fetch(PDO::FETCH_ASSOC);
			else
				$row = $result->fetch(PDO::FETCH_NUM);
		}
		$result = null;
	}

	return $list;
}

function QuickQueryList ($query, $pair = false, $dbconnect = false, $args = false) {
	DBDebug($query);
	global $_dbcon;
	$list = array();

	if ($dbconnect)
		$connection = $dbconnect;
	else
		$connection = $_dbcon;
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

function DBGetRow ($result, $assoc = false, $args = false) {
	if ($assoc)
		$row = $result->fetch(PDO::FETCH_ASSOC);
	else
		$row = $result->fetch(PDO::FETCH_NUM);
	return $row;
}

function DBConnect($host, $user, $pass, $database) {
	$dsn = 'mysql:dbname='.$database.';host='.$host;
	try {
		$custdb = new PDO($dsn, $user, $pass);
		$setcharset = "SET character_set_results = 'utf8', character_set_client = 'utf8', character_set_connection = 'utf8', character_set_database = 'utf8', character_set_server = 'utf8'";
		$custdb->query($setcharset);
		return $custdb;
	} catch (PDOException $e) {
		error_log("Problem connecting to MySQL database: " . $database . " error:" . $e->getMessage());
		return false;
	}
}


?>