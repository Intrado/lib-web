<?

$_dbcon = mysql_connect('localhost:3307', 'sharpteeth', 'sharpteeth202')
    or die('Could not connect: ' . mysql_error());

mysql_select_db('dialerasp') or die('Could not select database');


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