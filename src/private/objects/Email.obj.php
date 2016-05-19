<?

class Email extends DBMappedObject {

	var $personid;
	var $email;
	var $sequence;
	var $editlock;
	var $editlockdate;

	function Email ($id = NULL) {
		$this->_tablename = "email";
		$this->_allownulls = true;
		$this->_fieldlist = array("personid", "email", "sequence", "editlock","editlockdate");
		//call super's constructor
		DBMappedObject::DBMappedObject($id);
	}

	function update ($specificfields = NULL, $updatechildren = false) {
		if (isset($this->id)) {
			$originalObject = new Email($this->id);
			if (($originalObject->email != $this->email) ||
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

?>