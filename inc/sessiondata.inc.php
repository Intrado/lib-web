<?

function DmapiQuickQuery ($query) {
	global $dmapidb;
	$val = false;
	if ($result = mysql_query($query,$dmapidb)) {
		if ($row = mysql_fetch_row($result)) {
			$val = $row[0];
		}
		mysql_free_result($result);
	}
	return $val;
}

function DmapiQuickUpdate ($query) {
	global $dmapidb;
	if (mysql_query($query,$dmapidb)) {
		return mysql_affected_rows();
	}
	return false;
}


//load session data
function loadSessionData ($sessionid) {
	global $dmapidb;

	//escape the sessionid!
	$query = "select data from sessiondata where id='" . mysql_real_escape_string($sessionid, $dmapidb) . "'";
	$data = DmapiQuickQuery($query);

	if ($data !== false) {
		$retdata = unserialize($data);
		getSessionData($retdata[authSessionID]); // load customer db connection from auth server
		return $retdata;
	} else
		return false;
}

//store session data
function storeSessionData ($sessionid, $customerid, $data) {
	global $dmapidb;

	if (DmapiQuickQuery("select count(*) from sessiondata where id='" . mysql_real_escape_string($sessionid, $dmapidb) . "'") > 0) {
		$query = "update sessiondata set data='"
					. mysql_real_escape_string(serialize($data), $dmapidb) . "' where id='"
					. mysql_real_escape_string($sessionid, $dmapidb) . "'";
		DmapiQuickUpdate($query);
	} else {
		$query = "insert into sessiondata (id,customerid,data) values ('"
				. mysql_real_escape_string($sessionid, $dmapidb) . "', '"
				 . mysql_real_escape_string($customerid, $dmapidb) . "', '"
				 . mysql_real_escape_string(serialize($data), $dmapidb) . "')";

		DmapiQuickUpdate($query);
	}
}

//erase session data
function eraseSessionData ($sessionid) {
	global $dmapidb;

	DmapiQuickUpdate("delete from sessiondata where id='" . mysql_real_escape_string($sessionid, $dmapidb) . "'");
}

?>