<?
/*
 * SchoolMessenger Application API
 * author: Joshua J. Lai
 * Copyright Reliance Communications, Inc
 *
 * Simple api that handles basic application uses such as returning list and message names
 * with corresponding ids.  Uses soap as transport device.
 *
 *
 */

$CUSTOMERURL = substr($_SERVER["SCRIPT_NAME"],1);
$CUSTOMERURL = strtolower(substr($CUSTOMERURL,0,strpos($CUSTOMERURL,"/")));

apache_note("CS_CUST",urlencode($CUSTOMERURL)); //for logging

if (isset($_GET['wsdl'])) {
	
	$wsdl = file_get_contents("smapi.wsdl");

	//keyword stored in wsdl for service url is smapiurl
	$wsdl = preg_replace("[smapiurl]", 'https://' . $_SERVER["SERVER_NAME"] .'/' . $CUSTOMERURL . '/api/', $wsdl);

	if (isset($_GET['doc'])) { // display the documentation
		// replace comment with stylesheet
		$comment = '[<!-- stylesheet goes here -->]';
		$stylesheet = '<?xml-stylesheet type="text/xsl" href="wsdl-viewer.xsl"?>';
		$wsdl = preg_replace($comment, $stylesheet, $wsdl);

		header("Content-type: text/xml");
		echo $wsdl;
		exit();
		
	} else { // download the wsdl file
		header("Pragma: private");
		header("Cache-Control: private");
		header("Content-disposition: attachment; filename=smapi.wsdl");
		header("Content-type: text");
		echo $wsdl;
		exit();
	}
}




////////////////////////////////////////////////////////////////////////////////
// Server Class
////////////////////////////////////////////////////////////////////////////////

class ListIdList {
	var $listid; // array of int (listids)
}

class JobOptions {
	var $jobOption; // array of name/value pairs
}

class NameValuePair {
	var $name;
	var $value;
}

class SMAPI {

	function helperSetContact($sessionid, $pkey) {
		global $USER, $ACCESS;
		$result = array("resultcode" => "failure", "resultdescription" => "");

		if (!APISession($sessionid)) {
			$result['resultcode'] = 'invalidsession';
			$result["resultdescription"] = "Invalid Session ID";
			return $result;
		}
		
		$USER = $_SESSION['user'];
		$ACCESS = $_SESSION['access'];

		if (!$USER->id) {
			$result['resultcode'] = 'invalidsession';
			$result["resultdescription"] = "Invalid user for sessionid";
			return $result;
		}
		
		// user must be able to edit system contacts
		if (!$USER->authorize('managecontactdetailsettings')) {
			$result['resultcode'] = 'unauthorized';
			$result["resultdescription"] = "Unauthorized - user does not have privilege to edit contact details";
			return $result;
		}
		
		// validate the person to update
		$personid = QuickQuery("select id from person where pkey = ? and not deleted", false, array($pkey));
		if (!$personid) {
			$result['resultcode'] = 'invalidparam';
			$result["resultdescription"] = "Invalid pkey - Person does not exist";
			return $result;
		}
		
		if (!$USER->canSeePerson($personid)) {
			$result['resultcode'] = 'unauthorized';
			$result["resultdescription"] = "Unauthorized - User does not have access to update this person";
			return $result;
		}
		
		// success
		return $personid;
	}

	/*
	 * Internal helper function, creates all message parts from the provided text
	 */
	function createMessageParts ($messageid, $messagetext, $gender = 'female') {
		$errors = array();
		$voiceid = QuickQuery("select id from ttsvoice where language = 'english' and gender = ?", false, array($gender));
		$parts = Message::parse($messagetext, $errors, $voiceid);
		foreach ($parts as $part) {
			$part->messageid = $messageid;
			$part->create();
		}
	}
	
	/*
	 Given a valid oem/oemid combination, the customer url corresponding to the
	 matching customer is returned

	 getCustomerUrl:
		params: string oem, string oemid
		returns: string customerurl

		*/

	function getCustomerURL ($oem, $oemid) {
		$result = array("resultcode" => "failure", "resultdescription" => "", "customerurl" => "");

		if(trim($oem) == ""){
			$result['resultdescription'] = "Missing OEM";
			return $result;
		}
		if(trim($oemid) == ""){
			$result['resultdescription'] = "Missing OEM ID";
			return $result;
		}

		$customerurl = api_getCustomerURL(strtolower($oem), $oemid);
		if($customerurl != false){
			$result['customerurl'] = $customerurl;
			$result['resultcode'] = "success";
		} else {
			$result['resultdescription'] = "Invalid OEM/OEMID combination";
		}

		return $result;
	}


	/*
	 Given a valid loginname/password, a session id is generated and passed back.
	 If an error occurs, error will contain the error and sessionid will be empty string.

	 login:
		params: string loginname, string password
		returns: string resultcode, string resultdescription, string sessionid

		*/
	function login ($loginname, $password) {
		return systemLogin($loginname, $password);
	}

	/*
	 Given a valid loginname/password/customerurl combination, a session id is generated and passed back.
	 If an error occurs, error will contain the error and sessionid will be empty string.

	 loginToCustomer:
		params: string loginname, string password, string customerurl
		returns: string resultcode, string resultdescription, string sessionid

		*/
	function loginToCustomer ($loginname, $password, $customerurl) {
		return systemLogin($loginname, $password, $customerurl);
	}

	/*
	 Given a valid sessionid, an array of lists will be returned.
	 If error occurs, error will contain error string and lists will not be set.

	 getLists:
		params: string sessionid
		returns:
		lists: array of lists
		resultcode: string
		resultdescription: string

		*/
	function getLists ($sessionid) {
		global $USER;
		$result = array("resultcode" => "failure", "resultdescription" => "", "lists" => array());

		if(!APISession($sessionid)){
			$result["resultdescription"] = "Invalid Session ID";
			return $result;
		} else {
			$USER = $_SESSION['user'];
			if(!$USER->id){
				$result["resultdescription"] = "Invalid user";
				return $result;
			}
			$queryresult = Query("select l.id, l.name, l.description 
				from list l 
					inner join user u on
						(l.userid = u.id)
					left join publish p on
						(p.listid = l.id and p.userid = ?)
				where l.type in ('person','section')
					and (l.userid = ? or p.userid = ?)
					and not l.deleted
				group by id
				order by l.name", false, array($USER->id, $USER->id, $USER->id));
			$lists = array();
			while($row = DBGetRow($queryresult)){
				$list = new API_List();
				$list->id = $row[0];
				$list->name = $row[1];
				$list->description = $row[2];
				$lists[] = $list;
			}
			$result["resultcode"] = "success";
			$result["lists"] = $lists;
			return $result;
		}
	}

	/*
	 Given a valid sessionid and a valid message type, an array of messages will be returned.
	 If error occurs, error will contain error string and messages will not be set.

	 Valid messages types are:
		phone
		email
		sms

		getMessages:
		params: string sessionid, string message type
		returns:
		messages: array of messages
		resultcode: string
		resultdescription: string

		*/
	function getMessages ($sessionid, $type = "phone") {
		global $USER;
		$result = array("resultcode" => "failure","resultdescription" => "", "messages" => array());

		if(!APISession($sessionid)){

			$result["resultdescription"] = "Invalid Session ID";
			return $result;
		} else {
			$USER = $_SESSION['user'];
			if(!$USER->id){

				$result["resultdescription"] = "Invalid user";
				return $result;
			}
			
			$queryresult = Query("
				select
					m.id, m.name, m.description, m.type
				from
					message m inner join messagegroup mg
						on (m.messagegroupid = mg.id)
				where
					mg.userid = ? and
					mg.type = 'notification' and
					m.type = ? and
					m.autotranslate != 'source' and
					not mg.deleted
				order by
					m.name", false, array($USER->id, strtolower($type)));
			
			$messages = array();
			while($row = DBGetRow($queryresult)){
				$message = new API_Message();
				$message->id = $row[0];
				$message->name = $row[1];
				$message->description = $row[2];
				$message->type = $row[3];
				$messages[] = $message;
			}
			$result["resultcode"] = "success";
			$result["messages"] = $messages;
			return $result;
		}
	}

	/*
	 Given a valid sessionid, messageid and message text, the specified message
	 will have its text replaced.  If successful, result will be true.
	 If error occurs, error will contain error string and result will be false.

	 setMessageBody:
		params: string sessionid, int message id, string message text
		returns: boolean result, string resultcode, string resultdescription,

		*/

	function setMessageBody ($sessionid, $messageid, $messagetext) {
		global $USER;
		$result = array("resultcode" => "failure","resultdescription" => "", "result" => false);

		if(!APISession($sessionid)){
			$result["resultdescription"] = "Invalid Session ID";
			return $result;
		} else {
			$USER = $_SESSION['user'];
			if(!$USER->id){
				$result["resultdescription"] = "Invalid user";
				return $result;
			}

			if(!$messageid){
				$result["resultdescription"] = "Invalid Message ID";
				return $result;
			}
			if ($messagetext == ""){
				$result["resultdescription"] = "Message Text cannot be empty";
				return $result;
			}

			$message = new Message($messageid);
			if ($message->id && $message->userid != $USER->id) {
				$result["resultdescription"] = "Unauthorized access";
				return $result;
			}
			// validate user has privilage to update this type of message
			switch ($message->type) {
				case "phone":
					if (!$USER->authorize('sendphone')) {
						$result["resultdescription"] = "Unauthorized - user does not have privilege to edit phone messages";
						return $result;
					}
					break;
				case "email":
					if (!$USER->authorize('sendemail')) {
						$result["resultdescription"] = "Unauthorized - user does not have privilege to edit email messages";
						return $result;
					}
					
					// if plain text email, be sure to set 'overrideplaintext' so the GUI checkmark will display
					if ($message->subtype == 'plain' && strpos($message->data, 'overrideplaintext=1') === false) {
						$message->readHeaders();
						$message->overrideplaintext = '1';
						$message->stuffHeaders();
						$message->update();
					}
					// else html, simply set the body assuming they know what they are doing
					break;
				case "sms":
					if (!getSystemSetting('_hassms') || !$USER->authorize('sendsms')) {
						$result["resultdescription"] = "Unauthorized - user does not have privilege to edit sms messages";
						return $result;
					}
					break;
				default:
					$result["resultdescription"] = "Invalid message type : " . $message->type;
					return $result;
			}

			// NOTE: Assumes tts message is English
			// because messageparts are recreated with the English female voice.
			// However, if an audiofile is uploaded, the messagepart's voiceid doesn't matter so
			// it's still possible for the $message->languagecode to not be 'en',
			// which is why we don't check explicitly for $message->languagecode == 'en'.
			if ($message->autotranslate == 'translated' || $message->autotranslate == 'source') {
				$result["resultdescription"] = "Translation message cannot be edited.";
				return $result;
			}

			// delete all old parts
			QuickUpdate("delete from messagepart where messageid=$message->id");
			
			// recreate new parts, if sms only one part, else parse() parts to create
			if ($message->type == "sms") {
				if (strlen($messagetext) > 160) {
					$messagetext = substr($messagetext, 0, 160);
				}
				$part = new MessagePart();
				$part->messageid = $message->id;
				$part->txt = $messagetext;
				$part->type = "T";
				$part->sequence = 0;
				$part->create();
			} else {
				$errors = array();
				$voiceid = QuickQuery("select id from ttsvoice where language = 'english' and gender = 'female'");
				$parts = Message::parse($messagetext, $errors, $voiceid);
				foreach ($parts as $part) {
					$part->messageid = $message->id;
					$part->create();
				}
			}
			$result["resultcode"] = "success";
			$result["result"] = true;
			return $result;
		}
	}
	
	/*
	 Given a valid sessionid, name, mimetype, and audio file,
	 an audio file record will be generated and its resulting
	 name returned.
	 If an error occurs, error will contain the error string and audioname will be empty.

	 IMPORTANT: the audio data should be a base64 encoded string

	 uploadAudio:
		params: string sessionid, string name, string mimetype, base64 encoded string audio
		return:
		audioname: string
		resultcode: string
		resultdescription: string

		*/

	function uploadAudio ($sessionid, $name, $audio, $mimetype) {
		global $USER, $SETTINGS;
		$result = array("resultcode" => "failure","resultdescription" => "", "audioname" => "");

		if(!APISession($sessionid)){
			$result["resultdescription"] = "Invalid Session ID";
			return $result;
		} else {
			$USER = $_SESSION['user'];
			if(!$USER->id){

				$result["resultdescription"] = "Invalid user";
				return $result;
			}
			if($name == ""){

				$result["resultdescription"] = "Name cannot be empty";
				return $result;
			}
			if($audio == ""){

				$result["resultdescription"] = "Audio data cannot be empty";
				return $result;
			}
			
			// Supported mimetypes and file extensions
			$supportedmimetypes = array(
				"audio/wav" => ".wav",
				"audio/mpeg" => ".mp3",
				"audio/x-caf" => ".caf",
				"audio/3gpp" => ".3gp",
				"audio/3gpp2" => ".3g2"
			);
			
			// Check mimetype against supported mimetypes, default to wav file extension
			$fileext = isset($supportedmimetypes[$mimetype])?$supportedmimetypes[$mimetype]:".wav";
			
			//generate 2 temp files
			//write audio data to first file then run through sox to clean audio and output to second file
			//read second file and base64 encode it for the DB
			if (!$origtempfile = secure_tmpname($name . "origtempapiaudio", $fileext)) {
				$result["resultdescription"] = "Failed to generate audio file";
				return $result;
			}
			if (!$cleanedtempfile = secure_tmpname("cleanedtempapiaudio", ".wav")) {
				$result["resultdescription"] = "Failed to generate audio file";
				return $result;
			}

			//delete temp file that secure_tmpname generated so we can check if sox generated a file later
			unlink($cleanedtempfile);

			if (!file_put_contents($origtempfile, base64_decode($audio))) {
				$result['resultdescription'] = "Failed to generate audio file";
				return $result;
			}
			
			if ($mimetype == 'audio/x-caf' || $mimetype == 'audio/3gpp' || $mimetype == 'audio/3gpp2') {
				$cmd = "ffmpeg -i \"$origtempfile\" -ar 8000 -ac 1 \"$cleanedtempfile\" 2>/dev/null"; //  the 2>/dev/null is to make ffmpeg silent, there Is no other way of making it silent with a flag. 
				$ffmpegresult = exec($cmd, $res1,$res2);
			} else {
				$cmd = "sox \"$origtempfile\" -r 8000 -c 1 -s -w \"$cleanedtempfile\" ";
				//error_log($cmd);
				$soxresult = exec($cmd, $res1,$res2);
				//error_log("output " . $res1[0]);
			}
			
			$contentid = false;
			if ($res2 || !file_exists($cleanedtempfile)) {
				$result["resultdescription"]= "There was an error reading your audio file. Please try another file, or ensure the mimetype is correct. Supported mimetypes include: 'audio/wav' for .wav, 'audio/mpeg' for .mp3, and 'audio/x-caf' for .caf files.";
				unlink($origtempfile);
				unlink($cleanedtempfile);
				return $result;
			} else {
				
				// even if they upload .mp3 or .caf we store it as .wav
				$contentid = contentPut($cleanedtempfile, "audio/wav");

				unlink($origtempfile);
				unlink($cleanedtempfile);
			}
			if (!$contentid) {

				$result["resultdescription"] = "Failed to create audio file record";
				return $result;
			}
			$audiofile = new AudioFile();
			if(QuickQuery("select count(*) from audiofile where name = '" . DBsafe($name) . "' and not deleted")){
				$audiofile->name = $name . " - " . date("M j, Y G:i:s");
			} else {
				$audiofile->name = $name;
			}
			$audiofile->description = "Upload Audio API";
			$audiofile->recorddate = date("Y-m-d G:i:s");
			$audiofile->contentid = $contentid;
			$audiofile->userid = $USER->id;
			$audiofile->create();

			if(!$audiofile->id){
				$result["resultdescription"] = "Failed to create audio file record";
			} else {
				$result["resultcode"] = "success";
				$result["audioname"] = $audiofile->name;
			}
			return $result;
		}
	}

	/*
	 Given a valid sessionid, an array of jobtypes will be returned.
	 If an error occurs, error will contain the error string and jobtypes will not be set.

	 getJobTypes:
		params: string sessionid
		return:
		jobtypes: array of jobtypes
		resultcode: string
		resultdescription: string

		*/
	function getJobTypes ($sessionid) {
		global $USER;
		$result = array("resultcode" => "failure","resultdescription" => "", "jobtypes" => array());

		if(!APISession($sessionid)){

			$result["resultdescription"] = "Invalid Session ID";

			return $result;
		} else {
			$USER = $_SESSION['user'];
			if(!$USER->id){

				$result["resultdescription"] = "Invalid user";
				return $result;
			}

			$userjobtypes = JobType::getUserJobTypes();
			foreach($userjobtypes as $userjobtype){
				$jobtype = new API_Jobtype();
				$jobtype->id = $userjobtype->id;
				$jobtype->name = $userjobtype->name;
				$jobtype->info = $userjobtype->info;
				$jobtypes[] = $jobtype;
			}
			$result["resultcode"] = "success";
			$result["jobtypes"] = $jobtypes;
			return $result;
		}
	}

	/*
	 Given a valid sessionid, an array of jobtypes will be returned.
	 If an error occurs, error will contain the error string and jobtypes will not be set.

	 getJobTypes:
		params: string sessionid
		return:
		jobtypes: array of jobtypes
		resultcode: string
		resultdescription: string

		*/
	function getSurveyJobTypes ($sessionid) {
		global $USER;
		$result = array("resultcode" => "failure","resultdescription" => "", "jobtypes" => array());

		if(!APISession($sessionid)){

			$result["resultdescription"] = "Invalid Session ID";

			return $result;
		} else {
			$USER = $_SESSION['user'];
			if(!$USER->id){

				$result["resultdescription"] = "Invalid user";
				return $result;
			}

			$userjobtypes = JobType::getUserJobTypes(true);
			foreach($userjobtypes as $userjobtype){
				$jobtype = new API_Jobtype();
				$jobtype->id = $userjobtype->id;
				$jobtype->name = $userjobtype->name;
				$jobtype->info = $userjobtype->info;
				$jobtypes[] = $jobtype;
			}
			$result["resultcode"] = "success";
			$result["jobtypes"] = $jobtypes;
			return $result;
		}
	}

	/*
	 Given a valid sessionid, an array of jobs will be returned.
	 If an error occurs, error will contain the error string and jobs will not be set.

	 getActiveJobs:
	 params: string sessionid
	 return:
		jobs: array of job objects,
		resultcode: string
		resultdescription: string

		*/

	//jobid, name, desc, total, remaining
	function getActiveJobs ($sessionid) {
		global $USER;
		$result = array("resultcode" => "failure","resultdescription" => "", "jobs" => array());

		if(!APISession($sessionid)){

			$result["resultdescription"] = "Invalid Session ID";
			return $result;
		} else {
			$USER = $_SESSION['user'];
			if(!$USER->id){

				$result["resultdescription"] = "Invalid user";
				return $result;
			}
			$result["resultcode"] = "success";
			$result["jobs"] = getJobData();
			return $result;
		}
	}

	/*
	 Given a valid sessionid and jobid, a single job will be returned.
	 If an error occurs, error will contain the error string and job will not be set.

	 getJobStatus:
		params: string sessionid
		return:
		job: a job object,
		resultcode: string
		resultdescription: string

		*/

	function getJobStatus ($sessionid, $jobid) {
		global $USER;
		$result = array("resultcode" => "failure","resultdescription" => "", "job" => null);
		if(!APISession($sessionid)){

			$result["resultdescription"] = "Invalid Session ID";
			return $result;
		} else {
			$USER = $_SESSION['user'];
			if(!$USER->id){

				$result["resultdescription"] = "Invalid user";
				return $result;
			}
			$result["resultcode"] = "success";
			$result["job"] = getJobData($jobid);
			return $result;
		}
	}

	/*
	 Given a valid sessionid, an array of jobs will be returned.
	 If an error occurs, error will contain the error string and jobs will not be set.

	 getRepeatingJobs:
		params: string sessionid
		return:
		jobs: array of job objects,
		resultcode: string
		resultdescription: string

		*/
	function getRepeatingJobs ($sessionid) {
		global $USER;
		$result = array("resultcode" => "failure","resultdescription" => "", "jobs" => array());
		if(!APISession($sessionid)){

			$result["resultdescription"] = "Invalid Session ID";
			return $result;
		} else {
			$USER = $_SESSION['user'];
			if(!$USER->id){

				$result["resultdescription"] = "Invalid user";
				return $result;
			}

			$queryresult = Query("select id, name, description, messagegroupid
									from job where status = 'repeating' and type != 'alert' and userid = ? order by finishdate asc", false, array($USER->id));
			
			$hassms = getSystemSetting('_hassms');
			$multilingual = $USER->authorize('sendmulti');
			$jobs = array();
			while($row = DBGetRow($queryresult)){
				// NOTE: We don't want to return the autotranslate='source' messages because the autotranslate='translate' message would be included anyway.
				$defaultlanguagecode = QuickQuery('select defaultlanguagecode from messagegroup where id = ?', false, array($row[3]));
				$messages = DBFindMany('Message', 'from message where not autotranslate="source" and messagegroupid = ? ', false, array($row[3]));
				
				$job = new API_Job();
				$job->id = $row[0];
				$job->name = $row[1];
				$job->description = $row[2];

				$apimessages = array(); // Example: $apimessages[$message->type] = array(new API_Message(), new API_Message());
				foreach ($messages as $message) {
					if (!$hassms && $message->type == 'sms')
						continue;
					if (!$multilingual && $message->languagecode != $defaultlanguagecode)
						continue;
					
					$apimessage = new API_Message();
					$apimessage->id = $message->id;
					$apimessage->name = $message->name;
					$apimessage->type = $message->type;
					// NOTE: We just want to return the language name; the rest of the smapi does not accept language code nor language name as input.
					$apimessage->language = ($message->languagecode == $defaultlanguagecode) ? 'default' : Language::getName($message->languagecode);
					
					if (!isset($apimessages[$message->type]))
						$apimessages[$message->type] = array();
						
					$apimessages[$message->type][] = $apimessage;
				}

				$job->phonemessages = isset($apimessages['phone']) ? $apimessages['phone'] : array();
				$job->emailmessages = isset($apimessages['email']) ? $apimessages['email'] : array();
				$job->smsmessages = isset($apimessages['sms']) ? $apimessages['sms'] : array();
				$jobs[] = $job;
			}

			$result["resultcode"] = "success";
			$result["jobs"] = $jobs;
			return $result;
		}
	}

	/*
	 Given a valid sessionid and jobid, the active job's id will be returned
	 If an error occurs, error will contain the error string and jobid will be 0.

	 sendRepeatingJob:
		params: string sessionid
		return: int jobid, string resultcode, string resultdescription,

		*/

	function sendRepeatingJob ($sessionid, $jobid) {
		global $USER;
		$result = array("resultcode" => "failure","resultdescription" => "", "jobid" => 0);
		if(!APISession($sessionid)){
			$result["resultdescription"] = "Invalid Session ID";
			return $result;
		} else {

			$USER = $_SESSION['user'];
			if(!$USER->id){
				$result["resultdescription"] = "Invalid user";
				return $result;
			}
			if(!$jobid){
				$result["resultdescription"] = "Invalid Job ID";
				return $result;
			}
			$job = new Job($jobid);

			if($job->userid != $USER->id){
				$result["resultdescription"] = "Unauthorized access";
				return $result;
			}
			if($job->status != "repeating"){
				$result["resultdescription"] = "Invalid Repeating Job";
				return $result;
			}
			if(getSystemSetting("disablerepeat")){
				$result["resultdescription"] = "Repeating jobs are disabled";
				return $result;
			}
			$newjob = $job->runNow();
			$result["resultcode"] = "success";
			$result["jobid"] = $newjob->id;
			return $result;
		}
	}
	/*
	 Given a valid sessionid, name, description, listid, jobtypeid,
	 startdate, starttime, endtime, number of days to run, optional phone message id,
	 optional email id, optional sms message id, and max call attempts,
	 a job will be created and set to active.  The job id will be returned.
	 If an error occurs, error will contain the error string and jobid will be 0.

	 sendJob:
		params: string sessionid
		string name
		string description
		int listid
		int jobtypeid
		string startdate
		string starttime
		string endtime
		int number of days to run
		int phone message id
		int email message id
		int sms message id
		int max call attempts
		return: int jobid, string resultcode, string resultdescription,

		*/


	function sendJob ($sessionid, $name, $desc, $listid, $jobtypeid, $startdate, $starttime, $endtime, $daystorun, $phonemsgid, $emailmsgid, $smsmsgid, $maxcallattempts ){
		// put the single listid onto an array (to support multi-list via extended job)
		$listids = new ListIdList();
		$lids = array();
		$lids[] = $listid;
		$listids->listid = $lids;
		
		// create empty job options, all optional values for extended job
		$options = new JobOptions();
		$options->jobOption = array();
		
		$result = $this->sendJobExtended($sessionid, $name, $desc, $listids, $jobtypeid, $startdate, $starttime, $endtime, $daystorun, $phonemsgid, $emailmsgid, $smsmsgid, $maxcallattempts, $options);
		if ($result['resultcode'] != 'success') {
				// API v1.0 only used 'failure' and no other codes, so roll them all back to 'failure'
				$result['resultcode'] = 'failure';
		}
		return $result;
	}

	/*
		Given a valid session id the labels are returned

		getLabels
		params: String sessionid

		return:
		Labels: array of label objs

		*/

	function getLabels ($sessionid,$type){
		global $USER, $ACCESS;
		$result = array("resultcode" => "failure","resultdescription" => "", "labels" => null);

		if(!APISession($sessionid)){
			$result["resultdescription"] = "Invalid Session ID";
			return $result;
		} else {
			$USER = $_SESSION['user'];
			$ACCESS = $_SESSION['access'];

			if(!$USER->id){
				$result["resultdescription"] = "Invalid user";
				return $result;
			}
				
			$query = "select sequence, label from destlabel ";
			if($type == "phone" || $type == "email" || $type == "sms"){
				$query .= "where type='$type'";
			}else{
				$result["resultdescription"] = "Invalid type";
				return $result;
			}
			$queryresult = Query($query);
			$labels = array();
			while($row = DBGetRow($queryresult)){
				$label = new API_Label();
				$label->sequence = $row[0];
				$label->label = $row[1];
				$labels[] = $label;
			}

			$result['labels'] = $labels;
			$result['resultcode'] = "success";
			return $result;
		}
	}

	/*
		Given a valid session id and pkey, an array of contact objects is returned.
		A contact object contains a pkey, contact type(phone,email,sms), sequence number,
		contact information, and an array of contact preference objects.  A contact
		preference object contains a job type id and an enabled flag(boolean).

		The max number of contacts are always returned.

		getContacts
		params: String sessionid
		String pkey

		return:
		contacts: array of contact objs

		*/

	function getContacts ($sessionid, $pkey){
		global $USER, $ACCESS;
		$result = array("resultcode" => "failure","resultdescription" => "", "contacts" => null);

		if(!APISession($sessionid)){
			$result["resultdescription"] = "Invalid Session ID";
			return $result;
		} else {
			$USER = $_SESSION['user'];
			$ACCESS = $_SESSION['access'];

			if(!$USER->id){
				$result["resultdescription"] = "Invalid user";
				return $result;
			}
			if (!$ACCESS->getValue('viewcontacts', '0')) {
				$result['resultdescription'] = "Unauthorized - User is not authorized to view contact details";
				return $result;
			}
			$personid = QuickQuery("select id from person where pkey = ? and not deleted", false, array($pkey));
			if(!$personid){
				$result["resultdescription"] = "Invalid person";
				return $result;
			}
			if (!$USER->canSeePerson($personid)) {
				$result["resultdescription"] = "Unauthorized - User does not have access to view this person";
				return $result;
			}
			// build multidimensional array to reduce duplicate code when building return objects
			$contactdata = array();
			$contactdata["phone"] = resequence(DBFindMany("Phone", "from phone where personid = " . $personid), "sequence");
			$contactdata["email"] = resequence(DBFindMany("Email", "from email where personid = " . $personid), "sequence");
			$contactdata["sms"] = resequence(DBFindMany("Sms", "from sms where personid = " . $personid), "sequence");

			//Generate missing contact fields that do not yet exist
			if(count($contactdata["phone"]) != getSystemSetting("maxphones",1)){
				for($i = 0; $i < getSystemSetting("maxphones",1); $i++){
					if(!isset($contactdata["phone"][$i])){
						$newphone=new Phone();
						$newphone->personid = $personid;
						$newphone->sequence = $i;
						$contactdata["phone"][$i] = $newphone;
					}
				}
			}
			if(count($contactdata["email"]) != getSystemSetting("maxemails",1)){
				for($i=0; $i < getSystemSetting("maxemails",1); $i++){
					if(!isset($contactdata["email"][$i])){
						$newemail=new Email();
						$newemail->personid = $personid;
						$newemail->sequence = $i;
						$contactdata["email"][$i] = $newemail;
					}
				}
			}
			if(count($contactdata["sms"]) != getSystemSetting("maxsms",1)){
				for($i=0; $i < getSystemSetting("maxsms",1); $i++){
					if(!isset($contactdata["sms"][$i])){
						$newsms=new Sms();
						$newsms->personid = $personid;
						$newsms->sequence = $i;
						$contactdata["sms"][$i] = $newsms;
					}
				}
			}

			$contactprefs = getContactPrefs($personid);
			$defaultcontactprefs = getDefaultContactPrefs();

			$contacts = array();
			//array("phone", "email", "sms")
			foreach(array_keys($contactdata) as $type){
				foreach($contactdata[$type] as $object){
					$contact = new API_Contact();
					$contact->pkey = $pkey;
					$contact->type = $type;
					$contact->destination = $object->$type;
					$contact->sequence = $object->sequence;
					$contactpreferences = array();
					if(isset($contactprefs[$type][$object->sequence])){
						$pref = $contactprefs[$type][$object->sequence];
					} elseif (isset($defaultcontactprefs[$type][$object->sequence])) {
						$pref = $defaultcontactprefs[$type][$object->sequence];
					}
					if(isset($pref)){   // if has sms is not set in manager the pref is not set 
					  foreach($pref as $jobtypeid => $enabled){
						$contactpreference = new API_ContactPreference();
						$contactpreference->jobtypeid = $jobtypeid;
						$contactpreference->enabled = (bool)$enabled;
						$contactpreferences[] = $contactpreference;
					  }
					}
					$contact->contactpreferences = $contactpreferences;
					$contacts[] = $contact;
				}
			}
			$result['contacts'] = $contacts;
			$result['resultcode'] = "success";
			return $result;
		}
	}

	/*
		Given a valid session id and a contact object,
		setContact will update contact information in the system.  If any
		part of the contact object is invalid, an error is returned

		setContacts
		params: String sessionid
		contact: contact object

		return:
		Success/Fail

		*/


	function setContact ($sessionid, $contact){

		$personid = $this->helperSetContact($sessionid, $contact->pkey);
		if (count($personid) > 1) {
			// on failure, $personid is a result array with resultcode and resultdescription
			// API v1.0 only used 'failure' and no other codes, so roll them all back to 'failure'
			$personid['resultcode'] = 'failure';
			return $personid;
		}
		
		$result = array("resultcode" => "failure","resultdescription" => "", "contacts" => null);

			//if any fields are invalid, do not update system and return problems
			if(!in_array($contact->type, array("phone", "email", "sms"))){
				$result["resultdescription"] = "Invalid type";
				return $result;
			}

			$badsequence = false;
			$baddestination = false;
			if($contact->type == "phone" || $contact->type == "sms"){
				if($contact->destination!="" && $error = Phone::validate($contact->destination)){
					$result['resultdescription'] = "Not a valid phone number";
					return $result;
				}
			} else if($contact->type == "email"){
				if($contact->destination != "" && !validEmail($contact->destination)){
					$result['resultdescription'] = "Not a valid email address";
					return $result;
				}
			}

			if($contact->type == "phone"){
				if($contact->sequence >= getSystemSetting("maxphones", 1)){
					$badsequence = true;
				}
			} else if($contact->type == "sms"){
				if($contact->sequence >= getSystemSetting("maxsms", 1)){
					$badsequence = true;
				}
			} else if($contact->type == "email"){
				if($contact->sequence >= getSystemSetting("maxemails", 1)){
					$badsequence = true;
				}
			}
			if(($contact->sequence + 0) < 0 || $badsequence){
				$result['resultdescription'] = "Invalid Sequence";
				return $result;
			}

			$obj = DBFind(ucfirst($contact->type), "from " . $contact->type . " where personid = " . $personid . " and sequence = " . ($contact->sequence + 0));

			//if the contact obj doesn't exist, create a new one in the db
			//else update the one we found
			if($obj == false){
				if($contact->type == "phone"){
					$obj = new Phone();
					$obj->phone = Phone::parse($contact->destination);
				} else if($contact->type == "email"){
					$obj = new Email();
					$obj->email = $contact->destination;
				} else if($contact->type == "sms"){
					$obj = new Sms();
					$obj->sms = Phone::parse($contact->destination);
				}

				$obj->personid = $personid;
				$obj->editlock = 1; // NOTE if editlock is ever 0, must optin the number (bug 4128)
				$obj->sequence = ($contact->sequence + 0);
				$obj->create();
			} else {
				$obj->editlock = 1;
				if($contact->type == 'phone'){
					$obj->phone = Phone::parse($contact->destination);
				}else if($contact->type == 'sms'){
					$obj->sms = Phone::parse($contact->destination);
				} else if($contact->type == 'email'){
					$obj->email = $contact->destination;
				}
				$obj->update();
			}
            if(is_array($contact->contactpreferences)){
            	$personcontactprefs = array();
			    //generate array of contact prefs so that we can iterate later in a transaction
		    	foreach($contact->contactpreferences as $contactpreference){
				 	$personcontactprefs[($contactpreference->jobtypeid+0)] = ($contactpreference->enabled ? "1" : "0");
				}
				// find all valid jobtypeids, used to verify against input
				$jobtypeids = QuickQueryList("select id from jobtype where not deleted");

				// insert/update all contact preferences
				QuickUpdate("begin");
				foreach ($personcontactprefs as $jobtypeid => $enabled) {
					// validate $jobtypeid, caller can pass anything here like 99
					if (in_array($jobtypeid, $jobtypeids))
						QuickUpdate("insert into contactpref (personid, jobtypeid, type, sequence, enabled) values (?, ?, ?, ?, ?) on duplicate key update enabled = ?", false, array($personid, $jobtypeid, $contact->type, $contact->sequence, $enabled, $enabled));
					// else not a valid jobtypeid, just skip it (could error, but we don't want to break v1.0 api users)
				}
				QuickUpdate("commit");
            }
            else{
            	// No valid contact preferences
            }
			$result["resultcode"] = "success";
			return $result;

	}
		
	function createPhoneMessage ($sessionid, $name, $description, $messagetext, $gender) {
		global $USER, $ACCESS;
		$result = array("resultcode" => "failure","resultdescription" => "", "messageid" => 0);
		if (!APISession($sessionid)) {
			$result['resultcode'] = 'invalidsession';
			$result["resultdescription"] = "Invalid Session ID";
			return $result;
		} else {
			$USER = $_SESSION['user'];
			$ACCESS = $_SESSION['access'];

			if (!$USER->id) {
				$result['resultcode'] = 'invalidsession';
				$result["resultdescription"] = "Invalid User";
				return $result;
			}
			// validate args
			if (strlen($name) < 1 || strlen($name) > 50) {
				$result['resultcode'] = 'invalidparam';
				$result["resultdescription"] = "Invalid Name, must be 1-50 characters";
				return $result;
			}
			if (strlen($description) > 50) {
				$result['resultcode'] = 'invalidparam';
				$result["resultdescription"] = "Invalid Description, maximum 50 characters";
				return $result;
			}
			if (strlen($messagetext) < 1) {  // not checking for max length of text, assume if they try to send something so big, they can deal with the consequences
				$result['resultcode'] = 'invalidparam';
				$result["resultdescription"] = "Invalid Text, must be at least one character";
				return $result;
			}
			// no error for invalid gender, use default
			$gender = strtolower($gender);
			if ($gender != "female" && $gender != "male") {
				$gender = "female";
			}
			// validate permissions
			if (!$USER->authorize('sendphone')) {
				$result['resultcode'] = 'unauthorized';
				$result["resultdescription"] = "Unauthorized - user does not have privilege to create phone messages";
				return $result;
			}
			
			// create the message
			$message = new Message();
			$message->messagegroupid = null; // not used in a group, these messages are unseen by the application
			$message->userid = $USER->id;
			$message->name = $name;
			$message->description = $description;
			$message->type = "phone";
			$message->subtype = "voice";
			$message->data = ""; // not used by phone
			$message->modifydate = date("Y-m-d H:i:s", time());
			$message->autotranslate = "none";
			$message->languagecode = "en"; // hardcoded English
			$message->create();
			
			$this->createMessageParts($message->id, $messagetext, $gender);
								
			// success, return id
			$result["messageid"] = $message->id;
			$result["resultcode"] = "success";
			return $result;
		}
	}
	
	function createSmsMessage ($sessionid, $name, $description, $messagetext) {
		global $USER, $ACCESS;
		$result = array("resultcode" => "failure","resultdescription" => "", "messageid" => 0);
		if (!APISession($sessionid)) {
			$result['resultcode'] = 'invalidsession';
			$result["resultdescription"] = "Invalid Session ID";
			return $result;
		} else {
			$USER = $_SESSION['user'];
			$ACCESS = $_SESSION['access'];

			if (!$USER->id) {
				$result['resultcode'] = 'invalidsession';
				$result["resultdescription"] = "Invalid User";
				return $result;
			}
			// validate args
			if (strlen($name) < 1 || strlen($name) > 50) {
				$result['resultcode'] = 'invalidparam';
				$result["resultdescription"] = "Invalid Name, must be 1-50 characters";
				return $result;
			}
			if (strlen($description) > 50) {
				$result['resultcode'] = 'invalidparam';
				$result["resultdescription"] = "Invalid Description, maximum 50 characters";
				return $result;
			}
			if (strlen($messagetext) < 1 || strlen($messagetext) > 160) {
				$result['resultcode'] = 'invalidparam';
				$result["resultdescription"] = "Invalid Text, must be 1-160 characters";
				return $result;
			}
			// validate permissions
			if (!getSystemSetting('_hassms') || !$USER->authorize('sendsms')) {
				$result['resultcode'] = 'unauthorized';
				$result["resultdescription"] = "Unauthorized - user does not have privilege to create sms messages";
				return $result;
			}
			
			// create the message
			$message = new Message();
			$message->messagegroupid = null; // not used in a group, these messages are unseen by the application
			$message->userid = $USER->id;
			$message->name = $name;
			$message->description = $description;
			$message->type = "sms";
			$message->subtype = "plain";
			$message->data = ""; // not used by phone
			$message->modifydate = date("Y-m-d H:i:s", time());
			$message->autotranslate = "none";
			$message->languagecode = "en"; // hardcoded English
			$message->create();
			
			$this->createMessageParts($message->id, $messagetext);
						
			// success, return id
			$result["messageid"] = $message->id;
			$result["resultcode"] = "success";
			return $result;
		}
	}

	function createEmailMessage ($sessionid, $name, $description, $messagetext, $subject, $fromname, $fromemail) {
		global $USER, $ACCESS;
		$result = array("resultcode" => "failure","resultdescription" => "", "messageid" => 0);
		if (!APISession($sessionid)) {
			$result['resultcode'] = 'invalidsession';
			$result["resultdescription"] = "Invalid Session ID";
			return $result;
		} else {
			$USER = $_SESSION['user'];
			$ACCESS = $_SESSION['access'];

			if (!$USER->id) {
				$result['resultcode'] = 'invalidsession';
				$result["resultdescription"] = "Invalid User";
				return $result;
			}
			// validate args
			if (strlen($name) < 1 || strlen($name) > 50) {
				$result['resultcode'] = 'invalidparam';
				$result["resultdescription"] = "Invalid Name, must be 1-50 characters";
				return $result;
			}
			if (strlen($description) > 50) {
				$result['resultcode'] = 'invalidparam';
				$result["resultdescription"] = "Invalid Description, maximum 50 characters";
				return $result;
			}
			if (strlen($messagetext) < 1) {  // not checking for max length of text, assume if they try to send something so big, they can deal with the consequences
				$result['resultcode'] = 'invalidparam';
				$result["resultdescription"] = "Invalid Text, must be at least one character";
				return $result;
			}
			if (!validEmail($fromemail)) {
				$result['resultcode'] = 'invalidparam';
				$result["resultdescription"] = "Invalid fromemail, must be a valid email address";
				return $result;
			}
			// validate if email domain restricted
			$domain = getSystemSetting('emaildomain');
			if ($domain && !checkEmailDomain($fromemail, $domain)) {
				$result['resultcode'] = 'invalidparam';
				$result["resultdescription"] = "Invalid fromemail, must be a valid email address with the domain " . $domain;
				return $result;
			}
			
			// validate permissions
			if (!$USER->authorize('sendemail')) {
				$result['resultcode'] = 'unauthorized';
				$result["resultdescription"] = "Unauthorized - user does not have privilege to create email messages";
				return $result;
			}
			
			// create the message
			$message = new Message();
			$message->messagegroupid = null; // not used in a group, these messages are unseen by the application
			$message->userid = $USER->id;
			$message->name = $name;
			$message->description = $description;
			$message->type = "email";
			$message->subtype = "plain";
			$message->data = "subject=" . urlencode($subject) . "&fromname=" . urlencode($fromname) . "&fromemail=" . urlencode($fromemail) . "&overrideplaintext=1";
			$message->modifydate = date("Y-m-d H:i:s", time());
			$message->autotranslate = "none";
			$message->languagecode = "en"; // hardcoded English
			$message->create();
			
			$this->createMessageParts($message->id, $messagetext);
									
			// success, return id
			$result["messageid"] = $message->id;
			$result["resultcode"] = "success";
			return $result;
		}
	}
	
	function sendJobExtended ($sessionid, $name, $desc, $listids, $jobtypeid, $startdate, $starttime, $endtime, $daystorun, $phonemsgid, $emailmsgid, $smsmsgid, $maxcallattempts, $options) {
		global $USER, $ACCESS;
		$result = array("resultcode" => "failure","resultdescription" => "", "jobid" => 0);

		if (!APISession($sessionid)) {
			$result['resultcode'] = 'invalidsession';
			$result["resultdescription"] = "Invalid Session ID";
			return $result;
		} else {
			
			QuickQuery("begin");
			
			$USER = $_SESSION['user'];
			$ACCESS = $_SESSION['access'];

			if (!$USER->id) {
				$result['resultcode'] = 'invalidsession';
				$result["resultdescription"] = "Invalid user";
				return $result;
			}
			if (!strtotime($startdate)) {
				$result['resultcode'] = 'invalidparam';
				$result["resultdescription"] = "Invalid Start Date";
				return $result;
			} else if (!strtotime($starttime)) {
				$result['resultcode'] = 'invalidparam';
				$result["resultdescription"] = "Invalid Start Time";
				return $result;
			} else if (!strtotime($endtime)) {
				$result['resultcode'] = 'invalidparam';
				$result["resultdescription"] = "Invalid End Time";
				return $result;
			} else if ($daystorun < 1 || $daystorun > $ACCESS->getValue('maxjobdays', '7')) {
				$result['resultcode'] = 'invalidparam';
				$result["resultdescription"] = "Invalid Run Days.  Must be between 1 and " . $ACCESS->getValue('maxjobdays', '7');
				return $result;
			} else if ($maxcallattempts < 1 || $maxcallattempts > $ACCESS->getValue('callmax', '10')) {
				$result['resultcode'] = 'invalidparam';
				$result["resultdescription"] = "Invalid Max Call Attempts. Must be between 1 and " . $ACCESS->getValue('callmax', '10');
				return $result;
			} else if ($USER->authorize('sendphone') && $phonemsgid && !userOwns("message", $phonemsgid)) {
				$result['resultcode'] = 'unauthorized';
				$result["resultdescription"] =  "Invalid Phone Message ID";
				return $result;
			} else if ($USER->authorize('sendemail') && $emailmsgid && !userOwns("message", $emailmsgid)) {
				$result['resultcode'] = 'unauthorized';
				$result["resultdescription"] =  "Invalid Email Message ID";
				return $result;
			} else if ($smsmsgid && (!getSystemSetting('_hassms') || !$USER->authorize('sendsms') || !userOwns("message", $smsmsgid))) {
				$result['resultcode'] = 'unauthorized';
				$result["resultdescription"] = "Invalid SMS Message ID";
				return $result;
			} else if (strtotime($starttime) > strtotime($endtime)) {
				$result['resultcode'] = 'invalidparam';
				$result["resultdescription"] = "Start Time must be before End Time";
				return $result;
			} else if ($name == null || $name == "") {
				$result['resultcode'] = 'invalidparam';
				$result["resultdescription"] = "Name must be set";
				return $result;
			}
			
			// validate jobtype
			$jobtypeok = false;
			$userjobtypes = JobType::getUserJobTypes();
			foreach ($userjobtypes as $userjobtype) {
				if ($userjobtype->id == $jobtypeid) {
					$jobtypeok = true;
					break;
				}
			}
			if (!$jobtypeok) {
				$result['resultcode'] = 'unauthorized';
				$result["resultdescription"] = "Invalid Jobtype : User not authorized to send jobs of this type";
				return $result;
			}
			
			// validate listids
			foreach ($listids->listid as $listid) {
				if (!userOwns("list", $listid) && !isSubscribed("list", $listid)) {
					$result['resultcode'] = 'unauthorized';
					$result["resultdescription"] =  "Invalid List " . $listid;
					return $result;
				}
			}
			
			// validate call window
			if ($ACCESS->getValue('callearly') && strtotime($starttime) < strtotime($ACCESS->getValue('callearly'))) {
				$result['resultcode'] = 'unauthorized';
				$result["resultdescription"] =  "Invalid start time, must be after " . $ACCESS->getValue('callearly');
				return $result;
			}
			if ($ACCESS->getValue('calllate') && strtotime($endtime) > strtotime($ACCESS->getValue('calllate'))) {
				$result['resultcode'] = 'unauthorized';
				$result["resultdescription"] =  "Invalid end time, must be before " . $ACCESS->getValue('calllate');
				return $result;
			}

			// all valid, continue to create and submit job
			$job = Job::jobWithDefaults();
			// prep the job options into a name-value array
			$joboptions = array();
			if (isset($options->jobOption)) {
				foreach ($options->jobOption as $op) {
					//error_log($op->name . " -> " . $op->value);
					if (isset($op->name) && isset($op->value) && $op->value != "")
						$joboptions[$op->name] = $op->value;
				}
			}
			
			// set job options
			if (isset($joboptions['sendreport']) && $joboptions['sendreport'] == 'false') {
				$job->setOption("sendreport", 0);
			} else {
				// default to always send report
				$job->setOption("sendreport", 1);
			}
			// special case, maxcallattempts is a param so ignore if in options
			if ($maxcallattempts > 0 && $maxcallattempts <= $USER->getSetting('callmax', $ACCESS->getValue('callmax', '4'))) {
				$job->setOptionValue("maxcallattempts", $maxcallattempts);
			} else {
				//FIXME dead code
				$job->setOptionValue("maxcallattempts", $USER->getSetting('callmax', $ACCESS->getValue('callmax', '4')));
			}
			// if user is allowed to override callerid, then check that phone number is valid or error, otherwise we just ignore the option
			if (isset($joboptions['callerid']) && $USER->authorize('setcallerid') && !getSystemSetting('_hascallback', false)) {
				$errors = Phone::validate($joboptions['callerid']);
				if (count($errors)  > 0) {
					$result['resultcode'] = 'invalidparam';
					$result["resultdescription"] =  "Invalid callerid in job options";
					return $result;
				}
				$job->setOptionValue('callerid', Phone::parse($joboptions['callerid']));
			}
			if (isset($joboptions['leavemessage']) && $USER->authorize('leavemessage')) {
				if ($joboptions['leavemessage'] == 'true') {
					$job->setOption('leavemessage', '1');
				} else {
					$job->setOption('leavemessage', '0');
				}
			}
			if (isset($joboptions['messageconfirmation']) && $USER->authorize('messageconfirmation')) {
				if ($joboptions['messageconfirmation'] == 'true') {
					$job->setOption('messageconfirmation', '1');
				} else {
					$job->setOption('messageconfirmation', '0');
				}
			}
			/*
			 * // unused option today, comment out in case we put it in
			if (isset($joboptions['skipduplicates'])) {
				if ($joboptions['skipduplicates'] == "1") {
					$job->setOption('skipduplicates', '1');
					$job->setOption('skipemailduplicates', '1');
					$job->setOption('skipsmsduplicates', '1');
				} else {
					$job->setOption('skipduplicates', '0');
					$job->setOption('skipemailduplicates', '0');
					$job->setOption('skipsmsduplicates', '0');
				}
			}
			*/
			
			$job->name = $name;
			$job->description = isset($desc) ? $desc : ""; //avoid nulls
			$job->jobtypeid = $jobtypeid;
			$job->type = 'notification';
				
			// Create a deleted non-permanent messagegroup that contains duplicates of the client-supplied messages.
			$messagegroup = new MessageGroup();
			$messagegroup->userid = $USER->id;
			$messagegroup->defaultlanguagecode = 'en'; // NOTE: Default language is assumed to be English.
			$messagegroup->name = $job->name;
			$messagegroup->description = $job->description;
			$messagegroup->modified = date("Y-m-d H:i:s", time());
			$messagegroup->deleted = 1; // NOTE: We don't want this messagegroup to show in the UI.
			$messagegroup->permanent = 0; // NOTE: This is a hidden messagegroup anyway, so why keep it? The original messages remain intact.
			if (!$messagegroup->create()) {
				$result['resultcode'] = 'failure';
				$result["resultdescription"] = "Unable to create message group";
				return $result;
			}
			$job->messagegroupid = $messagegroup->id;
			$job->sendphone = false; // Default value.
			$job->sendemail = false; // Default value.
			$job->sendsms = false; // Default value.
			
			// add messages
			if ($USER->authorize('sendphone') && $phonemsgid) {
				$phonemessage = new Message($phonemsgid);
				if ($phonemessage->userid == $USER->id && $phonemessage->type == 'phone') {
					// NOTE: $phonemessage->copy() already calls $duplicatephonemessage->create();
					$duplicatephonemessage = $phonemessage->copy($messagegroup->id);
					if ($duplicatephonemessage->id) {
						// If the message is auto-translated, then copy its source message also, in case we need to refresh the translation.
						if ($phonemessage->autotranslate == 'translated') {
							$sourcephonemessage = DBFind('Message', 'from message where messagegroupid=? and type="phone" and subtype=? and languagecode=? and autotranslate="source"', false, array($phonemessage->messagegroupid, $phonemessage->subtype, $phonemessage->languagecode));
							$duplicatesourcephonemessage = $sourcephonemessage->copy($messagegroup->id);
							
							if ($duplicatesourcephonemessage->id)
								$job->sendphone = true;
						} else {
							$job->sendphone = true;
						}
					}
				}
			}
			if ($USER->authorize('sendemail') && $emailmsgid) {
				$emailmessage = new Message($emailmsgid);
				if ($emailmessage->userid == $USER->id && $emailmessage->type == 'email') {
					// NOTE: $emailmessage->copy() already calls $duplicateemailmessage->create();
					$duplicateemailmessage = $emailmessage->copy($messagegroup->id);
					if ($duplicateemailmessage->id) {
						// If the message is auto-translated, then copy its source message also, in case we need to refresh the translation.
						if ($emailmessage->autotranslate == 'translated') {
							$sourceemailmessage = DBFind('Message', 'from message where messagegroupid=? and type="email" and subtype=? and languagecode=? and autotranslate="source"', false, array($emailmessage->messagegroupid, $emailmessage->subtype, $emailmessage->languagecode));
							$duplicatesourceemailmessage = $sourceemailmessage->copy($messagegroup->id);
							
							if ($duplicatesourceemailmessage->id)
								$job->sendemail = true;
						} else {
							$job->sendemail = true;
						}
					}
				}
			}
			if (getSystemSetting('_hassms') && $USER->authorize('sendsms') && $smsmsgid) {
				$smsmessage = new Message($smsmsgid);
				if ($smsmessage->userid == $USER->id && $smsmessage->type == 'sms') {
					// NOTE: $smsmessage->copy() already calls $duplicatesmsmessage->create();
					$duplicatesmsmessage = $smsmessage->copy($messagegroup->id);
					if ($duplicatesmsmessage->id)
						$job->sendsms = true;
				}
			}
			// validate at least one message type was added
			if(!$job->sendphone && !$job->sendemail && !$job->sendsms) {
				$result['resultcode'] = 'invalidparam';
				$result["resultdescription"] = "You must have at least one message type";
				return $result;
			}
			// start-end times
			$job->startdate = date("Y-m-d", strtotime($startdate));
			if ($ACCESS->getValue('maxjobdays') && $daystorun > $ACCESS->getValue('maxjobdays')) {
				$daystorun = $USER->getSetting('maxjobdays', $ACCESS->getValue('maxjobdays', '2'));
			}
			$job->enddate = date("Y-m-d", strtotime($job->startdate) + (($daystorun - 1) * 86400));
			$job->starttime = date("H:i", strtotime($starttime));
			$job->endtime = date("H:i", strtotime($endtime));

			if (!$job->create()) { // create to generate the jobid
				$result['resultcode'] = 'failure';
				$result["resultdescription"] = "Unable to create job";
				return $result;
			}
			// associate this jobid with each listid
			foreach ($listids->listid as $listid) {
				// TODO batch insert
				QuickUpdate("insert into joblist (jobid, listid) values (?, ?)", false, array($job->id, $listid));
			}
			
			$job->runNow(); // run the job
			
			QuickQuery("commit");
			
			// success
			$result["resultcode"] = "success";
			$result["jobid"] = $job->id;
			return $result;
		}
	}
	
	/**
	 * If setting does not exist, return empty value, do not return defaults, allow calling application to determine
	 */
	function getSettings ($sessionid) {
		global $USER, $ACCESS;
		$result = array("resultcode" => "failure", "resultdescription" => "", "settings" => array());

		if (!APISession($sessionid)) {
			$result['resultcode'] = 'invalidsession';
			$result["resultdescription"] = "Invalid Session ID";
			return $result;
		}
		
		$USER = $_SESSION['user'];
		$ACCESS = $_SESSION['access'];

		if (!$USER->id) {
			$result['resultcode'] = 'invalidsession';
			$result["resultdescription"] = "Invalid user";
			return $result;
		}
		
		$settings = array();

		// maxjobdays
		$entry = new NameValuePair();
		$entry->name = "maxjobdays";
		$entry->value = $USER->getSetting('maxjobdays');
		$settings[] = $entry;
		
		// callmax
		$entry = new NameValuePair();
		$entry->name = "callmax";
		$entry->value = $USER->getSetting('callmax');
		$settings[] = $entry;
		
		// callearly
		$entry = new NameValuePair();
		$entry->name = "callearly";
		$entry->value = $USER->getSetting('callearly');
		$settings[] = $entry;
		
		// calllate
		$entry = new NameValuePair();
		$entry->name = "calllate";
		$entry->value = $USER->getSetting('calllate');
		$settings[] = $entry;
		
		// timezone
		$entry = new NameValuePair();
		$entry->name = "timezone";
		$entry->value = getSystemSetting('timezone');
		$settings[] = $entry;
		
		// emaildomain
		$entry = new NameValuePair();
		$entry->name = "emaildomain";
		$entry->value = getSystemSetting('emaildomain');
		$settings[] = $entry;
		
		// success
		$result["resultcode"] = "success";
		$result["settings"] = $settings;
		return $result;
	}
	
	function getPermissions ($sessionid) {
		global $USER, $ACCESS;
		$result = array("resultcode" => "failure", "resultdescription" => "", "permissions" => array());

		if (!APISession($sessionid)) {
			$result['resultcode'] = 'invalidsession';
			$result["resultdescription"] = "Invalid Session ID";
			return $result;
		}
		
		$USER = $_SESSION['user'];
		$ACCESS = $_SESSION['access'];

		if (!$USER->id) {
			$result['resultcode'] = 'invalidsession';
			$result["resultdescription"] = "Invalid user";
			return $result;
		}
		
		$permissions = array();
		
		// sendphone
		$entry = new NameValuePair();
		$entry->name = "sendphone";
		if ($USER->authorize('sendphone')) {
			$entry->value = "true";
		} else {
			$entry->value = "false";
		}
		$permissions[] = $entry;
			
		// sendemail
		$entry = new NameValuePair();
		$entry->name = "sendemail";
		if ($USER->authorize('sendemail')) {
			$entry->value = "true";
		} else {
			$entry->value = "false";
		}
		$permissions[] = $entry;
			
		// sendsms
		$entry = new NameValuePair();
		$entry->name = "sendsms";
		if (getSystemSetting('_hassms') && $USER->authorize('sendsms')) {
			$entry->value = "true";
		} else {
			$entry->value = "false";
		}
		$permissions[] = $entry;

		// callerid
		$entry = new NameValuePair();
		$entry->name = "callerid";
		if ($USER->authorize('setcallerid') && !getSystemSetting('_hascallback', false)) {
			$entry->value = "true";
		} else {
			$entry->value = "false";
		}
		$permissions[] = $entry;

		// maxjobdays
		$entry = new NameValuePair();
		$entry->name = "maxjobdays";
		$entry->value = $ACCESS->getValue('maxjobdays');
		$permissions[] = $entry;
		
		// callmax
		$entry = new NameValuePair();
		$entry->name = "callmax";
		$entry->value = $ACCESS->getValue('callmax');
		$permissions[] = $entry;
		
		// callearly
		$entry = new NameValuePair();
		$entry->name = "callearly";
		$entry->value = $ACCESS->getValue('callearly');
		$permissions[] = $entry;
		
		// calllate
		$entry = new NameValuePair();
		$entry->name = "calllate";
		$entry->value = $ACCESS->getValue('calllate');
		$permissions[] = $entry;
		
		// success
		$result["resultcode"] = "success";
		$result["permissions"] = $permissions;
		return $result;
	}
	
	function setContactDestination ($sessionid, $pkey, $type, $sequence, $destination, $editlock) {
		
		$personid = $this->helperSetContact($sessionid, $pkey);
		if (count($personid) > 1) {
			return $personid; // actually a result array with code and description
		}
		
		$result = array("resultcode" => "failure", "resultdescription" => "");
		
		$maxsettingname; // 'maxphones', 'maxemails', 'maxsms'
		
		// validate destination type
		switch ($type) {
			case "phone" :
				$errors = Phone::validate($destination); 
				if (count($errors)) {
					$result['resultcode'] = 'invalidparam';
					$result["resultdescription"] = "Invalid destination - must be valid phone number";
					return $result;
				}
				$destination = Phone::parse($destination); // strip the junk down to 10 digits
				$maxsettingname = "maxphones";
				break;
			case "email" :
				if (!validEmail($destination)) {
					$result['resultcode'] = 'invalidparam';
					$result["resultdescription"] = "Invalid destination - must be valid email address";
					return $result;
				}
				$maxsettingname = "maxemails";
				break;
			case "sms" :
				if (!getSystemSetting('_hassms')) {
					$result['resultcode'] = 'invalidparam';
					$result["resultdescription"] = "Invalid type - sms disabled for account";
					return $result;
				}
				$errors = Phone::validate($destination); 
				if (count($errors)) {
					$result['resultcode'] = 'invalidparam';
					$result["resultdescription"] = "Invalid destination - must be valid phone number";
					return $result;
				}
				$destination = Phone::parse($destination); // strip the junk down to 10 digits
				$maxsettingname = "maxsms";
				break;
			default :
				$result['resultcode'] = 'invalidparam';
				$result["resultdescription"] = "Invalid type - must be 'phone', 'email', 'sms'";
				return $result;
		}
		
		$maxdestinations = getSystemSetting($maxsettingname, 1);
		
		// validate sequence < max
		if ($sequence < 0 || $sequence >= $maxdestinations) {
			$result['resultcode'] = 'invalidparam';
			$result["resultdescription"] = "Invalid sequence - must be 0 through " . ($maxdestinations - 1);
			return $result;
		}
		
		
		// get existing phones from db, then create any additional based on the max allowed
		// what if the max is less than the number they already have? the GUI does not allow to decrease this value, so NO WORRIES :)
		// use array_values to reset starting index to 0
		$tempdestinations = resequence(DBFindMany(ucfirst($type), "from " . $type . " where personid = ? order by sequence", false, array($personid)), "sequence");
		$destinations = array();
		for ($i=0; $i<$maxdestinations; $i++) {
			if (!isset($tempdestinations[$i])) {
				switch ($type) {
					case "phone" :
						$destinations[$i] = new Phone();
						break;
					case "email" :
						$destinations[$i] = new Email();
						break;
					case "sms" :
						$destinations[$i] = new Sms();
						break;
				}
				$destinations[$i]->$type = "";
				$destinations[$i]->sequence = $i;
				$destinations[$i]->personid = $personid;
				$destinations[$i]->editlock = 0;
			} else {
				$destinations[$i] = $tempdestinations[$i];
			}
		}
		
		// set the fields for the destination being updated
		$destrecord = $destinations[$sequence];
	
		// convert boolean to int
		if ($editlock) {
			$destrecord->editlock = 1;
			$destrecord->editlockdate = date("Y-m-d G:i:s");
		} else {
			$destrecord->editlock = 0;
			$destrecord->editlockdate = null;
		}
		$destrecord->$type = $destination;
		// if sms, optin via authserver (minor hack to insert into global optin list so jobs will send to these sms numbers)
		if ("sms" == $type && !$editlock) {
			blocksms($destination, "optin", "Automated optin via SMAPI");
		}
		
		// update/create all destination records
		foreach ($destinations as $dest) {
			$dest->update();
		}
		
		// success
		$result["resultcode"] = "success";
		return $result;
	}

	function setContactPreference ($sessionid, $pkey, $type, $sequence, $jobtypeid, $enabled) {
		
		$personid = $this->helperSetContact($sessionid, $pkey);
		if (count($personid) > 1) {
			return $personid; // actually a result array with code and description
		}
		
		$result = array("resultcode" => "failure", "resultdescription" => "");
		
		// validate jobtypeid
		if (!QuickQuery("select 1 from jobtype where id = ? and not deleted", false, array($jobtypeid))) {
			$result['resultcode'] = 'invalidparam';
			$result["resultdescription"] = "Invalid jobtypeid";
			return $result;
		}
		
		// convert boolean to int
		if ($enabled) {
			$enabled = 1;
		} else {
			$enabled = 0;
		}
		
		QuickUpdate("insert into contactpref (personid, jobtypeid, type, sequence, enabled) values (?, ?, ?, ?, ?) on duplicate key update enabled = ?", false, array($personid, $jobtypeid, $type, $sequence, $enabled, $enabled));
		
		// success
		$result["resultcode"] = "success";
		return $result;
	}
	
	/**
	 * Create a new List with the given student IDs.
	 * 
	 * @param string $sessionid
	 * @param string $name - unique list name for this user
	 * @param string $description
	 * @param array of strings $ids - array of pkeys, maxsize 10,000
	 */
	function createListFromIds($sessionid, $name, $description, $ids) {
		global $USER, $ACCESS;
		$result = array("resultcode" => "warning", "resultdescription" => "", "listid" => 0, "numpeople" => 0);

		// validate session
		if (!APISession($sessionid)) {
			$result['resultcode'] = 'invalidsession';
			$result["resultdescription"] = "Invalid Session ID";
			return $result;
		}
		
		// set user and access of this session
		$USER = $_SESSION['user'];
		$ACCESS = $_SESSION['access'];

		// validate user
		if (!$USER->id) {
			$result['resultcode'] = 'invalidsession';
			$result["resultdescription"] = "Invalid user";
			return $result;
		}
		
		// authorize
		if (!$USER->authorize('createlist') || !$USER->authorize('listuploadids')) {
			$result['resultcode'] = 'unauthorized';
			$result["resultdescription"] =  "User does not have permission to create lists";
			return $result;
		}
		
		// name is required
		if (strlen($name) == 0) {
			$result['resultcode'] = 'invalidparam';
			$result["resultdescription"] =  "Name is required";
			return $result;
		}
		// validate name length
		if (strlen($name) > 50) {
			$result['resultcode'] = 'invalidparam';
			$result["resultdescription"] =  "Name cannot be greater than 50 characters";
			return $result;
		}
		
		// validate description length
		if (strlen($description) > 50) {
			$result['resultcode'] = 'invalidparam';
			$result["resultdescription"] =  "Description cannot be greater than 50 characters";
			return $result;
		}
		
		// validate unique name
		if (QuickQuery('select id from list where deleted=0 and name=? and userid=?', false, array($name, $USER->id))) {
			$result['resultcode'] = 'invalidparam';
			$result["resultdescription"] =  "There is already a list with this name";
			return $result;
		}
		
		$pkeys = $ids->pkeys;
		
		// create the new list
		$list = new PeopleList();
		$list->userid = $USER->id;
		$list->type = "person";
		$list->name = $name;
		$list->description = $description;
		$list->modifydate = QuickQuery("select now()");
		$list->deleted = 0;
		if (!$list->create()) {
			$result['resultcode'] = 'failure';
			$result["resultdescription"] =  "Unable to create the list.";
			return $result;
		}
		$result['listid'] = $list->id + 0;
		
		// add the people
		$numpeople = $list->updateManualAddByPkeys($pkeys);
		$result['numpeople'] = $numpeople + 0;
		
		// success if all people added, else warning
		if ($numpeople == count($pkeys))
			$result["resultcode"] = "success";
		else {
			$result["resultcode"] = "warning"; // somple people skipped, not added to list
			$result["resultdescription"] = "Some people may have been skipped.";
		}
		return $result;
	}
	
	function deleteList($sessionid, $listid) {
		global $USER, $ACCESS;
		$result = array("resultcode" => "failure", "resultdescription" => "");

		// validate session
		if (!APISession($sessionid)) {
			$result['resultcode'] = 'invalidsession';
			$result["resultdescription"] = "Invalid Session ID";
			return $result;
		}
		
		// set user and access of this session
		$USER = $_SESSION['user'];
		$ACCESS = $_SESSION['access'];

		// validate user
		if (!$USER->id) {
			$result['resultcode'] = 'invalidsession';
			$result["resultdescription"] = "Invalid user";
			return $result;
		}
		
		// validate list
		if (!userOwns("list", $listid)) {
			$result['resultcode'] = 'unauthorized';
			$result["resultdescription"] =  "Invalid List " . $listid;
			return $result;
		}
		
		// soft-delete list
		$list = new PeopleList($listid);
		if (!$list->softDelete()) {
			$result['resultcode'] = 'failure';
			$result["resultdescription"] =  "Unable to delete the list.";
			return $result;
		}
		
		// success
		$result["resultcode"] = "success";
		return $result;
	}
	
	function getImports($sessionid) {
		global $USER, $ACCESS;
		$result = array("resultcode" => "failure", "resultdescription" => "", "imports" => array());

		// validate session
		if (!APISession($sessionid)) {
			$result['resultcode'] = 'invalidsession';
			$result["resultdescription"] = "Invalid Session ID";
			return $result;
		}
		
		// set user and access of this session
		$USER = $_SESSION['user'];
		$ACCESS = $_SESSION['access'];

		// validate user
		if (!$USER->id) {
			$result['resultcode'] = 'invalidsession';
			$result["resultdescription"] = "Invalid user";
			return $result;
		}
		
		if (!$USER->authorize('managetasks')) {
			$result['resultcode'] = 'unauthorized';
			$result["resultdescription"] =  "User does not have permission";
			return $result;
		}
		
		$imports = DBFindMany("Import", "from import where ownertype != 'user' order by id");
		foreach ($imports as $import) {
			$result['imports'][] = new API_Import($import);
		}
		
		// success
		$result["resultcode"] = "success";
		return $result;
	}
	
	function uploadImport($sessionid, $importid, $base64data) {
		global $USER, $ACCESS;
		$result = array("resultcode" => "failure", "resultdescription" => "");

		// validate session
		if (!APISession($sessionid)) {
			$result['resultcode'] = 'invalidsession';
			$result["resultdescription"] = "Invalid Session ID";
			return $result;
		}
		
		// set user and access of this session
		$USER = $_SESSION['user'];
		$ACCESS = $_SESSION['access'];

		// validate user
		if (!$USER->id) {
			$result['resultcode'] = 'invalidsession';
			$result["resultdescription"] = "Invalid user";
			return $result;
		}
		if (!$USER->authorize('managetasks')) {
			$result['resultcode'] = 'unauthorized';
			$result["resultdescription"] =  "User does not have permission";
			return $result;
		}
		// verify there is some data
		if (strlen($base64data) == 0) {
			$result['resultcode'] = 'invalidparam';
			$result["resultdescription"] =  "Invalid Parameter : base64data cannot be empty";
			return $result;
		}
		
		$import = DBFind("Import", "from import where id = ?", false, array($importid));
		// validate importid
		if (!$import) {
			$result['resultcode'] = 'invalidparam';
			$result["resultdescription"] =  "Invalid Parameter : importid";
			return $result;
		}
				
		// store data, but decode first
		if ($import->upload(base64_decode($base64data)) === false) {
			$result['resultcode'] = 'failure';
			$result["resultdescription"] =  "Unable to upload data.";
			return $result;
		}
		
		// success
		$result["resultcode"] = "success";
		return $result;
	}

	function runImport($sessionid, $importid) {
		global $USER, $ACCESS;
		$result = array("resultcode" => "failure", "resultdescription" => "");

		// validate session
		if (!APISession($sessionid)) {
			$result['resultcode'] = 'invalidsession';
			$result["resultdescription"] = "Invalid Session ID";
			return $result;
		}
		
		// set user and access of this session
		$USER = $_SESSION['user'];
		$ACCESS = $_SESSION['access'];

		// validate user
		if (!$USER->id) {
			$result['resultcode'] = 'invalidsession';
			$result["resultdescription"] = "Invalid user";
			return $result;
		}
		if (!$USER->authorize('managetasks')) {
			$result['resultcode'] = 'unauthorized';
			$result["resultdescription"] =  "User does not have permission";
			return $result;
		}
		
		$import = DBFind("Import", "from import where id = ?", false, array($importid));
		// validate importid
		if (!$import) {
			$result['resultcode'] = 'invalidparam';
			$result["resultdescription"] =  "Invalid Parameter : importid";
			return $result;
		}
				
		// run the import
		$import->runNow();
		
		// success
		$result["resultcode"] = "success";
		return $result;
	}

	function getImportDetail($sessionid, $importid) {
		global $USER, $ACCESS;
		$result = array("resultcode" => "failure", "resultdescription" => "", "import" => new API_Import(null), "logentries" => array());

		// validate session
		if (!APISession($sessionid)) {
			$result['resultcode'] = 'invalidsession';
			$result["resultdescription"] = "Invalid Session ID";
			return $result;
		}
		
		// set user and access of this session
		$USER = $_SESSION['user'];
		$ACCESS = $_SESSION['access'];

		// validate user
		if (!$USER->id) {
			$result['resultcode'] = 'invalidsession';
			$result["resultdescription"] = "Invalid user";
			return $result;
		}
		if (!$USER->authorize('managetasks')) {
			$result['resultcode'] = 'unauthorized';
			$result["resultdescription"] =  "User does not have permission";
			return $result;
		}
		
		$import = DBFind("Import", "from import where id = ?", false, array($importid));
		// validate importid
		if (!$import) {
			$result['resultcode'] = 'invalidparam';
			$result["resultdescription"] =  "Invalid Parameter : importid";
			return $result;
		}

		// fill the log entries
		$logentries = DBFindMany("ImportLogEntry", "from importlogentry where importid = ? order by severity, linenum asc", false, array($importid));
		if ($logentries != false) {
			foreach ($logentries as $logentry) {
				$result['logentries'][] = new API_ImportLogEntry($logentry);
			}
		}
		
		// set the import object
		$result['import'] = new API_Import($import);
		
		// success
		$result["resultcode"] = "success";
		return $result;
	}
	
}

////////////////////////////////////////////////////////////////////////////////
// Functions
////////////////////////////////////////////////////////////////////////////////

//login function with shared code from login and loginToCustomer

function systemLogin($loginname, $password, $CUSTOMERURL=null){

	$result = array("resultcode" => "failure", "resultdescription" => "", "sessionid" => "");

	if($CUSTOMERURL === null){
		//get the customer URL

		$CUSTOMERURL = substr($_SERVER["SCRIPT_NAME"],1);
		$CUSTOMERURL = strtolower(substr($CUSTOMERURL,0,strpos($CUSTOMERURL,"/")));
	} else {
		//make sure customer url is alphanumeric
		if(!preg_match("/^[a-zA-Z0-9]*$/", $CUSTOMERURL)) {
			$result['resultdescription'] = "Invalid Customer URL";
			return $result;
		} else if ($CUSTOMERURL === ""){
			$result['resultdescription'] = "Invalid Customer URL";
			return $result;
		}
		$CUSTOMERURL = strtolower($CUSTOMERURL);
	}

	$userid = doLogin($loginname, $password, $CUSTOMERURL, $_SERVER['REMOTE_ADDR']);
	if($userid == -1){

		$result["resultdescription"] = "User is locked out";
		return $result;
	} else if ($userid){
		doStartSession();
		loadCredentials($userid);
		
		// check if api is disabled for this customer
		$hassmapi = getSystemSetting("_hassmapi");
		
		if ($hassmapi) {
			$result["resultcode"] = "success";
			$result["sessionid"] = session_id();
			return $result;
		} else {
			$result["resultdescription"] = "This service is currently unavailable. Please contact your system administrator for details on enabling this service.";
			return $result;
		}
	}

	$result["resultdescription"] = "Invalid LoginName/Password combination";
	return $result;
}


//starts or resumes a session with a valid session id
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
		
		apache_note("CS_USER",urlencode($USER->login)); //for logging

		$ACCESS = &$_SESSION['access'];
		$ACCESS->loadPermissions(true);

		if (!$USER->enabled || $USER->deleted) {
			return new SoapFault("Server", "User is not valid");
		}
		return true;
	}
}

//fetches job statistic data
//grabs all active jobs if no job id is given
function getJobData($jobid=0){
	global $USER;

	$query = "select j.id, j.name, j.description, j.messagegroupid,
	sum(rc.type='phone') as total_phone,
	sum(rc.type='email') as total_email,
	sum(rc.type='print') as total_print,
	sum(rc.type='sms') as total_sms,
	sum(rc.result not in ('A', 'M', 'duplicate', 'nocontacts', 'blocked') and rc.type='phone' and rc.numattempts < js.value) as remaining_phone,
	sum(rc.result not in ('sent', 'duplicate', 'nocontacts') and rc.type='email' and rc.numattempts < 1) as remaining_email,
	sum(rc.result not in ('sent', 'duplicate', 'nocontacts') and rc.type='print' and rc.numattempts < 1) as remaining_print,
	sum(rc.result not in ('sent', 'duplicate', 'nocontacts', 'blocked') and rc.type='sms' and rc.numattempts < 1) as remaining_sms,
	ADDTIME(j.startdate, j.starttime), j.status, j.deleted, j.type
	from job j
	left join reportcontact rc
	on j.id = rc.jobid
	left join jobsetting js on (js.jobid = j.id and js.name = 'maxcallattempts')
	where 1 and j.deleted=0
	and j.userid = $USER->id ";
	if ($jobid) {
		$query .= " and j.id = ? ";
	} else {
		$query .= " and (j.status = 'active' or j.status='scheduled' or j.status='procactive' or j.status='processing' or j.status = 'new' or j.status = 'cancelling') ";
	}
	$query .=" group by j.id order by j.startdate, j.starttime, j.id desc";
	if ($jobid) {
		$queryresult = Query($query, false, array($jobid));
	} else {
		$queryresult = Query($query);
	}
	$jobs = array();
	while ($row = DBGetRow($queryresult)) {
		// get this job messagegroup to find if 'has_phone', email, sms
		$messagegroup = new MessageGroup($row[3]);
		
		$job = new API_JobStatus();
		$job->id = $row[0];
		$job->name = $row[1];
		$job->description = $row[2];
		$job->phonetotal = $row[4];
		$job->emailtotal = $row[5];
		$job->printtotal = $row[6];
		$job->smstotal = $row[7];
		$job->hasphone = $messagegroup->hasMessage('phone') ? 1 : 0;
		$job->hasemail = $messagegroup->hasMessage('email') ? 1 : 0;
		$job->hasprint = 0;
		$job->hassms = $messagegroup->hasMessage('sms') ? 1 : 0;
		$job->phoneremaining = $row[8];
		$job->emailremaining = $row[9];
		$job->printremaining = $row[10];
		$job->smsremaining = $row[11];
		$job->startdate = $row[12];
		$job->status = $row[13];

		$jobs[] = $job;
	}
	if ($jobid) {
		return array_shift($jobs);
	} else {
		return $jobs;
	}
}

////////////////////////////////////////////////////////////////////////////////
// Server Code
////////////////////////////////////////////////////////////////////////////////
$SETTINGS = parse_ini_file("../inc/settings.ini.php",true);

require_once("XML/RPC.php");
require_once("../inc/auth.inc.php");

require_once("../inc/db.inc.php");
require_once("../inc/utils.inc.php");
require_once("../inc/memcache.inc.php");
require_once("../inc/DBMappedObject.php");
require_once("../inc/DBRelationMap.php");
require_once("../inc/sessionhandler.inc.php");

require_once('../inc/content.inc.php');
require_once("../obj/User.obj.php");
require_once("../obj/Access.obj.php");
require_once("../obj/Permission.obj.php");
require_once("../obj/Rule.obj.php"); //for search and sec profile rules
require_once("../inc/date.inc.php");
require_once("../inc/securityhelper.inc.php");

// OBJECTS
require_once("../obj/Section.obj.php");
require_once("../obj/Organization.obj.php");
require_once("../obj/RenderedList.obj.php");
require_once("../obj/PeopleList.obj.php");
require_once("../obj/Publish.obj.php");
require_once("../obj/Person.obj.php");
require_once("../obj/MessageGroup.obj.php");
require_once("../obj/Message.obj.php");
require_once("../obj/MessagePart.obj.php");
require_once("../obj/AudioFile.obj.php");
require_once("../obj/Content.obj.php");
require_once("../obj/JobType.obj.php");
require_once("../obj/Job.obj.php");
require_once("../obj/Voice.obj.php");
require_once("../obj/Language.obj.php");
require_once("../obj/Phone.obj.php");
require_once("../obj/Email.obj.php");
require_once("../obj/Sms.obj.php");
require_once("../obj/Import.obj.php");
require_once("../obj/ImportLogEntry.obj.php");
require_once("../obj/FieldMap.obj.php");

// API Files
require_once("API_List.obj.php");
require_once("API_Message.obj.php");
require_once("API_JobType.obj.php");
require_once("API_Job.obj.php");
require_once("API_JobStatus.obj.php");
require_once("API_Contact.obj.php");
require_once("API_ContactPreference.obj.php");
require_once("API_Label.obj.php");
require_once("API_Import.obj.php");
require_once("API_ImportLogEntry.obj.php");


ini_set("soap.wsdl_cache_enabled", "0"); // disabling WSDL cache

// without SOAP_SINGLE_ELEMENT_ARRAYS any params expecting an array will contain an array but instead the single object in it's place
// add this feature so that even a single element will be passed in an array
$server = new SoapServer("smapi.wsdl", array('features' => SOAP_SINGLE_ELEMENT_ARRAYS));
$server->setClass("SMAPI");
$server->handle();
//var_dump($server->getFunctions());



?>
