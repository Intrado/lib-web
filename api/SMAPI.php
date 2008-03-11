<?

class SMAPI{

	function login($loginname, $password){
		global $IS_COMMSUITE;
		//get the customer URL
		if ($IS_COMMSUITE) {
			$CUSTOMERURL = "default";
		} /*CSDELETEMARKER_START*/ else {
			$CUSTOMERURL = substr($_SERVER["SCRIPT_NAME"],1);
			$CUSTOMERURL = strtolower(substr($CUSTOMERURL,0,strpos($CUSTOMERURL,"/")));
		} /*CSDELETEMARKER_END*/

		$userid = doLogin($loginname, $password, $CUSTOMERURL, $_SERVER['REMOTE_ADDR']);
		if($userid == -1){
			return new SoapFault("Server", "User is locked out");
		} else if ($userid){
			doStartSession();
			loadCredentials($userid);
			return session_id();
		}

		return new SoapFault("Server", "Invalid LoginName/Password combination");
	}

	function getLists($sessionid){
		global $USER;
		if(!APISession($sessionid)){
			return new SoapFault("Server", "Invalid Session ID");
		} else {
			$USER = &$_SESSION['user'];
			$result = Query("select id, name, description from list where userid = " . $USER->id . " and not deleted order by name");
			$lists = array();
			while($row = DBGetRow($result)){
				$lists[] = new API_List($row[0], $row[1], $row[2]);
			}
			return $lists;
		}
	}

	function getMessages($sessionid, $type = "phone"){
		global $USER;
		if(!APISession($sessionid)){
			return new SoapFault("Server", "Invalid Session ID");
		} else {
			$USER = &$_SESSION['user'];
			$result = Query("select id, name, description from message where userid = " . $USER->id . " and type= '" . $type . "' and not deleted order by name");
			$messages = array();
			while($row = DBGetRow($result)){
				$messages[] = new API_List($row[0], $row[1], $row[2]);
			}
			return $messages;
		}
	}

	function setMessageBody($sessionid, $messageid, $messagetext){
		global $USER;
		if(!APISession($sessionid)){
			return new SoapFault("Server", "Invalid Session ID");
		} else {
			$USER = $_SESSION['user'];
			if(!$messageid){
				return new SoapFault("Server", "Invalid Message ID");
			}
			if ($messagetext == ""){
				return new SoapFault("Server", "Message Text cannot be empty");
			}

			$message = new Message($messageid);
			if ($message->id && $message->userid != $USER->id || $message->deleted ) {
				return new SoapFault("Server", "Unauthorized access");
			}
			error_log($messagetext);
			$parts = $message->parse($messagetext);
			$voiceid = QuickQuery("select id from ttsvoice where language = 'english' and gender = 'female'");
			QuickUpdate("delete from messagepart where messageid=$message->id");
			foreach ($parts as $part) {
				$part->voiceid = $voiceid;
				$part->messageid = $message->id;
				$part->create();
			}
			return true;
		}
	}

	function uploadAudio($sessionid, $name, $mimetype, $audio){
		global $USER;
		if(!APISession($sessionid)){
			return new SoapFault("Server", "Invalid Session ID");
		} else {
			$USER = &$_SESSION['user'];
			if($name == "")
				return new SoapFault("Server", "Name cannot be empty");


			$content = new Content();
			$content->type = $mimetype;
			$content->data = base64_decode($audio);
			$content->create();
			if(!$content->id){
				return new SoapFault("Server", "Failed to create content");
			}
			$audiofile = new AudioFile();
			if(QuickQuery("select count(*) from audiofile where name = '" . $name . "' and not deleted")){
				$audiofile->name = $name . " - " . date("M d, Y G:i:s");
			} else {
				$audiofile->name = $name;
			}
			$audiofile->description = "Upload Audio API";
			$audiofile->recorddate = date("Y-m-d G:i:s");
			$audiofile->contentid = $content->id;
			$audiofile->userid = $USER->id;
			$audiofile->create();

			if(!$audiofile->id){
				return new SoapFault("Server", "Failed to create audio file record");
			} else {
				return $audiofile->name;
			}
		}
	}

	function getJobs($sessionid){

	}

	function getJobStatus($sessionid, $jobid){

	}

	function runRepeatingJob($sessionid, $jobid){

	}

}

// Helper Functions
function APISession($sessionid){
	global $USER;
	session_id($sessionid);
	doStartSession();
	if (!isset($_SESSION['user']) || !isset($_SESSION['access']))
		return false;
	else {
		$USER = &$_SESSION['user'];
		$USER->refresh();
		$USER->optionsarray = false; /* will be reconstructed if needed */

		$ACCESS = &$_SESSION['access'];
		$ACCESS->refresh(NULL, true);

		if (!$USER->enabled || $USER->deleted) {
			return new SoapFault("Server", "User is not valid");
		}
		return true;
	}
}

?>