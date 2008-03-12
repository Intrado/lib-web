<?

////////////////////////////////////////////////////////////////////////////////
// Server Class
////////////////////////////////////////////////////////////////////////////////
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
			$USER = $_SESSION['user'];
			if(!$USER->id){
				return new SoapFault("Server", "Invalid user");
			}
			$result = Query("select id, name, description from list where userid = " . $USER->id . " and not deleted order by name");
			$lists = array();
			while($row = DBGetRow($result)){
				$list = new API_List();
				$list->id = $row[0];
				$list->name = $row[1];
				$list->description = $row[2];
				$lists[] = $list;
			}
			return $lists;
		}
	}

	function getMessages($sessionid, $type = "phone"){
		global $USER;
		if(!APISession($sessionid)){
			return new SoapFault("Server", "Invalid Session ID");
		} else {
			$USER = $_SESSION['user'];
			if(!$USER->id){
				return new SoapFault("Server", "Invalid user");
			}
			$result = Query("select id, name, description from message where userid = " . $USER->id . " and type= '" . $type . "' and not deleted order by name");
			$messages = array();
			while($row = DBGetRow($result)){
				$message = new API_Message();
				$message->id = $row[0];
				$message->name = $row[1];
				$message->description = $row[2];
				$messages[] = $message;
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
			if(!$USER->id){
				return new SoapFault("Server", "Invalid user");
			}
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

			$parts = Message::parse($messagetext);
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
		error_log("uploadaudio called");
		if(!APISession($sessionid)){
			return new SoapFault("Server", "Invalid Session ID");
		} else {
			$USER = $_SESSION['user'];
			if(!$USER->id){
				return new SoapFault("Server", "Invalid user");
			}
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


	//jobtypeid, name, info
	function getJobTypes($sessionid){
		global $USER;
		if(!APISession($sessionid)){
			return new SoapFault("Server", "Invalid Session ID");
		} else {
			$USER = $_SESSION['user'];
			if(!$USER->id){
				return new SoapFault("Server", "Invalid user");
			}

			/*
			$result = Query("select id, name, info from jobtype jt, userjobtypes ujt where ujt.jobtypeid = jt.id and ujt.userid=" . $USER->id . " and jt.deleted=0 and not jt.issurvey order by systempriority, name");

			$jobtypes = array();
			while($row = DBGetRow($result)){
				$jobtype = new API_JobType();
				$jobtype->id = $row[0];
				$jobtype->name = $row[1];
				$jobtype->info = $row[2];
				$jobtypes[] = $jobtype;
			}
			if(count($jobtypes) == 0){
				$result = Query("select id, name, info from jobtype jt where jt.deleted=0 and not jt.issurvey  order by systempriority, name");
				while($row = DBGetRow($result)){
					$jobtype = new API_JobType();
					$jobtype->id = $row[0];
					$jobtype->name = $row[1];
					$jobtype->info = $row[2];
					$jobtypes[] = $jobtype;
				}
			}

			*/

			$userjobtypes = JobType::getUserJobTypes();
			foreach($userjobtypes as $userjobtype){
				$jobtype = new API_Jobtype();
				$jobtype->id = $userjobtype->id;
				$jobtype->name = $userjobtype->name;
				$jobtype->info = $userjobtype->info;
				$jobtypes[] = $jobtype;
			}
			return $jobtypes;
		}
	}
	//jobid, name, desc, total, remaining
	function getActiveJobs($sessionid){
		global $USER;
		if(!APISession($sessionid)){
			return new SoapFault("Server", "Invalid Session ID");
		} else {
			$USER = $_SESSION['user'];
			if(!$USER->id){
				return new SoapFault("Server", "Invalid user");
			}
			return getJobData();
		}
	}
	function getJobStatus($sessionid, $jobid){
		global $USER;
		if(!APISession($sessionid)){
			return new SoapFault("Server", "Invalid Session ID");
		} else {
			$USER = $_SESSION['user'];
			if(!$USER->id){
				return new SoapFault("Server", "Invalid user");
			}
			return getJobData($jobid);
		}
	}

	//jobid, name, desc
	function getRepeatingJobs($sessionid){
		global $USER;
		if(!APISession($sessionid)){
			return new SoapFault("Server", "Invalid Session ID");
		} else {
			$USER = $_SESSION['user'];
			if(!$USER->id){
				return new SoapFault("Server", "Invalid user");
			}
			$result = Query("select id, name, description from job where status = 'repeating' and userid = " . $USER->id . " order by finishdate asc");
			$jobs = array();
			while($row = DBGetRow($result)){
				$job = new API_Job();
				$job->id = $row[0];
				$job->name = $row[1];
				$job->description = $row[2];
				$jobs[] = $job;
			}
			return $jobs;
		}
	}
	//jobid
	function sendRepeatingJob($sessionid, $jobid){
		global $USER;
		if(!APISession($sessionid)){
			return new SoapFault("Server", "Invalid Session ID");
		} else {
			$USER = $_SESSION['user'];
			if(!$USER->id){
				return new SoapFault("Server", "Invalid user");
			}
			if(!$jobid){
				return new SoapFault("Server", "Invalid Job ID");
			}
			$job = new Job($jobid);
			if($job->userid != $USER->id){
				return new SoapFault("Server", "Unauthorized access");
			}
			if($job->status != "repeating"){
				return new SoapFault("Server", "Invalid Repeating Job");
			}
			$job->runNow();
			// run the repeating job and return the ID of the job that gets created
			//TODO: BROKEN, last insert is into job language, not job table
			$newjobid = mysql_insert_id();

			return $newjobid;
		}
	}
	//jobid
	function sendJob($sessionid, $name, $desc, $listid, $jobtypeid, $startdate, $starttime, $endtime, $daystorun, $phonemsgid, $emailmsgid, $smsmsgid, $maxcallattempts ){
		global $USER, $ACCESS;
		if(!APISession($sessionid)){
			return new SoapFault("Server", "Invalid Session ID");
		} else {
			$USER = $_SESSION['user'];
			$ACCESS = $_SESSION['access'];
			if(!$USER->id){
				return new SoapFault("Server", "Invalid user");
			}
			$job = Job::jobWithDefaults();
			$job->name = $name;
			$job->description = $desc;
			$job->jobtypeid = $jobtypeid;
			if(userOwns("list", $listid)){
				$job->listid = $listid;
			} else {
				return new SoapFault("Server", "Invalid List");
			}
			if($USER->authorize('sendphone') && $phonemsgid && userOwns("message", $phonemsgid)){
				$job->sendphone = true;
				$job->phonemessageid = $phonemsgid;
			} else {
				$job->sendphone = false;
			}
			if($USER->authorize('sendemail') && $emailmsgid && userOwns("message", $emailmsgid)){
				$job->sendemail = true;
				$job->emailmessageid = $emailmsgid;
			} else {
				$job->sendemail = false;
			}
			if(getSystemSetting('hassms') & $USER->authorize('sendsms') && $smsmsgid && userOwns("message", $smsmsgid)){
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
			if ($job->sendprint && $job->printmessageid != 0) {
				$jobtypes[] = "print";
			} else {
				$job->printmessageid = NULL;
				$job->sendprint = false;
			}
			if ($hassms && $job->sendsms && $job->smsmessageid != 0) {
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
			$job->create();
			$job->runNow();
			return $job->id;
		}
	}


}

////////////////////////////////////////////////////////////////////////////////
// Functions
////////////////////////////////////////////////////////////////////////////////
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

function getJobData($jobid=null){
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
						where (j.status = 'active' or j.status='scheduled' or j.status='procactive' or j.status='processing' or j.status = 'new' or j.status = 'cancelling') and j.deleted=0
						and j.userid = $USER->id ";
	if($jobid){
		$query .= " and j.id = $jobid ";
	}
	$query .=" group by j.id order by j.startdate, j.starttime, j.id desc";
	$result = Query($query);
	$jobs = array();
	while($row = DBGetRow($result)){
		$job = new API_Job();
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

// API Files
require_once("../obj/API_List.obj.php");
require_once("../obj/API_Message.obj.php");
require_once("../obj/API_JobType.obj.php");
require_once("../obj/API_Job.obj.php");

ini_set("soap.wsdl_cache_enabled", "0"); // disabling WSDL cache
$server=new SoapServer("SMAPI.wsdl");
$server->setClass("SMAPI");
$server->handle();
//var_dump($server->getFunctions());



?>