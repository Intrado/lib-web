<?

class Sms extends DBMappedObject{

	var $personid;
	var $sms;
	var $sequence;
	var $editlock;
	var $editlockdate;

	function Sms ($id = NULL) {
		$this->_tablename = "sms";
		$this->_allownulls = true;
		$this->_fieldlist = array("personid", "sms", "sequence","editlock","editlockdate");
		//call super's constructor
		DBMappedObject::DBMappedObject($id);
	}

	function update ($specificfields = NULL, $updatechildren = false) {
		if (isset($this->id)) {
			$originalObject = new Sms($this->id);
			if (($originalObject->sms != $this->sms) ||
				($originalObject->editlock != $this->editlock)) {
					if ($this->editlock)
						$this->editlockdate = date("Y-m-d H:i:s", time());
					else
						$this->editlockdate = null;
			}
		} else {
					if ($this->editlock)
						$this->editlockdate = date("Y-m-d H:i:s", time());
					else
						$this->editlockdate = null;
		}
		DBMappedObject::update($specificfields, $updatechildren);
	}

}