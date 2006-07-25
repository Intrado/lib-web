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
		$this->_fieldlist = array("userid", "scheduleid", "jobtypeid", "name", "description", "listid", "phonemessageid", "emailmessageid",
					"printmessageid", "type", "createdate", "startdate", "enddate", "starttime", "endtime", "finishdate",
					"maxcallattempts", "options", "status", "deleted","cancelleduserid");
		//call super's constructor
		DBMappedObject::DBMappedObject($id);
	}

	function refresh() {
		parent::refresh();
		$this->optionsarray = false;
		$this->sendphone = (bool)$this->phonemessageid;
		$this->sendemail = (bool)$this->emailmessageid;
		$this->sendprint = (bool)$this->printmessageid;
	}

	function update() {
		$this->sendphone = (bool)$this->phonemessageid;
		$this->sendemail = (bool)$this->emailmessageid;
		$this->sendprint = (bool)$this->printmessageid;
		if(!$this->sendphone) $this->phonemessageid = NULL;
		if(!$this->sendemail) $this->emailmessageid = NULL;
		if(!$this->sendprint) $this->printmessageid = NULL;
		parent::update();
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