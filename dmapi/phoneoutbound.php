<?

//this file handles all of the outbound phone notifications.

include_once("dmapidb.inc.php"); //remember to use $dmapidb with all mysql calls to the dmapi db!
include_once("notifications.inc.php");

if ($REQUEST_TYPE == "new") {

	//check for specialtasks
	if ($specialtask = assignSpecialTask(array("EasyCall", "CallMe"),$dmapidb)) {
		list($id,$type) = $specialtask;
		$SESSIONDATA['specialtaskid'] = $id;

		switch ($type) {
			case "EasyCall":
				forwardToPage("easycall.php");
				return;
			case "CallMe":
				forwardToPage("callme.php");
				return;
			default:
?>
			<error>Wrong specialtask type</error>
<?
		}
	//check for normal tasks
	} else if ($foundid = assignTask("phone",$RESOURCEID, $dmapidb)) {

		//we assigned one, now grab it from the DB and send it out
		$query = "select renderedmessage from jobtaskactive where id='" . mysql_real_escape_string($foundid,$dmapidb) . "'";

		$res = mysql_query($query,$dmapidb);
		$row = mysql_fetch_row($res);
		$messagedata = $row[0];
		mysql_free_result($res);

		//add the sessionid to the voice tag
		echo str_replace("<voice>","<voice sessionid=\"outbound_$foundid\">",$messagedata);

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

	//trim off the "outbound_" marker from the sessionid
	$taskid = substr($SESSIONID,9); //trim off "outbound_" from the sessionid

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
	$resultdata = mysql_real_escape_string(http_build_query($BFXML_VARS,'','&'),$dmapidb);

	//try to find this sessionid first
	$query = "select count(*) from jobtaskactive where id='" . mysql_real_escape_string($taskid,$dmapidb) . "'";
	$res = mysql_query($query,$dmapidb);
	$row = mysql_fetch_row($res);
	if ($row[0]) {

		$query = "update jobtaskactive set
				callprogress='$callprogress', resultdata='$resultdata', duration='$callduration',
				status='done' where id='" . mysql_real_escape_string($taskid,$dmapidb) . "'";
		$res = mysql_query($query);
		$rowsaffected = mysql_affected_rows();
		if ($rowsaffected != 1) {
			error_log("Something bad happened to the data while we were trying to post results! sesisonid=$taskid. rows affected: $rowsaffected. mysql_error:" . mysql_error());
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