<?

//attempt to assign a task by setting the status to calling.
//TODO, check to see if this request is a failover request, and possible delay it until the DBs are syned up
//so that we don't assign any tasks that might have already been assigned from the failed node that are waiting in our mysql log.
function assignTask ($type, $resourceid, $dmapidb) {

	//because we might use persistent connections, ensure that we unlock the tables no matter what
	function ensure_unlock_tables($dmapidb) {
		mysql_query("unlock tables", $dmapidb);
	}
	register_shutdown_function("ensure_unlock_tables",$dmapidb);


	mysql_query("lock tables jobtaskactive write", $dmapidb);

	//get the id of next available task
	//update the task to assign it to us
	$query = "select id from jobtaskactive where status='new' and type='$type' and tasktime > date_sub(now(), interval 60 second) "
			. " and assignmentgroup = " . $resourceid % 2 . " order by tasktime limit 1";

	$res = mysql_query($query,$dmapidb);
	$row = mysql_fetch_row($res);
	if ($row && $row[0]) {
		$foundid = $row[0];
		$query = "update jobtaskactive set status='calling', starttime=unix_timestamp()*1000 where id='" . mysql_real_escape_string($foundid,$dmapidb) . "'";
		$res = mysql_query($query, $dmapidb);
		$gotatask = true;
	} else {
		$gotatask = false;
	}

	mysql_query("unlock tables", $dmapidb);

	return $gotatask ? $foundid : false;
}

?>