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

		if (count($listrules) > 0) {
			$allrules = array_merge($user->rules(), $listrules);
			$rulesql = "1 " . Rule::makeQuery($allrules, "p");
		} else {
			$rulesql = "0";
		}

		$this->thesql = $rulesql;
	}

	function copyNew($isrepeatingrunnow = false) {
		//make a copy of this job
		$newjob = new Job($this->id);
		$newjob->id = NULL;
		$newjob->name .= " - " . date("M j, g:i a");
		if ($isrepeatingrunnow || $newjob->status != "repeating") {
			$newjob->status = "new";
			$newjob->scheduleid = NULL;
		} else {
			$schedule = new Schedule($newjob->scheduleid);
			$schedule->id = null;
			$schedule->create();
			$newjob->scheduleid = $schedule->id;
		}
		$newjob->assigned = NULL;
		$newjob->finishdate = NULL;

		$newjob->createdate = QuickQuery("select now()");

		//refresh the dates to present
		$daydiff = strtotime($newjob->enddate) - strtotime($newjob->startdate);

		$newjob->startdate = date("Y-m-d", time());
		$newjob->enddate = date("Y-m-d", time() + $daydiff);

		$newjob->create();

		if (!$isrepeatingrunnow) {
		// copy the messages
		// if message is not deleted, then we can point to it directly
		// but if message is deleted, it's either already a copy from a previous run (uneditable)
		// or it's a translation message and we need to make another copy of these
		if ($newjob->isOption('translationphonemessage')) {
			$msg = new Message($newjob->phonemessageid);
			if ($msg->deleted) {
				$newmsg = $msg->copyNew();
				$newjob->phonemessageid = $newmsg->id;
				$newjob->update();
			}
			$joblangs = DBFindMany("JobLanguage", "from joblanguage where jobid=$this->id and type='phone'");
			foreach ($joblangs as $jl) {
				$newmsg = new Message($jl->messageid);
				$newmsg = $newmsg->copyNew();
				$newjl = $jl->copyNew();
				$newjl->messageid = $newmsg->id;
				$newjl->jobid = $newjob->id;
				$newjl->update();
			}
		} else {
			//copy all the job language settings
			QuickUpdate("insert into joblanguage (jobid, messageid, type, language)
				select $newjob->id, messageid, 'phone', language
				from joblanguage where jobid=$this->id");
		}
		// email messages
		if ($newjob->isOption('translationemailmessage')) {
			$msg = new Message($newjob->emailmessageid);
			if ($msg->deleted) {
				$newmsg = $msg->copyNew();
				$newjob->emailmessageid = $newmsg->id;
				$newjob->update();
			}
			$joblangs = DBFindMany("JobLanguage", "from joblanguage where jobid=$this->id and type='email'");
			foreach ($joblangs as $jl) {
				$newmsg = new Message($jl->messageid);
				$newmsg = $newmsg->copyNew();
				$newjl = $jl->copyNew();
				$newjl->messageid = $newmsg->id;
				$newjl->jobid = $newjob->id;
				$newjl->update();
			}
		} else {
			//copy all the job language settings
			QuickUpdate("insert into joblanguage (jobid, messageid, type, language)
				select $newjob->id, messageid, 'email', language
				from joblanguage where jobid=$this->id");
		}
		// sms has no translation or joblanguage, no need to copy
		}


		//copy all the job lists
		QuickUpdate("insert into joblist (jobid,listid,thesql)
			select $newjob->id, listid, thesql
			from joblist where jobid=$this->id");

		// do not need to copy jobsetting, these are handled by the job object
		// remove the 'translationexpire' jobsetting to force retranslation
		QuickUpdate("delete from jobsetting where jobid=$newjob->id and name='translationexpire'");

		// if _hascallback and user profile does not allow job callerid, be sure the option is set to use the default callerid
		$a = QuickQuery("select value from setting where name='_hascallback'");
		$b = QuickQuery("select p.value from permission p join user u " .
				"where p.name='setcallerid' and p.accessid=u.accessid and u.id=$newjob->userid");
		if ($a == "1" && $b != "1") {
			QuickUpdate("delete from jobsetting where jobid=".$newjob->id." and name='prefermycallerid'");
			QuickUpdate("delete from jobsetting where jobid=".$newjob->id." and name='callerid'");
		}
		$newjob->loadSettings(); // reload without the ones we deleted

		return $newjob;
	}

	function hasPeople($listid, $thesql) {
		$hasPeople = false;
		// find all person ids from list rules
		$query = "select p.id from person p " .
			"left join listentry le on (p.id=le.personid and le.listid=".$listid.") " .
			"where ".$thesql." and not p.deleted and le.type is null and p.userid is null " .
			"limit 1";
		$p = QuickQuery($query);
		if ($p) $hasPeople = true;

		$query = "select p.id " .
			"from listentry le " .
			"straight_join person p on (p.id=le.personid and not p.deleted) " .
			"where le.listid=".$listid." and le.type='A' " .
			"limit 1";
		$p = QuickQuery($query);
		if ($p) $hasPeople = true;
		return $hasPeople;
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

					// with latest lists, be sure at least one person (otherwise, why bother copying this job that does nothing)
					$hasPeople = $this->hasPeople($this->listid, $this->thesql);

					// check additional lists
					$joblists = DBFindMany('JobList', "from joblist where jobid=$this->id");
					foreach ($joblists as $joblist) {
						$joblist->generateSql($this->userid); // update thesql
						$p = $this->hasPeople($joblist->listid, $joblist->thesql);
						if ($p) $hasPeople = true;
					}

					if ($hasPeople == true) {
						// copy this repeater job to a normal job then run it

						//update the finishdate (reused as last run for repeating jobs)
						QuickUpdate("update job set finishdate=now() where id='$this->id'");

						$newjob = $this->copyNew(true);
						$newjob->runNow();
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

	// return array of fields with values other than their defaults
	// NOTE only 'advanced' options (for now), used by jobedit page
	function compareWithDefaults() {
		$defaultjob = $this->jobWithDefaults();
		$fielddiffs = array();

		// advanced settings
		if (strtotime($this->startdate) != strtotime($defaultjob->startdate)) $fielddiffs['startdate'] = 1;
		if (strtotime($this->enddate) != strtotime($defaultjob->enddate)) $fielddiffs['enddate'] = 1;
		if (strtotime($this->starttime) != strtotime($defaultjob->starttime)) $fielddiffs['starttime'] = 1;
		if (strtotime($this->endtime) != strtotime($defaultjob->endtime)) $fielddiffs['endtime'] = 1;
		if ($this->isOption("sendreport") != $defaultjob->isOption("sendreport")) $fielddiffs['sendreport'] = 1;

		// advanced phone options
		$phonelang = DBFindMany('JobLanguage', "from joblanguage where joblanguage.type = 'phone' and jobid = " . $this->id);
		if ($phonelang) $fielddiffs['phonelang'] = 1;
		if ($this->getOptionValue("maxcallattempts") != $defaultjob->getOptionValue("maxcallattempts")) $fielddiffs['maxcallattempts'] = 1;
		if ($this->getOptionValue("callerid") != $defaultjob->getOptionValue("callerid")) $fielddiffs['callerid'] = 1;
		if ($this->isOption("prefermycallerid") != $defaultjob->isOption("prefermycallerid")) $fielddiffs['radiocallerid'] = 1;
		if ($this->isOption("skipduplicates") != $defaultjob->isOption("skipduplicates")) $fielddiffs['skipduplicates'] = 1;
		if ($this->isOption("leavemessage") != $defaultjob->isOption("leavemessage")) $fielddiffs['leavemessage'] = 1;
		if ($this->isOption("messageconfirmation") != $defaultjob->isOption("messageconfirmation")) $fielddiffs['messageconfirmation'] = 1;

		// advanced email options
		$emaillang = DBFindMany('JobLanguage', "from joblanguage where joblanguage.type = 'email' and jobid = " . $this->id);
		if ($emaillang) $fielddiffs['emaillang'] = 1;
		if ($this->isOption("skipemailduplicates") != $defaultjob->isOption("skipemailduplicates")) $fielddiffs['skipemailduplicates'] = 1;

		return $fielddiffs;
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