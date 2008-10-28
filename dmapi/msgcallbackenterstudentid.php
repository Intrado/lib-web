<?
include_once("inboundutils.inc.php");

global $BFXML_VARS;


function makenumeric($studentid){
	$studentid = strtolower($studentid);
	$numeric = "";
	$tmp = "";
	while($studentid != "") {
		$tmp = substr($studentid,0,1);
		if (is_numeric($tmp)) {
			$numeric .= $tmp;
		} elseif ($tmp == 'a' || $tmp == 'b' || $tmp == 'c') {
			$numeric .= "2";
		} elseif ($tmp == 'd' || $tmp == 'e' || $tmp == 'f') {
			$numeric .= "3";
		} elseif ($tmp == 'g' || $tmp == 'h' || $tmp == 'i') {
			$numeric .= "4";
		} elseif ($tmp == 'j' || $tmp == 'k' || $tmp == 'l') {
			$numeric .= "5";
		} elseif ($tmp == 'm' || $tmp == 'n' || $tmp == 'o') {
			$numeric .= "6";
		} elseif ($tmp == 'p' || $tmp == 'q' || $tmp == 'r' || $tmp == 's') {
			$numeric .= "7";
		} elseif ($tmp == 't' || $tmp == 'u' || $tmp == 'v') {
			$numeric .= "8";
		} elseif ($tmp == 'w' || $tmp == 'x' || $tmp == 'y' || $tmp == 'z') {
			$numeric .= "9";
		}
		$studentid = substr($studentid,1);
	}
	return $numeric;
}


function enterstudentid($error, $studentids) {
glog("enterstudentid");
?>
<voice>
	<message name="choosestudentid">
			<field type="dtmf" name="studentid" timeout="5000">
			<prompt repeat="2">
			    <tts gender="female" language="english">Please enter the numeric Student I. D. for one of your students, followed by the pound key.</tts>
			</prompt>
			<?
				while($row = DBGetRow($studentids)) {
					$numeric = makenumeric($row[0]);
					glog("Student id: $row[0] amd numeric: $numeric");
					?>
					<choice digits="<? echo $numeric; ?>">
						<setvar name="success" value="1" />
						<tts gender="female" language="english">You have entered a correct student I. D.</tts>
					</choice>

					<?

				}
			?>
			<default>
	        	<tts gender="female" language="english">Sorry. That student number did not match our records. Remember that letters in the students I. D. correspond to a number on you phone. For example the letter A. is represented by the number 2. </tts>
			</default>
			<timeout>
				<tts gender="female" language="english">I was not able to understand your response, goodbye</tts>
				<hangup />
			</timeout>
		</field>
	</message>
</voice>
<?
}

function hangup() {
?>
<voice>
	<message name="hangup">
	       	<tts gender="female">Thank you. Goodbye </tts>
	       	<hangup />

	</message>
</voice>
<?
}

if($REQUEST_TYPE == "new") {
	forwardToPage("inboundstart.php");
} else if($REQUEST_TYPE == "continue") {

	if(isset($BFXML_VARS['studentid']) && isset($BFXML_VARS['success'])){
		glog("Entered a valid student id: implement forward");
		forwardToPage("msgcallbackgetlist.php");
	} else {
		glog("student id continue");
		$phonenumber = $_SESSION['contactphone'];
		$query = "select y.pkey as id from phone x, person y where x.phone=$phonenumber and x.personid=y.id and y.pkey is not null";
		glog("Query $query");
		$results = Query($query);

		if ($results) {
			enterstudentid(0,$results);
		} else {
			glog("SQL: no result hang up");
			hangup();
		}
	}

}




 ?>