<?

class Task {
	var $id;
	var $customerid;
	var $shardid;
	var $tasktime;
	var $renderedmessage;
}

class SpecialTask {
	var $id;
	var $customerid;
	var $specialtaskid;
	var $shardid;
	var $type;
}

//attempt to assign a task by setting the status to calling.
//TODO, check to see if this request is a failover request, and possible delay it until the DBs are syned up
//so that we don't assign any tasks that might have already been assigned from the failed node that are waiting in our mysql log.
function assignTask ($dmapidb) {

	//because we might use persistent connections, ensure that we rollback no matter what
	function ensure_rollback($dmapidb) {
		mysql_query("rollback", $dmapidb);
	}
	register_shutdown_function("ensure_rollback",$dmapidb);

	mysql_query("begin", $dmapidb);

	//get the id of next available task
	//update the task to assign it to us
	$query = "select id,customerid,shardid,tasktime,renderedmessage from jobtaskactive order by tasktime limit 1 for update";

	$res = mysql_query($query,$dmapidb);
	if ($row = mysql_fetch_row($res)) {
		$task = new Task();

		$task->id = $row[0];
		$task->customerid = $row[1];
		$task->shardid = $row[2];
		$task->tasktime = $row[3];
		$task->renderedmessage = $row[4];

		$query = "delete from jobtaskactive where id='" . mysql_real_escape_string($task->id,$dmapidb) . "'";
		mysql_query($query, $dmapidb);
	} else {
		$task = false;
	}

	mysql_query("commit", $dmapidb);

	return $task;
}


function assignSpecialTask ($dmapidb) {
	//because we might use persistent connections, ensure that we rollback no matter what
	function ensure_rollback($dmapidb) {
		mysql_query("rollback", $dmapidb);
	}
	register_shutdown_function("ensure_rollback",$dmapidb);

	mysql_query("begin", $dmapidb);

	//get the id of next available task
	//update the task to assign it to us
	$query = "select id,customerid,specialtaskid,shardid,type specialtaskactive limit 1 for update";

	$res = mysql_query($query,$dmapidb);
	if ($row = mysql_fetch_row($res)) {
		$task = new SpecialTask();

		$task->id = $row[0];
		$task->customerid = $row[1];
		$task->specialtaskid = $row[2];
		$task->shardid = $row[3];
		$task->type = $row[4];

		$query = "delete from specialtaskactive where id='" . mysql_real_escape_string($task->id,$dmapidb) . "'";
		mysql_query($query, $dmapidb);
	} else {
		$task = false;
	}

	mysql_query("commit", $dmapidb);

	return $task;

}


?>