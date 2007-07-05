<?

//this file handles all of the outbound phone notifications.

include_once("dmapidb.inc.php"); //remember to use $dmapidb with all mysql calls to the dmapi db!
global $dmapidb;
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
	} else if ($foundtask = assignTask("phone",$RESOURCEID, $dmapidb)) {


		$sessid = base64url_encode(implode(":",array($foundtask->id,$foundtask->customerid,$foundtask->shardid,ceil(microtime(true)*1000))));

		//add the sessionid to the voice tag
		echo str_replace("<voice>","<voice sessionid=\"outbound_$sessid\">",$foundtask->renderedmessage);

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
	$sessid = substr($SESSIONID,9); //trim off "outbound_" from the sessionid

	//parse out the bits:

	list($taskid,$customerid,$shardid,$tasktime) = explode(":",base64url_decode($sessid));

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

	$callduration = (isset($BFXML_VARS['callduration']) ? $BFXML_VARS['callduration'] + 0 : 0);
	$resultdata = mysql_real_escape_string(http_build_query($BFXML_VARS,'','&'),$dmapidb);


	//insert into jobtaskcomplete
	$query = "insert into jobtaskcomplete (id,customerid,shardid,starttime,duration,result,resultdata) values "
			."('" . mysql_real_escape_string($taskid,$dmapidb) . "',"
			."'" .  mysql_real_escape_string($customerid,$dmapidb) . "',"
			."'" .  mysql_real_escape_string($shardid,$dmapidb) . "',"
			."'" .  mysql_real_escape_string($tasktime,$dmapidb) . "',"
			."'" .  mysql_real_escape_string($callduration,$dmapidb) . "',"
			."'" .  mysql_real_escape_string($callprogress,$dmapidb) . "',"
			."'" .  mysql_real_escape_string($resultdata,$dmapidb) . "')";

	$res = mysql_query($query);
	$rowsaffected = mysql_affected_rows();
	if ($rowsaffected != 1) {
		error_log("Something bad happened to the data while we were trying to post results! sesisonid=$taskid. rows affected: $rowsaffected. mysql_error:" . mysql_error());
	}
?>
			<ok />
<?

}

$SESSIONID = NULL; //tell session machine we dont need to store session data


?>