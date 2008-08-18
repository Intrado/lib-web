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
	var $smsmessageid;
	var $questionnaireid;
	var $type;
	var $createdate;
	var $startdate;
	var $enddate;
	var $starttime;
	var $endtime;
	var $finishdate;
	var $status;
	var $percentprocessed = 0;
	var $deleted = 0;
	var $thesql = "";

	var $cancelleduserid;

	var $sendphone;
	var $sendemail;
	var $sendprint;
	var $sendsms;

	var $optionsarray = null; //options to update


	function Job ($id = NULL) {
		$this->_allownulls = true;
		$this->_tablename = "job";
		$this->_fieldlist = array("userid", "scheduleid", "jobtypeid", "name", "description", "listid",
				"phonemessageid", "emailmessageid", "printmessageid", "smsmessageid", "questionnaireid",
				"type", "createdate", "startdate", "enddate", "starttime", "endtime", "finishdate",
				"status", "percentprocessed", "deleted", "cancelleduserid", "thesql");
		//call super's constructor
		DBMappedObject::DBMappedObject($id);
	}

	// generate sql to store into 'thesql' field (used by jobprocessor to select person list)
	function generateSql() {
		// user rules
		$user = new User($this->userid);

		//get and compose list rules
		$listrules = DBFindMany("Rule","from listentry le, rule r where le.type='R'
				and le.ruleid=r.id and le.listid='" . $this->listid .  "'", "r");

		$allrules = array_merge($user->rules(), $listrules);

		$this->thesql = "1 " . Rule::makeQuery($allrules, "p");
	}

	// assumes this job was already created in the database
	// returns newjob else returns null
	function runNow() {
		if ($this->status=="repeating") {
			// check for system disablerepeat
			if (!getSystemSetting("disablerepeat")) {
				// check for empty message
				if ($this->phonemessageid != null || $this->emailmessageid != null || $this->smsmessageid != null || $this->printmessageid || $this->questionnaireid != null) {
					// check for empty list
					$this->generateSql(); // update thesql

					$hasPeople = false;
					// with latest list, be sure at least one person (otherwise, why bother copying this job that does nothing)
					// find all person ids from list rules
					$query = "select p.id from person p " .
						"left join listentry le on (p.id=le.personid and le.listid=".$this->listid.") " .
						"where ".$this->thesql." and not p.deleted and le.type is null and p.userid is null " .
						"limit 1";
					$p = QuickQuery($query);
					if ($p) $hasPeople = true;

					$query = "select p.id " .
						"from listentry le " .
						"straight_join person p on (p.id=le.personid and not p.deleted) " .
						"where le.listid=".$this->listid." and le.type='A' " .
						"limit 1";
					$p = QuickQuery($query);
					if ($p) $hasPeople = true;

					if ($hasPeople == true) {
						// copy this repeater job to a normal job then run it

						//update the finishdate (reused as last run for repeating jobs)
						QuickUpdate("update job set finishdate=now() where id='$this->id'");

						//make a copy of this job and run it
						$newjob = new Job($this->id);
						$newjob->id = NULL;
						$newjob->name .= " - " . date("M j, g:i a");
						$newjob->status = "new";
						$newjob->assigned = NULL;
						$newjob->scheduleid = NULL;
						$newjob->finishdate = NULL;

						$newjob->createdate = QuickQuery("select now()");

						//refresh the dates to present
						$daydiff = strtotime($newjob->enddate) - strtotime($newjob->startdate);

						$newjob->startdate = date("Y-m-d", time());
						$newjob->enddate = date("Y-m-d", time() + $daydiff);

						$newjob->create();

						//copy all the job language settings
						QuickUpdate("insert into joblanguage (jobid,messageid,type,language)
							select $newjob->id, messageid, type,language
							from joblanguage where jobid=$this->id");

						$newjob->runNow();
						sleep(3);
						return $newjob;

					}
				}
			}
		} else {
			$status = QuickQuery("select status from job where id = '" . $this->id . "'");
			if($status == "new"){
				$this->generateSql();
				$this->status = "scheduled"; // set state, schedulemanager will set it to 'processing' then jobprocessor will set it to 'active'
				$this->update();
			}
		}
		return null;
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

		//call settings
		$job->setOptionValue("maxcallattempts", min($ACCESS->getValue('callmax'), $USER->getSetting("callmax","4")));

		//options
		$job->setOption("skipduplicates",1);
		$job->setOption("skipemailduplicates",1);
		$job->setOption("skipsmsduplicates",1);
		$job->setOption("sendreport",1);
		if($USER->authorize("leavemessage"))
			$job->setOption("leavemessage", $USER->getSetting("leavemessage", 1));

		//date/time/numer of days
		$job->startdate = date("Y-m-d", strtotime("today"));
		$numdays = min($ACCESS->getValue('maxjobdays'), $USER->getSetting("maxjobdays","1"));
		$job->enddate = date("Y-m-d", strtotime($job->startdate) + (($numdays - 1) * 86400));
		$job->starttime = date("H:i", strtotime($USER->getCallEarly()));
		$job->endtime = date("H:i", strtotime($USER->getCallLate()));

		//callerid
		$job->setOptionValue("callerid", $USER->getSetting("callerid",getSystemSetting('callerid')));

		return $job;
	}



	function refresh($specificfields = NULL, $refreshchildren = false) {
		parent::refresh($specificfields, $refreshchildren);
		$this->loadSettings();
		$this->sendphone = (bool)$this->phonemessageid;
		$this->sendemail = (bool)$this->emailmessageid;
		$this->sendprint = (bool)$this->printmessageid;
		$this->sendsms = (bool)$this->smsmessageid;
	}

	function update($specificfields = NULL, $updatechildren = false) {
		$this->sendphone = (bool)$this->phonemessageid;
		$this->sendemail = (bool)$this->emailmessageid;
		$this->sendprint = (bool)$this->printmessageid;
		$this->sendsms = (bool)$this->smsmessageid;
		if(!$this->sendphone) $this->phonemessageid = NULL;
		if(!$this->sendemail) $this->emailmessageid = NULL;
		if(!$this->sendprint) $this->printmessageid = NULL;
		if(!$this->sendsms) $this->smsmessageid = NULL;
		parent::update($specificfields,$updatechildren);

		if($this->id){
			QuickUpdate("delete from jobsetting where jobid='$this->id'");
			foreach ($this->optionsarray as $name => $value) {
				QuickUpdate("insert into jobsetting (jobid,name,value) values ('$this->id','" . DBSafe($name) . "','" . DBSafe($value) . "')");
			}
		}
		if($this->id){
			return true;
		}
		return false;
	}

	function create($specificfields = NULL, $createchildren = false) {
		$this->sendphone = (bool)$this->phonemessageid;
		$this->sendemail = (bool)$this->emailmessageid;
		$this->sendprint = (bool)$this->printmessageid;
		$this->sendsms = (bool)$this->smsmessageid;
		if(!$this->sendphone) $this->phonemessageid = NULL;
		if(!$this->sendemail) $this->emailmessageid = NULL;
		if(!$this->sendprint) $this->printmessageid = NULL;
		if(!$this->sendsms) $this->smsmessageid = NULL;
		$id = parent::create($specificfields, $createchildren);

		if($id){
			// now we have a jobid to create the jobsettings with
			foreach ($this->optionsarray as $name => $value) {
				QuickUpdate("insert into jobsetting (jobid,name,value) values ($this->id,'" . DBSafe($name) . "','" . DBSafe($value) . "')");
			}
		}
		return $id;
	}

	function loadSettings(){
		if($this->id){
			$this->optionsarray = QuickQueryList("select name,value from jobsetting where jobid='$this->id'", true);
		}
	}

	function getSetting ($name, $defaultvalue = false, $refresh = false) {
		if ($this->optionsarray == null || $refresh) {
			$this->loadSettings();
		}

		if (isset($this->optionsarray[$name]))
			return $this->optionsarray[$name];
		else
			return $defaultvalue;
	}

	function setSetting ($name, $value) {

		if($this->optionsarray == null)
			$this->loadSettings();
		if($this->optionsarray == null)
			$this->optionsarray = array();
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