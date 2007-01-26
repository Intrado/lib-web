<?

//this file handles all of the outbound phone notifications.

include_once("dmapidb.inc.php"); //remember to use $dmapidb with all mysql calls to the dmapi db!
include_once("notifications.inc.php");

if ($REQUEST_TYPE == "new") {


	//check for specialtasks
	if ($specialtask = assignSpecialTask(array("Email"),$dmapidb)) {
		list($id,$type) = $specialtask;
		$SESSIONDATA['specialtaskid'] = $id;

		forwardToPage("specialemail.php");
		return;

	//check for normal tasks
	} else if ($foundid = assignTask("email",$RESOURCEID,$dmapidb)) {
		//we assigned one, now grab it from the DB and send it out

		//TODO handle specialtasks (hand off to another file) we can tell by the subtype != notification

		$query = "select renderedmessage from jobtaskactive where id='" . mysql_real_escape_string($foundid,$dmapidb) . "'";

		$res = mysql_query($query,$dmapidb);
		$row = mysql_fetch_row($res);
		$messagedata = $row[0];
		mysql_free_result($res);

		//add the sessionid to the email tag
		echo str_replace("<email>","<email sessionid=\"outbound_$foundid\">",$messagedata);

	} else {
?>
		<notask />
<?
	}
} else if ($REQUEST_TYPE == "result") {

	//now we should update the task info with the results

	//trim off the "outbound_" marker from the sessionid
	$taskid = substr($SESSIONID,9); //trim off "outbound_" from the sessionid

	//TODO read and do something with the email sent=true|false info, for now just don't report on it

	$resultdata = mysql_real_escape_string(http_build_query($BFXML_VARS,'','&'),$dmapidb);

	//try to find this sessionid first
	$query = "select count(*) from jobtaskactive where id='" . mysql_real_escape_string($taskid,$dmapidb) . "'";
	$res = mysql_query($query,$dmapidb);
	$row = mysql_fetch_row($res);
	if ($row[0]) {

		$query = "update jobtaskactive set resultdata='$resultdata', status='done' "
				. "where id='" . mysql_real_escape_string($taskid,$dmapidb) . "'";
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

} else {
	//no continue requests for email
}

$SESSIONID = NULL; //tell session machine we dont need to store session data


?>