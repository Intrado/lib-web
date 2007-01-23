<?

$dbs = array($SETTINGS['db']);
if (isset($SETTINGS['db2']))
	$dbs[] = $SETTINGS['db2'];


foreach ($dbs as $db) {

	if ($db['persistent'])
		$_dbcon = mysql_pconnect($db['host'], $db['user'], $db['pass']);
	else
		$_dbcon = mysql_connect($db['host'], $db['user'], $db['pass']);
	if (!$_dbcon) {
		error_log("Problem connecting to MySQL server at " . $db['host'] . " error:" . mysql_error());
		continue;
	}

	if (mysql_select_db($db['db'])) {
		break; //got one!
	} else {
		error_log("Problem selecting databse for " . $db['host'] . " error:" . mysql_error());
	}
}

unset($dbs);


function DBDebug($query) {
	static $initdblog = false;
	static $logfp;

	if (!$initdblog) {
		list($usec,$sec) = explode(" ", microtime());
		$logfp = fopen("dblog.txt","a");
		fwrite($logfp,"\n=================== " . date("Y-m-d H:i:") . sprintf("%.3f",$usec + ($sec %60)) . ", ===================\n");
		$initdblog = true;
	}
	fwrite($logfp,"------------------------------\n$query\n");
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
	return mysql_query($query,$_dbcon);
}

function QuickQuery ($query) {
	DBDebug($query);
	global $_dbcon;
	$val = false;
	if ($result = mysql_query($query,$_dbcon)) {
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
	if (mysql_query($query,$_dbcon)) {
		return mysql_affected_rows();
	}
	return false;
}

function QuickQueryRow ($query, $assoc = false) {
	DBDebug($query);
	global $_dbcon;
	$row = false;
	if ($result = mysql_query($query,$_dbcon)) {
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

	if ($result = mysql_query($query,$_dbcon)) {
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