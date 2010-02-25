<?


class Job extends DBMappedObject {
	var $userid;
	var $messagegroupid;
	var $scheduleid;
	var $jobtypeid;
	var $name;
	var $description;
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

	var $cancelleduserid;

	var $sendinfo = false; // if $sendphone, $sendemail, $sendsms is correct. Refreash, Update and Create will reset this to false
	var $sendphone;
	var $sendemail;
	var $sendsms;

	var $optionsarray = null; //options to update


	function Job ($id = NULL) {
		$this->_allownulls = true;
		$this->_tablename = "job";
		$this->_fieldlist = array("userid","messagegroupid", "scheduleid", "jobtypeid", "name", "description",
				"questionnaireid", "type", "modifydate", "createdate", "startdate", "enddate", "starttime", "endtime", "finishdate",
				"status", "percentprocessed", "deleted", "cancelleduserid");
		//call super's constructor
		DBMappedObject::DBMappedObject($id);
	}

	function copyMessage($messagegroupid) {
	
		// copy the message group
		$newmessagegroup = new MessageGroup($messagegroupid);
		$newmessagegroup->id = null;
		$newmessagegroup->create();

		$messages = DBFindMany("Message", "from message where messagegroupid=$messagegroupid");
		foreach ($messages as $message) {
			// NOTE: $message-copy() already calls $newmessage->create().
			$newmessage = $message->copy($newmessagegroup->id);
		}

		return $newmessagegroup;
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
		if (!$isrepeatingrunnow) {
			$msg = new MessageGroup($newjob->messagegroupid);
			if ($msg->deleted) {
				$newmsg = Job::copyMessage($msg->id);
				$newjob->messagegroupid = $newmsg->id;
				$newjob->update();
			}			 
		}


		//copy all the job lists
		QuickUpdate("insert into joblist (jobid,listid)
			select ?, listid
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
			"where le.listid=".$listid." and le.type='add' " .
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
				if ($this->messagegroupid != null || $this->questionnaireid != null) {

					// check lists for people
					$hasPeople = false;
					$joblists = DBFindMany('JobList', "from joblist where jobid=$this->id");
					foreach ($joblists as $joblist) {
						$thesql = $joblist->generateSql($this->userid); // update thesql
						$p = $this->hasPeople($joblist->listid, $thesql);
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
			if ($status == "new") {
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

	// TODO Check where  sendphone,sendemail and sendsms is used and see if we eliminate them
	function refresh($specificfields = NULL, $refreshchildren = false) {
		parent::refresh($specificfields, $refreshchildren);
		$this->loadSettings();
		$this->sendinfo = false;
	}

	function update($specificfields = NULL, $updatechildren = false) {
		$this->sendinfo = false;

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
		$this->sendinfo = false;


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


	// Update the send info if necessary, After a Refrash, Update and Create the cache is flushed this may be optimized.
	function updatesendinfo() {
		if(!$this->sendinfo) {
			if($this->messagegroupid != null) {
				$value = QuickQueryRow("select sum(type='phone') as phone, sum(type='email') as email, sum(type='sms') as sms from message where messagegroupid = ? group by messagegroupid",false,false,array($this->messagegroupid));
				if($value !== false) {
					$this->sendphone = ($value[0] > 0);
					$this->sendemail = ($value[1] > 0);
					$this->sendsms = ($value[2] > 0);
					$this->sendinfo = true;
				}
			}
		}
	}

	// Update cache if necessary and return if this job has a message with phone type
	function hasPhone() {
		$this->updatesendinfo();
		return $this->sendinfo && $this->sendphone;
	}

	// Update cache if necessary and return if this job has a message with email type
	function hasEmail() {
		$this->updatesendinfo();
		return $this->sendinfo && $this->sendemail;
	}

	// Update cache if necessary and return if this job has a message with sms type
	function hasSMS() {
		$this->updatesendinfo();
		return $this->sendinfo && $this->sendsms;
	}

}

?>
