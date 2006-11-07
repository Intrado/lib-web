<?

//load session data
function loadSessionData ($sessionid) {
	//escape the sessionid!
	$query = "select data from sessiondata where id='" . DBSafe($sessionid) . "'";
	$data = QuickQuery($query);

	if ($data !== false)
		return unserialize($data);
	else
		return false;
}

//store session data
function storeSessionData ($sessionid, $customerid, $data) {
	if (QuickQuery("select count(*) from sessiondata where id='" . DBSafe($sessionid) . "'") > 0) {
		$query = "update sessiondata set data='"
					. DBSafe(serialize($data)) . "' where id='"
					. DBSafe($sessionid) . "'";
		QuickUpdate($query);
	} else {
		$query = "insert into sessiondata (id,customerid,data) values ('"
				. DBSafe($sessionid) . "', '"
				 . DBSafe($customerid) . "', '"
				 . DBSafe(serialize($data)) . "')";

		QuickUpdate($query);

	}
}

//erase session data
function eraseSessionData ($sessionid) {
	QuickUpdate("delete from sessiondata where id='" . DBSafe($sessionid) . "'");
}

?>