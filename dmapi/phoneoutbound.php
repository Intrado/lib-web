<?

//establish a DB connection to the dmapi db using mysqli
//remember we can't mix DB functions like DBSafe,Query, etc and need to specify


//is there an alternate DB to use for the dmapi?
if (isset($SETTINGS['dmapidb']['host'])) {
	$db = $SETTINGS['dmapidb'];

	if ($db['persistent'])
		$dmapidb = mysql_pconnect($db['host'], $db['user'], $db['pass']);
	else
		$dmapidb = mysql_connect($db['host'], $db['user'], $db['pass']);
	if (!$dmapidb) {
		error_log("Problem connecting to MySQL server at " . $db['host'] . " error:" . mysql_error());
?>
		<error>Something happened connecting to the DB</error>
<?
		return;
	}

	if (!mysql_select_db($db['db'])) {
		error_log("Problem selecting databse for " . $db['host'] . " error:" . mysql_error());
?>
		<error>Something happened connecting to the DB</error>
<?
		return;
	}
} else {
	global $_dbcon;
	$dmapidb = $_dbcon; //just set it to the same as the default DB connection
}


if ($REQUEST_TYPE == "new") {
	//assign a task to us by changing the ID to something new and setting the status to calling.
	//changing the ID will ensure that any DM result comming back will fail (and be retried)
	//until the failover node has synched this assignment statement. it will also prevent result data from overwriting
	//another assignment that may have taken place in the failover.
	//TODO, check to see if this request is a failover request, and possible delay it until the DBs are syned up
	//so that we don't assign any tasks that might have already been assigned from the failed node that are waiting in our mysql log.


	//because we might use persistent connections, ensure that we unlock the tables no matter what
	function ensure_unlock_tables($dmapidb) {
		mysql_query("unlock tables", $dmapidb);
	}
	register_shutdown_function("ensure_unlock_tables",$dmapidb);

	mysql_query("lock tables jobtaskactive write", $dmapidb);

	//get the id of next available task
	//update the task to assign it to us
	$query = "select id from jobtaskactive where status='new' and tasktime > date_sub(now(), interval 60 second) "
			. " and assignmentgroup = " . $RESOURCEID % 2 . " order by tasktime limit 1";
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

	if ($gotatask) {
		//we assigned one, now grab it from the DB and send it out
		$query = "select renderedmessage from jobtaskactive where id='" . mysql_real_escape_string($foundid,$dmapidb) . "'";

		$res = mysql_query($query,$dmapidb);
		$row = mysql_fetch_row($res);
		$messagedata = $row[0];
		mysql_free_result($res);

		//add the sessionid to the voice tag
		echo str_replace("<voice>","<voice sessionid=\"$foundid\">",$messagedata);

	} else {
?>
		<notask />
<?
	}
} else if ($REQUEST_TYPE == "continue") {
?>
	<voice sessionid="<?= $SESSIONID ?>">
		<message name="hangup">
			<hangup />
		</message>
	</voice>
<?
} else {

	//now we should update the task info with the results

	$cpcodes = array("answered" => "A",
					"machine" => "M",
					"failed" => "F",
					"busy" => "B",
					"noanswer" => "N",
					"trunkbusy" => "F",
					);

	if (isset($cpcodes[$BFXML_VARS['callprogress']])) {
		$callprogress = $cpcodes[$BFXML_VARS['callprogress']];
	} else {
		$callprogress = "F";
	}

	$callduration = $BFXML_VARS['callduration'] + 0;
	$resultdata = mysql_real_escape_string(http_build_query($BFXML_VARS),$dmapidb);

	//try to find this sessionid first
	$query = "select count(*) from jobtaskactive where id='" . mysql_real_escape_string($SESSIONID,$dmapidb) . "'";
	$res = mysql_query($query,$dmapidb);
	$row = mysql_fetch_row($res);
	if ($row[0]) {

		$query = "update jobtaskactive set
				callprogress='$callprogress', resultdata='$resultdata', duration='$callduration',
				status='done' where id='" . mysql_real_escape_string($SESSIONID,$dmapidb) . "'";
		$res = mysql_query($query);
		$rowsaffected = mysql_affected_rows();
		if ($rowsaffected != 1) {
			error_log("Something bad happened to the data while we were trying to post results! sesisonid=$SESSIONID. rows affected: $rowsaffected. mysql_error:" . mysql_error());
		}
?>
			<ok />
<?
	} else {
?>
		<error>Cant find that sessionid!</error>
<?
	}

}

$SESSIONID = NULL; //tell session machine we dont need to store session data


?>