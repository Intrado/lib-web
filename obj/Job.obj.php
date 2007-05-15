<?


class Job extends DBMappedObject {


	var $userid;
	var $scheduleid;
	var $jobtypeid;
	var $name;
	var $description;
	var $listid;
	var $phonemessageid;
	var $emailmessageid;
	var $printmessageid;
	var $questionnaireid;
	var $type;
	var $createdate;
	var $startdate;
	var $enddate;
	var $starttime;
	var $endtime;
	var $finishdate;
	var $status;
	var $deleted = 0;
	var $thesql = "";

	var $cancelleduserid;

	var $sendphone;
	var $sendemail;
	var $sendprint;

	function Job ($id = NULL) {
		$this->_allownulls = true;
		$this->_tablename = "job";
		$this->_fieldlist = array("userid", "scheduleid", "jobtypeid", "name", "description", "listid",
				"phonemessageid", "emailmessageid", "printmessageid", "questionnaireid",
				"type", "createdate", "startdate", "enddate", "starttime", "endtime", "finishdate",
				"status", "deleted", "cancelleduserid", "thesql");
		//call super's constructor
		DBMappedObject::DBMappedObject($id);
	}


	//creates a new job object prepopulated with all of the user/system defaults
	//date/time values are in DB format and should be beautified for forms
	function jobWithDefaults () {
		global $USER, $ACCESS;
		$job = new Job();
		//basic job info -- not used/visible on forms, these need to set this again after post data
		$job->status = "new";
		$job->userid = $USER->id;
		$job->createdate = QuickQuery("select now()");

		//job type
		$VALIDJOBTYPES = JobType::getUserJobTypes();
		$job->jobtypeid = end($VALIDJOBTYPES)->id;

		//call settings
		$job->setOptionValue("maxcallattempts", min($ACCESS->getValue('callmax'), $USER->getSetting("callmax","4")));
		if (getSystemSetting('retry') != "")
			$job->setOptionValue("retry",getSystemSetting('retry'));

		//options
		$job->setOption("callall",$USER->getSetting("callall"));
		$job->setOption("callfirst",!$USER->getSetting("callall") + 0);
		$job->setOption("skipduplicates",1);
		$job->setOption("skipemailduplicates",1);
		$job->setOption("sendreport",1);
		if($USER->authorize("leavemessage"))
			$job->setOption("leavemessage", $USER->getSetting("leavemessage", 0));

		//date/time/numer of days
		$job->startdate = date("Y-m-d", strtotime("today"));
		$numdays = min($ACCESS->getValue('maxjobdays'), $USER->getSetting("maxjobdays","2"));
		$job->enddate = date("Y-m-d", strtotime($job->startdate) + (($numdays - 1) * 86400));
		$job->starttime = date("H:i", strtotime($USER->getCallEarly()));
		$job->endtime = date("H:i", strtotime($USER->getCallLate()));

		//callerid
		$job->setOptionValue("callerid", $USER->getSetting("callerid",getSystemSetting('callerid')));

		return $job;
	}



	function refresh($specificfields = NULL, $refreshchildren = false) {
		parent::refresh($specificfields, $refreshchildren);
		$this->optionsarray = false;
		$this->sendphone = (bool)$this->phonemessageid;
		$this->sendemail = (bool)$this->emailmessageid;
		$this->sendprint = (bool)$this->printmessageid;
	}

	function update($specificfields = NULL, $updatechildren = false) {
		$this->sendphone = (bool)$this->phonemessageid;
		$this->sendemail = (bool)$this->emailmessageid;
		$this->sendprint = (bool)$this->printmessageid;
		if(!$this->sendphone) $this->phonemessageid = NULL;
		if(!$this->sendemail) $this->emailmessageid = NULL;
		if(!$this->sendprint) $this->printmessageid = NULL;
		parent::update($specificfields,$updatechildren);
	}

	function create($specificfields = NULL, $createchildren = false) {
		$this->sendphone = (bool)$this->phonemessageid;
		$this->sendemail = (bool)$this->emailmessageid;
		$this->sendprint = (bool)$this->printmessageid;
		if(!$this->sendphone) $this->phonemessageid = NULL;
		if(!$this->sendemail) $this->emailmessageid = NULL;
		if(!$this->sendprint) $this->printmessageid = NULL;
		parent::create($specificfields, $createchildren);
	}


	function getSetting ($name, $defaultvalue = false, $refresh = false) {
		static $settings = null;

		if ($settings === null || $refresh) {
			$settings = array();
			if ($res = Query("select name,value from jobsetting where jobid='$this->id'")) {
				while ($row = DBGetRow($res)) {
					$settings[$row[0]] = $row[1];
				}
			}
		}

		if (isset($settings[$name]))
			return $settings[$name];
		else
			return $defaultvalue;
	}

	function setSetting ($name, $value) {
		$old = $this->getSetting($name,false,true);

		if ($old === false) {
			$settings[$name] = $value;
			if ($value)
				QuickUpdate("insert into jobsetting (jobid,name,value) values ($this->id,'" . DBSafe($name) . "','" . DBSafe($value) . "')");
		} else {
			if ($value !== false && $value !== '' && $value !== null) {
				QuickUpdate("update jobsetting set value='" . DBSafe($value) . "' where jobid=$this->id and name='" . DBSafe($name) . "'");
			} else {
				QuickUpdate("delete from jobsetting where jobid=$this->id and name='" . DBSafe($name) . "'");

			}
		}
	}

	function isOption ($option) {
		return ($this->getOptionValue($option) == 1);
	}

	function setOption ($option,$set) {
		$this->setOptionValue($option,$set);
	}

	function getOptionValue ($searchoption) {
		return $this->getSetting($searchoption);
	}
	function setOptionValue ($option,$value) {
		$this->setSetting($option, $value);
	}

}

?>