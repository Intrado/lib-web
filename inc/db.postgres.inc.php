<?

mysql_connect('localhost', 'root', '')
    or die('Could not connect: ' . mysql_error());

mysql_select_db('dialer') or die('Could not select database');

function dbsafe ($string) {
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

function GetRow ($result, $assoc = false) {
	if ($assoc)
		return = mysql_fetch_assoc($result);
	else
		return = mysql_fetch_row($result);
}



?>