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

function DBClose () {
	global $_dbcon;
	mysql_close($_dbcon);
}

function DBSafe ($string) {
	return mysql_real_escape_string($string);
}

function Query ($query) {
	return mysql_query($query);
}

function QuickQuery ($query) {
	$val = false;
	if ($result = mysql_query($query)) {
		if ($row = mysql_fetch_row($result)) {
			$val = $row[0];
		}
		mysql_free_result($result);
	}

	return $val;
}

function QuickUpdate ($query) {
	if (mysql_query($query)) {
		return mysql_affected_rows();
	}
	return false;
}

function QuickQueryRow ($query, $assoc = false) {
	$row = false;
	if ($result = mysql_query($query)) {
		if ($assoc)
			$row = mysql_fetch_assoc($result);
		else
			$row = mysql_fetch_row($result);

		mysql_free_result($result);
	}

	return $row;
}

function QuickQueryList ($query, $pair = false) {
	$list = array();

	if ($result = mysql_query($query)) {
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
	if ($assoc)
		$result = mysql_fetch_assoc($query);
	else
		$result = mysql_fetch_row($query);
	return $result;
}



?>