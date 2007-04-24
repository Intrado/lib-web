<?

//attempt to assign a task by setting the status to calling.
//TODO, check to see if this request is a failover request, and possible delay it until the DBs are syned up
//so that we don't assign any tasks that might have already been assigned from the failed node that are waiting in our mysql log.
function assignTask ($type, $resourceid, $dmapidb) {

	//because we might use persistent connections, ensure that we unlock the tables no matter what
	function ensure_rollback_tables($dmapidb) {
		mysql_query("rollback", $dmapidb);
	}
	register_shutdown_function("ensure_rollback_tables",$dmapidb);

	mysql_query("begin", $dmapidb);

	//get the id of next available task
	//update the task to assign it to us
	$query = "select id from jobtaskactive where status='new' and type='$type' and tasktime > date_sub(now(), interval 60 second) "
			. " and assignmentgroup = " . $resourceid % 2 . " order by tasktime limit 1 for update";

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

	mysql_query("commit", $dmapidb);
	
	return $gotatask ? $foundid : false;
}


function assignSpecialTask ($types, $dmapidb) {
	$success = false;
	foreach($types as $specialtype) {
		//when was the last time we checked the specialtasks table? We shouldn't check more than once every 5 seconds
		$query = "select count(*) from tasksyncdata where name='specialtaskcheck_" . $specialtype ."' and value > (now() - interval 5 second)";
		$res = DBQueryWrapper($query, $dmapidb);
		$row = DBGetRow($res);
		if ($row[0] == 0) {
			//check for a special task, if we dont fine one, update the table

			QuickUpdate("begin");
			$res = QuickQueryRow("select id, type from specialtask where status='queued' and type in ('$specialtype') limit 1 for update");
			if ($res) {
				list($id,$type) = $res;
				if (QuickUpdate("update specialtask set status='assigned' where id=$id")) {
					$success = true;
				}
			} else {
				$query = "insert into tasksyncdata (name,value) values
						('specialtaskcheck_" . $specialtype ."',now()) on duplicate key update value=now()";
				DBQueryWrapper($query, $dmapidb);
			}
			QuickUpdate("commit");
		}
		if($success)
			break;
	}
	if (!$success)
		return false;
	else
		return array($id,$type);
}


?>