<?

class Task {
	var $id;
	var $customerid;
	var $shardid;
	var $tasktime;
	var $renderedmessage;
}

class SpecialTaskDmapi {
	var $id;
	var $customerid;
	var $specialtaskid;
	var $shardid;
	var $type;
}

// read one task from table then delete the row
function assignTask ($dmapidb) {

	//because we might use persistent connections, ensure that we rollback no matter what
	function ensure_rollback_task($dmapidb) {
		mysql_query("unlock tables", $dmapidb);
	}
	register_shutdown_function("ensure_rollback_task",$dmapidb);

	mysql_query("lock table jobtaskactive write", $dmapidb);

	//get the id of next available task
	//update the task to assign it to us
	$query = "select id,customerid,shardid,tasktime,renderedmessage from jobtaskactive order by tasktime limit 1";

	$res = mysql_query($query,$dmapidb) or error_log("mysql had a problem: " . mysql_error());
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

	mysql_query("unlock tables", $dmapidb);

	return $task;
}

// read one specialtask from table then delete the row
function assignSpecialTask ($dmapidb) {
	//because we might use persistent connections, ensure that we rollback no matter what
	function ensure_rollback($dmapidb) {
		mysql_query("unlock tables", $dmapidb);
	}
	register_shutdown_function("ensure_rollback",$dmapidb);

	mysql_query("lock table specialtaskactive write", $dmapidb);

	//get the id of next available task
	//update the task to assign it to us
	$query = "select id,customerid,specialtaskid,shardid,type from specialtaskactive limit 1";

	$res = mysql_query($query,$dmapidb) or error_log("mysql had a problem: " . mysql_error());
	if ($row = mysql_fetch_row($res)) {
		$task = new SpecialTaskDmapi();

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

	mysql_query("unlock tables", $dmapidb);

	return $task;
}


?>