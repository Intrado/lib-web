<?

//load session data
function loadSessionData ($sessionid) {
	global $dmapidb;
	//escape the sessionid!
	$query = "select data from sessiondata where id='" . DBSafe($sessionid) . "'";
	$data = QuickQuery($query,$dmapidb);

	if ($data !== false)
		return unserialize($data);
	else
		return false;
}

//store session data
function storeSessionData ($sessionid, $customerid, $data) {
	global $dmapidb;
	if (QuickQuery("select count(*) from sessiondata where id='" . DBSafe($sessionid,$dmapidb) . "'",$dmapidb) > 0) {
		$query = "update sessiondata set data='"
					. DBSafe(serialize($data),$dmapidb) . "' where id='"
					. DBSafe($sessionid,$dmapidb) . "'";
		QuickUpdate($query,$dmapidb);
	} else {
		$query = "insert into sessiondata (id,customerid,data) values ('"
				. DBSafe($sessionid,$dmapidb) . "', '"
				 . DBSafe($customerid,$dmapidb) . "', '"
				 . DBSafe(serialize($data),$dmapidb) . "')";

		QuickUpdate($query,$dmapidb);

	}
}

//erase session data
function eraseSessionData ($sessionid) {
	QuickUpdate("delete from sessiondata where id='" . DBSafe($sessionid,$dmapidb) . "'",$dmapidb);
}

?>