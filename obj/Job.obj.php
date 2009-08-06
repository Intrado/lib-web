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
	var $modifydate;
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
				"type", "modifydate", "createdate", "startdate", "enddate", "starttime", "endtime", "finishdate",
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

	function copyMessage($msgid) {
		// copy the message
		$newmsg = new Message($msgid);
		$newmsg->id = null;
		$newmsg->create();

		// copy the parts
		$parts = DBFindMany("MessagePart", "from messagepart where messageid=$msgid");
		foreach ($parts as $part) {
			$newpart = new MessagePart($part->id);
			$newpart->id = null;
			$newpart->messageid = $newmsg->id;
			$newpart->create();
		}

		// copy the attachments
		QuickUpdate("insert into messageattachment (messageid,contentid,filename,size,deleted) " .
			"select $newmsg->id, ma.contentid, ma.filename, ma.size, 1 as deleted " .
			"from messageattachment ma where ma.messageid=$msgid and not deleted");

		return $newmsg;
	}

	function copyNew($isrepeatingrunnow = false) {
		// never copy a survey
		if ($this->questionnaireid != null)
			return false;

		//make a copy of this job
		$newjob = new Job($this->id);
		$newjob->id = NULL;
		if ($isrepeatingrunnow || $newjob->status == "repeating") {
			$tmpDate = date("M j, g:i a");
			$newjob->name = substr($newjob->name,0,47 - strlen($tmpDate)) . " - $tmpDate";
		} else {
			$tmpJobName = $newjob->name;
			$copySuffix = " (Copy)";
			if (strlen($tmpJobName) > 40)
				$tmpJobName = substr($tmpJobName,0,39) . "... ";
			$copyCount = 1;
			while (DBFind("Job", "from job where name =? and not deleted and cancelleduserid is NULL", false, array($tmpJobName.$copySuffix))) {
				$copySuffix = " (Copy $copyCount)";
				if (strlen($tmpJobName) > 39 - strlen($copyCount))
					$tmpJobName = substr($tmpJobName,0,38 - strlen($copyCount)) . "... ";
				$copyCount++;
			}
			$newjob->name = $tmpJobName . $copySuffix;
		}

		$newjob->status = "new";
		$newjob->scheduleid = null;
		$newjob->assigned = NULL;
		$newjob->finishdate = NULL;
		$newjob->deleted = 0; // copy archived job is ok so we must set this to undeleted
		$newjob->cancelleduserid = NULL;
		$newjob->percentprocessed = 0;
		
		$newjob->createdate = date("Y-m-d H:i:s", time());
		$newjob->modifydate = $newjob->createdate;
		
		//refresh the dates to present
		$daydiff = strtotime($newjob->enddate) - strtotime($newjob->startdate);

		$newjob->startdate = date("Y-m-d", time());
		$newjob->enddate = date("Y-m-d", time() + $daydiff);

		$newjob->create();

		// copy the messages
		// if message is not deleted, then we can point to it directly
		// but if message is deleted, it's either already a copy from a previous run (uneditable)
		// or it's a translation message and we need to make another copy of these
		if (!$isrepeatingrunnow && $newjob->isOption('jobcreatedphone')) {
			$msg = new Message($newjob->phonemessageid);
			if ($msg->deleted) {
				$newmsg = Job::copyMessage($msg->id);
				$newjob->phonemessageid = $newmsg->id;
				$newjob->update();
			}
			$joblangs = DBFindMany("JobLanguage", "from joblanguage where jobid=? and type='phone'", false, array($this->id));
			foreach ($joblangs as $jl) {
				$newmsg = Job::copyMessage($jl->messageid);

				$newjl = new JobLanguage($jl->id);
				$newjl->id = null;
				$newjl->messageid = $newmsg->id;
				$newjl->jobid = $newjob->id;
				$newjl->create();
			}
		} else {
			//copy all the job language settings
			QuickUpdate("insert into joblanguage (jobid, messageid, type, language)
				select ?, messageid, 'phone', language
				from joblanguage where jobid=? and type='phone'", false, array($newjob->id, $this->id));
		}
		// email messages
		if (!$isrepeatingrunnow && $newjob->isOption('jobcreatedemail')) {
			$msg = new Message($newjob->emailmessageid);
			if ($msg->deleted) {
				$newmsg = Job::copyMessage($msg->id);
				$newjob->emailmessageid = $newmsg->id;
				$newjob->update();
			}
			$joblangs = DBFindMany("JobLanguage", "from joblanguage where jobid=? and type='email'", false, array($this->id));
			foreach ($joblangs as $jl) {
				$newmsg = Job::copyMessage($jl->messageid);

				$newjl = new JobLanguage($jl->id);
				$newjl->id = null;
				$newjl->messageid = $newmsg->id;
				$newjl->jobid = $newjob->id;
				$newjl->create();
			}
		} else {
			//copy all the job language settings
			QuickUpdate("insert into joblanguage (jobid, messageid, type, language)
				select ?, messageid, 'email', language
				from joblanguage where jobid=? and type='email'", false, array($newjob->id, $this->id));
		}
		// sms has no translation or joblanguage, no need to copy


		//copy all the job lists
		QuickUpdate("insert into joblist (jobid,listid,thesql)
			select ?, listid, thesql
			from joblist where jobid=?", false, array($newjob->id, $this->id));

		// do not need to copy jobsetting, these are handled by the job object
		// remove the 'translationexpire' jobsetting to force retranslation
		QuickUpdate("delete from jobsetting where jobid=? and name='translationexpire'", false, array($newjob->id));

		// if _hascallback and user profile does not allow job callerid, be sure the option is set to use the default callerid
		$a = getSystemSetting('_hascallback', "0");
		$b = QuickQuery("select p.value from permission p join user u " .
				"where p.name='setcallerid' and p.accessid=u.accessid and u.id=?", false, array($newjob->userid));
		if ($a == "1" && $b != "1") {
			QuickUpdate("delete from jobsetting where jobid=? and name='callerid'", false, array($newjob->id));
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
		$job->createdate = date("Y-m-d H:i:s", time());
		$job->modifydate = date("Y-m-d H:i:s", time());
		
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
		$job->setOptionValue("callerid", getDefaultCallerID());

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
