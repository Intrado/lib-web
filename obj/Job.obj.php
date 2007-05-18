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

	var $optionsarray = array(); //options to update


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

	function runNow($user, $jobid = null) {
		if (!isset($jobid))
			$jobid = $this->id;

		$usersql = $user->userSQL("p");
		//get and compose list rules
		$listrules = DBFindMany("Rule","from listentry le, rule r where le.type='R'
				and le.ruleid=r.id and le.listid='" . $this->listid .  "' order by le.sequence", "r");
		if (count($listrules) > 0)
			$listsql = "1" . Rule::makeQuery($listrules, "p");
		else
			$listsql = "0";//dont assume anyone is in the list if there are no rules

		if ($usersql == "")
    		$this->thesql = $listsql;
		else
    		$this->thesql = $usersql ." and ". $listsql;

		$this->status = "processing"; // set state, jobprocessor will set it to 'active'
		$this->update();
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
		$this->optionsarray = array();
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

//		var_dump($this->optionsarray);
		foreach ($this->optionsarray as $name => $value) {
			QuickUpdate("update jobsetting set value='" . DBSafe($value) . "' where jobid=$this->id and name='" . DBSafe($name) . "'");
		}
	}

	function create($specificfields = NULL, $createchildren = false) {
		$this->sendphone = (bool)$this->phonemessageid;
		$this->sendemail = (bool)$this->emailmessageid;
		$this->sendprint = (bool)$this->printmessageid;
		if(!$this->sendphone) $this->phonemessageid = NULL;
		if(!$this->sendemail) $this->emailmessageid = NULL;
		if(!$this->sendprint) $this->printmessageid = NULL;
		parent::create($specificfields, $createchildren);

		// now we have a jobid to create the jobsettings with
		foreach ($this->optionsarray as $name => $value) {
			QuickUpdate("insert into jobsetting (jobid,name,value) values ($this->id,'" . DBSafe($name) . "','" . DBSafe($value) . "')");
		}
	}

	function getSetting ($name, $defaultvalue = false, $refresh = false) {
		if (sizeof($this->optionsarray) == 0 || $refresh) {
			$this->optionsarray = array();
			if ($res = Query("select name,value from jobsetting where jobid='$this->id'")) {
				while ($row = DBGetRow($res)) {
					$this->optionsarray[$row[0]] = $row[1];
				}
			}
		}

		if (isset($this->optionsarray[$name]))
			return $this->optionsarray[$name];
		else
			return $defaultvalue;
	}

	function setSetting ($name, $value) {
		$this->optionsarray[$name] = $value;
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