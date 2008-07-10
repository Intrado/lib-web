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



////////////////////////////////////////////////////////////////////////////////
// Server Class
////////////////////////////////////////////////////////////////////////////////
class SMAPI{

	function getCustomerURL($oem, $oemid){
		global $IS_COMMSUITE;
		$result = array("resultcode" => "failure", "resultdescription" => "", "customerurl" => "");
		if($oem == ""){
			$result['resultdescription'] = "Missing OEM";
			return $result;
		}
		if($oemid == ""){
			$result['resultdescription'] = "Missing OEM ID";
			return $result;
		}

		$customerurl = api_getCustomerURL(strtolower($oem), $oemid);
		if($customerurl != false){
			$result['customerurl'] = $customerurl;
			$result['resultcode'] = "success";
		} else {
			$result['resultdescription'] = "An error occurred";
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
	function login($loginname, $password){
		global $IS_COMMSUITE;
		return systemLogin($loginname, $password);
	}

	/*
	Given a valid loginname/password/customerurl combination, a session id is generated and passed back.
	If an error occurs, error will contain the error and sessionid will be empty string.

	login:
		params: string loginname, string password, string customerurl
		returns: string resultcode, string resultdescription, string sessionid

	*/
	function loginToCustomer($loginname, $password, $customerurl){
		global $IS_COMMSUITE;
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
	function getLists($sessionid){
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
			$queryresult = Query("select id, name, description from list where userid = " . $USER->id . " and not deleted order by name");
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
	function getMessages($sessionid, $type = "phone"){
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
			$queryresult = Query("select id, name, description, type from message where userid = " . $USER->id . " and type= '" . DBSafe(strtolower($type)) . "' and not deleted order by name");
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

	function setMessageBody($sessionid, $messageid, $messagetext){
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
			if ($message->id && $message->userid != $USER->id || $message->deleted ) {
				$result["resultdescription"] = "Unauthorized access";
				return $result;
			}

			if($message->type == "sms"){
				if(strlen($messagetext) > 160){
					$messagetext = substr($messagetext, 0, 160);
				}
			}
			$parts = Message::parse($messagetext);
			$voiceid = QuickQuery("select id from ttsvoice where language = 'english' and gender = 'female'");
			QuickUpdate("delete from messagepart where messageid=$message->id");
			foreach ($parts as $part) {
				$part->voiceid = $voiceid;
				$part->messageid = $message->id;
				$part->create();
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

	function uploadAudio($sessionid, $name, $audio, $mimetype){
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

			//generate 2 temp files
			//write audio data to first file then run through sox to clean audio and output to second file
			//read second file and base64 encode it for the DB

			if(!$origtempfile = secure_tmpname($name . "origtempapiaudio", ".wav")){
				$result["resultdescription"] = "Failed to generate audio file";
				return $result;
			}
			if(!$cleanedtempfile = secure_tmpname("cleanedtempapiaudio", ".wav")){
				$result["resultdescription"] = "Failed to generate audio file";
				return $result;
			}

			//delete temp file that secure_tmpname generated so we can check if sox generated a file later
			@unlink($cleanedtempfile);

			if(!file_put_contents($origtempfile, base64_decode($audio))){
				$result['resultdescription'] = "Failed to generate audio file";
				return $result;
			}
			$cmd = "sox \"$origtempfile\" -r 8000 -c 1 -s -w \"$cleanedtempfile\" ";
			$soxresult = exec($cmd, $res1,$res2);
			$content = null;
			if($res2 || !file_exists($cleanedtempfile)) {
				$result["resultdescription"]= 'There was an error reading your audio file. Please try another file. Supported formats include: .wav';
				@unlink($origtempfile);
				@unlink($cleanedtempfile);
				return $result;
			} else {
				$content = new Content();
				$content->type = $mimetype;
				$content->data = base64_encode(file_get_contents($cleanedtempfile));
				$content->create();

				@unlink($origtempfile);
				@unlink($cleanedtempfile);
			}
			if($content == null || !$content->id){

				$result["resultdescription"] = "Failed to create audio file record";
				return $result;
			}
			$audiofile = new AudioFile();
			if(QuickQuery("select count(*) from audiofile where name = '" . $name . "' and not deleted")){
				$audiofile->name = $name . " - " . date("M j, Y G:i:s");
			} else {
				$audiofile->name = $name;
			}
			$audiofile->description = "Upload Audio API";
			$audiofile->recorddate = date("Y-m-d G:i:s");
			$audiofile->contentid = $content->id;
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
	function getJobTypes($sessionid){
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
	function getActiveJobs($sessionid){
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

	function getJobStatus($sessionid, $jobid){
		global $USER;
		$result = array("resultcode" => "failure","resultdescription" => "", "job" => null);
		$jobid = $jobid + 0;
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
	function getRepeatingJobs($sessionid){
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

			$queryresult = Query("select id, name, description, phonemessageid, emailmessageid, smsmessageid
									from job where status = 'repeating' and userid = " . $USER->id . " order by finishdate asc");

			//fetch all messages
			$messages['phone'] = DBFindMany("Message", "from message where type='phone' and not deleted and userid = " . $USER->id);
			$messages['email'] = DBFindMany("Message", "from message where type='email' and not deleted and userid = " . $USER->id);
			$messages['sms'] = DBFindMany("Message", "from message where type='sms' and not deleted and userid = " . $USER->id);

			$jobs = array();
			while($row = DBGetRow($queryresult)){
				$joblangs['phone'] = DBFindMany('JobLanguage', "from joblanguage where joblanguage.type = 'phone' and jobid = " . $row[0]);
				$joblangs['email'] = DBFindMany('JobLanguage', "from joblanguage where joblanguage.type = 'email' and jobid = " . $row[0]);
				$joblangs['sms'] = DBFindMany('JobLanguage', "from joblanguage where joblanguage.type = 'sms' and jobid = " . $row[0]);

				$job = new API_Job();
				$job->id = $row[0];
				$job->name = $row[1];
				$job->description = $row[2];

				$phonemessages = array();
				$emailmessages = array();
				$smsmessages = array();
				//only set message array if exists
				//do not return alternative job langs if defaults are not set(this should never happen)
				$types = array();
				if($row[3]){
					$phonemessage = new API_Message();
					$phonemessage->id = $row[3];
					$phonemessage->name = $messages['phone'][$row[3]]->name;
					$phonemessage->type = 'phone';
					$phonemessage->language = 'default';
					$phonemessages[] = $phonemessage;
					$types[] = "phone";
				}
				if($row[4]){
					$emailmessage = new API_Message();
					$emailmessage->id = $row[4];
					$emailmessage->name = $messages['email'][$row[4]]->name;
					$emailmessage->type = 'email';
					$emailmessage->language = 'default';
					$emailmessages[] = $emailmessage;
					$types[] = "email";
				}
				if(getSystemSetting('_hassms') && $row[5]){
					$smsmessage = new API_Message();
					$smsmessage->id = $row[5];
					$smsmessage->name = $messages['sms'][$row[5]]->name;
					$smsmessage->type = 'sms';
					$smsmessage->language = 'default';
					$smsmessages[] = $smsmessage;
					$types[] = "sms";
				}
				if($USER->authorize('sendmulti')){
					foreach($types as $type){
						$arrayname = $type . "messages";
						foreach($joblangs[$type] as $joblang){
							$joblangmessage = new API_Message();
							$joblangmessage->id = $joblang->messageid;
							$joblangmessage->name = $messages[$type][$joblang->messageid]->name;
							$joblangmessage->type = $type;
							$joblangmessage->language = $joblang->language;
							if($type == 'phone')
								$phonemessages[] = $joblangmessage;
							else if($type == 'email')
								$emailmessages[] = $joblangmessage;
							else if($type == 'sms')
								$smsmessages[] = $joblangmessage;
						}
					}
				}

				$job->phonemessages = $phonemessages;
				$job->emailmessages = $emailmessages;
				$job->smsmessages = $smsmessages;
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

	function sendRepeatingJob($sessionid, $jobid){
		global $USER;
		$result = array("resultcode" => "failure","resultdescription" => "", "jobid" => 0);
		$jobid = $jobid + 0;
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


	function sendJob($sessionid, $name, $desc, $listid, $jobtypeid, $startdate, $starttime, $endtime, $daystorun, $phonemsgid, $emailmsgid, $smsmsgid, $maxcallattempts ){
		global $USER, $ACCESS;
		$result = array("resultcode" => "failure","resultdescription" => "", "jobid" => 0);

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
			if(!strtotime($startdate)){

				$result["resultdescription"] = "Invalid Start Date";
				return $result;
			} else if(!strtotime($starttime)){

				$result["resultdescription"] = "Invalid Start Time";
				return $result;
			} else if(!strtotime($endtime)){

				$result["resultdescription"] = "Invalid End Time";
				return $result;
			} else if(!$daystorun){

				$result["resultdescription"] = "Invalid Run Days";
				return $result;
			} else if(!$maxcallattempts){

				$result["resultdescription"] = "Invalid Max Call Attempts";
				return $result;
			} else if(!userOwns("list", $listid)){

				$result["resultdescription"] =  "Invalid List";
				return $result;
			} else if($USER->authorize('sendphone') && $phonemsgid && !userOwns("message", $phonemsgid)){

				$result["resultdescription"] =  "Invalid Phone Message ID";
				return $result;
			} else if($USER->authorize('sendemail') && $emailmsgid && !userOwns("message", $emailmsgid)){

				$result["resultdescription"] =  "Invalid Email Message ID";
				return $result;
			} else if(getSystemSetting('_hassms') && $USER->authorize('sendsms') && $smsmsgid && !userOwns("message", $smsmsgid)){

				$result["resultdescription"] = "Invalid SMS Message ID";
				return $result;
			} else if(strtotime($starttime) > strtotime($endtime)){

				$result["resultdescription"] = "Start Time must be before End Time";
				return $result;
			} else {
				$job = Job::jobWithDefaults();
				$job->name = $name;
				$job->description = $desc;
				$job->jobtypeid = $jobtypeid;
				$job->listid = $listid;
				if($USER->authorize('sendphone') && $phonemsgid && userOwns("message", $phonemsgid) &&
					QuickQuery("select type from message where id = " . $phonemsgid) == "phone"){
					$job->sendphone = true;
					$job->phonemessageid = $phonemsgid;
				} else {
					$job->sendphone = false;
				}
				if($USER->authorize('sendemail') && $emailmsgid && userOwns("message", $emailmsgid) &&
					QuickQuery("select type from message where id = " . $emailmsgid) == "phone"){
					$job->sendemail = true;
					$job->emailmessageid = $emailmsgid;
				} else {
					$job->sendemail = false;
				}
				if(getSystemSetting('_hassms') & $USER->authorize('sendsms') && $smsmsgid && userOwns("message", $smsmsgid) &&
					QuickQuery("select type from message where id = " . $smsmsgid) == "phone"){
					$job->sendsms = true;
					$job->smsmessageid = $smsmsgid;
				} else {
					$job->sendsms = false;
				}

				$jobtypes = array();
				if ($job->sendphone && $job->phonemessageid != 0) {
					$jobtypes[] = "phone";
				} else {
					$job->phonemessageid = NULL;
					$job->sendphone = false;
				}
				if ($job->sendemail && $job->emailmessageid != 0) {
					$jobtypes[] = "email";
				} else {
					$job->emailmessageid = NULL;
					$job->sendemail = false;
				}
				if (getSystemSetting('_hassms') && $job->sendsms && $job->smsmessageid != 0) {
					$jobtypes[] = "sms";
				} else {
					$job->smsmessageid = NULL;
					$job->sendsms = false;
				}
				$job->type=implode(",",$jobtypes);

				$job->startdate = date("Y-m-d", strtotime($startdate));
				if($ACCESS->getValue('maxjobdays') && $daystorun > $ACCESS->getValue('maxjobdays')){
					$daystorun = $ACCESS->getValue('maxjobdays');
				}
				$job->enddate = date("Y-m-d", strtotime($job->startdate) + (($daystorun - 1) * 86400));
				$job->starttime = date("H:i", strtotime($starttime));
				$job->endtime = date("H:i", strtotime($endtime));

				$job->setOption("sendreport", 1);
				$job->setOptionValue("maxcallattempts", $maxcallattempts);


				if(!$job->sendphone && !$job->sendemail && !$job->sendsms){

					$result["resultdescription"] = "You must have at least one message type";
					return $result;
				}

				$job->create();
				$job->runNow();
				$result["resultcode"] = "success";
				$result["jobid"] = $job->id;
				return $result;
			}
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

	function getContacts($sessionid, $pkey){
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
			$personid = QuickQuery("select id from person where pkey = '" . DBSafe($pkey) . "' and not deleted " . $USER->userSQL("p"));
			if(!$personid){
				$result["resultdescription"] = "Invalid person";
				return $result;
			}
			// build multidimensional array to reduce duplicate code when building return objects
			$contactdata = array();
			$contactdata["phone"] = resequence(DBFindMany("Phone", "from phone where personid = " . $personid), "sequence");
			$contactdata["email"] = resequence(DBFindMany("Email", "from email where personid = " . $personid), "sequence");
			$contactdata["sms"] = resequence(DBFindMany("Sms", "from sms where personid = " . $personid), "sequence");

			//Generate missing contact fields that do not yet exist
			if(count($contactdata["phone"]) != getSystemSetting("maxphones",1)){
				for($i = 0; $i < getSystemSetting("maxphones"); $i++){
					if(!isset($contactdata["phone"][$i])){
						$newphone=new Phone();
						$newphone->personid = $personid;
						$newphone->sequence = $i;
						$contactdata["phone"][$i] = $newphone;
					}
				}
			}
			if(count($contactdata["email"]) != getSystemSetting("maxemails",1)){
				for($i=0; $i < getSystemSetting("maxemails"); $i++){
					if(!isset($contactdata["email"][$i])){
						$newemail=new Email();
						$newemail->personid = $personid;
						$newemail->sequence = $i;
						$contactdata["email"][$i] = $newemail;
					}
				}
			}
			if(count($contactdata["sms"]) != getSystemSetting("maxsms",1)){
				for($i=0; $i < getSystemSetting("maxsms"); $i++){
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
			foreach(array("phone", "email", "sms") as $type){
				foreach($contactdata[$type] as $object){
					$contact = new API_Contact();
					$contact->pkey = $pkey;
					$contact->type = $type;
					$contact->destination = $object->$type;
					$contact->sequence = $object->sequence;
					$contactpreferences = array();
					if(isset($contactprefs[$type][$object->sequence])){
						$pref = $contactprefs[$type][$object->sequence];
					} else {
						$pref = $defaultcontactprefs[$type][$object->sequence];
					}
					foreach($pref as $jobtypeid => $enabled){
						$contactpreference = new API_ContactPreference();
						$contactpreference->jobtypeid = $jobtypeid;
						$contactpreference->enabled = (bool)$enabled;
						$contactpreferences[] = $contactpreference;
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


	function setContact($sessionid, $contact){
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

			$personcontactprefs = array();

			$personid = QuickQuery("select id from person where pkey = '" . DBSafe($contact->pkey) . "' and not deleted " . $USER->userSQL("p"));
			if(!$personid){
				$result["resultdescription"] = "Invalid person: " . $contact->pkey;
				return $result;
			}

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
				$obj->editlock = 1;
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

			//generate array of contact prefs so that we can iterate later in a transaction
			foreach($contact->contactpreferences as $contactpreference){
				$personcontactprefs[($contactpreference->jobtypeid+0)] = ($contactpreference->enabled ? "1" : "0");
			}

			QuickUpdate("begin");
			foreach($personcontactprefs as $jobtypeid => $enabled){
				QuickUpdate("delete from contactpref where personid = " . $personid . " and jobtypeid = " . $jobtypeid . "  and type = '" . $contact->type . "' and sequence = " . ($contact->sequence + 0));
				QuickUpdate("insert into contactpref values (" . $personid . ", " . $jobtypeid . ", '" . $contact->type . "', " . ($contact->sequence + 0) . ", '" . $enabled . "')");
			}
			QuickUpdate("commit");

			$result["resultcode"] = "success";
			return $result;
		}
	}
}

////////////////////////////////////////////////////////////////////////////////
// Functions
////////////////////////////////////////////////////////////////////////////////

//login function with shared code from login and loginToCustomer

function systemLogin($loginname, $password, $CUSTOMERURL=null){

	global $IS_COMMSUITE;
	$result = array("resultcode" => "failure", "resultdescription" => "", "sessionid" => "");

	if($CUSTOMERURL === null){
		//get the customer URL
		if ($IS_COMMSUITE) {
			$CUSTOMERURL = "default";
		} /*CSDELETEMARKER_START*/ else {
			$CUSTOMERURL = substr($_SERVER["SCRIPT_NAME"],1);
			$CUSTOMERURL = strtolower(substr($CUSTOMERURL,0,strpos($CUSTOMERURL,"/")));
		} /*CSDELETEMARKER_END*/
	} else {
		//make sure customer url is alphanumeric
		if(!ereg("^[a-zA-Z0-9]*$", $CUSTOMERURL)) {
			$result['resultdescription'] = "Invalid Customer URL";
			return $result;
		}
	}

	$userid = doLogin($loginname, $password, $CUSTOMERURL, $_SERVER['REMOTE_ADDR']);
	if($userid == -1){

		$result["resultdescription"] = "User is locked out";
		return $result;
	} else if ($userid){
		doStartSession();
		loadCredentials($userid);
		$result["resultcode"] = "success";
		$result["sessionid"] = session_id();
		return $result;
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

		$ACCESS = &$_SESSION['access'];
		$ACCESS->refresh(NULL, true);

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

	$query = "select j.id, j.name, j.description,
						sum(rc.type='phone') as total_phone,
						sum(rc.type='email') as total_email,
						sum(rc.type='print') as total_print,
						sum(rc.type='sms') as total_sms,
						j.type LIKE '%phone%' AS has_phone,
						j.type LIKE '%email%' AS has_email,
						j.type LIKE '%print%' AS has_print,
						j.type LIKE '%sms%' AS has_sms,
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
	if($jobid){
		$jobid = $jobid + 0;
		$query .= " and j.id = $jobid ";
	} else {
		$query .= " and (j.status = 'active' or j.status='scheduled' or j.status='procactive' or j.status='processing' or j.status = 'new' or j.status = 'cancelling') ";
	}
	$query .=" group by j.id order by j.startdate, j.starttime, j.id desc";
	$queryresult = Query($query);
	$jobs = array();
	while($row = DBGetRow($queryresult)){
		$job = new API_JobStatus();
		$job->id = $row[0];
		$job->name = $row[1];
		$job->description = $row[2];
		$job->phonetotal = $row[3];
		$job->emailtotal = $row[4];
		$job->printtotal = $row[5];
		$job->smstotal = $row[6];
		$job->hasphone = $row[7];
		$job->hasemail = $row[8];
		$job->hasprint = $row[9];
		$job->hassms = $row[10];
		$job->phoneremaining = $row[11];
		$job->emailremaining = $row[12];
		$job->printremaining = $row[13];
		$job->smsremaining = $row[14];
		$job->startdate = $row[15];
		$job->status = $row[16];

		$jobs[] = $job;
	}
	if($jobid){
		return $jobs[0];
	} else {
		return $jobs;
	}
}

////////////////////////////////////////////////////////////////////////////////
// Server Code
////////////////////////////////////////////////////////////////////////////////
$SETTINGS = parse_ini_file("../inc/settings.ini.php",true);
$IS_COMMSUITE = $SETTINGS['feature']['is_commsuite'];

require_once("XML/RPC.php");
require_once("../inc/auth.inc.php");

require_once("../inc/db.inc.php");
require_once("../inc/DBMappedObject.php");
require_once("../inc/DBRelationMap.php");
require_once("../inc/sessionhandler.inc.php");

require_once("../inc/utils.inc.php");
require_once("../obj/User.obj.php");
require_once("../obj/Access.obj.php");
require_once("../obj/Permission.obj.php");
require_once("../obj/Rule.obj.php"); //for search and sec profile rules
require_once("../inc/securityhelper.inc.php");

// OBJECTS
require_once("../obj/Message.obj.php");
require_once("../obj/MessagePart.obj.php");
require_once("../obj/AudioFile.obj.php");
require_once("../obj/Content.obj.php");
require_once("../obj/JobType.obj.php");
require_once("../obj/Job.obj.php");
require_once("../obj/Voice.obj.php");
require_once("../obj/JobLanguage.obj.php");
require_once("../obj/Phone.obj.php");
require_once("../obj/Email.obj.php");
require_once("../obj/Sms.obj.php");

// API Files
require_once("API_List.obj.php");
require_once("API_Message.obj.php");
require_once("API_JobType.obj.php");
require_once("API_Job.obj.php");
require_once("API_JobStatus.obj.php");
require_once("API_Contact.obj.php");
require_once("API_ContactPreference.obj.php");

ini_set("soap.wsdl_cache_enabled", "0"); // disabling WSDL cache
$server=new SoapServer("smapi.wsdl");
$server->setClass("SMAPI");
$server->handle();
//var_dump($server->getFunctions());



?>