<?

// global $_dbcon is set by auth.inc.php during login


function DBDebug($query) {
	global $SETTINGS;
	static $initdblog = false;
	static $logfp;

	if (!$SETTINGS['feature']['log_db_queries'])
		return;

	if (!$initdblog) {
		list($usec,$sec) = explode(" ", microtime());
		$logfp = fopen($SETTINGS['feature']['log_dir'] . "dblog.txt","a");
		fwrite($logfp,"\n=== t:" . mysql_thread_id() . " ================ " . date("Y-m-d H:i:") . sprintf("%.3f",$usec + ($sec %60)) . ", ===================\n");
		$initdblog = true;
	}
	fwrite($logfp,"--- t:" . mysql_thread_id() . " ---------------------------\n$query\n");
}

//wrap mysql_query, and optionally log query errors
function DBQueryWrapper($query, $dbcon) {
	global $SETTINGS;

	static $initdblog = false;
	static $logfp;

	$res = mysql_query($query, $dbcon);
	if (!$res && $SETTINGS['feature']['log_db_errors']) {
		if (!$initdblog) {
			$logfp = fopen($SETTINGS['feature']['log_dir'] . "dberrors.txt","a");
			$initdblog = true;
		}
		list($usec,$sec) = explode(" ", microtime());
		$errorstr = "\n" . date("Y-m-d H:i:") . sprintf("%.3f",$usec + ($sec %60)) . " t:" . mysql_thread_id() . " e:" . mysql_error() . " q:" . preg_replace('/\s\s+/', ' ',$query);
		fwrite($logfp, $errorstr);
	}
	return $res;
}

function DBClose () {
	global $_dbcon;
	mysql_close($_dbcon);
}

function DBSafe ($string) {
	global $_dbcon;
	return mysql_real_escape_string($string,$_dbcon);
}

function Query ($query) {
	DBDebug($query);
	global $_dbcon;
	return DBQueryWrapper($query,$_dbcon);
}

function QuickQuery ($query) {
	DBDebug($query);
	global $_dbcon;
	$val = false;
	if ($result = DBQueryWrapper($query,$_dbcon)) {
		if ($row = mysql_fetch_row($result)) {
			$val = $row[0];
		}
		mysql_free_result($result);
	}

	return $val;
}

function QuickUpdate ($query) {
	DBDebug($query);
	global $_dbcon;
	if (DBQueryWrapper($query,$_dbcon)) {
		return mysql_affected_rows();
	}
	return false;
}

function QuickQueryRow ($query, $assoc = false) {
	DBDebug($query);
	global $_dbcon;
	$row = false;
	if ($result = DBQueryWrapper($query,$_dbcon)) {
		if ($assoc)
			$row = mysql_fetch_assoc($result);
		else
			$row = mysql_fetch_row($result);

		mysql_free_result($result);
	}

	return $row;
}

function QuickQueryList ($query, $pair = false) {
	DBDebug($query);
	global $_dbcon;
	$list = array();

	if ($result = DBQueryWrapper($query,$_dbcon)) {
		while ($row = mysql_fetch_row($result)) {
			if($pair)
				$list[$row[0]] = $row[1];
			else
			$list[] = $row[0];
		}
	}
	return $list;
}

function DBGetRow ($query, $assoc = false) {
	global $_dbcon;
	if ($assoc)
		$result = mysql_fetch_assoc($query);
	else
		$result = mysql_fetch_row($query);
	return $result;
}


?>