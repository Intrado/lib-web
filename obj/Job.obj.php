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
	var $maxcallattempts;
	var $options = "";
	var $status;
	var $deleted = 0;

	var $cancelleduserid;

	var $sendphone;
	var $sendemail;
	var $sendprint;

	var $optionsarray = false;

	function Job ($id = NULL) {
		$this->_allownulls = true;
		$this->_tablename = "job";
		$this->_fieldlist = array("userid", "scheduleid", "jobtypeid", "name", "description", "listid",
				"phonemessageid", "emailmessageid", "printmessageid", "questionnaireid",
				"type", "createdate", "startdate", "enddate", "starttime", "endtime", "finishdate",
				"maxcallattempts", "options", "status", "deleted","cancelleduserid");
		//call super's constructor
		DBMappedObject::DBMappedObject($id);
	}

	function runNow($jobid = null) {
		if (!isset($jobid))
			$jobid = $this->id;

		if (isset($_SERVER['WINDIR'])) {
			$cmd = "start php jobprocess.php $jobid";
			pclose(popen($cmd,"r"));
		} else {
			$cmd = "php jobprocess.php $jobid > /dev/null &";
			exec($cmd);
		}
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
		$job->maxcallattempts = min($ACCESS->getValue('callmax'), $USER->getSetting("callmax","4"));
		if (getSystemSetting('retry') != "")
			$job->setOptionValue("retry",getSystemSetting('retry'));

		//options
		$job->setOption("callall",$USER->getSetting("callall"));
		$job->setOption("callfirst",!$USER->getSetting("callall") + 0);
		$job->setOption("skipduplicates",1);
		$job->setOption("skipemailduplicates",1);
		$job->setOption("sendreport",1);

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



	function refresh() {
		parent::refresh();
		$this->optionsarray = false;
		$this->sendphone = (bool)$this->phonemessageid;
		$this->sendemail = (bool)$this->emailmessageid;
		$this->sendprint = (bool)$this->printmessageid;
	}

	function update($specificfields = NULL) {
		$this->sendphone = (bool)$this->phonemessageid;
		$this->sendemail = (bool)$this->emailmessageid;
		$this->sendprint = (bool)$this->printmessageid;
		if(!$this->sendphone) $this->phonemessageid = NULL;
		if(!$this->sendemail) $this->emailmessageid = NULL;
		if(!$this->sendprint) $this->printmessageid = NULL;
		parent::update($specificfields);
	}

	function create() {
		$this->sendphone = (bool)$this->phonemessageid;
		$this->sendemail = (bool)$this->emailmessageid;
		$this->sendprint = (bool)$this->printmessageid;
		if(!$this->sendphone) $this->phonemessageid = NULL;
		if(!$this->sendemail) $this->emailmessageid = NULL;
		if(!$this->sendprint) $this->printmessageid = NULL;
		parent::create();
	}

	function isOption ($option) {
		if (!$this->optionsarray) {
			$this->parseOptions();
		}
		return (in_array($option, $this->optionsarray));
	}

	function setOption ($option,$set) {
		if ($set) {
			if (!$this->isOption($option))
				$this->optionsarray[] = $option;
		} else {
			if ($this->isOption($option)) {
				$key = array_search($option,$this->optionsarray);
				unset($this->optionsarray[$key]);
			}
		}
		$this->buildOptions();
	}


	function getOptionValue ($searchoption) {
		if (!$this->optionsarray) {
			$this->parseOptions();
		}

		if (isset($this->optionsarray[$searchoption]))
			return $this->optionsarray[$searchoption];
		else
			return false;
	}

	function setOptionValue ($option,$value) {
		$this->optionsarray[$option] = $value;
		$this->buildOptions();
	}

	function parseOptions () {
        $temparray = explode(",",$this->options);
        $this->optionsarray = array();
        foreach ($temparray as $index => $option) {
			if (strpos($option,"=") !== false) {
			    list($name,$val) = explode("=",$option);
			    $this->optionsarray[$name] = $val;
			} else {
		    	$this->optionsarray[] = $option;
			}
        }
	}

	function buildOptions () {
		$this->options = "";
		foreach ($this->optionsarray as $index => $option) {
			if (is_int($index))
				$this->options .= $option . ",";
			else
				$this->options .= $index . "=" . $option . ",";
		}

		$this->options = substr($this->options,0,strlen($this->options) -1);
	}

}

?>